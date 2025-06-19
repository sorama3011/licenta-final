<?php
// Include database configuration
require_once 'db-config.php';

// Start session
session_start();

// Initialize response array
$response = [
    'success' => false,
    'message' => '',
    'cart_count' => 0,
    'cart_total' => 0
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

// Check if product ID and quantity are provided
if (!isset($_POST['product_id']) || empty($_POST['product_id']) || !isset($_POST['quantity'])) {
    $response['message'] = 'Datele necesare lipsesc.';
    
    if ($is_ajax) {
        echo json_encode($response);
        exit;
    } else {
        header("Location: cart.html");
        exit;
    }
}

// Get user ID, product ID and quantity
$user_id = $_SESSION['user_id'];
$product_id = (int)$_POST['product_id'];
$quantity = (int)$_POST['quantity'];

// Check if quantity is valid
if ($quantity <= 0) {
    // If quantity is 0 or negative, remove product from cart
    $delete_sql = "DELETE FROM cos_cumparaturi WHERE user_id = $user_id AND produs_id = $product_id";
    
    if (mysqli_query($conn, $delete_sql)) {
        $response['success'] = true;
        $response['message'] = 'Produsul a fost eliminat din coș.';
        
        // Log the action
        log_action($user_id, 'eliminare_din_cos', "Produs ID: $product_id");
    } else {
        $response['message'] = 'A apărut o eroare la eliminarea produsului din coș.';
    }
} else {
    // Check if product exists and is in stock
    $product_sql = "SELECT id, stoc FROM produse WHERE id = $product_id AND activ = 1";
    $product_result = mysqli_query($conn, $product_sql);
    
    if (mysqli_num_rows($product_result) == 0) {
        $response['message'] = 'Produsul nu există sau nu este disponibil.';
        
        if ($is_ajax) {
            echo json_encode($response);
            exit;
        } else {
            header("Location: cart.html");
            exit;
        }
    }
    
    $product = mysqli_fetch_assoc($product_result);
    
    // Check if quantity exceeds stock
    if ($quantity > $product['stoc']) {
        $quantity = $product['stoc'];
        $response['message'] = 'Cantitatea a fost ajustată la stocul disponibil.';
    }
    
    // Update cart
    $update_sql = "UPDATE cos_cumparaturi SET cantitate = $quantity, data_actualizare = NOW() 
                  WHERE user_id = $user_id AND produs_id = $product_id";
    
    if (mysqli_query($conn, $update_sql)) {
        $response['success'] = true;
        $response['message'] = 'Coșul a fost actualizat.';
        
        // Log the action
        log_action($user_id, 'actualizare_cos', "Produs ID: $product_id, Cantitate: $quantity");
    } else {
        $response['message'] = 'A apărut o eroare la actualizarea coșului.';
    }
}

// Get cart count
$count_sql = "SELECT SUM(cantitate) as total FROM cos_cumparaturi WHERE user_id = $user_id";
$count_result = mysqli_query($conn, $count_sql);
$count_row = mysqli_fetch_assoc($count_result);
$response['cart_count'] = $count_row['total'] ? (int)$count_row['total'] : 0;

// Get cart total
$total_sql = "SELECT SUM(p.pret * c.cantitate) as total 
             FROM cos_cumparaturi c 
             JOIN produse p ON c.produs_id = p.id 
             WHERE c.user_id = $user_id";
$total_result = mysqli_query($conn, $total_sql);
$total_row = mysqli_fetch_assoc($total_result);
$response['cart_total'] = $total_row['total'] ? (float)$total_row['total'] : 0;

// Return response for AJAX requests
if ($is_ajax) {
    echo json_encode($response);
    exit;
}

// Redirect for regular requests
header("Location: cart.html");
exit;
?>