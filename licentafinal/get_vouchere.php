<?php
// Include database configuration
require_once 'db-config.php';

// Start session
session_start();

// Initialize response array
$response = [
    'success' => false,
    'message' => '',
    'vouchers' => []
];

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Trebuie să fii autentificat pentru a vedea voucherele.';
    echo json_encode($response);
    exit;
}

// Get user ID
$user_id = $_SESSION['user_id'];

// Get vouchers assigned to user
$vouchers_sql = "SELECT v.id, v.cod, v.tip, v.valoare, v.minim_comanda, v.data_sfarsit,
                vu.utilizat, vu.data_utilizare
                FROM vouchere v
                JOIN vouchere_utilizatori vu ON v.id = vu.voucher_id
                WHERE vu.user_id = $user_id
                AND v.activ = 1
                AND v.data_inceput <= CURDATE()
                AND v.data_sfarsit >= CURDATE()
                AND vu.utilizat = 0
                ORDER BY v.data_sfarsit ASC";
$vouchers_result = mysqli_query($conn, $vouchers_sql);

// Check if query was successful
if (!$vouchers_result) {
    $response['message'] = 'A apărut o eroare la încărcarea voucherelor.';
    echo json_encode($response);
    exit;
}

// Process vouchers
$vouchers = [];

while ($voucher = mysqli_fetch_assoc($vouchers_result)) {
    $vouchers[] = [
        'id' => (int)$voucher['id'],
        'code' => $voucher['cod'],
        'type' => $voucher['tip'],
        'value' => (float)$voucher['valoare'],
        'min_order' => (float)$voucher['minim_comanda'],
        'expiration_date' => date('d.m.Y', strtotime($voucher['data_sfarsit'])),
        'used' => (bool)$voucher['utilizat'],
        'usage_date' => $voucher['data_utilizare'] ? date('d.m.Y', strtotime($voucher['data_utilizare'])) : null
    ];
}

// Update response
$response['success'] = true;
$response['vouchers'] = $vouchers;

// Return response
echo json_encode($response);
exit;
?>