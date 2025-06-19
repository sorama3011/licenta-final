<?php
// Include database configuration
require_once 'db-config.php';

// Initialize response array
$response = [
    'success' => true,
    'active' => true,
    'end_time' => null,
    'message' => 'Reducerea expiră în:',
    'hours' => 0,
    'minutes' => 0,
    'seconds' => 0
];

// In a real application, this would be fetched from a database
// For now, we'll use a fixed end time 2 hours from now
$end_time = time() + (2 * 60 * 60); // 2 hours from now

// Calculate remaining time
$remaining = $end_time - time();

if ($remaining <= 0) {
    $response['active'] = false;
    $response['message'] = 'Oferta a expirat 😢';
} else {
    $response['end_time'] = date('Y-m-d H:i:s', $end_time);
    $response['hours'] = floor($remaining / 3600);
    $response['minutes'] = floor(($remaining % 3600) / 60);
    $response['seconds'] = $remaining % 60;
}

// Return response
echo json_encode($response);
exit;
?>