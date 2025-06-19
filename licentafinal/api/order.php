
<?php
require_once '../db-config.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Nu ești autentificat']);
    exit;
}

$action = $_POST['action'] ?? 'place_order';
$user_id = $_SESSION['user_id'];

switch ($action) {
    case 'place_order':
        // Check if cart is empty
        $cart_check_sql = "SELECT COUNT(*) as count FROM cos_cumparaturi WHERE user_id = $user_id";
        $cart_check_result = mysqli_query($conn, $cart_check_sql);
        $cart_check_row = mysqli_fetch_assoc($cart_check_result);

        if ($cart_check_row['count'] == 0) {
            echo json_encode(['success' => false, 'message' => 'Coșul tău este gol']);
            exit;
        }

        // Get form data
        $adresa_livrare_id = isset($_POST['adresa_livrare']) ? (int)$_POST['adresa_livrare'] : (isset($_POST['address_id']) ? (int)$_POST['address_id'] : 0);
        $adresa_facturare_id = isset($_POST['adresa_facturare']) ? (int)$_POST['adresa_facturare'] : $adresa_livrare_id;
        $metoda_plata = sanitize_input($_POST['metoda_plata'] ?? $_POST['payment_method'] ?? '');
        $observatii = isset($_POST['observatii']) ? sanitize_input($_POST['observatii']) : (isset($_POST['notes']) ? sanitize_input($_POST['notes']) : '');
        $voucher_code = isset($_POST['voucher_code']) ? sanitize_input($_POST['voucher_code']) : '';
        $use_points = isset($_POST['use_points']) ? (int)$_POST['use_points'] : 0;

        // Validate addresses
        if ($adresa_livrare_id == 0) {
            echo json_encode(['success' => false, 'message' => 'Selectează o adresă de livrare']);
            exit;
        }

        // Validate payment method
        if (!in_array($metoda_plata, ['card', 'transfer', 'ramburs'])) {
            echo json_encode(['success' => false, 'message' => 'Metoda de plată selectată nu este validă']);
            exit;
        }

        // Start transaction
        mysqli_begin_transaction($conn);

        try {
            // Get cart items
            $cart_sql = "SELECT c.produs_id, c.cantitate, p.nume, p.pret, p.stoc
                        FROM cos_cumparaturi c
                        JOIN produse p ON c.produs_id = p.id
                        WHERE c.user_id = $user_id";
            $cart_result = mysqli_query($conn, $cart_sql);
            
            // Calculate totals
            $subtotal = 0;
            $cart_items = [];
            
            while ($item = mysqli_fetch_assoc($cart_result)) {
                // Check if product is in stock
                if ($item['stoc'] < $item['cantitate']) {
                    mysqli_rollback($conn);
                    echo json_encode(['success' => false, 'message' => "Produsul '{$item['nume']}' nu mai are stoc suficient"]);
                    exit;
                }
                
                $item_subtotal = $item['pret'] * $item['cantitate'];
                $subtotal += $item_subtotal;
                
                $cart_items[] = [
                    'produs_id' => $item['produs_id'],
                    'nume' => $item['nume'],
                    'pret' => $item['pret'],
                    'cantitate' => $item['cantitate'],
                    'subtotal' => $item_subtotal
                ];
            }

            // Apply voucher if provided
            $discount = 0;
            $voucher_id = null;
            
            if (!empty($voucher_code)) {
                $voucher_sql = "SELECT v.id, v.tip, v.valoare, v.minim_comanda
                               FROM vouchere v
                               WHERE v.cod = '$voucher_code'
                               AND v.activ = 1
                               AND v.data_inceput <= CURDATE()
                               AND v.data_sfarsit >= CURDATE()
                               AND (v.utilizari_maxime IS NULL OR v.utilizari_curente < v.utilizari_maxime)";
                $voucher_result = mysqli_query($conn, $voucher_sql);
                
                if (mysqli_num_rows($voucher_result) > 0) {
                    $voucher = mysqli_fetch_assoc($voucher_result);
                    
                    if ($subtotal >= $voucher['minim_comanda']) {
                        if ($voucher['tip'] == 'procent') {
                            $discount = $subtotal * ($voucher['valoare'] / 100);
                        } else {
                            $discount = min($voucher['valoare'], $subtotal);
                        }
                        
                        $voucher_id = $voucher['id'];
                        
                        // Update voucher usage
                        $update_voucher_sql = "UPDATE vouchere SET utilizari_curente = utilizari_curente + 1 WHERE id = {$voucher['id']}";
                        mysqli_query($conn, $update_voucher_sql);
                    }
                }
            }

            // Apply loyalty points if requested
            $points_discount = 0;
            
            if ($use_points > 0) {
                $points_sql = "SELECT puncte FROM puncte_fidelitate WHERE user_id = $user_id";
                $points_result = mysqli_query($conn, $points_sql);
                
                if (mysqli_num_rows($points_result) > 0) {
                    $points_row = mysqli_fetch_assoc($points_result);
                    $available_points = (int)$points_row['puncte'];
                    
                    if ($available_points >= $use_points) {
                        $points_discount = $use_points * 0.05;
                        $max_points_discount = $subtotal - $discount;
                        if ($points_discount > $max_points_discount) {
                            $points_discount = $max_points_discount;
                            $use_points = ceil($points_discount / 0.05);
                        }
                        
                        // Update user's points
                        $update_points_sql = "UPDATE puncte_fidelitate SET puncte = puncte - $use_points WHERE user_id = $user_id";
                        mysqli_query($conn, $update_points_sql);
                    }
                }
            }

            // Calculate shipping
            $shipping = ($subtotal - $discount - $points_discount) >= 150 ? 0 : 15;
            
            // Calculate total
            $total = $subtotal - $discount - $points_discount + $shipping;
            
            // Generate order number
            $order_number = 'GR-' . date('Ymd') . '-' . sprintf('%04d', rand(1, 9999));
            
            // Create order
            $order_sql = "INSERT INTO comenzi (user_id, numar_comanda, status, subtotal, transport, discount, total, 
                         metoda_plata, status_plata, adresa_livrare_id, adresa_facturare_id, voucher_id, 
                         puncte_folosite, observatii) 
                         VALUES ($user_id, '$order_number', 'plasata', $subtotal, $shipping, " . ($discount + $points_discount) . ", 
                         $total, '$metoda_plata', 'in_asteptare', $adresa_livrare_id, $adresa_facturare_id, " . 
                         ($voucher_id ? $voucher_id : "NULL") . ", $use_points, '$observatii')";
            
            if (!mysqli_query($conn, $order_sql)) {
                throw new Exception("Eroare la crearea comenzii: " . mysqli_error($conn));
            }
            
            $order_id = mysqli_insert_id($conn);
            
            // Add order items
            foreach ($cart_items as $item) {
                $order_item_sql = "INSERT INTO comenzi_produse (comanda_id, produs_id, nume_produs, pret, cantitate, subtotal) 
                                  VALUES ($order_id, {$item['produs_id']}, '{$item['nume']}', {$item['pret']}, 
                                  {$item['cantitate']}, {$item['subtotal']})";
                
                if (!mysqli_query($conn, $order_item_sql)) {
                    throw new Exception("Eroare la adăugarea produselor în comandă");
                }
                
                // Update product stock
                $update_stock_sql = "UPDATE produse SET stoc = stoc - {$item['cantitate']} WHERE id = {$item['produs_id']}";
                mysqli_query($conn, $update_stock_sql);
            }

            // Calculate loyalty points earned
            $points_earned = floor($total / 10);
            
            if ($points_earned > 0) {
                // Update order with points earned
                $update_order_points_sql = "UPDATE comenzi SET puncte_castigate = $points_earned WHERE id = $order_id";
                mysqli_query($conn, $update_order_points_sql);
                
                // Update user's loyalty points
                $update_user_points_sql = "INSERT INTO puncte_fidelitate (user_id, puncte) VALUES ($user_id, $points_earned) 
                                          ON DUPLICATE KEY UPDATE puncte = puncte + $points_earned";
                mysqli_query($conn, $update_user_points_sql);
            }
            
            // Clear cart
            $clear_cart_sql = "DELETE FROM cos_cumparaturi WHERE user_id = $user_id";
            mysqli_query($conn, $clear_cart_sql);
            
            // Log the action
            log_action($user_id, 'plasare_comanda', "Comandă ID: $order_id, Număr: $order_number, Total: $total RON");
            
            // Commit transaction
            mysqli_commit($conn);
            
            echo json_encode([
                'success' => true,
                'message' => 'Comanda a fost plasată cu succes!',
                'order_number' => $order_number,
                'order_id' => $order_id,
                'total' => $total,
                'redirect' => 'order-success.php?order=' . $order_number
            ]);
            
        } catch (Exception $e) {
            mysqli_rollback($conn);
            echo json_encode(['success' => false, 'message' => 'Eroare la plasarea comenzii: ' . $e->getMessage()]);
            log_action($user_id, 'eroare_plasare_comanda', $e->getMessage());
        }
        break;

    case 'get_order':
        $order_id = (int)$_POST['order_id'];
        
        $order_sql = "SELECT c.*, 
                      al.adresa as adresa_livrare, al.oras as oras_livrare,
                      af.adresa as adresa_facturare, af.oras as oras_facturare
                      FROM comenzi c
                      LEFT JOIN adrese al ON c.adresa_livrare_id = al.id
                      LEFT JOIN adrese af ON c.adresa_facturare_id = af.id
                      WHERE c.id = $order_id AND c.user_id = $user_id";
        
        $order_result = mysqli_query($conn, $order_sql);
        
        if (mysqli_num_rows($order_result) == 0) {
            echo json_encode(['success' => false, 'message' => 'Comanda nu a fost găsită']);
            exit;
        }
        
        $order = mysqli_fetch_assoc($order_result);
        
        // Get order items
        $items_sql = "SELECT * FROM comenzi_produse WHERE comanda_id = $order_id";
        $items_result = mysqli_query($conn, $items_sql);
        $items = [];
        
        while ($item = mysqli_fetch_assoc($items_result)) {
            $items[] = $item;
        }
        
        $order['items'] = $items;
        
        echo json_encode(['success' => true, 'order' => $order]);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Acțiune necunoscută']);
        break;
}
?>
