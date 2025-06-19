<?php
// Include database configuration
require_once 'db-config.php';

// Start session
session_start();

// Initialize response array
$response = [
    'success' => false,
    'message' => '',
    'discount' => 0,
    'voucher' => null
];

// Check if request is AJAX
$is_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // For AJAX requests, return error
    if ($is_ajax) {
        $response['message'] = 'Trebuie să fii autentificat pentru a folosi vouchere.';
        echo json_encode($response);
        exit;
    }
    
    // For regular requests, redirect to login
    $_SESSION['redirect_url'] = 'cart.html';
    header("Location: login.php");
    exit;
}

// Check if voucher code is provided
if (!isset($_POST['voucher_code']) || empty($_POST['voucher_code'])) {
    $response['message'] = 'Codul voucher lipsește.';
    
    if ($is_ajax) {
        echo json_encode($response);
        exit;
    } else {
        header("Location: cart.html");
        exit;
    }
}

// Get user ID and voucher code
$user_id = $_SESSION['user_id'];
$voucher_code = sanitize_input($_POST['voucher_code']);

// Check if voucher exists and is valid
$voucher_sql = "SELECT v.id, v.cod, v.tip, v.valoare, v.minim_comanda, v.data_sfarsit
               FROM vouchere v
               WHERE v.cod = '$voucher_code'
               AND v.activ = 1
               AND v.data_inceput <= CURDATE()
               AND v.data_sfarsit >= CURDATE()
               AND (v.utilizari_maxime IS NULL OR v.utilizari_curente < v.utilizari_maxime)";
$voucher_result = mysqli_query($conn, $voucher_sql);

if (mysqli_num_rows($voucher_result) == 0) {
    $response['message'] = 'Codul voucher introdus nu este valid sau a expirat.';
    
    if ($is_ajax) {
        echo json_encode($response);
        exit;
    } else {
        header("Location: cart.html");
        exit;
    }
}

$voucher = mysqli_fetch_assoc($voucher_result);

// Check if user has already used this voucher
$check_sql = "SELECT id, utilizat FROM vouchere_utilizatori 
             WHERE voucher_id = {$voucher['id']} AND user_id = $user_id";
$check_result = mysqli_query($conn, $check_sql);

if (mysqli_num_rows($check_result) > 0) {
    $usage = mysqli_fetch_assoc($check_result);
    
    if ($usage['utilizat']) {
        $response['message'] = 'Acest voucher a fost deja folosit.';
        
        if ($is_ajax) {
            echo json_encode($response);
            exit;
        } else {
            header("Location: cart.html");
            exit;
        }
    }
} else {
    // Assign voucher to user
    $assign_sql = "INSERT INTO vouchere_utilizatori (voucher_id, user_id) VALUES ({$voucher['id']}, $user_id)";
    mysqli_query($conn, $assign_sql);
}

// Get cart total to check minimum order
$cart_sql = "SELECT SUM(p.pret * c.cantitate) as total 
            FROM cos_cumparaturi c 
            JOIN produse p ON c.produs_id = p.id 
            WHERE c.user_id = $user_id";
$cart_result = mysqli_query($conn, $cart_sql);
$cart_row = mysqli_fetch_assoc($cart_result);
$cart_total = $cart_row['total'] ? (float)$cart_row['total'] : 0;

// Check if cart meets minimum order
if ($cart_total < $voucher['minim_comanda']) {
    $response['message'] = "Comanda minimă pentru acest voucher este de {$voucher['minim_comanda']} RON.";
    
    if ($is_ajax) {
        echo json_encode($response);
        exit;
    } else {
        header("Location: cart.html");
        exit;
    }
}

// Calculate discount
$discount = 0;
if ($voucher['tip'] == 'procent') {
    $discount = $cart_total * ($voucher['valoare'] / 100);
} else {
    $discount = $voucher['valoare'];
    if ($discount > $cart_total) {
        $discount = $cart_total;
    }
}

// Format voucher data
$voucher_data = [
    'id' => (int)$voucher['id'],
    'code' => $voucher['cod'],
    'type' => $voucher['tip'],
    'value' => (float)$voucher['valoare'],
    'min_order' => (float)$voucher['minim_comanda'],
    'expiration_date' => date('d.m.Y', strtotime($voucher['data_sfarsit'])),
    'discount' => $discount
];

// Update response
$response['success'] = true;
$response['message'] = $voucher['tip'] == 'procent' ? 
                      "Voucher aplicat cu succes! Reducere de {$voucher['valoare']}%" : 
                      "Voucher aplicat cu succes! Reducere de {$voucher['valoare']} RON";
$response['discount'] = $discount;
$response['voucher'] = $voucher_data;

// Return response for AJAX requests
if ($is_ajax) {
    echo json_encode($response);
    exit;
}

// Redirect for regular requests
header("Location: cart.html");
exit;
?>