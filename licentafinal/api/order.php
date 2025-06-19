
<?php
require_once '../db-config.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Nu ești autentificat']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$user_id = $_SESSION['user_id'];

switch ($action) {
    case 'place_order':
        $adresa_id = (int)($_POST['address_id'] ?? $_POST['adresa_id'] ?? 0);
        $mod_plata = sanitize_input($_POST['payment_method'] ?? $_POST['mod_plata'] ?? '');
        $observatii = sanitize_input($_POST['notes'] ?? $_POST['observatii'] ?? '');
        
        // Get cart items
        $cart_sql = "SELECT cc.produs_id, cc.cantitate, cc.pret, p.nume, p.pret as pret_curent, p.stoc 
                     FROM cos_cumparaturi cc 
                     JOIN produse p ON cc.produs_id = p.id 
                     WHERE cc.user_id=$user_id AND p.activ=1";
        $cart_result = mysqli_query($conn, $cart_sql);
        
        if (mysqli_num_rows($cart_result) == 0) {
            echo json_encode(['success' => false, 'message' => 'Coșul este gol']);
            exit;
        }
        
        // Calculate total
        $subtotal = 0;
        $cart_items = [];
        while ($item = mysqli_fetch_assoc($cart_result)) {
            if ($item['stoc'] < $item['cantitate']) {
                echo json_encode(['success' => false, 'message' => "Stoc insuficient pentru {$item['nume']}"]);
                exit;
            }
            $cart_items[] = $item;
            $subtotal += $item['pret_curent'] * $item['cantitate'];
        }
        
        // Apply voucher discount
        $voucher = $_SESSION['applied_voucher'] ?? null;
        $discount = 0;
        $voucher_id = null;
        
        if ($voucher) {
            if ($voucher['tip'] == 'procent') {
                $discount = $subtotal * ($voucher['valoare'] / 100);
            } else {
                $discount = $voucher['valoare'];
            }
            $voucher_id = $voucher['id'];
        }
        
        // Calculate shipping
        $shipping = ($subtotal - $discount >= 150) ? 0 : 15;
        $total = $subtotal - $discount + $shipping;
        
        // Create order
        $numar_comanda = 'GR-' . date('Ymd') . '-' . sprintf('%04d', rand(1, 9999));
        $order_sql = "INSERT INTO comenzi (user_id, numar_comanda, subtotal, transport, discount, total, metoda_plata, status_plata, adresa_livrare_id, adresa_facturare_id, voucher_id, observatii, status) 
                      VALUES ($user_id, '$numar_comanda', $subtotal, $shipping, $discount, $total, '$mod_plata', 'in_asteptare', $adresa_id, $adresa_id, " . ($voucher_id ? $voucher_id : "NULL") . ", '$observatii', 'plasata')";
        
        if (mysqli_query($conn, $order_sql)) {
            $order_id = mysqli_insert_id($conn);
            
            // Add order items
            foreach ($cart_items as $item) {
                $item_subtotal = $item['pret_curent'] * $item['cantitate'];
                $item_sql = "INSERT INTO comenzi_produse (comanda_id, produs_id, nume_produs, pret, cantitate, subtotal) 
                             VALUES ($order_id, {$item['produs_id']}, '{$item['nume']}', {$item['pret_curent']}, {$item['cantitate']}, $item_subtotal)";
                mysqli_query($conn, $item_sql);
                
                // Update stock
                $stock_sql = "UPDATE produse SET stoc = stoc - {$item['cantitate']} WHERE id = {$item['produs_id']}";
                mysqli_query($conn, $stock_sql);
            }
            
            // Clear cart
            mysqli_query($conn, "DELETE FROM cos_cumparaturi WHERE user_id=$user_id");
            unset($_SESSION['applied_voucher']);
            
            // Add loyalty points (1 point per 10 RON)
            $points_to_add = floor($total / 10);
            if ($points_to_add > 0) {
                $points_sql = "UPDATE puncte_fidelitate SET puncte = puncte + $points_to_add WHERE user_id=$user_id";
                mysqli_query($conn, $points_sql);
            }
            
            log_action($user_id, 'comanda_plasata', "Comanda #$numar_comanda, Total: $total RON");
            
            echo json_encode([
                'success' => true,
                'order_id' => $order_id,
                'order_number' => $numar_comanda,
                'message' => 'Comanda a fost plasată cu succes'
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Eroare la plasarea comenzii']);
        }
        break;

    case 'get_details':
        $order_id = (int)$_GET['order_id'];
        
        // Get order details
        $order_sql = "SELECT c.*, a.nume as adresa_nume, a.strada, a.oras, a.judet, a.cod_postal, a.telefon 
                      FROM comenzi c 
                      LEFT JOIN adrese a ON c.adresa_id = a.id 
                      WHERE c.id=$order_id AND c.user_id=$user_id";
        $order_result = mysqli_query($conn, $order_sql);
        
        if (mysqli_num_rows($order_result) == 0) {
            echo json_encode(['success' => false, 'message' => 'Comanda nu a fost găsită']);
            exit;
        }
        
        $order = mysqli_fetch_assoc($order_result);
        
        // Get order items
        $items_sql = "SELECT cp.*, p.nume, p.imagine 
                      FROM comenzi_produse cp 
                      JOIN produse p ON cp.produs_id = p.id 
                      WHERE cp.comanda_id=$order_id";
        $items_result = mysqli_query($conn, $items_sql);
        
        $items = [];
        while ($item = mysqli_fetch_assoc($items_result)) {
            $items[] = $item;
        }
        
        $order['items'] = $items;
        
        echo json_encode(['success' => true, 'order' => $order]);
        break;

    case 'get_list':
        $page = (int)($_GET['page'] ?? 1);
        $limit = 10;
        $offset = ($page - 1) * $limit;
        
        $orders_sql = "SELECT id, numar_comenda, total, status, data_crearii 
                       FROM comenzi 
                       WHERE user_id=$user_id 
                       ORDER BY data_crearii DESC 
                       LIMIT $limit OFFSET $offset";
        $orders_result = mysqli_query($conn, $orders_sql);
        
        $orders = [];
        while ($order = mysqli_fetch_assoc($orders_result)) {
            $orders[] = $order;
        }
        
        // Get total count
        $count_sql = "SELECT COUNT(*) as total FROM comenzi WHERE user_id=$user_id";
        $count_result = mysqli_query($conn, $count_sql);
        $total_orders = mysqli_fetch_assoc($count_result)['total'];
        
        echo json_encode([
            'success' => true,
            'orders' => $orders,
            'total' => $total_orders,
            'page' => $page,
            'pages' => ceil($total_orders / $limit)
        ]);
        break;

    case 'cancel_order':
        $order_id = (int)$_POST['order_id'];
        
        // Check if order can be cancelled
        $order_sql = "SELECT * FROM comenzi WHERE id=$order_id AND user_id=$user_id AND status IN ('noua', 'confirmata')";
        $order_result = mysqli_query($conn, $order_sql);
        
        if (mysqli_num_rows($order_result) == 0) {
            echo json_encode(['success' => false, 'message' => 'Comanda nu poate fi anulată']);
            exit;
        }
        
        // Update order status
        $update_sql = "UPDATE comenzi SET status='anulata' WHERE id=$order_id";
        if (mysqli_query($conn, $update_sql)) {
            // Restore stock
            $items_sql = "SELECT produs_id, cantitate FROM comenzi_produse WHERE comanda_id=$order_id";
            $items_result = mysqli_query($conn, $items_sql);
            
            while ($item = mysqli_fetch_assoc($items_result)) {
                $stock_sql = "UPDATE produse SET stoc = stoc + {$item['cantitate']} WHERE id = {$item['produs_id']}";
                mysqli_query($conn, $stock_sql);
            }
            
            log_action($user_id, 'comanda_anulata', "Comanda ID: $order_id");
            echo json_encode(['success' => true, 'message' => 'Comanda a fost anulată']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Eroare la anularea comenzii']);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Acțiune necunoscută']);
        break;
}
?>
