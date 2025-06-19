<?php
// Database connection configuration
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'gusturi_romanesti');

// Attempt to connect to MySQL database
$conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Check connection
if($conn === false) {
    die("ERROR: Could not connect to database. " . mysqli_connect_error());
}

// Set character set to UTF-8
mysqli_set_charset($conn, "utf8mb4");

// Function to sanitize input data
function sanitize_input($data) {
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    $data = mysqli_real_escape_string($conn, $data);
    return $data;
}

// Function to generate a secure random token
function generate_token($length = 32) {
    return bin2hex(random_bytes($length));
}

// Function to log actions in the system
function log_action($user_id, $action, $details = ''): void {
    global $conn;

    $user_id = (int)$user_id;

    // Nu loga nimic dacă ID-ul e invalid (ex: 0)
    if ($user_id <= 0) {
        return;
    }

    $action = sanitize_input($action);
    $details = sanitize_input($details);
    $ip_address = $_SERVER['REMOTE_ADDR'];

    $stmt = $conn->prepare("INSERT INTO jurnalizare (user_id, actiune, detalii, ip_address) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $user_id, $action, $details, $ip_address);
    $stmt->execute();
    $stmt->close();
}

// Function to format price with RON currency
function format_price($price) {
    return number_format($price, 2, '.', ',') . ' RON';
}
?>