<?php
// Include database configuration
require_once 'db-config.php';

// Start session
session_start();

// Initialize response array
$response = [
    'success' => false,
    'message' => '',
    'cart_count' => 0
];

// Check if request is AJAX
$is_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // For AJAX requests, return error
    if ($is_ajax) {
        $response['message'] = 'Trebuie să fii autentificat pentru a modifica coșul.';
        echo json_encode($response);
        exit;
    }
    
    // For regular requests, redirect to login
    $_SESSION['redirect_url'] = 'cart.html';
    header("Location: login.php");
    exit;
}

// Check if product ID is provided
if (!isset($_POST['product_id']) || empty($_POST['product_id'])) {
    $response['message'] = 'ID-ul produsului lipsește.';
    
    if ($is_ajax) {
        echo json_encode($response);
        exit;
    } else {
        header("Location: cart.html");
        exit;
    }
}

// Get user ID and product ID
$user_id = $_SESSION['user_id'];
$product_id = (int)$_POST['product_id'];

// Delete product from cart
$delete_sql = "DELETE FROM cos_cumparaturi WHERE user_id = $user_id AND produs_id = $product_id";

if (mysqli_query($conn, $delete_sql)) {
    $response['success'] = true;
    $response['message'] = 'Produsul a fost eliminat din coș.';
    
    // Log the action
    log_action($user_id, 'eliminare_din_cos', "Produs ID: $product_id");
} else {
    $response['message'] = 'A apărut o eroare la eliminarea produsului din coș.';
}

// Get cart count
$count_sql = "SELECT SUM(cantitate) as total FROM cos_cumparaturi WHERE user_id = $user_id";
$count_result = mysqli_query($conn, $count_sql);
$count_row = mysqli_fetch_assoc($count_result);
$response['cart_count'] = $count_row['total'] ? (int)$count_row['total'] : 0;

// Return response for AJAX requests
if ($is_ajax) {
    echo json_encode($response);
    exit;
}

// Redirect for regular requests
header("Location: cart.html");
exit;
?>