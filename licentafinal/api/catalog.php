
<?php
require_once '../db-config.php';

header('Content-Type: application/json');

$type = $_GET['type'] ?? '';

switch ($type) {
    case 'products':
        $category = $_GET['category'] ?? '';
        $region = $_GET['region'] ?? '';
        $search = $_GET['search'] ?? '';
        $sort = $_GET['sort'] ?? 'nume';
        $order = $_GET['order'] ?? 'ASC';
        $page = (int)($_GET['page'] ?? 1);
        $limit = (int)($_GET['limit'] ?? 12);
        $offset = ($page - 1) * $limit;
        
        $where_conditions = ["p.activ = 1"];
        
        if ($category) {
            $category = sanitize_input($category);
            $where_conditions[] = "p.categorie = '$category'";
        }
        
        if ($region) {
            $region = sanitize_input($region);
            $where_conditions[] = "p.regiune = '$region'";
        }
        
        if ($search) {
            $search = sanitize_input($search);
            $where_conditions[] = "(p.nume LIKE '%$search%' OR p.descriere LIKE '%$search%')";
        }
        
        $where_clause = implode(' AND ', $where_conditions);
        $sort = sanitize_input($sort);
        $order = $order === 'DESC' ? 'DESC' : 'ASC';
        
        $sql = "SELECT p.*, 
                       COALESCE(AVG(r.rating), 0) as rating_mediu,
                       COUNT(r.id) as total_recenzii
                FROM produse p 
                LEFT JOIN recenzii r ON p.id = r.produs_id AND r.aprobat = 1
                WHERE $where_clause
                GROUP BY p.id
                ORDER BY $sort $order
                LIMIT $limit OFFSET $offset";
        
        $result = mysqli_query($conn, $sql);
        $products = [];
        
        while ($row = mysqli_fetch_assoc($result)) {
            $products[] = $row;
        }
        
        // Get total count
        $count_sql = "SELECT COUNT(*) as total FROM produse p WHERE $where_clause";
        $count_result = mysqli_query($conn, $count_sql);
        $total_products = mysqli_fetch_assoc($count_result)['total'];
        
        echo json_encode([
            'success' => true,
            'products' => $products,
            'total' => $total_products,
            'page' => $page,
            'pages' => ceil($total_products / $limit)
        ]);
        break;

    case 'product_details':
        $id = (int)$_GET['id'];
        
        $sql = "SELECT p.*, 
                       COALESCE(AVG(r.rating), 0) as rating_mediu,
                       COUNT(r.id) as total_recenzii
                FROM produse p 
                LEFT JOIN recenzii r ON p.id = r.produs_id AND r.aprobat = 1
                WHERE p.id = $id AND p.activ = 1
                GROUP BY p.id";
        
        $result = mysqli_query($conn, $sql);
        
        if (mysqli_num_rows($result) == 0) {
            echo json_encode(['success' => false, 'message' => 'Produsul nu a fost găsit']);
            exit;
        }
        
        $product = mysqli_fetch_assoc($result);
        
        // Get related products
        $related_sql = "SELECT id, nume, pret, imagine FROM produse 
                        WHERE categorie = '{$product['categorie']}' AND id != $id AND activ = 1 
                        ORDER BY RAND() LIMIT 4";
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

    case 'tags':
        $sql = "SELECT DISTINCT tag FROM produse_taguri pt 
                JOIN produse p ON pt.produs_id = p.id 
                WHERE p.activ = 1 
                ORDER BY tag";
        $result = mysqli_query($conn, $sql);
        
        $tags = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $tags[] = $row['tag'];
        }
        
        echo json_encode(['success' => true, 'tags' => $tags]);
        break;

    case 'offers':
        $sql = "SELECT * FROM oferte WHERE activ = 1 AND data_expirare >= CURDATE() ORDER BY data_crearii DESC";
        $result = mysqli_query($conn, $sql);
        
        $offers = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $offers[] = $row;
        }
        
        echo json_encode(['success' => true, 'offers' => $offers]);
        break;

    case 'packages':
        $sql = "SELECT * FROM pachete WHERE activ = 1 ORDER BY nume";
        $result = mysqli_query($conn, $sql);
        
        $packages = [];
        while ($row = mysqli_fetch_assoc($result)) {
            // Get package products
            $products_sql = "SELECT p.id, p.nume, p.pret, p.imagine FROM produse p 
                             JOIN pachete_produse pp ON p.id = pp.produs_id 
                             WHERE pp.pachet_id = {$row['id']} AND p.activ = 1";
            $products_result = mysqli_query($conn, $products_sql);
            
            $products = [];
            while ($product = mysqli_fetch_assoc($products_result)) {
                $products[] = $product;
            }
            
            $row['products'] = $products;
            $packages[] = $row;
        }
        
        echo json_encode(['success' => true, 'packages' => $packages]);
        break;

    case 'featured':
        $sql = "SELECT p.*, 
                       COALESCE(AVG(r.rating), 0) as rating_mediu,
                       COUNT(r.id) as total_recenzii
                FROM produse p 
                LEFT JOIN recenzii r ON p.id = r.produs_id AND r.aprobat = 1
                WHERE p.recomandat = 1 AND p.activ = 1
                GROUP BY p.id
                ORDER BY p.data_adaugarii DESC
                LIMIT 8";
        
        $result = mysqli_query($conn, $sql);
        $featured = [];
        
        while ($row = mysqli_fetch_assoc($result)) {
            $featured[] = $row;
        }
        
        echo json_encode(['success' => true, 'featured' => $featured]);
        break;

    case 'bestsellers':
        $sql = "SELECT p.*, 
                       COALESCE(AVG(r.rating), 0) as rating_mediu,
                       COUNT(r.id) as total_recenzii,
                       COALESCE(SUM(cp.cantitate), 0) as total_vandut
                FROM produse p 
                LEFT JOIN recenzii r ON p.id = r.produs_id AND r.aprobat = 1
                LEFT JOIN comenzi_produse cp ON p.id = cp.produs_id
                LEFT JOIN comenzi c ON cp.comanda_id = c.id AND c.status != 'anulata'
                WHERE p.activ = 1
                GROUP BY p.id
                ORDER BY total_vandut DESC
                LIMIT 8";
        
        $result = mysqli_query($conn, $sql);
        $bestsellers = [];
        
        while ($row = mysqli_fetch_assoc($result)) {
            $bestsellers[] = $row;
        }
        
        echo json_encode(['success' => true, 'bestsellers' => $bestsellers]);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Tip necunoscut']);
        break;
}
?>
