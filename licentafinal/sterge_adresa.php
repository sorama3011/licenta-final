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

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // For AJAX requests, return error
    if ($is_ajax) {
        $response['message'] = 'Trebuie să fii autentificat pentru a șterge o adresă.';
        echo json_encode($response);
        exit;
    }
    
    // For regular requests, redirect to login
    $_SESSION['redirect_url'] = 'client-dashboard.html#addresses';
    header("Location: login.php");
    exit;
}

// Check if address ID is provided
if (!isset($_POST['address_id']) || empty($_POST['address_id'])) {
    $response['message'] = 'ID-ul adresei lipsește.';
    
    if ($is_ajax) {
        echo json_encode($response);
        exit;
    } else {
        header("Location: client-dashboard.html#addresses");
        exit;
    }
}

// Get user ID and address ID
$user_id = $_SESSION['user_id'];
$address_id = (int)$_POST['address_id'];

// Check if address exists and belongs to user
$check_sql = "SELECT id, implicit FROM adrese WHERE id = $address_id AND user_id = $user_id";
$check_result = mysqli_query($conn, $check_sql);

if (mysqli_num_rows($check_result) == 0) {
    $response['message'] = 'Adresa nu există sau nu vă aparține.';
    
    if ($is_ajax) {
        echo json_encode($response);
        exit;
    } else {
        header("Location: client-dashboard.html#addresses");
        exit;
    }
}

$address = mysqli_fetch_assoc($check_result);

// Check if address is used in any orders
$orders_sql = "SELECT id FROM comenzi WHERE adresa_livrare_id = $address_id OR adresa_facturare_id = $address_id";
$orders_result = mysqli_query($conn, $orders_sql);

if (mysqli_num_rows($orders_result) > 0) {
    $response['message'] = 'Această adresă nu poate fi ștearsă deoarece este folosită în comenzi.';
    
    if ($is_ajax) {
        echo json_encode($response);
        exit;
    } else {
        header("Location: client-dashboard.html#addresses");
        exit;
    }
}

// Start transaction
mysqli_begin_transaction($conn);

try {
    // Delete address
    $delete_sql = "DELETE FROM adrese WHERE id = $address_id AND user_id = $user_id";
    
    if (!mysqli_query($conn, $delete_sql)) {
        throw new Exception("Eroare la ștergerea adresei: " . mysqli_error($conn));
    }
    
    // If this was the default address, set another address as default
    if ($address['implicit']) {
        $update_sql = "UPDATE adrese SET implicit = 1 WHERE user_id = $user_id ORDER BY data_adaugare DESC LIMIT 1";
        mysqli_query($conn, $update_sql);
    }
    
    // Log the action
    log_action($user_id, 'stergere_adresa', "Adresă ID: $address_id");
    
    // Commit transaction
    mysqli_commit($conn);
    
    // Set success response
    $response['success'] = true;
    $response['message'] = 'Adresa a fost ștearsă cu succes.';
    
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
header("Location: client-dashboard.html#addresses");
exit;
?>