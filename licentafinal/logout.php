<?php
// Include database configuration
require_once 'db-config.php';

// Start session
session_start();

// Check if user is logged in
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    
    // Log the action
    log_action($user_id, 'deconectare', 'Utilizator deconectat');
    
    // Unset all session variables
    $_SESSION = array();
    
    // Destroy the session
    session_destroy();
}

// Redirect to login page
header("Location: login.php");
exit;
?>