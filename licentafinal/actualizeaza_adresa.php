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
        $response['message'] = 'Trebuie să fii autentificat pentru a actualiza o adresă.';
        echo json_encode($response);
        exit;
    }
    
    // For regular requests, redirect to login
    $_SESSION['redirect_url'] = 'client-dashboard.html#addresses';
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
        header("Location: client-dashboard.html#addresses");
        exit;
    }
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

// Get user ID and form data
$user_id = $_SESSION['user_id'];
$address_id = (int)$_POST['address_id'];
$nume_adresa = isset($_POST['name']) ? sanitize_input($_POST['name']) : '';
$adresa = sanitize_input($_POST['address']);
$oras = sanitize_input($_POST['city']);
$judet = sanitize_input($_POST['county']);
$cod_postal = sanitize_input($_POST['postal_code']);
$telefon = isset($_POST['phone']) ? sanitize_input($_POST['phone']) : '';
$implicit = isset($_POST['default']) && $_POST['default'] == '1' ? 1 : 0;

// Validate input
if (empty($adresa) || empty($oras) || empty($judet) || empty($cod_postal)) {
    $response['message'] = 'Toate câmpurile obligatorii trebuie completate.';
    
    if ($is_ajax) {
        echo json_encode($response);
        exit;
    } else {
        header("Location: client-dashboard.html#addresses");
        exit;
    }
}

// Check if address exists and belongs to user
$check_sql = "SELECT id FROM adrese WHERE id = $address_id AND user_id = $user_id";
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

// Start transaction
mysqli_begin_transaction($conn);

try {
    // If this is the default address, unset any existing default
    if ($implicit) {
        $update_default_sql = "UPDATE adrese SET implicit = 0 WHERE user_id = $user_id AND id != $address_id";
        mysqli_query($conn, $update_default_sql);
    }
    
    // Update address
    $update_sql = "UPDATE adrese SET nume_adresa = '$nume_adresa', adresa = '$adresa', oras = '$oras', 
                  judet = '$judet', cod_postal = '$cod_postal', telefon = '$telefon', implicit = $implicit 
                  WHERE id = $address_id AND user_id = $user_id";
    
    if (!mysqli_query($conn, $update_sql)) {
        throw new Exception("Eroare la actualizarea adresei: " . mysqli_error($conn));
    }
    
    // Log the action
    log_action($user_id, 'actualizare_adresa', "Adresă ID: $address_id");
    
    // Commit transaction
    mysqli_commit($conn);
    
    // Set success response
    $response['success'] = true;
    $response['message'] = 'Adresa a fost actualizată cu succes.';
    
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