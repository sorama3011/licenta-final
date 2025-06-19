<?php
// Include database configuration
require_once 'db-config.php';

// Start session
session_start();

// Initialize response array
$response = [
    'success' => false,
    'message' => '',
    'cart' => [],
    'cart_count' => 0,
    'cart_total' => 0,
    'shipping' => 15.00,
    'free_shipping_threshold' => 150.00
];

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Trebuie să fii autentificat pentru a vedea coșul.';
    echo json_encode($response);
    exit;
}

// Get user ID
$user_id = $_SESSION['user_id'];

// Get cart items
$cart_sql = "SELECT c.produs_id, c.cantitate, p.nume, p.pret, p.imagine, p.cantitate as greutate
            FROM cos_cumparaturi c
            JOIN produse p ON c.produs_id = p.id
            WHERE c.user_id = $user_id";
$cart_result = mysqli_query($conn, $cart_sql);

// Check if query was successful
if (!$cart_result) {
    $response['message'] = 'A apărut o eroare la încărcarea coșului.';
    echo json_encode($response);
    exit;
}

// Process cart items
$cart_items = [];
$cart_count = 0;
$cart_total = 0;

while ($item = mysqli_fetch_assoc($cart_result)) {
    $subtotal = $item['pret'] * $item['cantitate'];
    $cart_total += $subtotal;
    $cart_count += $item['cantitate'];
    
    $cart_items[] = [
        'id' => (int)$item['produs_id'],
        'name' => $item['nume'],
        'price' => (float)$item['pret'],
        'image' => $item['imagine'],
        'weight' => $item['greutate'],
        'quantity' => (int)$item['cantitate'],
        'subtotal' => $subtotal
    ];
}

// Calculate shipping
$shipping = $cart_total >= $response['free_shipping_threshold'] ? 0 : $response['shipping'];

// Update response
$response['success'] = true;
$response['cart'] = $cart_items;
$response['cart_count'] = $cart_count;
$response['cart_total'] = $cart_total;
$response['shipping'] = $shipping;
$response['total'] = $cart_total + $shipping;

// Return response
echo json_encode($response);
exit;
?>