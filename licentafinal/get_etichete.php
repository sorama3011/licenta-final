<?php
// Include database configuration
require_once 'db-config.php';

// Initialize response array
$response = [
    'success' => false,
    'message' => '',
    'tags' => []
];

// Get active tags
$tags_sql = "SELECT id, nume, slug, descriere FROM etichete WHERE activ = 1 ORDER BY nume";
$tags_result = mysqli_query($conn, $tags_sql);

// Check if query was successful
if (!$tags_result) {
    $response['message'] = 'A apărut o eroare la încărcarea etichetelor.';
    echo json_encode($response);
    exit;
}

// Process tags
$tags = [];

while ($tag = mysqli_fetch_assoc($tags_result)) {
    // Count products with this tag
    $count_sql = "SELECT COUNT(*) as count FROM produse_etichete WHERE eticheta_id = {$tag['id']}";
    $count_result = mysqli_query($conn, $count_sql);
    $count_row = mysqli_fetch_assoc($count_result);
    
    $tags[] = [
        'id' => (int)$tag['id'],
        'name' => $tag['nume'],
        'slug' => $tag['slug'],
        'description' => $tag['descriere'],
        'products_count' => (int)$count_row['count']
    ];
}

// Update response
$response['success'] = true;
$response['tags'] = $tags;

// Return response
echo json_encode($response);
exit;
?>