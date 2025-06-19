<?php
// Include database configuration
require_once 'db-config.php';

// Start session
session_start();

// Initialize response array
$response = [
    'success' => false,
    'message' => '',
    'order' => null
];

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Trebuie să fii autentificat pentru a vedea detaliile comenzii.';
    echo json_encode($response);
    exit;
}

// Check if order ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $response['message'] = 'ID-ul comenzii lipsește.';
    echo json_encode($response);
    exit;
}

// Get user ID, role and order ID
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];
$order_id = (int)$_GET['id'];

// Prepare SQL based on user role
if ($user_role == 'client') {
    // Clients can only see their own orders
    $order_sql = "SELECT c.id, c.numar_comanda, c.status, c.subtotal, c.transport, c.discount, c.total, 
                 c.metoda_plata, c.status_plata, c.observatii, c.puncte_folosite, c.puncte_castigate,
                 c.data_plasare, c.data_procesare, c.data_livrare,
                 al.adresa as adresa_livrare, al.oras as oras_livrare, al.judet as judet_livrare, 
                 al.cod_postal as cod_postal_livrare, al.telefon as telefon_livrare,
                 af.adresa as adresa_facturare, af.oras as oras_facturare, af.judet as judet_facturare, 
                 af.cod_postal as cod_postal_facturare, af.telefon as telefon_facturare,
                 v.cod as voucher_cod, v.tip as voucher_tip, v.valoare as voucher_valoare
                 FROM comenzi c
                 LEFT JOIN adrese al ON c.adresa_livrare_id = al.id
                 LEFT JOIN adrese af ON c.adresa_facturare_id = af.id
                 LEFT JOIN vouchere v ON c.voucher_id = v.id
                 WHERE c.id = $order_id AND c.user_id = $user_id";
} else {
    // Admins and employees can see all orders
    $order_sql = "SELECT c.id, c.numar_comanda, c.status, c.subtotal, c.transport, c.discount, c.total, 
                 c.metoda_plata, c.status_plata, c.observatii, c.puncte_folosite, c.puncte_castigate,
                 c.data_plasare, c.data_procesare, c.data_livrare,
                 al.adresa as adresa_livrare, al.oras as oras_livrare, al.judet as judet_livrare, 
                 al.cod_postal as cod_postal_livrare, al.telefon as telefon_livrare,
                 af.adresa as adresa_facturare, af.oras as oras_facturare, af.judet as judet_facturare, 
                 af.cod_postal as cod_postal_facturare, af.telefon as telefon_facturare,
                 v.cod as voucher_cod, v.tip as voucher_tip, v.valoare as voucher_valoare,
                 CONCAT(u.prenume, ' ', u.nume) as client_name, u.email as client_email, u.telefon as client_telefon
                 FROM comenzi c
                 LEFT JOIN adrese al ON c.adresa_livrare_id = al.id
                 LEFT JOIN adrese af ON c.adresa_facturare_id = af.id
                 LEFT JOIN vouchere v ON c.voucher_id = v.id
                 JOIN utilizatori u ON c.user_id = u.id
                 WHERE c.id = $order_id";
}

$order_result = mysqli_query($conn, $order_sql);

// Check if query was successful and order exists
if (!$order_result || mysqli_num_rows($order_result) == 0) {
    $response['message'] = 'Comanda nu există sau nu aveți permisiunea de a o vedea.';
    echo json_encode($response);
    exit;
}

$order = mysqli_fetch_assoc($order_result);

// Get order items
$items_sql = "SELECT cp.produs_id, cp.nume_produs, cp.pret, cp.cantitate, cp.subtotal
             FROM comenzi_produse cp
             WHERE cp.comanda_id = $order_id";
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

// Format order data
$order_data = [
    'id' => (int)$order['id'],
    'order_number' => $order['numar_comanda'],
    'status' => $order['status'],
    'subtotal' => (float)$order['subtotal'],
    'shipping' => (float)$order['transport'],
    'discount' => (float)$order['discount'],
    'total' => (float)$order['total'],
    'payment_method' => $order['metoda_plata'],
    'payment_status' => $order['status_plata'],
    'notes' => $order['observatii'],
    'points_used' => (int)$order['puncte_folosite'],
    'points_earned' => (int)$order['puncte_castigate'],
    'date_placed' => date('d.m.Y H:i', strtotime($order['data_plasare'])),
    'date_processed' => $order['data_procesare'] ? date('d.m.Y H:i', strtotime($order['data_procesare'])) : null,
    'date_delivered' => $order['data_livrare'] ? date('d.m.Y H:i', strtotime($order['data_livrare'])) : null,
    'shipping_address' => [
        'address' => $order['adresa_livrare'],
        'city' => $order['oras_livrare'],
        'county' => $order['judet_livrare'],
        'postal_code' => $order['cod_postal_livrare'],
        'phone' => $order['telefon_livrare']
    ],
    'billing_address' => [
        'address' => $order['adresa_facturare'],
        'city' => $order['oras_facturare'],
        'county' => $order['judet_facturare'],
        'postal_code' => $order['cod_postal_facturare'],
        'phone' => $order['telefon_facturare']
    ],
    'voucher' => $order['voucher_cod'] ? [
        'code' => $order['voucher_cod'],
        'type' => $order['voucher_tip'],
        'value' => (float)$order['voucher_valoare']
    ] : null,
    'items' => $items,
    'items_count' => count($items)
];

// Add client info for admin/employee
if ($user_role != 'client') {
    $order_data['client'] = [
        'name' => $order['client_name'],
        'email' => $order['client_email'],
        'phone' => $order['client_telefon']
    ];
}

// Update response
$response['success'] = true;
$response['order'] = $order_data;

// Return response
echo json_encode($response);
exit;
?>