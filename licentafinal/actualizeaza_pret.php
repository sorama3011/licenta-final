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

// Check if product ID and price are provided
if (!isset($_POST['product_id']) || empty($_POST['product_id']) || !isset($_POST['price'])) {
    $response['message'] = 'Datele necesare lipsesc.';
    
    if ($is_ajax) {
        echo json_encode($response);
        exit;
    } else {
        header("Location: " . ($_SESSION['user_role'] == 'administrator' ? 'admin-dashboard.html' : 'employee-dashboard.html'));
        exit;
    }
}

// Get user ID, product ID and price
$user_id = $_SESSION['user_id'];
$product_id = (int)$_POST['product_id'];
$price = (float)$_POST['price'];

// Validate price
if ($price <= 0) {
    $response['message'] = 'Prețul trebuie să fie mai mare decât zero.';
    
    if ($is_ajax) {
        echo json_encode($response);
        exit;
    } else {
        header("Location: " . ($_SESSION['user_role'] == 'administrator' ? 'admin-dashboard.html' : 'employee-dashboard.html'));
        exit;
    }
}

// Check if product exists
$product_sql = "SELECT id, nume, pret FROM produse WHERE id = $product_id AND activ = 1";
$product_result = mysqli_query($conn, $product_sql);

if (mysqli_num_rows($product_result) == 0) {
    $response['message'] = 'Produsul nu există sau nu este disponibil.';
    
    if ($is_ajax) {
        echo json_encode($response);
        exit;
    } else {
        header("Location: " . ($_SESSION['user_role'] == 'administrator' ? 'admin-dashboard.html' : 'employee-dashboard.html'));
        exit;
    }
}

$product = mysqli_fetch_assoc($product_result);
$old_price = (float)$product['pret'];

// Start transaction
mysqli_begin_transaction($conn);

try {
    // Update product price
    $update_sql = "UPDATE produse SET pret = $price, data_actualizare = NOW() WHERE id = $product_id";
    
    if (!mysqli_query($conn, $update_sql)) {
        throw new Exception("Eroare la actualizarea prețului: " . mysqli_error($conn));
    }
    
    // Record price change in history
    $history_sql = "INSERT INTO istoric_preturi (produs_id, pret_vechi, pret_nou, user_id) 
                   VALUES ($product_id, $old_price, $price, $user_id)";
    
    if (!mysqli_query($conn, $history_sql)) {
        throw new Exception("Eroare la înregistrarea istoricului: " . mysqli_error($conn));
    }
    
    // Log the action
    log_action($user_id, 'actualizare_pret', "Produs ID: $product_id, Nume: {$product['nume']}, Preț vechi: $old_price, Preț nou: $price");
    
    // Commit transaction
    mysqli_commit($conn);
    
    // Set success response
    $response['success'] = true;
    $response['message'] = 'Prețul produsului a fost actualizat cu succes.';
    
} catch (Exception $e) {
    // Rollback transaction on error
    mysqli_rollback($conn);
    
    $response['message'] = 'A apărut o eroare: ' . $e->getMessage();
}

// Return response for AJAX requests
if ($is_ajax) {
    echo json_encode($response);
    exit;
}

// Redirect for regular requests
header("Location: " . ($_SESSION['user_role'] == 'administrator' ? 'admin-dashboard.html' : 'employee-dashboard.html'));
exit;
?>