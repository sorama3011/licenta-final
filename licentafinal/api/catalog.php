<?php
require_once '../db-config.php';
session_start();

header('Content-Type: application/json');

// Check database connection
if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . mysqli_connect_error()]);
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
        $result = mysqli_query($conn, $sql);

        if (!$result) {
            echo json_encode(['success' => false, 'message' => 'Database query failed: ' . mysqli_error($conn)]);
            exit;
        }

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
            echo json_encode(['success' => false, 'message' => 'ID produs invalid']);
            exit;
        }

        $sql = "SELECT * FROM produse WHERE id = $product_id AND activ = 1";
        $result = mysqli_query($conn, $sql);

        if (mysqli_num_rows($result) == 0) {
            echo json_encode(['success' => false, 'message' => 'Produsul nu a fost găsit']);
            exit;
        }

        $product = mysqli_fetch_assoc($result);

        // Get product tags
        $tags_sql = "SELECT tag FROM produse_taguri WHERE produs_id = $product_id";
        $tags_result = mysqli_query($conn, $tags_sql);
        $tags = [];
        while ($tag = mysqli_fetch_assoc($tags_result)) {
            $tags[] = $tag['tag'];
        }
        $product['tags'] = $tags;

        // Get related products (same category, different products)
        $related_sql = "SELECT * FROM produse WHERE categorie = '{$product['categorie']}' AND id != $product_id AND activ = 1 LIMIT 4";
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

        echo json_encode(['success' => true, 'products' => $featured]);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Tip necunoscut']);
        break;
}
?>