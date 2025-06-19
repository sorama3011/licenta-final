<?php
// Include database configuration
require_once 'db-config.php';

// Initialize response array
$response = [
    'success' => false,
    'message' => '',
    'products' => [],
    'total' => 0,
    'page' => 1,
    'total_pages' => 1
];

// Get filter parameters
$category = isset($_GET['category']) ? sanitize_input($_GET['category']) : '';
$region = isset($_GET['region']) ? sanitize_input($_GET['region']) : '';
$tag = isset($_GET['tag']) ? sanitize_input($_GET['tag']) : '';
$search = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';
$sort = isset($_GET['sort']) ? sanitize_input($_GET['sort']) : 'recommended';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 12;

// Validate page and limit
if ($page < 1) $page = 1;
if ($limit < 1) $limit = 12;
if ($limit > 50) $limit = 50;

// Calculate offset
$offset = ($page - 1) * $limit;

// Build base query
$base_sql = "SELECT p.id, p.nume, p.slug, p.descriere_scurta, p.pret, p.pret_redus, 
            p.stoc, p.cantitate, p.imagine, p.recomandat, p.restrictie_varsta,
            c.nume as categorie, c.slug as categorie_slug,
            r.nume as regiune
            FROM produse p
            JOIN categorii c ON p.categorie_id = c.id
            JOIN regiuni r ON p.regiune_id = r.id
            WHERE p.activ = 1";

// Add filters
$params = [];

if (!empty($category)) {
    $base_sql .= " AND c.slug = ?";
    $params[] = $category;
}

if (!empty($region)) {
    $base_sql .= " AND r.nume = ?";
    $params[] = $region;
}

if (!empty($tag)) {
    $base_sql .= " AND p.id IN (
                    SELECT pe.produs_id 
                    FROM produse_etichete pe 
                    JOIN etichete e ON pe.eticheta_id = e.id 
                    WHERE e.slug = ?
                  )";
    $params[] = $tag;
}

if (!empty($search)) {
    $base_sql .= " AND (p.nume LIKE ? OR p.descriere_scurta LIKE ? OR p.descriere LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

// Count total products
$count_sql = str_replace("SELECT p.id, p.nume, p.slug, p.descriere_scurta, p.pret, p.pret_redus, 
            p.stoc, p.cantitate, p.imagine, p.recomandat, p.restrictie_varsta,
            c.nume as categorie, c.slug as categorie_slug,
            r.nume as regiune", "SELECT COUNT(*) as total", $base_sql);

$stmt = mysqli_prepare($conn, $count_sql);

if ($stmt) {
    if (!empty($params)) {
        $types = str_repeat('s', count($params));
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    
    mysqli_stmt_execute($stmt);
    $count_result = mysqli_stmt_get_result($stmt);
    $count_row = mysqli_fetch_assoc($count_result);
    $total = (int)$count_row['total'];
    
    mysqli_stmt_close($stmt);
} else {
    $response['message'] = 'A apărut o eroare la numărarea produselor.';
    echo json_encode($response);
    exit;
}

// Calculate total pages
$total_pages = ceil($total / $limit);

// Add sorting
switch ($sort) {
    case 'price-asc':
        $base_sql .= " ORDER BY COALESCE(p.pret_redus, p.pret) ASC";
        break;
    case 'price-desc':
        $base_sql .= " ORDER BY COALESCE(p.pret_redus, p.pret) DESC";
        break;
    case 'name-asc':
        $base_sql .= " ORDER BY p.nume ASC";
        break;
    case 'name-desc':
        $base_sql .= " ORDER BY p.nume DESC";
        break;
    case 'newest':
        $base_sql .= " ORDER BY p.data_adaugare DESC";
        break;
    case 'recommended':
    default:
        $base_sql .= " ORDER BY p.recomandat DESC, p.data_adaugare DESC";
        break;
}

// Add pagination
$base_sql .= " LIMIT $offset, $limit";

// Prepare and execute query
$stmt = mysqli_prepare($conn, $base_sql);

if ($stmt) {
    if (!empty($params)) {
        $types = str_repeat('s', count($params));
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    // Process products
    $products = [];
    
    while ($product = mysqli_fetch_assoc($result)) {
        // Get product tags
        $tags_sql = "SELECT e.slug, e.nume
                    FROM produse_etichete pe
                    JOIN etichete e ON pe.eticheta_id = e.id
                    WHERE pe.produs_id = {$product['id']}";
        $tags_result = mysqli_query($conn, $tags_sql);
        
        $tags = [];
        while ($tag = mysqli_fetch_assoc($tags_result)) {
            $tags[] = [
                'slug' => $tag['slug'],
                'name' => $tag['nume']
            ];
        }
        
        // Format product data
        $products[] = [
            'id' => (int)$product['id'],
            'name' => $product['nume'],
            'slug' => $product['slug'],
            'description' => $product['descriere_scurta'],
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
            'tags' => $tags,
            'in_stock' => (int)$product['stoc'] > 0
        ];
    }
    
    mysqli_stmt_close($stmt);
    
    // Update response
    $response['success'] = true;
    $response['products'] = $products;
    $response['total'] = $total;
    $response['page'] = $page;
    $response['total_pages'] = $total_pages;
    
} else {
    $response['message'] = 'A apărut o eroare la încărcarea produselor.';
}

// Return response
echo json_encode($response);
exit;
?>