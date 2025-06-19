<?php
// Include database configuration
require_once 'db-config.php';

// Initialize response array
$response = [
    'success' => false,
    'message' => '',
    'offers' => []
];

// Get active offers
$offers_sql = "SELECT p.id, p.nume, p.slug, p.descriere_scurta, p.pret, p.pret_redus, 
              p.stoc, p.cantitate, p.imagine, c.nume as categorie
              FROM produse p
              JOIN categorii c ON p.categorie_id = c.id
              WHERE p.activ = 1 AND p.pret_redus IS NOT NULL
              ORDER BY (p.pret - p.pret_redus) / p.pret DESC
              LIMIT 12";
$offers_result = mysqli_query($conn, $offers_sql);

// Check if query was successful
if (!$offers_result) {
    $response['message'] = 'A apărut o eroare la încărcarea ofertelor.';
    echo json_encode($response);
    exit;
}

// Process offers
$offers = [];

while ($offer = mysqli_fetch_assoc($offers_result)) {
    $discount_percent = round(((float)$offer['pret'] - (float)$offer['pret_redus']) / (float)$offer['pret'] * 100);
    
    $offers[] = [
        'id' => (int)$offer['id'],
        'name' => $offer['nume'],
        'slug' => $offer['slug'],
        'description' => $offer['descriere_scurta'],
        'regular_price' => (float)$offer['pret'],
        'sale_price' => (float)$offer['pret_redus'],
        'discount_percent' => $discount_percent,
        'stock' => (int)$offer['stoc'],
        'weight' => $offer['cantitate'],
        'image' => $offer['imagine'],
        'category' => $offer['categorie'],
        'in_stock' => (int)$offer['stoc'] > 0
    ];
}

// Update response
$response['success'] = true;
$response['offers'] = $offers;

// Return response
echo json_encode($response);
exit;
?>