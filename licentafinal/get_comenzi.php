<?php
// Include database configuration
require_once 'db-config.php';

// Start session
session_start();

// Initialize response array
$response = [
    'success' => false,
    'message' => '',
    'orders' => []
];

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Trebuie să fii autentificat pentru a vedea comenzile.';
    echo json_encode($response);
    exit;
}

// Get user ID and role
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];

// Prepare SQL based on user role
if ($user_role == 'client') {
    // Clients can only see their own orders
    $orders_sql = "SELECT c.id, c.numar_comanda, c.status, c.total, c.metoda_plata, c.status_plata, 
                  c.data_plasare, c.data_procesare, c.data_livrare
                  FROM comenzi c
                  WHERE c.user_id = $user_id
                  ORDER BY c.data_plasare DESC";
} else {
    // Admins and employees can see all orders
    $orders_sql = "SELECT c.id, c.numar_comanda, c.status, c.total, c.metoda_plata, c.status_plata, 
                  c.data_plasare, c.data_procesare, c.data_livrare, 
                  CONCAT(u.prenume, ' ', u.nume) as client_name, u.email as client_email
                  FROM comenzi c
                  JOIN utilizatori u ON c.user_id = u.id
                  ORDER BY c.data_plasare DESC";
}

$orders_result = mysqli_query($conn, $orders_sql);

// Check if query was successful
if (!$orders_result) {
    $response['message'] = 'A apărut o eroare la încărcarea comenzilor.';
    echo json_encode($response);
    exit;
}

// Process orders
$orders = [];

while ($order = mysqli_fetch_assoc($orders_result)) {
    $order_data = [
        'id' => (int)$order['id'],
        'order_number' => $order['numar_comanda'],
        'status' => $order['status'],
        'total' => (float)$order['total'],
        'payment_method' => $order['metoda_plata'],
        'payment_status' => $order['status_plata'],
        'date_placed' => date('d.m.Y H:i', strtotime($order['data_plasare'])),
        'date_processed' => $order['data_procesare'] ? date('d.m.Y H:i', strtotime($order['data_procesare'])) : null,
        'date_delivered' => $order['data_livrare'] ? date('d.m.Y H:i', strtotime($order['data_livrare'])) : null
    ];
    
    // Add client info for admin/employee
    if ($user_role != 'client') {
        $order_data['client_name'] = $order['client_name'];
        $order_data['client_email'] = $order['client_email'];
    }
    
    // Get order items
    $items_sql = "SELECT cp.produs_id, cp.nume_produs, cp.pret, cp.cantitate, cp.subtotal
                 FROM comenzi_produse cp
                 WHERE cp.comanda_id = {$order['id']}";
    $items_result = mysqli_query($conn, $items_sql);
    
    $items = [];
    while ($item = mysqli_fetch_assoc($items_result)) {
        $items[] = [
            'product_id' => (int)$item['produs_id'],
            'name' => $item['nume_produs'],
            'price' => (float)$item['pret'],
            'quantity' => (int)$item['cantitate'],
            'subtotal' => (float)$item['subtotal']
        ];
    }
    
    $order_data['items'] = $items;
    $order_data['items_count'] = count($items);
    
    $orders[] = $order_data;
}

// Update response
$response['success'] = true;
$response['orders'] = $orders;

// Return response
echo json_encode($response);
exit;
?>