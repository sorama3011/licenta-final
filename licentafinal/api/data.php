
<?php
require_once '../db-config.php';
session_start();

header('Content-Type: application/json');

$type = $_GET['type'] ?? '';
$user_id = $_SESSION['user_id'] ?? null;

switch ($type) {
    case 'addresses':
        if (!$user_id) {
            echo json_encode(['success' => false, 'message' => 'Utilizator neautentificat']);
            exit;
        }
        
        $sql = "SELECT * FROM adrese WHERE user_id = $user_id ORDER BY implicit DESC, id ASC";
        $result = mysqli_query($conn, $sql);
        $addresses = [];
        
        while ($row = mysqli_fetch_assoc($result)) {
            $addresses[] = $row;
        }
        
        echo json_encode(['success' => true, 'addresses' => $addresses]);
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

    case 'order':
        if (!$user_id) {
            echo json_encode(['success' => false, 'message' => 'Utilizator neautentificat']);
            exit;
        }
        
        $order_id = (int)$_GET['id'];
        
        $sql = "SELECT c.*, a.nume as nume_adresa, a.adresa, a.oras, a.judet, a.cod_postal, a.telefon
                FROM comenzi c
                LEFT JOIN adrese a ON c.adresa_id = a.id
                WHERE c.id = $order_id AND c.user_id = $user_id";
        
        $result = mysqli_query($conn, $sql);
        
        if (mysqli_num_rows($result) == 0) {
            echo json_encode(['success' => false, 'message' => 'Comanda nu a fost găsită']);
            exit;
        }
        
        $order = mysqli_fetch_assoc($result);
        
        // Get order products
        $products_sql = "SELECT cp.*, p.nume, p.imagine 
                         FROM comenzi_produse cp
                         JOIN produse p ON cp.produs_id = p.id
                         WHERE cp.comanda_id = $order_id";
        
        $products_result = mysqli_query($conn, $products_sql);
        $products = [];
        
        while ($product = mysqli_fetch_assoc($products_result)) {
            $products[] = $product;
        }
        
        $order['products'] = $products;
        
        echo json_encode(['success' => true, 'order' => $order]);
        break;

    case 'orders':
        if (!$user_id) {
            echo json_encode(['success' => false, 'message' => 'Utilizator neautentificat']);
            exit;
        }
        
        $page = (int)($_GET['page'] ?? 1);
        $limit = 10;
        $offset = ($page - 1) * $limit;
        
        $sql = "SELECT * FROM comenzi WHERE user_id = $user_id ORDER BY data_crearii DESC LIMIT $limit OFFSET $offset";
        $result = mysqli_query($conn, $sql);
        
        $orders = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $orders[] = $row;
        }
        
        // Get total count
        $count_sql = "SELECT COUNT(*) as total FROM comenzi WHERE user_id = $user_id";
        $count_result = mysqli_query($conn, $count_sql);
        $total = mysqli_fetch_assoc($count_result)['total'];
        
        echo json_encode([
            'success' => true, 
            'orders' => $orders, 
            'total' => $total,
            'page' => $page,
            'pages' => ceil($total / $limit)
        ]);
        break;

    case 'cart':
        if (!$user_id) {
            echo json_encode(['success' => false, 'message' => 'Utilizator neautentificat']);
            exit;
        }
        
        $sql = "SELECT c.produs_id, c.cantitate, p.nume, p.pret, p.imagine, p.cantitate as greutate
                FROM cos_cumparaturi c
                JOIN produse p ON c.produs_id = p.id
                WHERE c.user_id = $user_id";
        $result = mysqli_query($conn, $sql);
        
        $cart_items = [];
        $cart_count = 0;
        $cart_total = 0;
        
        while ($item = mysqli_fetch_assoc($result)) {
            $subtotal = $item['pret'] * $item['cantitate'];
            $cart_total += $subtotal;
            $cart_count += $item['cantitate'];
            
            $cart_items[] = [
                'id' => (int)$item['produs_id'],
                'name' => $item['nume'],
                'price' => (float)$item['pret'],
                'image' => $item['imagine'],
                'weight' => $item['greutate'],
                'quantity' => (int)$item['cantitate'],
                'subtotal' => $subtotal
            ];
        }
        
        $shipping = $cart_total >= 150.00 ? 0 : 15.00;
        
        echo json_encode([
            'success' => true,
            'cart' => $cart_items,
            'cart_count' => $cart_count,
            'cart_total' => $cart_total,
            'shipping' => $shipping,
            'total' => $cart_total + $shipping
        ]);
        break;

    case 'favorites':
        if (!$user_id) {
            echo json_encode(['success' => false, 'message' => 'Utilizator neautentificat']);
            exit;
        }
        
        $sql = "SELECT p.* FROM produse p
                JOIN favorite f ON p.id = f.produs_id
                WHERE f.user_id = $user_id AND p.activ = 1
                ORDER BY f.data_adaugarii DESC";
        
        $result = mysqli_query($conn, $sql);
        $favorites = [];
        
        while ($row = mysqli_fetch_assoc($result)) {
            $favorites[] = $row;
        }
        
        echo json_encode(['success' => true, 'favorites' => $favorites]);
        break;

    case 'reviews':
        $product_id = (int)$_GET['product_id'];
        $page = (int)($_GET['page'] ?? 1);
        $limit = 10;
        $offset = ($page - 1) * $limit;
        
        $sql = "SELECT r.*, u.prenume, u.nume
                FROM recenzii r
                JOIN utilizatori u ON r.user_id = u.id
                WHERE r.produs_id = $product_id AND r.aprobat = 1
                ORDER BY r.data_crearii DESC
                LIMIT $limit OFFSET $offset";
        
        $result = mysqli_query($conn, $sql);
        $reviews = [];
        
        while ($row = mysqli_fetch_assoc($result)) {
            $reviews[] = $row;
        }
        
        // Get total count and average rating
        $stats_sql = "SELECT COUNT(*) as total, AVG(rating) as average_rating
                      FROM recenzii 
                      WHERE produs_id = $product_id AND aprobat = 1";
        $stats_result = mysqli_query($conn, $stats_sql);
        $stats = mysqli_fetch_assoc($stats_result);
        
        echo json_encode([
            'success' => true,
            'reviews' => $reviews,
            'total' => $stats['total'],
            'average_rating' => round($stats['average_rating'], 1),
            'page' => $page,
            'pages' => ceil($stats['total'] / $limit)
        ]);
        break;

    case 'loyalty_points':
        if (!$user_id) {
            echo json_encode(['success' => false, 'message' => 'Utilizator neautentificat']);
            exit;
        }
        
        $sql = "SELECT * FROM puncte_fidelitate WHERE user_id = $user_id";
        $result = mysqli_query($conn, $sql);
        
        if (mysqli_num_rows($result) > 0) {
            $points = mysqli_fetch_assoc($result);
        } else {
            $points = ['user_id' => $user_id, 'puncte' => 0];
        }
        
        // Get points history
        $history_sql = "SELECT * FROM istoric_puncte WHERE user_id = $user_id ORDER BY data_crearii DESC LIMIT 20";
        $history_result = mysqli_query($conn, $history_sql);
        $history = [];
        
        while ($row = mysqli_fetch_assoc($history_result)) {
            $history[] = $row;
        }
        
        $points['history'] = $history;
        
        echo json_encode(['success' => true, 'points' => $points]);
        break;

    case 'vouchers':
        if (!$user_id) {
            echo json_encode(['success' => false, 'message' => 'Utilizator neautentificat']);
            exit;
        }
        
        $sql = "SELECT v.* FROM vouchere v
                LEFT JOIN utilizatori_vouchere uv ON v.id = uv.voucher_id AND uv.user_id = $user_id
                WHERE v.activ = 1 AND v.data_expirare >= CURDATE()
                AND (v.utilizari_maxime IS NULL OR v.utilizari_curente < v.utilizari_maxime)
                AND (uv.id IS NULL OR v.tip_utilizare = 'multiplu')
                ORDER BY v.data_crearii DESC";
        
        $result = mysqli_query($conn, $sql);
        $vouchers = [];
        
        while ($row = mysqli_fetch_assoc($result)) {
            $vouchers[] = $row;
        }
        
        echo json_encode(['success' => true, 'vouchers' => $vouchers]);
        break;

    case 'countdown':
        $sql = "SELECT * FROM countdown WHERE activ = 1 AND data_expirare > NOW() ORDER BY prioritate DESC LIMIT 1";
        $result = mysqli_query($conn, $sql);
        
        if (mysqli_num_rows($result) > 0) {
            $countdown = mysqli_fetch_assoc($result);
            echo json_encode(['success' => true, 'countdown' => $countdown]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Nu există countdown activ']);
        }
        break;

    case 'statistics':
        // Check admin permissions
        if (!$user_id || !in_array($_SESSION['user_role'], ['administrator', 'angajat'])) {
            echo json_encode(['success' => false, 'message' => 'Acces interzis']);
            exit;
        }
        
        try {
            $products_sql = "SELECT COUNT(*) as total FROM produse WHERE activ = 1";
            $products_result = mysqli_query($conn, $products_sql);
            $total_products = mysqli_fetch_assoc($products_result)['total'];
            
            $out_of_stock_sql = "SELECT COUNT(*) as total FROM produse WHERE activ = 1 AND stoc = 0";
            $out_of_stock_result = mysqli_query($conn, $out_of_stock_sql);
            $out_of_stock = mysqli_fetch_assoc($out_of_stock_result)['total'];
            
            $customers_sql = "SELECT COUNT(*) as total FROM utilizatori WHERE rol = 'client' AND activ = 1";
            $customers_result = mysqli_query($conn, $customers_sql);
            $total_customers = mysqli_fetch_assoc($customers_result)['total'];
            
            $orders_sql = "SELECT COUNT(*) as total FROM comenzi";
            $orders_result = mysqli_query($conn, $orders_sql);
            $total_orders = mysqli_fetch_assoc($orders_result)['total'];
            
            $sales_sql = "SELECT COALESCE(SUM(total), 0) as total_sales FROM comenzi WHERE status != 'anulata'";
            $sales_result = mysqli_query($conn, $sales_sql);
            $total_sales = mysqli_fetch_assoc($sales_result)['total_sales'];
            
            $stats = [
                'products' => ['total' => $total_products, 'out_of_stock' => $out_of_stock],
                'customers' => ['total' => $total_customers],
                'orders' => ['total' => $total_orders],
                'sales' => ['total' => $total_sales]
            ];
            
            echo json_encode(['success' => true, 'stats' => $stats]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Eroare la obținerea statisticilor']);
        }
        break;

    case 'price_history':
        // Check admin permissions
        if (!$user_id || !in_array($_SESSION['user_role'], ['administrator', 'angajat'])) {
            echo json_encode(['success' => false, 'message' => 'Acces interzis']);
            exit;
        }
        
        $product_id = (int)$_GET['product_id'];
        $page = (int)($_GET['page'] ?? 1);
        $limit = 20;
        $offset = ($page - 1) * $limit;
        
        $sql = "SELECT ih.*, u.prenume, u.nume, p.nume as produs_nume
                FROM istoric_preturi ih
                JOIN utilizatori u ON ih.modificat_de = u.id
                JOIN produse p ON ih.produs_id = p.id
                WHERE ih.produs_id = $product_id
                ORDER BY ih.data_modificarii DESC
                LIMIT $limit OFFSET $offset";
        
        $result = mysqli_query($conn, $sql);
        $history = [];
        
        while ($row = mysqli_fetch_assoc($result)) {
            $history[] = $row;
        }
        
        // Get total count
        $count_sql = "SELECT COUNT(*) as total FROM istoric_preturi WHERE produs_id = $product_id";
        $count_result = mysqli_query($conn, $count_sql);
        $total = mysqli_fetch_assoc($count_result)['total'];
        
        echo json_encode([
            'success' => true, 
            'history' => $history,
            'total' => $total,
            'page' => $page,
            'pages' => ceil($total / $limit)
        ]);
        break;

    case 'activity_log':
        // Check admin permissions
        if (!$user_id || !in_array($_SESSION['user_role'], ['administrator', 'angajat'])) {
            echo json_encode(['success' => false, 'message' => 'Acces interzis']);
            exit;
        }
        
        $page = (int)($_GET['page'] ?? 1);
        $limit = 50;
        $offset = ($page - 1) * $limit;
        
        $sql = "SELECT j.*, u.prenume, u.nume
                FROM jurnalizare j
                JOIN utilizatori u ON j.user_id = u.id
                ORDER BY j.data_actiunii DESC
                LIMIT $limit OFFSET $offset";
        
        $result = mysqli_query($conn, $sql);
        $logs = [];
        
        while ($row = mysqli_fetch_assoc($result)) {
            $logs[] = $row;
        }
        
        // Get total count
        $count_sql = "SELECT COUNT(*) as total FROM jurnalizare";
        $count_result = mysqli_query($conn, $count_sql);
        $total = mysqli_fetch_assoc($count_result)['total'];
        
        echo json_encode([
            'success' => true,
            'logs' => $logs,
            'total' => $total,
            'page' => $page,
            'pages' => ceil($total / $limit)
        ]);
        break;

    case 'sales_report':
        // Check admin permissions
        if (!$user_id || !in_array($_SESSION['user_role'], ['administrator', 'angajat'])) {
            echo json_encode(['success' => false, 'message' => 'Acces interzis']);
            exit;
        }
        
        $start_date = $_GET['start_date'] ?? date('Y-m-01');
        $end_date = $_GET['end_date'] ?? date('Y-m-d');
        
        $sql = "SELECT DATE(data_crearii) as data, COUNT(*) as numar_comenzi, SUM(total) as vanzari_totale
                FROM comenzi 
                WHERE status != 'anulata' AND DATE(data_crearii) BETWEEN '$start_date' AND '$end_date'
                GROUP BY DATE(data_crearii)
                ORDER BY data";
        
        $result = mysqli_query($conn, $sql);
        $report_data = [];
        
        while ($row = mysqli_fetch_assoc($result)) {
            $report_data[] = $row;
        }
        
        echo json_encode(['success' => true, 'data' => $report_data]);
        break;

    case 'products_report':
        // Check admin permissions
        if (!$user_id || !in_array($_SESSION['user_role'], ['administrator', 'angajat'])) {
            echo json_encode(['success' => false, 'message' => 'Acces interzis']);
            exit;
        }
        
        $sql = "SELECT p.id, p.nume, p.categorie, p.stoc, p.pret,
                       COALESCE(SUM(cp.cantitate), 0) as total_vandut,
                       COALESCE(SUM(cp.cantitate * cp.pret), 0) as venituri_totale
                FROM produse p
                LEFT JOIN comenzi_produse cp ON p.id = cp.produs_id
                LEFT JOIN comenzi c ON cp.comanda_id = c.id AND c.status != 'anulata'
                WHERE p.activ = 1
                GROUP BY p.id
                ORDER BY total_vandut DESC";
        
        $result = mysqli_query($conn, $sql);
        $products_report = [];
        
        while ($row = mysqli_fetch_assoc($result)) {
            $products_report[] = $row;
        }
        
        echo json_encode(['success' => true, 'products' => $products_report]);
        break;

    case 'customers_report':
        // Check admin permissions
        if (!$user_id || !in_array($_SESSION['user_role'], ['administrator', 'angajat'])) {
            echo json_encode(['success' => false, 'message' => 'Acces interzis']);
            exit;
        }
        
        $sql = "SELECT u.id, u.prenume, u.nume, u.email, u.data_inregistrarii,
                       COUNT(c.id) as total_comenzi,
                       COALESCE(SUM(c.total), 0) as total_cheltuit,
                       pf.puncte as puncte_fidelitate
                FROM utilizatori u
                LEFT JOIN comenzi c ON u.id = c.user_id AND c.status != 'anulata'
                LEFT JOIN puncte_fidelitate pf ON u.id = pf.user_id
                WHERE u.rol = 'client' AND u.activ = 1
                GROUP BY u.id
                ORDER BY total_cheltuit DESC";
        
        $result = mysqli_query($conn, $sql);
        $customers_report = [];
        
        while ($row = mysqli_fetch_assoc($result)) {
            $customers_report[] = $row;
        }
        
        echo json_encode(['success' => true, 'customers' => $customers_report]);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Tip necunoscut']);
        break;
}
?>
