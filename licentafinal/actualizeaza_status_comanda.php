<?php
// Include database configuration
require_once 'db-config.php';

// Start session
session_start();

// Initialize response array
$response = [
    'success' => false,
    'message' => ''
];

// Check if request is AJAX
$is_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

// Check if user is logged in and has appropriate role
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['administrator', 'angajat'])) {
    // For AJAX requests, return error
    if ($is_ajax) {
        $response['message'] = 'Nu aveți permisiunea de a efectua această acțiune.';
        echo json_encode($response);
        exit;
    }
    
    // For regular requests, redirect to login
    header("Location: login.php");
    exit;
}

// Check if order ID and status are provided
if (!isset($_POST['order_id']) || empty($_POST['order_id']) || !isset($_POST['status']) || empty($_POST['status'])) {
    $response['message'] = 'Datele necesare lipsesc.';
    
    if ($is_ajax) {
        echo json_encode($response);
        exit;
    } else {
        header("Location: admin-dashboard.html");
        exit;
    }
}

// Get user ID, order ID and status
$user_id = $_SESSION['user_id'];
$order_id = (int)$_POST['order_id'];
$status = sanitize_input($_POST['status']);

// Validate status
$valid_statuses = ['plasata', 'procesata', 'in_livrare', 'livrata', 'anulata'];
if (!in_array($status, $valid_statuses)) {
    $response['message'] = 'Status invalid.';
    
    if ($is_ajax) {
        echo json_encode($response);
        exit;
    } else {
        header("Location: admin-dashboard.html");
        exit;
    }
}

// Check if order exists
$order_sql = "SELECT id, status, user_id FROM comenzi WHERE id = $order_id";
$order_result = mysqli_query($conn, $order_sql);

if (mysqli_num_rows($order_result) == 0) {
    $response['message'] = 'Comanda nu există.';
    
    if ($is_ajax) {
        echo json_encode($response);
        exit;
    } else {
        header("Location: admin-dashboard.html");
        exit;
    }
}

$order = mysqli_fetch_assoc($order_result);

// Update order status
$update_sql = "UPDATE comenzi SET status = '$status'";

// Update timestamp based on status
if ($status == 'procesata') {
    $update_sql .= ", data_procesare = NOW()";
} elseif ($status == 'livrata') {
    $update_sql .= ", data_livrare = NOW()";
}

$update_sql .= " WHERE id = $order_id";

if (mysqli_query($conn, $update_sql)) {
    $response['success'] = true;
    $response['message'] = 'Statusul comenzii a fost actualizat.';
    
    // Log the action
    log_action($user_id, 'actualizare_status_comanda', "Comandă ID: $order_id, Status nou: $status");
    
    // If order is canceled, restore stock
    if ($status == 'anulata') {
        $items_sql = "SELECT produs_id, cantitate FROM comenzi_produse WHERE comanda_id = $order_id";
        $items_result = mysqli_query($conn, $items_sql);
        
        while ($item = mysqli_fetch_assoc($items_result)) {
            $update_stock_sql = "UPDATE produse SET stoc = stoc + {$item['cantitate']} WHERE id = {$item['produs_id']}";
            mysqli_query($conn, $update_stock_sql);
        }
    }
} else {
    $response['message'] = 'A apărut o eroare la actualizarea statusului comenzii.';
}

// Return response for AJAX requests
if ($is_ajax) {
    echo json_encode($response);
    exit;
}

// Redirect for regular requests
header("Location: detalii-comanda.php?id=$order_id");
exit;
?>