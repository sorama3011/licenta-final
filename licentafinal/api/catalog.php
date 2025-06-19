<?php
require_once '../db-config.php';
session_start();

header('Content-Type: application/json');

// Check database connection
if (!$conn) {
    error_log('Database connection failed: ' . mysqli_connect_error());
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . mysqli_connect_error()]);
    exit;
}

// Test database connection
if (!mysqli_ping($conn)) {
    error_log('Database connection lost');
    echo json_encode(['success' => false, 'message' => 'Database connection lost']);
    exit;
}

$type = $_GET['type'] ?? '';
$user_id = $_SESSION['user_id'] ?? null;

switch ($type) {
    case 'products':
        $page = (int)($_GET['page'] ?? 1);
        $limit = (int)($_GET['limit'] ?? 20);
        $offset = ($page - 1) * $limit;

        $category = $_GET['category'] ?? '';
        $region = $_GET['region'] ?? '';
        $search = $_GET['search'] ?? '';
        $sort = $_GET['sort'] ?? 'nume';

        $where_conditions = ["activ = 1"];

        if (!empty($category)) {
            $category = sanitize_input($category);
            $where_conditions[] = "categorie = '$category'";
        }

        if (!empty($region)) {
            $region = sanitize_input($region);
            $where_conditions[] = "regiune = '$region'";
        }

        if (!empty($search)) {
            $search = sanitize_input($search);
            $where_conditions[] = "(nume LIKE '%$search%' OR descriere LIKE '%$search%')";
        }

        $where_clause = implode(' AND ', $where_conditions);

        // Validate sort parameter
        $allowed_sorts = ['nume', 'pret', 'data_adaugarii'];
        if (!in_array($sort, $allowed_sorts)) {
            $sort = 'nume';
        }

        $sql = "SELECT * FROM produse WHERE $where_clause ORDER BY $sort LIMIT $limit OFFSET $offset";
        error_log("Executing SQL: " . $sql);
        $result = mysqli_query($conn, $sql);

        if (!$result) {
            error_log('Database query failed: ' . mysqli_error($conn));
            echo json_encode(['success' => false, 'message' => 'Database query failed: ' . mysqli_error($conn)]);
            exit;
        }

        $row_count = mysqli_num_rows($result);
        error_log("Query returned $row_count rows");

        $products = [];
        while ($row = mysqli_fetch_assoc($result)) {
            // Get product tags
            $tags_sql = "SELECT tag FROM produse_taguri WHERE produs_id = {$row['id']}";
            $tags_result = mysqli_query($conn, $tags_sql);
            $tags = [];
            while ($tag = mysqli_fetch_assoc($tags_result)) {
                $tags[] = $tag['tag'];
            }
            $row['tags'] = $tags;

            $products[] = $row;
        }

        // Get total count
        $count_sql = "SELECT COUNT(*) as total FROM produse WHERE $where_clause";
        $count_result = mysqli_query($conn, $count_sql);
        $total = mysqli_fetch_assoc($count_result)['total'];

        echo json_encode([
            'success' => true,
            'products' => $products,
            'total' => $total,
            'page' => $page,
            'pages' => ceil($total / $limit)
        ]);
        break;

    case 'product':
        $product_id = (int)$_GET['id'];

        if (!$product_id) {
            error_log('Invalid product ID provided: ' . $_GET['id']);
            echo json_encode(['success' => false, 'message' => 'ID produs invalid']);
            exit;
        }

        error_log('Fetching product with ID: ' . $product_id);

        $sql = "SELECT p.id, p.nume, p.descriere, p.descriere_lunga, p.ingrediente, p.pret, p.imagine, 
                       p.categorie, p.regiune, p.cantitate, p.stoc, p.producator, p.recomandat, 
                       p.restrictie_varsta, p.data_adaugarii, p.activ,
                       pn.valoare_energetica, pn.grasimi, pn.grasimi_saturate, pn.glucide, 
                       pn.zaharuri, pn.fibre, pn.proteine, pn.sare, pn.ingrediente as ingrediente_nutritionale
                FROM produse p
                LEFT JOIN produse_nutritionale pn ON p.id = pn.produs_id
                WHERE p.id = $product_id AND p.activ = 1";
        
        error_log('Executing product query: ' . $sql);
        $result = mysqli_query($conn, $sql);

        if (!$result) {
            error_log('Product query failed: ' . mysqli_error($conn));
            echo json_encode(['success' => false, 'message' => 'Eroare baza de date: ' . mysqli_error($conn)]);
            exit;
        }

        if (mysqli_num_rows($result) == 0) {
            error_log('No product found with ID: ' . $product_id);
            echo json_encode(['success' => false, 'message' => 'Produsul nu a fost găsit sau nu este activ']);
            exit;
        }

        $product = mysqli_fetch_assoc($result);

        // Ensure all fields have default values if missing
        $product['descriere'] = $product['descriere'] ?? '';
        $product['descriere_lunga'] = $product['descriere_lunga'] ?? $product['descriere'];
        $product['ingrediente'] = $product['ingrediente'] ?? 'Informații indisponibile';
        $product['cantitate'] = $product['cantitate'] ?? '';
        $product['producator'] = $product['producator'] ?? '';
        
        // Ensure nutritional fields have default values
        $product['valoare_energetica'] = $product['valoare_energetica'] ?? '';
        $product['grasimi'] = $product['grasimi'] ?? '';
        $product['grasimi_saturate'] = $product['grasimi_saturate'] ?? '';
        $product['glucide'] = $product['glucide'] ?? '';
        $product['zaharuri'] = $product['zaharuri'] ?? '';
        $product['fibre'] = $product['fibre'] ?? '';
        $product['proteine'] = $product['proteine'] ?? '';
        $product['sare'] = $product['sare'] ?? '';
        $product['ingrediente_nutritionale'] = $product['ingrediente_nutritionale'] ?? $product['ingrediente'];

        // Get product tags
        $tags_sql = "SELECT tag FROM produse_taguri WHERE produs_id = $product_id";
        $tags_result = mysqli_query($conn, $tags_sql);
        $tags = [];
        while ($tag = mysqli_fetch_assoc($tags_result)) {
            $tags[] = $tag['tag'];
        }
        $product['tags'] = $tags;

        // Get related products (same category, different products)
        $related_sql = "SELECT id, nume, descriere, pret, imagine, categorie, regiune, cantitate 
                        FROM produse WHERE categorie = '{$product['categorie']}' AND id != $product_id AND activ = 1 LIMIT 4";
        $related_result = mysqli_query($conn, $related_sql);
        $related_products = [];
        while ($related = mysqli_fetch_assoc($related_result)) {
            $related_products[] = $related;
        }
        $product['related_products'] = $related_products;

        echo json_encode(['success' => true, 'product' => $product]);
        break;

    case 'categories':
        $sql = "SELECT DISTINCT categorie as name, COUNT(*) as count 
                FROM produse WHERE activ = 1 
                GROUP BY categorie 
                ORDER BY categorie";
        $result = mysqli_query($conn, $sql);

        $categories = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $categories[] = $row;
        }

        echo json_encode(['success' => true, 'categories' => $categories]);
        break;

    case 'regions':
        $sql = "SELECT DISTINCT regiune as name, COUNT(*) as count 
                FROM produse WHERE activ = 1 
                GROUP BY regiune 
                ORDER BY regiune";
        $result = mysqli_query($conn, $sql);

        $regions = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $regions[] = $row;
        }

        echo json_encode(['success' => true, 'regions' => $regions]);
        break;

    case 'featured':
        // First try to get products marked as recommended
        $sql = "SELECT * FROM produse WHERE activ = 1 AND recomandat = 1 ORDER BY RAND() LIMIT 8";
        $result = mysqli_query($conn, $sql);

        $featured = [];
        while ($row = mysqli_fetch_assoc($result)) {
            // Get product tags
            $tags_sql = "SELECT tag FROM produse_taguri WHERE produs_id = {$row['id']}";
            $tags_result = mysqli_query($conn, $tags_sql);
            $tags = [];
            while ($tag = mysqli_fetch_assoc($tags_result)) {
                $tags[] = $tag['tag'];
            }
            $row['tags'] = $tags;

            $featured[] = $row;
        }

        // If no featured products found, get random active products
        if (empty($featured)) {
            $sql = "SELECT * FROM produse WHERE activ = 1 ORDER BY RAND() LIMIT 4";
            $result = mysqli_query($conn, $sql);
            
            while ($row = mysqli_fetch_assoc($result)) {
                // Get product tags
                $tags_sql = "SELECT tag FROM produse_taguri WHERE produs_id = {$row['id']}";
                $tags_result = mysqli_query($conn, $tags_sql);
                $tags = [];
                while ($tag = mysqli_fetch_assoc($tags_result)) {
                    $tags[] = $tag['tag'];
                }
                $row['tags'] = $tags;

                $featured[] = $row;
            }
        }

        echo json_encode(['success' => true, 'products' => $featured]);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Tip necunoscut']);
        break;
}
?>