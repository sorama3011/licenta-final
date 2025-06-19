<?php
// Include database configuration
require_once 'db-config.php';

// Start session
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'administrator') {
    header("Location: login.php");
    exit;
}

// Check if order ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: admin-orders.php");
    exit;
}

$order_id = (int)$_GET['id'];

// Get order details
$order_sql = "SELECT c.*, 
              CONCAT(u.prenume, ' ', u.nume) as client_name, 
              u.email as client_email, 
              u.telefon as client_phone,
              al.adresa as adresa_livrare, al.oras as oras_livrare, al.judet as judet_livrare, 
              al.cod_postal as cod_postal_livrare, al.telefon as telefon_livrare,
              af.adresa as adresa_facturare, af.oras as oras_facturare, af.judet as judet_facturare, 
              af.cod_postal as cod_postal_facturare, af.telefon as telefon_facturare
              FROM comenzi c
              JOIN utilizatori u ON c.user_id = u.id
              LEFT JOIN adrese al ON c.adresa_livrare_id = al.id
              LEFT JOIN adrese af ON c.adresa_facturare_id = af.id
              WHERE c.id = ?";

$order_stmt = mysqli_prepare($conn, $order_sql);
mysqli_stmt_bind_param($order_stmt, "i", $order_id);
mysqli_stmt_execute($order_stmt);
$order_result = mysqli_stmt_get_result($order_stmt);

if (mysqli_num_rows($order_result) == 0) {
    header("Location: admin-orders.php");
    exit;
}

$order = mysqli_fetch_assoc($order_result);

// Get order items
$items_sql = "SELECT cp.*
              FROM comenzi_produse cp
              WHERE cp.comanda_id = ?";

$items_stmt = mysqli_prepare($conn, $items_sql);
mysqli_stmt_bind_param($items_stmt, "i", $order_id);
mysqli_stmt_execute($items_stmt);
$items_result = mysqli_stmt_get_result($items_stmt);

// Get voucher details if used
$voucher = null;
if ($order['voucher_id']) {
    $voucher_sql = "SELECT * FROM vouchere WHERE id = ?";
    $voucher_stmt = mysqli_prepare($conn, $voucher_sql);
    mysqli_stmt_bind_param($voucher_stmt, "i", $order['voucher_id']);
    mysqli_stmt_execute($voucher_stmt);
    $voucher_result = mysqli_stmt_get_result($voucher_stmt);
    
    if (mysqli_num_rows($voucher_result) > 0) {
        $voucher = mysqli_fetch_assoc($voucher_result);
    }
}

// Format status for display
function getStatusText($status) {
    switch ($status) {
        case 'plasata':
            return 'Plasată';
        case 'procesata':
            return 'Procesată';
        case 'in_livrare':
            return 'În livrare';
        case 'livrata':
            return 'Livrată';
        case 'anulata':
            return 'Anulată';
        default:
            return 'Necunoscut';
    }
}

// Format payment method for display
function getPaymentMethod($method) {
    switch ($method) {
        case 'card':
            return 'Card de credit/debit';
        case 'transfer':
            return 'Transfer bancar';
        case 'ramburs':
            return 'Plata la livrare (Ramburs)';
        default:
            return 'Necunoscut';
    }
}

// Format payment status for display
function getPaymentStatusText($status) {
    switch ($status) {
        case 'in_asteptare':
            return 'În așteptare';
        case 'platita':
            return 'Plătită';
        case 'rambursata':
            return 'Rambursată';
        case 'anulata':
            return 'Anulată';
        default:
            return 'Necunoscut';
    }
}
?>

