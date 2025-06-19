<?php
// Include database configuration
require_once 'db-config.php';

// Start session
session_start();

// Initialize response array
$response = [
    'success' => false,
    'message' => '',
    'order_number' => '',
    'redirect' => ''
];

// Check if request is AJAX
$is_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // For AJAX requests, return error
    if ($is_ajax) {
        $response['message'] = 'Trebuie să fii autentificat pentru a plasa o comandă.';
        echo json_encode($response);
        exit;
    }
    
    // For regular requests, redirect to login
    $_SESSION['redirect_url'] = 'checkout.php';
    header("Location: login.php");
    exit;
}

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] != "POST") {
    $response['message'] = 'Metoda de cerere invalidă.';
    
    if ($is_ajax) {
        echo json_encode($response);
        exit;
    } else {
        header("Location: checkout.php");
        exit;
    }
}

// Get user ID
$user_id = $_SESSION['user_id'];

// Check if cart is empty
$cart_check_sql = "SELECT COUNT(*) as count FROM cos_cumparaturi WHERE user_id = $user_id";
$cart_check_result = mysqli_query($conn, $cart_check_sql);
$cart_check_row = mysqli_fetch_assoc($cart_check_result);

if ($cart_check_row['count'] == 0) {
    $response['message'] = 'Coșul tău este gol. Adaugă produse pentru a plasa o comandă.';
    
    if ($is_ajax) {
        echo json_encode($response);
        exit;
    } else {
        header("Location: checkout.php");
        exit;
    }
}

// Get form data
$adresa_livrare_id = isset($_POST['adresa_livrare']) ? (int)$_POST['adresa_livrare'] : 0;
$adresa_facturare_id = isset($_POST['adresa_facturare']) ? (int)$_POST['adresa_facturare'] : $adresa_livrare_id;
$metoda_plata = sanitize_input($_POST['metoda_plata']);
$observatii = isset($_POST['observatii']) ? sanitize_input($_POST['observatii']) : '';
$voucher_code = isset($_POST['voucher_code']) ? sanitize_input($_POST['voucher_code']) : '';
$use_points = isset($_POST['use_points']) ? (int)$_POST['use_points'] : 0;

// Validate addresses
if ($adresa_livrare_id == 0) {
    $response['message'] = 'Selectează o adresă de livrare.';
    $response['status'] = 'error';
    
    if ($is_ajax) {
        echo json_encode($response);
        exit;
    } else {
        $_SESSION['error'] = $response['message'];
        header("Location: checkout.php");
        exit;
    }
}

