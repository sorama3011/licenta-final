<?php
// Include database configuration
require_once 'db-config.php';

// Start session
session_start();

// Initialize response array
$response = [
    'success' => false,
    'message' => '',
    'favorites' => []
];

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Trebuie să fii autentificat pentru a vedea lista de favorite.';
    echo json_encode($response);
    exit;
}

// Get user ID
$user_id = $_SESSION['user_id'];

// Get favorites
$favorites_sql = "SELECT f.produs_id, p.nume, p.pret, p.imagine, p.cantitate as greutate, p.stoc
                 FROM favorite f
                 JOIN produse p ON f.produs_id = p.id
                 WHERE f.user_id = $user_id
                 ORDER BY f.data_adaugare DESC";
$favorites_result = mysqli_query($conn, $favorites_sql);

// Check if query was successful
if (!$favorites_result) {
    $response['message'] = 'A apărut o eroare la încărcarea listei de favorite.';
    echo json_encode($response);
    exit;
}

// Process favorites
$favorites = [];

while ($item = mysqli_fetch_assoc($favorites_result)) {
    $favorites[] = [
        'id' => (int)$item['produs_id'],
        'name' => $item['nume'],
        'price' => (float)$item['pret'],
        'image' => $item['imagine'],
        'weight' => $item['greutate'],
        'in_stock' => (int)$item['stoc'] > 0
    ];
}

// Update response
$response['success'] = true;
$response['favorites'] = $favorites;

// Return response
echo json_encode($response);
exit;
?>