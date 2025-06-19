<?php
// Include database configuration
require_once 'db-config.php';

// Start session
session_start();

// Initialize response array
$response = [
    'success' => false,
    'message' => '',
    'logs' => [],
    'total' => 0,
    'page' => 1,
    'total_pages' => 1
];

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'administrator') {
    $response['message'] = 'Nu aveți permisiunea de a accesa jurnalul de activitate.';
    echo json_encode($response);
    exit;
}

// Get filter parameters
$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
$action = isset($_GET['action']) ? sanitize_input($_GET['action']) : '';
$start_date = isset($_GET['start_date']) ? sanitize_input($_GET['start_date']) : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? sanitize_input($_GET['end_date']) : date('Y-m-d');
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;

// Validate page and limit
if ($page < 1) $page = 1;
if ($limit < 1) $limit = 20;
if ($limit > 100) $limit = 100;

// Calculate offset
$offset = ($page - 1) * $limit;

// Build base query
$base_sql = "SELECT j.id, j.user_id, j.actiune, j.detalii, j.ip_address, j.data_actiune,
            CONCAT(u.prenume, ' ', u.nume) as user_name, u.email as user_email
            FROM jurnalizare j
            JOIN utilizatori u ON j.user_id = u.id
            WHERE 1=1";

// Add filters
$params = [];

if ($user_id > 0) {
    $base_sql .= " AND j.user_id = ?";
    $params[] = $user_id;
}

if (!empty($action)) {
    $base_sql .= " AND j.actiune = ?";
    $params[] = $action;
}

if (!empty($start_date) && !empty($end_date)) {
    $base_sql .= " AND j.data_actiune BETWEEN ? AND ?";
    $params[] = $start_date . ' 00:00:00';
    $params[] = $end_date . ' 23:59:59';
}

// Count total logs
$count_sql = str_replace("SELECT j.id, j.user_id, j.actiune, j.detalii, j.ip_address, j.data_actiune,
            CONCAT(u.prenume, ' ', u.nume) as user_name, u.email as user_email", "SELECT COUNT(*) as total", $base_sql);

$stmt = mysqli_prepare($conn, $count_sql);

if ($stmt) {
    if (!empty($params)) {
        $types = str_repeat('s', count($params));
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    
    mysqli_stmt_execute($stmt);
    $count_result = mysqli_stmt_get_result($stmt);
    $count_row = mysqli_fetch_assoc($count_result);
    $total = (int)$count_row['total'];
    
    mysqli_stmt_close($stmt);
} else {
    $response['message'] = 'A apărut o eroare la numărarea înregistrărilor.';
    echo json_encode($response);
    exit;
}

// Calculate total pages
$total_pages = ceil($total / $limit);

// Add sorting and pagination
$base_sql .= " ORDER BY j.data_actiune DESC LIMIT $offset, $limit";

// Prepare and execute query
$stmt = mysqli_prepare($conn, $base_sql);

if ($stmt) {
    if (!empty($params)) {
        $types = str_repeat('s', count($params));
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    // Process logs
    $logs = [];
    
    while ($log = mysqli_fetch_assoc($result)) {
        $logs[] = [
            'id' => (int)$log['id'],
            'user_id' => (int)$log['user_id'],
            'user_name' => $log['user_name'],
            'user_email' => $log['user_email'],
            'action' => $log['actiune'],
            'details' => $log['detalii'],
            'ip_address' => $log['ip_address'],
            'date' => date('d.m.Y H:i:s', strtotime($log['data_actiune']))
        ];
    }
    
    mysqli_stmt_close($stmt);
    
    // Update response
    $response['success'] = true;
    $response['logs'] = $logs;
    $response['total'] = $total;
    $response['page'] = $page;
    $response['total_pages'] = $total_pages;
    
} else {
    $response['message'] = 'A apărut o eroare la încărcarea jurnalului.';
}

// Return response
echo json_encode($response);
exit;
?>