<!doctype html>
<html lang="ro">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Printare Comandă #<?php echo $order['numar_comanda']; ?> - Gusturi Românești</title>
    <meta name="description" content="Printare comandă pentru administrare pe platforma Gusturi Românești.">
    
    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f8f9fa;
        }
        
        .print-container {
            max-width: 800px;
            margin: 20px auto;
            background-color: #fff;
            padding: 30px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        
        .print-header {
            border-bottom: 2px solid #8B0000;
            padding-bottom: 20px;
            margin-bottom: 20px;
        }
        
        .print-logo {
            font-weight: bold;
            font-size: 24px;
            color: #8B0000;
        }
        
        .print-title {
            font-size: 22px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .print-subtitle {
            font-size: 16px;
            color: #6c757d;
        }
        
        .print-section {
            margin-bottom: 25px;
        }
        
        .print-section-title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 10px;
            padding-bottom: 5px;
            border-bottom: 1px solid #dee2e6;
        }
        
        .print-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        .print-table th, .print-table td {
            padding: 10px;
            border: 1px solid #dee2e6;
        }
        
        .print-table th {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        
        .print-footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #dee2e6;
            font-size: 14px;
            color: #6c757d;
            text-align: center;
        }
        
        @media print {
            body {
                background-color: #fff;
            }
            
            .print-container {
                box-shadow: none;
                margin: 0;
                padding: 15px;
            }
            
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body>
    <div class="print-container">
        <!-- Print Header -->
        <div class="print-header d-flex justify-content-between align-items-center">
            <div>
                <div class="print-logo">🇷🇴 Gusturi Românești</div>
                <div class="text-muted">Strada Gusturilor Nr. 25, București</div>
                <div class="text-muted">Tel: +40 721 234 567</div>
                <div class="text-muted">Email: contact@gusturi-romanesti.ro</div>
            </div>
            <div class="text-end">
                <div class="print-title">Comandă #<?php echo $order['numar_comanda']; ?></div>
                <div class="print-subtitle">Data: <?php echo date('d.m.Y H:i', strtotime($order['data_plasare'])); ?></div>
                <div class="print-subtitle">Status: <?php echo getStatusText($order['status']); ?></div>
            </div>
        </div>
        
        <!-- Client Information -->
        <div class="print-section row">
            <div class="col-md-6">
                <div class="print-section-title">Informații Client</div>
                <p class="mb-1"><strong>Nume:</strong> <?php echo htmlspecialchars($order['client_name']); ?></p>
                <p class="mb-1"><strong>Email:</strong> <?php echo htmlspecialchars($order['client_email']); ?></p>
                <p class="mb-0"><strong>Telefon:</strong> <?php echo htmlspecialchars($order['client_phone']); ?></p>
            </div>
            <div class="col-md-6">
                <div class="print-section-title">Informații Livrare</div>
                <p class="mb-1"><strong>Adresa:</strong> <?php echo htmlspecialchars($order['adresa_livrare']); ?></p>
                <p class="mb-1"><strong>Oraș/Județ:</strong> <?php echo htmlspecialchars($order['oras_livrare']) . ', ' . htmlspecialchars($order['judet_livrare']); ?></p>
                <p class="mb-1"><strong>Cod Poștal:</strong> <?php echo htmlspecialchars($order['cod_postal_livrare']); ?></p>
                <p class="mb-0"><strong>Telefon:</strong> <?php echo htmlspecialchars($order['telefon_livrare']); ?></p>
            </div>
        </div>
        
        <!-- Payment Information -->
        <div class="print-section">
            <div class="print-section-title">Informații Plată</div>
            <p class="mb-1"><strong>Metoda de Plată:</strong> <?php echo getPaymentMethod($order['metoda_plata']); ?></p>
            <p class="mb-0"><strong>Status Plată:</strong> <?php echo getPaymentStatusText($order['status_plata']); ?></p>
        </div>
        
        <!-- Order Items -->
        <div class="print-section">
            <div class="print-section-title">Produse Comandate</div>
            <table class="print-table">
                <thead>
                    <tr>
                        <th>Produs</th>
                        <th class="text-center">Preț</th>
                        <th class="text-center">Cantitate</th>
                        <th class="text-end">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php mysqli_data_seek($items_result, 0); ?>
                    <?php while ($item = mysqli_fetch_assoc($items_result)): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['nume_produs']); ?></td>
                            <td class="text-center"><?php echo number_format($item['pret'], 2); ?> RON</td>
                            <td class="text-center"><?php echo $item['cantitate']; ?></td>
                            <td class="text-end"><?php echo number_format($item['subtotal'], 2); ?> RON</td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="3" class="text-end">Subtotal:</th>
                        <th class="text-end"><?php echo number_format($order['subtotal'], 2); ?> RON</th>
                    </tr>
                    <?php if ($order['discount'] > 0): ?>
                        <tr>
                            <th colspan="3" class="text-end">Reducere:</th>
                            <th class="text-end">-<?php echo number_format($order['discount'], 2); ?> RON</th>
                        </tr>
                    <?php endif; ?>
                    <tr>
                        <th colspan="3" class="text-end">Transport:</th>
                        <th class="text-end">
                            <?php echo $order['transport'] > 0 ? number_format($order['transport'], 2) . ' RON' : 'Gratuit'; ?>
                        </th>
                    </tr>
                    <tr>
                        <th colspan="3" class="text-end">Total:</th>
                        <th class="text-end"><?php echo number_format($order['total'], 2); ?> RON</th>
                    </tr>
                </tfoot>
            </table>
        </div>
        
        <!-- Additional Information -->
        <?php if (!empty($order['observatii']) || $order['puncte_folosite'] > 0 || $order['puncte_castigate'] > 0 || $voucher): ?>
            <div class="print-section">
                <div class="print-section-title">Informații Suplimentare</div>
                
                <?php if (!empty($order['observatii'])): ?>
                    <p class="mb-2"><strong>Observații Client:</strong> <?php echo nl2br(htmlspecialchars($order['observatii'])); ?></p>
                <?php endif; ?>
                
                <?php if ($order['puncte_folosite'] > 0): ?>
                    <p class="mb-2"><strong>Puncte de Fidelitate Folosite:</strong> <?php echo $order['puncte_folosite']; ?> puncte</p>
                <?php endif; ?>
                
                <?php if ($order['puncte_castigate'] > 0): ?>
                    <p class="mb-2"><strong>Puncte de Fidelitate Câștigate:</strong> <?php echo $order['puncte_castigate']; ?> puncte</p>
                <?php endif; ?>
                
                <?php if ($voucher): ?>
                    <p class="mb-0"><strong>Voucher Aplicat:</strong> <?php echo htmlspecialchars($voucher['cod']); ?> 
                        (<?php echo $voucher['tip'] === 'procent' ? $voucher['valoare'] . '%' : number_format($voucher['valoare'], 2) . ' RON'; ?>)
                    </p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <!-- Print Footer -->
        <div class="print-footer">
            <p class="mb-1">Vă mulțumim pentru comandă!</p>
            <p class="mb-0">Pentru orice întrebări, vă rugăm să ne contactați la contact@gusturi-romanesti.ro sau +40 721 234 567.</p>
        </div>
        
        <!-- Print Button (visible only on screen) -->
        <div class="text-center mt-4 no-print">
            <button class="btn btn-primary" onclick="window.print();">
                <i class="bi bi-printer"></i> Printează Comanda
            </button>
            <button class="btn btn-secondary ms-2" onclick="window.close();">
                <i class="bi bi-x"></i> Închide
            </button>
        </div>
    </div>
    
    <!-- Auto-print script -->
    <script>
        // Auto-print when page loads
        window.onload = function() {
            // Small delay to ensure everything is loaded
            setTimeout(function() {
                window.print();
            }, 500);
        };
    </script>
</body>
</html>