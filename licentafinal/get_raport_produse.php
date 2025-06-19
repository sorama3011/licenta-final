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
$report_type = isset($_GET['type']) ? sanitize_input($_GET['type']) : 'bestsellers';
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
    case 'bestsellers':
        $sql = "SELECT p.id, p.nume, c.nume as categorie, 
               SUM(cp.cantitate) as cantitate_vanduta, 
               SUM(cp.subtotal) as valoare_vanzari
               FROM comenzi_produse cp
               JOIN comenzi co ON cp.comanda_id = co.id
               JOIN produse p ON cp.produs_id = p.id
               JOIN categorii c ON p.categorie_id = c.id
               WHERE co.data_plasare BETWEEN '$start_date' AND '$end_date 23:59:59'
               AND co.status != 'anulata'
               GROUP BY p.id, p.nume, c.nume
               ORDER BY cantitate_vanduta DESC
               LIMIT $limit";
        break;
        
    case 'inventory':
        $sql = "SELECT p.id, p.nume, c.nume as categorie, p.stoc, 
               p.pret, (p.stoc * p.pret) as valoare_stoc
               FROM produse p
               JOIN categorii c ON p.categorie_id = c.id
               WHERE p.activ = 1
               ORDER BY p.stoc ASC
               LIMIT $limit";
        break;
        
    case 'categories':
        $sql = "SELECT c.nume as categorie, 
               COUNT(DISTINCT cp.produs_id) as numar_produse,
               SUM(cp.cantitate) as cantitate_vanduta, 
               SUM(cp.subtotal) as valoare_vanzari
               FROM comenzi_produse cp
               JOIN comenzi co ON cp.comanda_id = co.id
               JOIN produse p ON cp.produs_id = p.id
               JOIN categorii c ON p.categorie_id = c.id
               WHERE co.data_plasare BETWEEN '$start_date' AND '$end_date 23:59:59'
               AND co.status != 'anulata'
               GROUP BY c.nume
               ORDER BY valoare_vanzari DESC";
        break;
        
    case 'profit':
        // This is a simplified profit calculation
        // In a real scenario, you would need actual cost data
        $sql = "SELECT p.id, p.nume, c.nume as categorie, 
               SUM(cp.cantitate) as cantitate_vanduta, 
               SUM(cp.subtotal) as venituri,
               SUM(cp.subtotal * 0.6) as costuri, 
               SUM(cp.subtotal * 0.4) as profit,
               40 as marja_profit
               FROM comenzi_produse cp
               JOIN comenzi co ON cp.comanda_id = co.id
               JOIN produse p ON cp.produs_id = p.id
               JOIN categorii c ON p.categorie_id = c.id
               WHERE co.data_plasare BETWEEN '$start_date' AND '$end_date 23:59:59'
               AND co.status != 'anulata'
               GROUP BY p.id, p.nume, c.nume
               ORDER BY profit DESC
               LIMIT $limit";
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
        case 'bestsellers':
            $labels[] = $row['nume'];
            $values[] = (int)$row['cantitate_vanduta'];
            $data[] = [
                'id' => (int)$row['id'],
                'name' => $row['nume'],
                'category' => $row['categorie'],
                'quantity_sold' => (int)$row['cantitate_vanduta'],
                'sales_value' => (float)$row['valoare_vanzari']
            ];
            break;
            
        case 'inventory':
            $labels[] = $row['nume'];
            $values[] = (int)$row['stoc'];
            $data[] = [
                'id' => (int)$row['id'],
                'name' => $row['nume'],
                'category' => $row['categorie'],
                'stock' => (int)$row['stoc'],
                'price' => (float)$row['pret'],
                'stock_value' => (float)$row['valoare_stoc']
            ];
            break;
            
        case 'categories':
            $labels[] = $row['categorie'];
            $values[] = (float)$row['valoare_vanzari'];
            $data[] = [
                'category' => $row['categorie'],
                'products_count' => (int)$row['numar_produse'],
                'quantity_sold' => (int)$row['cantitate_vanduta'],
                'sales_value' => (float)$row['valoare_vanzari']
            ];
            break;
            
        case 'profit':
            $labels[] = $row['nume'];
            $values[] = (float)$row['profit'];
            $data[] = [
                'id' => (int)$row['id'],
                'name' => $row['nume'],
                'category' => $row['categorie'],
                'quantity_sold' => (int)$row['cantitate_vanduta'],
                'revenue' => (float)$row['venituri'],
                'costs' => (float)$row['costuri'],
                'profit' => (float)$row['profit'],
                'margin' => (int)$row['marja_profit']
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