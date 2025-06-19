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
$report_type = isset($_GET['type']) ? sanitize_input($_GET['type']) : 'topSpenders';
$start_date = isset($_GET['start_date']) ? sanitize_input($_GET['start_date']) : date('Y-m-d', strtotime('-1 year'));
$end_date = isset($_GET['end_date']) ? sanitize_input($_GET['end_date']) : date('Y-m-d');
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;

// Validate dates
if (strtotime($start_date) === false || strtotime($end_date) === false) {
    $response['message'] = 'Datele furnizate nu sunt valide.';
    echo json_encode($response);
    exit;
}

// Prepare SQL based on report type
switch ($report_type) {
    case 'topSpenders':
        $sql = "SELECT u.id, CONCAT(u.prenume, ' ', u.nume) as nume, u.email,
               COUNT(c.id) as numar_comenzi,
               SUM(c.total) as total_cheltuit
               FROM utilizatori u
               JOIN comenzi c ON u.id = c.user_id
               WHERE c.data_plasare BETWEEN '$start_date' AND '$end_date 23:59:59'
               AND c.status != 'anulata'
               GROUP BY u.id, nume, u.email
               ORDER BY total_cheltuit DESC
               LIMIT $limit";
        break;
        
    case 'frequency':
        $sql = "SELECT 
               CASE 
                   WHEN count_orders = 1 THEN 'O dată'
                   WHEN count_orders BETWEEN 2 AND 5 THEN '2-5 ori'
                   WHEN count_orders BETWEEN 6 AND 10 THEN '6-10 ori'
                   ELSE '11+ ori'
               END as frecventa,
               COUNT(*) as numar_clienti
               FROM (
                   SELECT u.id, COUNT(c.id) as count_orders
                   FROM utilizatori u
                   JOIN comenzi c ON u.id = c.user_id
                   WHERE c.data_plasare BETWEEN '$start_date' AND '$end_date 23:59:59'
                   AND c.status != 'anulata'
                   GROUP BY u.id
               ) as order_counts
               GROUP BY frecventa
               ORDER BY FIELD(frecventa, 'O dată', '2-5 ori', '6-10 ori', '11+ ori')";
        break;
        
    case 'newVsReturning':
        $sql = "SELECT 
               CASE 
                   WHEN first_order >= '$start_date' THEN 'Noi'
                   ELSE 'Recurenți'
               END as tip_client,
               COUNT(*) as numar_clienti
               FROM (
                   SELECT u.id, MIN(c.data_plasare) as first_order
                   FROM utilizatori u
                   JOIN comenzi c ON u.id = c.user_id
                   WHERE c.status != 'anulata'
                   GROUP BY u.id
                   HAVING first_order <= '$end_date 23:59:59'
               ) as first_orders
               GROUP BY tip_client";
        break;
        
    case 'region':
        $sql = "SELECT a.judet, COUNT(DISTINCT c.user_id) as numar_clienti
               FROM comenzi c
               JOIN adrese a ON c.adresa_livrare_id = a.id
               WHERE c.data_plasare BETWEEN '$start_date' AND '$end_date 23:59:59'
               AND c.status != 'anulata'
               GROUP BY a.judet
               ORDER BY numar_clienti DESC";
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

while ($row = mysqli_fetch_assoc($result)) {
    switch ($report_type) {
        case 'topSpenders':
            $labels[] = $row['nume'];
            $values[] = (float)$row['total_cheltuit'];
            $data[] = [
                'id' => (int)$row['id'],
                'name' => $row['nume'],
                'email' => $row['email'],
                'orders_count' => (int)$row['numar_comenzi'],
                'total_spent' => (float)$row['total_cheltuit']
            ];
            break;
            
        case 'frequency':
            $labels[] = $row['frecventa'];
            $values[] = (int)$row['numar_clienti'];
            $data[] = [
                'frequency' => $row['frecventa'],
                'clients_count' => (int)$row['numar_clienti']
            ];
            break;
            
        case 'newVsReturning':
            $labels[] = $row['tip_client'];
            $values[] = (int)$row['numar_clienti'];
            $data[] = [
                'client_type' => $row['tip_client'],
                'clients_count' => (int)$row['numar_clienti']
            ];
            break;
            
        case 'region':
            $labels[] = $row['judet'];
            $values[] = (int)$row['numar_clienti'];
            $data[] = [
                'region' => $row['judet'],
                'clients_count' => (int)$row['numar_clienti']
            ];
            break;
    }
}

// Update response
$response['success'] = true;
$response['data'] = [
    'report_type' => $report_type,
    'start_date' => $start_date,
    'end_date' => $end_date,
    'labels' => $labels,
    'values' => $values,
    'detailed_data' => $data
];

// Return response
echo json_encode($response);
exit;
?>