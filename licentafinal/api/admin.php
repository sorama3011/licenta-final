
<?php
require_once '../db-config.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['administrator', 'angajat'])) {
    echo json_encode(['success' => false, 'message' => 'Acces interzis']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$type = $_GET['type'] ?? '';
$user_id = $_SESSION['user_id'];

// Admin actions
if ($action) {
    switch ($action) {
        case 'update_price':
            if ($_SESSION['user_role'] !== 'administrator') {
                echo json_encode(['success' => false, 'message' => 'Acces interzis']);
                exit;
            }
            
            $produs_id = (int)$_POST['produs_id'];
            $pret_nou = (float)$_POST['pret_nou'];
            
            // Save price history
            $old_price_sql = "SELECT pret FROM produse WHERE id=$produs_id";
            $old_price_result = mysqli_query($conn, $old_price_sql);
            $old_price = mysqli_fetch_assoc($old_price_result)['pret'];
            
            $history_sql = "INSERT INTO istoric_preturi (produs_id, pret_vechi, pret_nou, modificat_de) 
                            VALUES ($produs_id, $old_price, $pret_nou, $user_id)";
            mysqli_query($conn, $history_sql);
            
            // Update product price
            $update_sql = "UPDATE produse SET pret=$pret_nou WHERE id=$produs_id";
            if (mysqli_query($conn, $update_sql)) {
                log_action($user_id, 'actualizare_pret', "Produs ID: $produs_id, Pret vechi: $old_price, Pret nou: $pret_nou");
                echo json_encode(['success' => true, 'message' => 'Prețul a fost actualizat']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Eroare la actualizarea prețului']);
            }
            break;

        case 'update_order_status':
            $comanda_id = (int)$_POST['comanda_id'];
            $status_nou = sanitize_input($_POST['status_nou']);
            
            $sql = "UPDATE comenzi SET status='$status_nou' WHERE id=$comanda_id";
            if (mysqli_query($conn, $sql)) {
                log_action($user_id, 'actualizare_status_comanda', "Comanda ID: $comanda_id, Status nou: $status_nou");
                echo json_encode(['success' => true, 'message' => 'Statusul comenzii a fost actualizat']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Eroare la actualizarea statusului']);
            }
            break;

        case 'approve_review':
            if ($_SESSION['user_role'] !== 'administrator') {
                echo json_encode(['success' => false, 'message' => 'Acces interzis']);
                exit;
            }
            
            $recenzie_id = (int)$_POST['recenzie_id'];
            $aprobat = (int)$_POST['aprobat'];
            
            $sql = "UPDATE recenzii SET aprobat=$aprobat WHERE id=$recenzie_id";
            if (mysqli_query($conn, $sql)) {
                $action_text = $aprobat ? 'aprobata' : 'respinsa';
                log_action($user_id, 'recenzie_' . $action_text, "Recenzie ID: $recenzie_id");
                echo json_encode(['success' => true, 'message' => "Recenzia a fost $action_text"]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Eroare la procesarea recenziei']);
            }
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Acțiune necunoscută']);
            break;
    }
} elseif ($type) {
    // Admin reports and data
    switch ($type) {
        case 'statistics':
            try {
                // Get basic statistics (using existing logic from get_statistici.php)
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

        case 'sales_report':
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

        case 'price_history':
            $produs_id = (int)$_GET['produs_id'];
            
            $sql = "SELECT ih.*, u.prenume, u.nume, p.nume as produs_nume
                    FROM istoric_preturi ih
                    JOIN utilizatori u ON ih.modificat_de = u.id
                    JOIN produse p ON ih.produs_id = p.id
                    WHERE ih.produs_id = $produs_id
                    ORDER BY ih.data_modificarii DESC";
            
            $result = mysqli_query($conn, $sql);
            $history = [];
            
            while ($row = mysqli_fetch_assoc($result)) {
                $history[] = $row;
            }
            
            echo json_encode(['success' => true, 'history' => $history]);
            break;

        case 'activity_log':
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
            
            echo json_encode(['success' => true, 'logs' => $logs]);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Tip necunoscut']);
            break;
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Parametru lipsă']);
}
?>
