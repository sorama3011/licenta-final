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

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'administrator') {
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

// Check if review ID and action are provided
if (!isset($_POST['review_id']) || empty($_POST['review_id']) || !isset($_POST['action']) || empty($_POST['action'])) {
    $response['message'] = 'Datele necesare lipsesc.';
    
    if ($is_ajax) {
        echo json_encode($response);
        exit;
    } else {
        header("Location: admin-dashboard.html");
        exit;
    }
}

// Get user ID, review ID and action
$user_id = $_SESSION['user_id'];
$review_id = (int)$_POST['review_id'];
$action = sanitize_input($_POST['action']);

// Validate action
if (!in_array($action, ['approve', 'reject'])) {
    $response['message'] = 'Acțiune invalidă.';
    
    if ($is_ajax) {
        echo json_encode($response);
        exit;
    } else {
        header("Location: admin-dashboard.html");
        exit;
    }
}

// Check if review exists
$review_sql = "SELECT r.id, r.produs_id, p.nume as produs_nume, u.id as user_id, 
              CONCAT(u.prenume, ' ', u.nume) as user_name
              FROM recenzii r
              JOIN produse p ON r.produs_id = p.id
              JOIN utilizatori u ON r.user_id = u.id
              WHERE r.id = $review_id";
$review_result = mysqli_query($conn, $review_sql);

if (mysqli_num_rows($review_result) == 0) {
    $response['message'] = 'Recenzia nu există.';
    
    if ($is_ajax) {
        echo json_encode($response);
        exit;
    } else {
        header("Location: admin-dashboard.html");
        exit;
    }
}

$review = mysqli_fetch_assoc($review_result);

// Process action
if ($action == 'approve') {
    // Approve review
    $update_sql = "UPDATE recenzii SET aprobat = 1, data_aprobare = NOW() WHERE id = $review_id";
    
    if (mysqli_query($conn, $update_sql)) {
        $response['success'] = true;
        $response['message'] = 'Recenzia a fost aprobată.';
        
        // Log the action
        log_action($user_id, 'aprobare_recenzie', "Recenzie ID: $review_id, Produs: {$review['produs_nume']}, Utilizator: {$review['user_name']}");
    } else {
        $response['message'] = 'A apărut o eroare la aprobarea recenziei.';
    }
} else {
    // Reject (delete) review
    $delete_sql = "DELETE FROM recenzii WHERE id = $review_id";
    
    if (mysqli_query($conn, $delete_sql)) {
        $response['success'] = true;
        $response['message'] = 'Recenzia a fost respinsă și ștearsă.';
        
        // Log the action
        log_action($user_id, 'respingere_recenzie', "Recenzie ID: $review_id, Produs: {$review['produs_nume']}, Utilizator: {$review['user_name']}");
    } else {
        $response['message'] = 'A apărut o eroare la respingerea recenziei.';
    }
}

// Return response for AJAX requests
if ($is_ajax) {
    echo json_encode($response);
    exit;
}

// Redirect for regular requests
header("Location: admin-dashboard.html");
exit;
?>