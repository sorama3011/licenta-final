<?php
// Include database configuration
require_once 'db-config.php';

// Start session
session_start();

// Initialize response array
$response = [
    'success' => false,
    'message' => '',
    'is_favorite' => false
];

// Check if request is AJAX
$is_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // For AJAX requests, return error
    if ($is_ajax) {
        $response['message'] = 'Trebuie să fii autentificat pentru a adăuga produse la favorite.';
        echo json_encode($response);
        exit;
    }
    
    // For regular requests, redirect to login
    $_SESSION['redirect_url'] = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'products.html';
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
$action = isset($_POST['action']) ? sanitize_input($_POST['action']) : 'toggle';

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

// Check if product is already in favorites
$check_sql = "SELECT id FROM favorite WHERE user_id = $user_id AND produs_id = $product_id";
$check_result = mysqli_query($conn, $check_sql);
$is_favorite = mysqli_num_rows($check_result) > 0;

// Perform action based on request
if ($action === 'add' || ($action === 'toggle' && !$is_favorite)) {
    // Add to favorites if not already there
    if (!$is_favorite) {
        $insert_sql = "INSERT INTO favorite (user_id, produs_id) VALUES ($user_id, $product_id)";
        
        if (mysqli_query($conn, $insert_sql)) {
            $response['success'] = true;
            $response['is_favorite'] = true;
            $response['message'] = 'Produsul a fost adăugat la favorite.';
            
            // Log the action
            log_action($user_id, 'adaugare_favorit', "Produs ID: $product_id, Nume: {$product['nume']}");
        } else {
            $response['message'] = 'A apărut o eroare la adăugarea produsului la favorite.';
        }
    } else {
        $response['success'] = true;
        $response['is_favorite'] = true;
        $response['message'] = 'Produsul este deja în lista de favorite.';
    }
} elseif ($action === 'remove' || ($action === 'toggle' && $is_favorite)) {
    // Remove from favorites
    $delete_sql = "DELETE FROM favorite WHERE user_id = $user_id AND produs_id = $product_id";
    
    if (mysqli_query($conn, $delete_sql)) {
        $response['success'] = true;
        $response['is_favorite'] = false;
        $response['message'] = 'Produsul a fost eliminat din favorite.';
        
        // Log the action
        log_action($user_id, 'eliminare_favorit', "Produs ID: $product_id, Nume: {$product['nume']}");
    } else {
        $response['message'] = 'A apărut o eroare la eliminarea produsului din favorite.';
    }
} elseif ($action === 'check') {
    // Just check if product is in favorites
    $response['success'] = true;
    $response['is_favorite'] = $is_favorite;
    $response['message'] = $is_favorite ? 'Produsul este în lista de favorite.' : 'Produsul nu este în lista de favorite.';
}

// Return response for AJAX requests
if ($is_ajax) {
    echo json_encode($response);
    exit;
}

// Redirect for regular requests
header("Location: " . (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'products.html'));
exit;
?>