// Validate payment method
if (!in_array($metoda_plata, ['card', 'transfer', 'ramburs'])) {
    $response['message'] = 'Metoda de plată selectată nu este validă.';
    
    if ($is_ajax) {
        echo json_encode($response);
        exit;
    } else {
        header("Location: checkout.php");
        exit;
    }
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
    $out_of_stock = false;
    
    while ($item = mysqli_fetch_assoc($cart_result)) {
        // Check if product is in stock
        if ($item['stoc'] < $item['cantitate']) {
            $out_of_stock = true;
            $response['message'] = "Produsul '{$item['nume']}' nu mai are stoc suficient. Vă rugăm să actualizați coșul.";
            break;
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
    
    // If any product is out of stock, rollback and exit
    if ($out_of_stock) {
        mysqli_rollback($conn);
        
        if ($is_ajax) {
            echo json_encode($response);
            exit;
        } else {
            header("Location: checkout.php");
            exit;
        }
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
            
            // Check if order meets minimum amount
            if ($subtotal >= $voucher['minim_comanda']) {
                // Calculate discount
                if ($voucher['tip'] == 'procent') {
                    $discount = $subtotal * ($voucher['valoare'] / 100);
                } else {
                    $discount = $voucher['valoare'];
                    if ($discount > $subtotal) {
                        $discount = $subtotal;
                    }
                }
                
                $voucher_id = $voucher['id'];
                
                // Update voucher usage
                $update_voucher_sql = "UPDATE vouchere SET utilizari_curente = utilizari_curente + 1 WHERE id = {$voucher['id']}";
                mysqli_query($conn, $update_voucher_sql);
                
                // Mark voucher as used by this user
                $user_voucher_sql = "INSERT INTO vouchere_utilizatori (voucher_id, user_id, utilizat) 
                                    VALUES ({$voucher['id']}, $user_id, 1)
                                    ON DUPLICATE KEY UPDATE utilizat = 1, data_utilizare = NOW()";
                mysqli_query($conn, $user_voucher_sql);
            } else {
                $response['message'] = "Comanda minimă pentru acest voucher este de {$voucher['minim_comanda']} RON.";
                mysqli_rollback($conn);
                
                if ($is_ajax) {
                    echo json_encode($response);
                    exit;
                } else {
                    header("Location: checkout.php");
                    exit;
                }
            }
        } else {
            $response['message'] = "Codul voucher introdus nu este valid sau a expirat.";
            mysqli_rollback($conn);
            
            if ($is_ajax) {
                echo json_encode($response);
                exit;
            } else {
                header("Location: checkout.php");
                exit;
            }
        }
    }
    
    // Apply loyalty points if requested
    $points_discount = 0;
    
    if ($use_points > 0) {
        // Get user's available points
        $points_sql = "SELECT puncte FROM puncte_fidelitate WHERE user_id = $user_id";
        $points_result = mysqli_query($conn, $points_sql);
        
        if (mysqli_num_rows($points_result) > 0) {
            $points_row = mysqli_fetch_assoc($points_result);
            $available_points = (int)$points_row['puncte'];
            
            // Check if user has enough points
            if ($available_points >= $use_points) {
                // Calculate discount (1 point = 0.05 RON)
                $points_discount = $use_points * 0.05;
                
                // Ensure discount doesn't exceed subtotal minus voucher discount
                $max_points_discount = $subtotal - $discount;
                if ($points_discount > $max_points_discount) {
                    $points_discount = $max_points_discount;
                    $use_points = ceil($points_discount / 0.05);
                }
                
                // Update user's points
                $update_points_sql = "UPDATE puncte_fidelitate SET puncte = puncte - $use_points WHERE user_id = $user_id";
                mysqli_query($conn, $update_points_sql);
                
                // Record points transaction
                $points_transaction_sql = "INSERT INTO tranzactii_puncte (user_id, puncte, tip, descriere) 
                                         VALUES ($user_id, $use_points, 'folosire', 'Puncte folosite la comandă')";
                mysqli_query($conn, $points_transaction_sql);
            } else {
                $response['message'] = "Nu ai suficiente puncte de fidelitate.";
                mysqli_rollback($conn);
                
                if ($is_ajax) {
                    echo json_encode($response);
                    exit;
                } else {
                    header("Location: checkout.php");
                    exit;
                }
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
                 $total, '$metoda_plata', " . 
                 ($metoda_plata == 'ramburs' ? "'in_asteptare'" : "'in_asteptare'") . ", 
                 $adresa_livrare_id, $adresa_facturare_id, " . 
                 ($voucher_id ? $voucher_id : "NULL") . ", $use_points, '$observatii')";
    
    if (!mysqli_query($conn, $order_sql)) {
        throw new Exception("Eroare la crearea comenzii: " . mysqli_error($conn));
    }
    
    $order_id = mysqli_insert_id($conn);
    
    // Update voucher usage with order ID
    if ($voucher_id) {
        $update_voucher_usage_sql = "UPDATE vouchere_utilizatori 
                                    SET comanda_id = $order_id, data_utilizare = NOW() 
                                    WHERE voucher_id = $voucher_id AND user_id = $user_id";
        mysqli_query($conn, $update_voucher_usage_sql);
    }
    
    // Add order items
    foreach ($cart_items as $item) {
        $order_item_sql = "INSERT INTO comenzi_produse (comanda_id, produs_id, nume_produs, pret, cantitate, subtotal) 
                          VALUES ($order_id, {$item['produs_id']}, '{$item['nume']}', {$item['pret']}, 
                          {$item['cantitate']}, {$item['subtotal']})";
        
        if (!mysqli_query($conn, $order_item_sql)) {
            throw new Exception("Eroare la adăugarea produselor în comandă: " . mysqli_error($conn));
        }
        
        // Update product stock
        $update_stock_sql = "UPDATE produse SET stoc = stoc - {$item['cantitate']} WHERE id = {$item['produs_id']}";
        mysqli_query($conn, $update_stock_sql);
    }
    
    // Calculate loyalty points earned (1 point for every 10 RON spent)
    $points_earned = floor($total / 10);
    
    if ($points_earned > 0) {
        // Update order with points earned
        $update_order_points_sql = "UPDATE comenzi SET puncte_castigate = $points_earned WHERE id = $order_id";
        mysqli_query($conn, $update_order_points_sql);
        
        // Update user's loyalty points
        $update_user_points_sql = "UPDATE puncte_fidelitate SET puncte = puncte + $points_earned WHERE user_id = $user_id";
        mysqli_query($conn, $update_user_points_sql);
        
        // Record points transaction
        $points_transaction_sql = "INSERT INTO tranzactii_puncte (user_id, puncte, tip, comanda_id, descriere) 
                                 VALUES ($user_id, $points_earned, 'adaugare', $order_id, 'Puncte câștigate din comandă')";
        mysqli_query($conn, $points_transaction_sql);
    }
    
    // Clear cart
    $clear_cart_sql = "DELETE FROM cos_cumparaturi WHERE user_id = $user_id";
    mysqli_query($conn, $clear_cart_sql);
    
    // Log the action
    log_action($user_id, 'plasare_comanda', "Comandă ID: $order_id, Număr: $order_number, Total: $total RON");
    
    // Commit transaction
    mysqli_commit($conn);
    
    // Set success response
    $response['success'] = true;
    $response['message'] = 'Comanda a fost plasată cu succes!';
    $response['order_number'] = $order_number;
    $response['redirect'] = 'client-dashboard.html#order-history';
    
} catch (Exception $e) {
    // Rollback transaction on error
    mysqli_rollback($conn);
    
    $response['message'] = 'A apărut o eroare la plasarea comenzii: ' . $e->getMessage();
    
    // Log the error
    log_action($user_id, 'eroare_plasare_comanda', $e->getMessage());
}

// Return response for AJAX requests
if ($is_ajax) {
    echo json_encode($response);
    exit;
}

// Redirect for regular requests
if ($response['success']) {
    // Set a session variable to indicate successful order placement
    $_SESSION['order_placed'] = true;
    $_SESSION['order_number'] = $response['order_number'];
    
    // Redirect to success page
    header("Location: order-success.php?order=" . $response['order_number']);
} else {
    // Redirect back to checkout with error
    $_SESSION['checkout_error'] = $response['message'];
    header("Location: checkout.php");
}
exit;
?>