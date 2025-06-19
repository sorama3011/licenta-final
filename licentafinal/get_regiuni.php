<?php
// Include database configuration
require_once 'db-config.php';

// Initialize response array
$response = [
    'success' => false,
    'message' => '',
    'regions' => []
];

// Get active regions
$regions_sql = "SELECT id, nume, descriere, imagine FROM regiuni WHERE activ = 1 ORDER BY nume";
$regions_result = mysqli_query($conn, $regions_sql);

// Check if query was successful
if (!$regions_result) {
    $response['message'] = 'A apărut o eroare la încărcarea regiunilor.';
    echo json_encode($response);
    exit;
}

// Process regions
$regions = [];

while ($region = mysqli_fetch_assoc($regions_result)) {
    // Count products in region
    $count_sql = "SELECT COUNT(*) as count FROM produse WHERE regiune_id = {$region['id']} AND activ = 1";
    $count_result = mysqli_query($conn, $count_sql);
    $count_row = mysqli_fetch_assoc($count_result);
    
    $regions[] = [
        'id' => (int)$region['id'],
        'name' => $region['nume'],
        'description' => $region['descriere'],
        'image' => $region['imagine'],
        'products_count' => (int)$count_row['count']
    ];
}

// Update response
$response['success'] = true;
$response['regions'] = $regions;

// Return response
echo json_encode($response);
exit;
?>