<?php
// Include database configuration
require_once 'db-config.php';

// Start session
session_start();

// Initialize response array
$response = [
    'success' => false,
    'message' => '',
    'points' => 0,
    'transactions' => []
];

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Trebuie să fii autentificat pentru a vedea punctele de fidelitate.';
    echo json_encode($response);
    exit;
}

// Get user ID
$user_id = $_SESSION['user_id'];

// Get user's loyalty points
$points_sql = "SELECT puncte FROM puncte_fidelitate WHERE user_id = $user_id";
$points_result = mysqli_query($conn, $points_sql);

if (mysqli_num_rows($points_result) > 0) {
    $points_row = mysqli_fetch_assoc($points_result);
    $response['points'] = (int)$points_row['puncte'];
} else {
    // Create loyalty points record if it doesn't exist
    $insert_sql = "INSERT INTO puncte_fidelitate (user_id, puncte) VALUES ($user_id, 0)";
    mysqli_query($conn, $insert_sql);
    $response['points'] = 0;
}

// Get points transactions
$transactions_sql = "SELECT tp.id, tp.puncte, tp.tip, tp.comanda_id, tp.descriere, tp.data_tranzactie,
                    c.numar_comanda
                    FROM tranzactii_puncte tp
                    LEFT JOIN comenzi c ON tp.comanda_id = c.id
                    WHERE tp.user_id = $user_id
                    ORDER BY tp.data_tranzactie DESC
                    LIMIT 10";
$transactions_result = mysqli_query($conn, $transactions_sql);

// Process transactions
$transactions = [];

while ($transaction = mysqli_fetch_assoc($transactions_result)) {
    $transactions[] = [
        'id' => (int)$transaction['id'],
        'points' => (int)$transaction['puncte'],
        'type' => $transaction['tip'],
        'order_id' => $transaction['comanda_id'] ? (int)$transaction['comanda_id'] : null,
        'order_number' => $transaction['numar_comanda'],
        'description' => $transaction['descriere'],
        'date' => date('d.m.Y H:i', strtotime($transaction['data_tranzactie']))
    ];
}

// Update response
$response['success'] = true;
$response['transactions'] = $transactions;

// Return response
echo json_encode($response);
exit;
?>