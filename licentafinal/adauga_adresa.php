<?php
// Include database configuration
require_once 'db-config.php';

// Start session
session_start();

// Initialize response array
$response = [
    'success' => false,
    'message' => '',
    'address_id' => 0
];

// Check if request is AJAX
$is_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // For AJAX requests, return error
    if ($is_ajax) {
        $response['message'] = 'Trebuie să fii autentificat pentru a adăuga o adresă.';
        echo json_encode($response);
        exit;
    }
    
    // For regular requests, redirect to login
    $_SESSION['redirect_url'] = 'checkout.php';
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
        header("Location: checkout.php");
        exit;
    }
}

// Get user ID and form data
$user_id = $_SESSION['user_id'];
$nume_adresa = isset($_POST['address_name']) ? sanitize_input($_POST['address_name']) : '';
$adresa = sanitize_input($_POST['address']);
$oras = sanitize_input($_POST['city']);
$judet = sanitize_input($_POST['county']);
$cod_postal = sanitize_input($_POST['postal_code']);
$telefon = isset($_POST['phone']) ? sanitize_input($_POST['phone']) : '';
$implicit = isset($_POST['default_address']) && $_POST['default_address'] == '1' ? 1 : 0;

// Validate input
if (empty($adresa) || empty($oras) || empty($judet) || empty($cod_postal)) {
    $response['message'] = 'Toate câmpurile obligatorii trebuie completate.';
    
    if ($is_ajax) {
        echo json_encode($response);
        exit;
    } else {
        header("Location: checkout.php");
        exit;
    }
}

// Start transaction
mysqli_begin_transaction($conn);

try {
    // If this is the default address, unset any existing default
    if ($implicit) {
        $update_sql = "UPDATE adrese SET implicit = 0 WHERE user_id = $user_id";
        mysqli_query($conn, $update_sql);
    }
    
    // Insert new address
    $insert_sql = "INSERT INTO adrese (user_id, nume_adresa, adresa, oras, judet, cod_postal, telefon, implicit) 
                  VALUES ($user_id, '$nume_adresa', '$adresa', '$oras', '$judet', '$cod_postal', '$telefon', $implicit)";
    
    if (!mysqli_query($conn, $insert_sql)) {
        throw new Exception("Eroare la adăugarea adresei: " . mysqli_error($conn));
    }
    
    $address_id = mysqli_insert_id($conn);
    
    // Log the action
    log_action($user_id, 'adaugare_adresa', "Adresă ID: $address_id");
    
    // Commit transaction
    mysqli_commit($conn);
    
    // Set success response
    $response['success'] = true;
    $response['message'] = 'Adresa a fost adăugată cu succes.';
    $response['address_id'] = $address_id;
    
    // Redirect to checkout page for regular requests
    if (!$is_ajax) {
        header("Location: checkout.php");
        exit;
    }
    
} catch (Exception $e) {
    // Rollback transaction on error
    mysqli_rollback($conn);
    
    $response['message'] = 'A apărut o eroare: ' . $e->getMessage();
    
    // Redirect to checkout page for regular requests
    if (!$is_ajax) {
        header("Location: checkout.php");
        exit;
    }
}

// Return response for AJAX requests
echo json_encode($response);
exit;
?>