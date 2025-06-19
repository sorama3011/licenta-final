<?php
// Include database configuration
require_once 'db-config.php';

// Start session
session_start();

// Initialize response array
$response = [
    'success' => false,
    'message' => '',
    'history' => [],
    'total' => 0,
    'page' => 1,
    'total_pages' => 1
];

// Check if user is logged in and has appropriate role
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['administrator', 'angajat'])) {
    $response['message'] = 'Nu aveți permisiunea de a accesa istoricul prețurilor.';
    echo json_encode($response);
    exit;
}

// Get filter parameters
$product_id = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;
$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
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
$base_sql = "SELECT ip.id, ip.produs_id, ip.pret_vechi, ip.pret_nou, ip.user_id, ip.data_modificare,
            p.nume as produs_nume, p.slug as produs_slug,
            CONCAT(u.prenume, ' ', u.nume) as user_name, u.email as user_email
            FROM istoric_preturi ip
            JOIN produse p ON ip.produs_id = p.id
            JOIN utilizatori u ON ip.user_id = u.id
            WHERE 1=1";

// Add filters
$params = [];

if ($product_id > 0) {
    $base_sql .= " AND ip.produs_id = ?";
    $params[] = $product_id;
}

if ($user_id > 0) {
    $base_sql .= " AND ip.user_id = ?";
    $params[] = $user_id;
}

if (!empty($start_date) && !empty($end_date)) {
    $base_sql .= " AND ip.data_modificare BETWEEN ? AND ?";
    $params[] = $start_date . ' 00:00:00';
    $params[] = $end_date . ' 23:59:59';
}

// Count total records
$count_sql = str_replace("SELECT ip.id, ip.produs_id, ip.pret_vechi, ip.pret_nou, ip.user_id, ip.data_modificare,
            p.nume as produs_nume, p.slug as produs_slug,
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
$base_sql .= " ORDER BY ip.data_modificare DESC LIMIT $offset, $limit";

// Prepare and execute query
$stmt = mysqli_prepare($conn, $base_sql);

if ($stmt) {
    if (!empty($params)) {
        $types = str_repeat('s', count($params));
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    // Process history
    $history = [];
    
    while ($record = mysqli_fetch_assoc($result)) {
        $history[] = [
            'id' => (int)$record['id'],
            'product_id' => (int)$record['produs_id'],
            'product_name' => $record['produs_nume'],
            'product_slug' => $record['produs_slug'],
            'old_price' => (float)$record['pret_vechi'],
            'new_price' => (float)$record['pret_nou'],
            'difference' => (float)$record['pret_nou'] - (float)$record['pret_vechi'],
            'difference_percent' => round(((float)$record['pret_nou'] - (float)$record['pret_vechi']) / (float)$record['pret_vechi'] * 100, 2),
            'user_id' => (int)$record['user_id'],
            'user_name' => $record['user_name'],
            'user_email' => $record['user_email'],
            'date' => date('d.m.Y H:i:s', strtotime($record['data_modificare']))
        ];
    }
    
    mysqli_stmt_close($stmt);
    
    // Update response
    $response['success'] = true;
    $response['history'] = $history;
    $response['total'] = $total;
    $response['page'] = $page;
    $response['total_pages'] = $total_pages;
    
} else {
    $response['message'] = 'A apărut o eroare la încărcarea istoricului.';
}

// Return response
echo json_encode($response);
exit;
?>