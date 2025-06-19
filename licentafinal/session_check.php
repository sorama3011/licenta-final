
<?php
require_once 'db-config.php';
session_start();

header('Content-Type: application/json');

if (isset($_SESSION['user_id'])) {
    echo json_encode([
        'loggedIn' => true,
        'id' => $_SESSION['user_id'],
        'name' => $_SESSION['user_name'],
        'email' => $_SESSION['user_email'],
        'role' => $_SESSION['user_role'],
        'phone' => $_SESSION['user_phone'] ?? '',
        'loyalty_points' => $_SESSION['loyalty_points'] ?? 0
    ]);
} else {
    echo json_encode(['loggedIn' => false]);
}
?>
