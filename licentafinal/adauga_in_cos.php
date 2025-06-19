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
        $response['message'] = 'Trebuie să fii autentificat pentru a adăuga produse în coș.';
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
        header("Location: products.html");
        exit;
    }
}

// Get user ID and product ID
$user_id = $_SESSION['user_id'];
$product_id = (int)$_POST['product_id'];
$quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

// Validate quantity
if ($quantity <= 0) {
    $quantity = 1;
}

// Check if product exists and is in stock
$product_sql = "SELECT id, nume, pret, stoc FROM produse WHERE id = $product_id AND activ = 1";
$product_result = mysqli_query($conn, $product_sql);

if (mysqli_num_rows($product_result) == 0) {
    $response['message'] = 'Produsul nu există sau nu este disponibil.';
    
    if ($is_ajax) {
        echo json_encode($response);
        exit;
    } else {
        header("Location: products.html");
        exit;
    }
}

$product = mysqli_fetch_assoc($product_result);

// Check if product is in stock
if ($product['stoc'] < $quantity) {
    $response['message'] = 'Ne pare rău, dar nu avem suficient stoc pentru acest produs.';
    
    if ($is_ajax) {
        echo json_encode($response);
        exit;
    } else {
        header("Location: product.html?id=$product_id");
        exit;
    }
}

// Check if product is already in cart
$check_sql = "SELECT id, cantitate FROM cos_cumparaturi WHERE user_id = $user_id AND produs_id = $product_id";
$check_result = mysqli_query($conn, $check_sql);

if (mysqli_num_rows($check_result) > 0) {
    // Product is already in cart, update quantity
    $cart_item = mysqli_fetch_assoc($check_result);
    $new_quantity = $cart_item['cantitate'] + $quantity;
    
    // Check if new quantity exceeds stock
    if ($new_quantity > $product['stoc']) {
        $new_quantity = $product['stoc'];
        $response['message'] = 'Cantitatea a fost ajustată la stocul disponibil.';
    }
    
    $update_sql = "UPDATE cos_cumparaturi SET cantitate = $new_quantity, data_actualizare = NOW() WHERE id = {$cart_item['id']}";
    
    if (mysqli_query($conn, $update_sql)) {
        $response['success'] = true;
        $response['message'] = 'Cantitatea produsului a fost actualizată în coș.';
    } else {
        $response['message'] = 'A apărut o eroare la actualizarea coșului.';
    }
} else {
    // Add product to cart
    $insert_sql = "INSERT INTO cos_cumparaturi (user_id, produs_id, cantitate) VALUES ($user_id, $product_id, $quantity)";
    
    if (mysqli_query($conn, $insert_sql)) {
        $response['success'] = true;
        $response['message'] = 'Produsul a fost adăugat în coș.';
    } else {
        $response['message'] = 'A apărut o eroare la adăugarea produsului în coș.';
    }
}

// Get cart count
$count_sql = "SELECT SUM(cantitate) as total FROM cos_cumparaturi WHERE user_id = $user_id";
$count_result = mysqli_query($conn, $count_sql);
$count_row = mysqli_fetch_assoc($count_result);
$response['cart_count'] = $count_row['total'] ? (int)$count_row['total'] : 0;

// Log the action
log_action($user_id, 'adaugare_in_cos', "Produs ID: $product_id, Cantitate: $quantity");

// Return response for AJAX requests
if ($is_ajax) {
    echo json_encode($response);
    exit;
}

// Redirect for regular requests
header("Location: cart.html");
exit;
?>