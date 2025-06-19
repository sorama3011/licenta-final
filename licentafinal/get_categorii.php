<?php
// Include database configuration
require_once 'db-config.php';

// Initialize response array
$response = [
    'success' => false,
    'message' => '',
    'categories' => []
];

// Get active categories
$categories_sql = "SELECT id, nume, slug, descriere, imagine FROM categorii WHERE activ = 1 ORDER BY nume";
$categories_result = mysqli_query($conn, $categories_sql);

// Check if query was successful
if (!$categories_result) {
    $response['message'] = 'A apărut o eroare la încărcarea categoriilor.';
    echo json_encode($response);
    exit;
}

// Process categories
$categories = [];

while ($category = mysqli_fetch_assoc($categories_result)) {
    // Count products in category
    $count_sql = "SELECT COUNT(*) as count FROM produse WHERE categorie_id = {$category['id']} AND activ = 1";
    $count_result = mysqli_query($conn, $count_sql);
    $count_row = mysqli_fetch_assoc($count_result);
    
    $categories[] = [
        'id' => (int)$category['id'],
        'name' => $category['nume'],
        'slug' => $category['slug'],
        'description' => $category['descriere'],
        'image' => $category['imagine'],
        'products_count' => (int)$count_row['count']
    ];
}

// Update response
$response['success'] = true;
$response['categories'] = $categories;

// Return response
echo json_encode($response);
exit;
?>