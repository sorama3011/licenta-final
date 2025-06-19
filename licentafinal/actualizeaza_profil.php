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
        $response['message'] = 'Trebuie să fii autentificat pentru a actualiza profilul.';
        echo json_encode($response);
        exit;
    }
    
    // For regular requests, redirect to login
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
        header("Location: client-dashboard.html");
        exit;
    }
}

// Get user ID and form data
$user_id = $_SESSION['user_id'];
$prenume = sanitize_input($_POST['firstName']);
$nume = sanitize_input($_POST['lastName']);
$telefon = isset($_POST['phone']) ? sanitize_input($_POST['phone']) : '';
$newsletter = isset($_POST['newsletter']) && $_POST['newsletter'] == '1' ? 1 : 0;

// Optional password change
$current_password = isset($_POST['currentPassword']) ? $_POST['currentPassword'] : '';
$new_password = isset($_POST['newPassword']) ? $_POST['newPassword'] : '';

// Validate input
if (empty($prenume) || empty($nume)) {
    $response['message'] = 'Numele și prenumele sunt obligatorii.';
    
    if ($is_ajax) {
        echo json_encode($response);
        exit;
    } else {
        header("Location: client-dashboard.html");
        exit;
    }
}

// Start transaction
mysqli_begin_transaction($conn);

try {
    // Update basic profile information
    $update_sql = "UPDATE utilizatori SET prenume = '$prenume', nume = '$nume', telefon = '$telefon', 
                  newsletter = $newsletter WHERE id = $user_id";
    
    if (!mysqli_query($conn, $update_sql)) {
        throw new Exception("Eroare la actualizarea profilului: " . mysqli_error($conn));
    }
    
    // Update password if provided
    if (!empty($current_password) && !empty($new_password)) {
        // Verify current password
        $password_sql = "SELECT parola FROM utilizatori WHERE id = $user_id";
        $password_result = mysqli_query($conn, $password_sql);
        $user = mysqli_fetch_assoc($password_result);
        
        if (!password_verify($current_password, $user['parola'])) {
            throw new Exception("Parola actuală este incorectă.");
        }
        
        // Validate new password
        if (strlen($new_password) < 8) {
            throw new Exception("Parola nouă trebuie să aibă cel puțin 8 caractere.");
        }
        
        // Hash and update new password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $password_update_sql = "UPDATE utilizatori SET parola = '$hashed_password' WHERE id = $user_id";
        
        if (!mysqli_query($conn, $password_update_sql)) {
            throw new Exception("Eroare la actualizarea parolei: " . mysqli_error($conn));
        }
        
        // Log password change
        log_action($user_id, 'schimbare_parola', "Parolă actualizată");
    }
    
    // Update session variables
    $_SESSION['user_name'] = "$prenume $nume";
    $_SESSION['user_phone'] = $telefon;
    
    // Log the action
    log_action($user_id, 'actualizare_profil', "Profil actualizat");
    
    // Commit transaction
    mysqli_commit($conn);
    
    // Set success response
    $response['success'] = true;
    $response['message'] = 'Profilul a fost actualizat cu succes.';
    
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
header("Location: client-dashboard.html");
exit;
?>