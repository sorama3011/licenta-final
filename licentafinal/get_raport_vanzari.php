<?php
// Include database configuration
require_once 'db-config.php';

// Start session
session_start();

// Initialize response array
$response = [
    'success' => false,
    'message' => '',
    'data' => null
];

// Check if user is logged in and has appropriate role
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['administrator', 'angajat'])) {
    $response['message'] = 'Nu aveți permisiunea de a accesa acest raport.';
    echo json_encode($response);
    exit;
}

// Get report parameters
$report_type = isset($_GET['type']) ? sanitize_input($_GET['type']) : 'monthly';
$start_date = isset($_GET['start_date']) ? sanitize_input($_GET['start_date']) : date('Y-m-d', strtotime('-1 year'));
$end_date = isset($_GET['end_date']) ? sanitize_input($_GET['end_date']) : date('Y-m-d');

// Validate dates
if (strtotime($start_date) === false || strtotime($end_date) === false) {
    $response['message'] = 'Datele furnizate nu sunt valide.';
    echo json_encode($response);
    exit;
}

// Prepare SQL based on report type
switch ($report_type) {
    case 'daily':
        $sql = "SELECT DATE(data_plasare) as date, 
               COUNT(*) as orders_count, 
               SUM(total) as total_sales
               FROM comenzi
               WHERE data_plasare BETWEEN '$start_date' AND '$end_date 23:59:59'
               AND status != 'anulata'
               GROUP BY DATE(data_plasare)
               ORDER BY date";
        break;
        
    case 'weekly':
        $sql = "SELECT YEAR(data_plasare) as year, 
               WEEK(data_plasare, 1) as week, 
               MIN(DATE(data_plasare)) as start_date,
               MAX(DATE(data_plasare)) as end_date,
               COUNT(*) as orders_count, 
               SUM(total) as total_sales
               FROM comenzi
               WHERE data_plasare BETWEEN '$start_date' AND '$end_date 23:59:59'
               AND status != 'anulata'
               GROUP BY YEAR(data_plasare), WEEK(data_plasare, 1)
               ORDER BY year, week";
        break;
        
    case 'monthly':
        $sql = "SELECT YEAR(data_plasare) as year, 
               MONTH(data_plasare) as month, 
               COUNT(*) as orders_count, 
               SUM(total) as total_sales
               FROM comenzi
               WHERE data_plasare BETWEEN '$start_date' AND '$end_date 23:59:59'
               AND status != 'anulata'
               GROUP BY YEAR(data_plasare), MONTH(data_plasare)
               ORDER BY year, month";
        break;
        
    case 'yearly':
        $sql = "SELECT YEAR(data_plasare) as year, 
               COUNT(*) as orders_count, 
               SUM(total) as total_sales
               FROM comenzi
               WHERE data_plasare BETWEEN '$start_date' AND '$end_date 23:59:59'
               AND status != 'anulata'
               GROUP BY YEAR(data_plasare)
               ORDER BY year";
        break;
        
    default:
        $response['message'] = 'Tip de raport invalid.';
        echo json_encode($response);
        exit;
}

$result = mysqli_query($conn, $sql);

// Check if query was successful
if (!$result) {
    $response['message'] = 'A apărut o eroare la generarea raportului.';
    echo json_encode($response);
    exit;
}

// Process results
$data = [];
$labels = [];
$values = [];
$orders_count = [];

while ($row = mysqli_fetch_assoc($result)) {
    switch ($report_type) {
        case 'daily':
            $label = date('d.m.Y', strtotime($row['date']));
            break;
            
        case 'weekly':
            $label = 'S' . $row['week'] . ' (' . date('d.m', strtotime($row['start_date'])) . ' - ' . date('d.m', strtotime($row['end_date'])) . ')';
            break;
            
        case 'monthly':
            $months = [
                1 => 'Ian', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'Mai', 6 => 'Iun',
                7 => 'Iul', 8 => 'Aug', 9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dec'
            ];
            $label = $months[(int)$row['month']] . ' ' . $row['year'];
            break;
            
        case 'yearly':
            $label = $row['year'];
            break;
    }
    
    $labels[] = $label;
    $values[] = (float)$row['total_sales'];
    $orders_count[] = (int)$row['orders_count'];
    
    $data[] = [
        'label' => $label,
        'orders_count' => (int)$row['orders_count'],
        'total_sales' => (float)$row['total_sales']
    ];
}

// Calculate totals
$total_orders = array_sum($orders_count);
$total_sales = array_sum($values);
$average_order = $total_orders > 0 ? $total_sales / $total_orders : 0;

// Update response
$response['success'] = true;
$response['data'] = [
    'report_type' => $report_type,
    'start_date' => $start_date,
    'end_date' => $end_date,
    'labels' => $labels,
    'values' => $values,
    'orders_count' => $orders_count,
    'total_orders' => $total_orders,
    'total_sales' => $total_sales,
    'average_order' => $average_order,
    'detailed_data' => $data
];

// Return response
echo json_encode($response);
exit;
?>