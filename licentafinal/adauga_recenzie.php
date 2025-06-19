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
        $response['message'] = 'Trebuie să fii autentificat pentru a adăuga o recenzie.';
        echo json_encode($response);
        exit;
    }
    
    // For regular requests, redirect to login
    $_SESSION['redirect_url'] = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'products.html';
    header("Location: login.php");
    exit;
}

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] != "POST") {
    $response['message'] = 'Metoda de cerere invalidă.';
    
    if ($is_ajax) {
        echo json_encode($response);
        exit;
    } else {
        header("Location: products.html");
        exit;
    }
}

// Get user ID and form data
$user_id = $_SESSION['user_id'];
$product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
$rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
$title = isset($_POST['title']) ? sanitize_input($_POST['title']) : '';
$comment = isset($_POST['comment']) ? sanitize_input($_POST['comment']) : '';

// Validate input
if ($product_id <= 0) {
    $response['message'] = 'ID-ul produsului lipsește sau este invalid.';
    
    if ($is_ajax) {
        echo json_encode($response);
        exit;
    } else {
        header("Location: products.html");
        exit;
    }
}

if ($rating < 1 || $rating > 5) {
    $response['message'] = 'Evaluarea trebuie să fie între 1 și 5 stele.';
    
    if ($is_ajax) {
        echo json_encode($response);
        exit;
    } else {
        header("Location: product.html?id=$product_id");
        exit;
    }
}

// Check if product exists
$product_sql = "SELECT id, nume FROM produse WHERE id = $product_id AND activ = 1";
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

// Check if user has already reviewed this product
$check_sql = "SELECT id FROM recenzii WHERE user_id = $user_id AND produs_id = $product_id";
$check_result = mysqli_query($conn, $check_sql);

if (mysqli_num_rows($check_result) > 0) {
    // Update existing review
    $review = mysqli_fetch_assoc($check_result);
    $update_sql = "UPDATE recenzii SET rating = $rating, titlu = '$title', comentariu = '$comment', 
                  aprobat = 0, data_adaugare = NOW(), data_aprobare = NULL 
                  WHERE id = {$review['id']}";
    
    if (mysqli_query($conn, $update_sql)) {
        $response['success'] = true;
        $response['message'] = 'Recenzia ta a fost actualizată și va fi revizuită înainte de a fi publicată.';
        
        // Log the action
        log_action($user_id, 'actualizare_recenzie', "Produs ID: $product_id, Nume: {$product['nume']}, Rating: $rating");
    } else {
        $response['message'] = 'A apărut o eroare la actualizarea recenziei.';
    }
} else {
    // Insert new review
    $insert_sql = "INSERT INTO recenzii (produs_id, user_id, rating, titlu, comentariu) 
                  VALUES ($product_id, $user_id, $rating, '$title', '$comment')";
    
    if (mysqli_query($conn, $insert_sql)) {
        $response['success'] = true;
        $response['message'] = 'Recenzia ta a fost adăugată și va fi revizuită înainte de a fi publicată.';
        
        // Log the action
        log_action($user_id, 'adaugare_recenzie', "Produs ID: $product_id, Nume: {$product['nume']}, Rating: $rating");
    } else {
        $response['message'] = 'A apărut o eroare la adăugarea recenziei.';
    }
}

// Return response for AJAX requests
if ($is_ajax) {
    echo json_encode($response);
    exit;
}

// Redirect for regular requests
header("Location: product.html?id=$product_id");
exit;
?>