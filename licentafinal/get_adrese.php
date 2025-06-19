<?php
// Include database configuration
require_once 'db-config.php';

// Start session
session_start();

// Initialize response array
$response = [
    'success' => false,
    'message' => '',
    'addresses' => []
];

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Trebuie să fii autentificat pentru a vedea adresele.';
    echo json_encode($response);
    exit;
}

// Get user ID
$user_id = $_SESSION['user_id'];

// Get user's addresses
$addresses_sql = "SELECT id, nume_adresa, adresa, oras, judet, cod_postal, telefon, implicit
                 FROM adrese
                 WHERE user_id = $user_id
                 ORDER BY implicit DESC, data_adaugare DESC";
$addresses_result = mysqli_query($conn, $addresses_sql);

// Check if query was successful
if (!$addresses_result) {
    $response['message'] = 'A apărut o eroare la încărcarea adreselor.';
    echo json_encode($response);
    exit;
}

// Process addresses
$addresses = [];

while ($address = mysqli_fetch_assoc($addresses_result)) {
    $addresses[] = [
        'id' => (int)$address['id'],
        'name' => $address['nume_adresa'],
        'address' => $address['adresa'],
        'city' => $address['oras'],
        'county' => $address['judet'],
        'postal_code' => $address['cod_postal'],
        'phone' => $address['telefon'],
        'default' => (bool)$address['implicit']
    ];
}

// Update response
$response['success'] = true;
$response['addresses'] = $addresses;

// Return response
echo json_encode($response);
exit;
?>