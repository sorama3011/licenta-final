
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
    case 'add_item':
        $produs_id = (int)$_POST['produs_id'];
        $cantitate = (int)$_POST['cantitate'];
        
        // Check if product exists and is active
        $product_sql = "SELECT * FROM produse WHERE id=$produs_id AND activ=1";
        $product_result = mysqli_query($conn, $product_sql);
        
        if (mysqli_num_rows($product_result) == 0) {
            echo json_encode(['success' => false, 'message' => 'Produsul nu există']);
            exit;
        }
        
        $product = mysqli_fetch_assoc($product_result);
        if ($product['stoc'] < $cantitate) {
            echo json_encode(['success' => false, 'message' => 'Stoc insuficient']);
            exit;
        }
        
        // Check if item already in cart
        $check_sql = "SELECT * FROM cos_cumparaturi WHERE user_id=$user_id AND produs_id=$produs_id";
        $check_result = mysqli_query($conn, $check_sql);
        
        if (mysqli_num_rows($check_result) > 0) {
            $existing = mysqli_fetch_assoc($check_result);
            $new_quantity = $existing['cantitate'] + $cantitate;
            $sql = "UPDATE cos_cumparaturi SET cantitate=$new_quantity WHERE user_id=$user_id AND produs_id=$produs_id";
        } else {
            $sql = "INSERT INTO cos_cumparaturi (user_id, produs_id, cantitate, pret) 
                    VALUES ($user_id, $produs_id, $cantitate, {$product['pret']})";
        }
        
        if (mysqli_query($conn, $sql)) {
            log_action($user_id, 'produs_adaugat_cos', "Produs ID: $produs_id, Cantitate: $cantitate");
            echo json_encode(['success' => true, 'message' => 'Produsul a fost adăugat în coș']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Eroare la adăugarea în coș']);
        }
        break;

    case 'update_quantity':
        $produs_id = (int)$_POST['produs_id'];
        $cantitate = (int)$_POST['cantitate'];
        
        if ($cantitate <= 0) {
            $sql = "DELETE FROM cos_cumparaturi WHERE user_id=$user_id AND produs_id=$produs_id";
        } else {
            // Check stock
            $product_sql = "SELECT stoc FROM produse WHERE id=$produs_id";
            $product_result = mysqli_query($conn, $product_sql);
            $product = mysqli_fetch_assoc($product_result);
            
            if ($product['stoc'] < $cantitate) {
                echo json_encode(['success' => false, 'message' => 'Stoc insuficient']);
                exit;
            }
            
            $sql = "UPDATE cos_cumparaturi SET cantitate=$cantitate WHERE user_id=$user_id AND produs_id=$produs_id";
        }
        
        if (mysqli_query($conn, $sql)) {
            echo json_encode(['success' => true, 'message' => 'Coșul a fost actualizat']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Eroare la actualizarea coșului']);
        }
        break;

    case 'remove_item':
        $produs_id = (int)$_POST['produs_id'];
        $sql = "DELETE FROM cos_cumparaturi WHERE user_id=$user_id AND produs_id=$produs_id";
        
        if (mysqli_query($conn, $sql)) {
            log_action($user_id, 'produs_scos_cos', "Produs ID: $produs_id");
            echo json_encode(['success' => true, 'message' => 'Produsul a fost scos din coș']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Eroare la scoaterea din coș']);
        }
        break;

    case 'apply_voucher':
        $cod_voucher = sanitize_input($_POST['cod_voucher']);
        
        // Check if voucher exists and is valid
        $voucher_sql = "SELECT * FROM vouchere WHERE cod='$cod_voucher' AND activ=1 AND data_expirare >= CURDATE()";
        $voucher_result = mysqli_query($conn, $voucher_sql);
        
        if (mysqli_num_rows($voucher_result) == 0) {
            echo json_encode(['success' => false, 'message' => 'Voucher invalid sau expirat']);
            exit;
        }
        
        $voucher = mysqli_fetch_assoc($voucher_result);
        
        // Check if user can use this voucher
        $user_voucher_sql = "SELECT * FROM utilizatori_vouchere WHERE user_id=$user_id AND voucher_id={$voucher['id']}";
        $user_voucher_result = mysqli_query($conn, $user_voucher_sql);
        
        if (mysqli_num_rows($user_voucher_result) == 0) {
            echo json_encode(['success' => false, 'message' => 'Nu ai acces la acest voucher']);
            exit;
        }
        
        // Store voucher in session
        $_SESSION['applied_voucher'] = $voucher;
        echo json_encode(['success' => true, 'voucher' => $voucher, 'message' => 'Voucherul a fost aplicat']);
        break;

    case 'remove_voucher':
        unset($_SESSION['applied_voucher']);
        echo json_encode(['success' => true, 'message' => 'Voucherul a fost eliminat']);
        break;

    case 'get_cart':
        $sql = "SELECT cc.*, p.nume, p.imagine, p.pret as pret_curent, p.stoc 
                FROM cos_cumparaturi cc 
                JOIN produse p ON cc.produs_id = p.id 
                WHERE cc.user_id=$user_id AND p.activ=1";
        $result = mysqli_query($conn, $sql);
        
        $cart_items = [];
        $total = 0;
        
        while ($row = mysqli_fetch_assoc($result)) {
            $cart_items[] = $row;
            $total += $row['pret'] * $row['cantitate'];
        }
        
        $voucher = $_SESSION['applied_voucher'] ?? null;
        $discount = 0;
        if ($voucher) {
            if ($voucher['tip'] == 'procent') {
                $discount = $total * ($voucher['valoare'] / 100);
            } else {
                $discount = $voucher['valoare'];
            }
        }
        
        echo json_encode([
            'success' => true,
            'items' => $cart_items,
            'subtotal' => $total,
            'discount' => $discount,
            'total' => $total - $discount,
            'voucher' => $voucher
        ]);
        break;

    case 'clear_cart':
        $sql = "DELETE FROM cos_cumparaturi WHERE user_id=$user_id";
        if (mysqli_query($conn, $sql)) {
            unset($_SESSION['applied_voucher']);
            echo json_encode(['success' => true, 'message' => 'Coșul a fost golit']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Eroare la golirea coșului']);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Acțiune necunoscută']);
        break;
}
?>
