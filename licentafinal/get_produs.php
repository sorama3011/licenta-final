<?php
// Include database configuration
require_once 'db-config.php';

// Start session
session_start();

// Initialize response array
$response = [
    'success' => false,
    'message' => '',
    'product' => null
];

// Check if product ID or slug is provided
if ((!isset($_GET['id']) || empty($_GET['id'])) && (!isset($_GET['slug']) || empty($_GET['slug']))) {
    $response['message'] = 'ID-ul sau slug-ul produsului lipsește.';
    echo json_encode($response);
    exit;
}

// Get product ID or slug
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$product_slug = isset($_GET['slug']) ? sanitize_input($_GET['slug']) : '';

// Build query based on provided parameter
if ($product_id > 0) {
    $product_sql = "SELECT p.*, c.nume as categorie, c.slug as categorie_slug, r.nume as regiune
                   FROM produse p
                   JOIN categorii c ON p.categorie_id = c.id
                   JOIN regiuni r ON p.regiune_id = r.id
                   WHERE p.id = $product_id AND p.activ = 1";
} else {
    $product_sql = "SELECT p.*, c.nume as categorie, c.slug as categorie_slug, r.nume as regiune
                   FROM produse p
                   JOIN categorii c ON p.categorie_id = c.id
                   JOIN regiuni r ON p.regiune_id = r.id
                   WHERE p.slug = '$product_slug' AND p.activ = 1";
}

$product_result = mysqli_query($conn, $product_sql);

// Check if product exists
if (mysqli_num_rows($product_result) == 0) {
    $response['message'] = 'Produsul nu există sau nu este disponibil.';
    echo json_encode($response);
    exit;
}

$product = mysqli_fetch_assoc($product_result);
$product_id = (int)$product['id'];

// Get product tags
$tags_sql = "SELECT e.slug, e.nume
            FROM produse_etichete pe
            JOIN etichete e ON pe.eticheta_id = e.id
            WHERE pe.produs_id = $product_id";
$tags_result = mysqli_query($conn, $tags_sql);

$tags = [];
while ($tag = mysqli_fetch_assoc($tags_result)) {
    $tags[] = [
        'slug' => $tag['slug'],
        'name' => $tag['nume']
    ];
}

// Get nutritional info
$nutritional_sql = "SELECT * FROM produse_nutritionale WHERE produs_id = $product_id";
$nutritional_result = mysqli_query($conn, $nutritional_sql);
$nutritional_info = mysqli_num_rows($nutritional_result) > 0 ? mysqli_fetch_assoc($nutritional_result) : null;

// Get related products
$related_sql = "SELECT p.id, p.nume, p.slug, p.descriere_scurta, p.pret, p.pret_redus, 
               p.stoc, p.cantitate, p.imagine, p.recomandat
               FROM produse_relationate pr
               JOIN produse p ON pr.produs_relationat_id = p.id
               WHERE pr.produs_id = $product_id AND p.activ = 1
               LIMIT 4";
$related_result = mysqli_query($conn, $related_sql);

$related_products = [];
while ($related = mysqli_fetch_assoc($related_result)) {
    $related_products[] = [
        'id' => (int)$related['id'],
        'name' => $related['nume'],
        'slug' => $related['slug'],
        'description' => $related['descriere_scurta'],
        'price' => (float)$related['pret'],
        'sale_price' => $related['pret_redus'] ? (float)$related['pret_redus'] : null,
        'stock' => (int)$related['stoc'],
        'weight' => $related['cantitate'],
        'image' => $related['imagine'],
        'recommended' => (bool)$related['recomandat'],
        'in_stock' => (int)$related['stoc'] > 0
    ];
}

// Check if product is in user's favorites
$is_favorite = false;
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $favorite_sql = "SELECT id FROM favorite WHERE user_id = $user_id AND produs_id = $product_id";
    $favorite_result = mysqli_query($conn, $favorite_sql);
    $is_favorite = mysqli_num_rows($favorite_result) > 0;
}

// Format product data
$product_data = [
    'id' => (int)$product['id'],
    'name' => $product['nume'],
    'slug' => $product['slug'],
    'short_description' => $product['descriere_scurta'],
    'description' => $product['descriere'],
    'price' => (float)$product['pret'],
    'sale_price' => $product['pret_redus'] ? (float)$product['pret_redus'] : null,
    'stock' => (int)$product['stoc'],
    'weight' => $product['cantitate'],
    'image' => $product['imagine'],
    'category' => [
        'name' => $product['categorie'],
        'slug' => $product['categorie_slug']
    ],
    'region' => $product['regiune'],
    'recommended' => (bool)$product['recomandat'],
    'age_restriction' => (bool)$product['restrictie_varsta'],
    'expiration_date' => $product['data_expirare'],
    'tags' => $tags,
    'nutritional_info' => $nutritional_info,
    'related_products' => $related_products,
    'in_stock' => (int)$product['stoc'] > 0,
    'is_favorite' => $is_favorite
];

// Update response
$response['success'] = true;
$response['product'] = $product_data;

// Return response
echo json_encode($response);
exit;
?>