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
$items_sql = "SELECT cp.*, p.imagine
              FROM comenzi_produse cp
              LEFT JOIN produse p ON cp.produs_id = p.id
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

// Process status update if form is submitted
$status_updated = false;
$status_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $new_status = sanitize_input($_POST['status']);
    
    // Validate status
    $valid_statuses = ['plasata', 'procesata', 'in_livrare', 'livrata', 'anulata'];
    if (in_array($new_status, $valid_statuses)) {
        $update_sql = "UPDATE comenzi SET status = ?";
        
        // Update timestamp based on status
        if ($new_status == 'procesata') {
            $update_sql .= ", data_procesare = NOW()";
        } elseif ($new_status == 'livrata') {
            $update_sql .= ", data_livrare = NOW()";
        }
        
        $update_sql .= " WHERE id = ?";
        
        $update_stmt = mysqli_prepare($conn, $update_sql);
        mysqli_stmt_bind_param($update_stmt, "si", $new_status, $order_id);
        
        if (mysqli_stmt_execute($update_stmt)) {
            $status_updated = true;
            $status_message = 'Statusul comenzii a fost actualizat cu succes.';
            
            // Log the action
            log_action($_SESSION['user_id'], 'actualizare_status_comanda', "Comandă ID: $order_id, Status nou: $new_status");
            
            // If order is canceled, restore stock
            if ($new_status == 'anulata') {
                $items_restore_sql = "SELECT produs_id, cantitate FROM comenzi_produse WHERE comanda_id = ?";
                $items_restore_stmt = mysqli_prepare($conn, $items_restore_sql);
                mysqli_stmt_bind_param($items_restore_stmt, "i", $order_id);
                mysqli_stmt_execute($items_restore_stmt);
                $items_restore_result = mysqli_stmt_get_result($items_restore_stmt);
                
                while ($item = mysqli_fetch_assoc($items_restore_result)) {
                    $update_stock_sql = "UPDATE produse SET stoc = stoc + ? WHERE id = ?";
                    $update_stock_stmt = mysqli_prepare($conn, $update_stock_sql);
                    mysqli_stmt_bind_param($update_stock_stmt, "ii", $item['cantitate'], $item['produs_id']);
                    mysqli_stmt_execute($update_stock_stmt);
                }
            }
            
            // Refresh order data
            mysqli_stmt_execute($order_stmt);
            $order_result = mysqli_stmt_get_result($order_stmt);
            $order = mysqli_fetch_assoc($order_result);
        } else {
            $status_message = 'A apărut o eroare la actualizarea statusului comenzii.';
        }
    } else {
        $status_message = 'Status invalid.';
    }
}

// Format status for display
function getStatusBadge($status) {
    switch ($status) {
        case 'plasata':
            return '<span class="badge bg-info text-dark">Plasată</span>';
        case 'procesata':
            return '<span class="badge bg-primary">Procesată</span>';
        case 'in_livrare':
            return '<span class="badge bg-warning text-dark">În livrare</span>';
        case 'livrata':
            return '<span class="badge bg-success">Livrată</span>';
        case 'anulata':
            return '<span class="badge bg-danger">Anulată</span>';
        default:
            return '<span class="badge bg-secondary">Necunoscut</span>';
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
function getPaymentStatus($status) {
    switch ($status) {
        case 'in_asteptare':
            return '<span class="badge bg-warning text-dark">În așteptare</span>';
        case 'platita':
            return '<span class="badge bg-success">Plătită</span>';
        case 'rambursata':
            return '<span class="badge bg-info text-dark">Rambursată</span>';
        case 'anulata':
            return '<span class="badge bg-danger">Anulată</span>';
        default:
            return '<span class="badge bg-secondary">Necunoscut</span>';
    }
}
?>

<!doctype html>
<html lang="ro">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Detalii Comandă #<?php echo $order['numar_comanda']; ?> - Gusturi Românești</title>
    <meta name="description" content="Detalii comandă pentru administrare pe platforma Gusturi Românești.">
    
    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="admin-dashboard.css">
    
    <style>
        .timeline {
            position: relative;
            padding-left: 30px;
        }
        
        .timeline::before {
            content: '';
            position: absolute;
            left: 10px;
            top: 0;
            bottom: 0;
            width: 2px;
            background-color: #dee2e6;
        }
        
        .timeline-item {
            position: relative;
            margin-bottom: 20px;
        }
        
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -30px;
            top: 0;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background-color: #fff;
            border: 2px solid #dee2e6;
        }
        
        .timeline-item.active::before {
            background-color: #0d6efd;
            border-color: #0d6efd;
        }
        
        .timeline-item.completed::before {
            background-color: #198754;
            border-color: #198754;
        }
        
        .timeline-item.canceled::before {
            background-color: #dc3545;
            border-color: #dc3545;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.html">
                Gusturi Românești
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="admin-dashboard.html">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="admin-orders.php">Comenzi</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="admin-products.php">Produse</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="admin-users.php">Utilizatori</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="admin-reports.php">Rapoarte</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">
                            <i class="bi bi-box-arrow-right"></i> Deconectare
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Page Header -->
    <section class="bg-secondary text-white py-4" style="margin-top: 76px;">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item"><a href="admin-dashboard.html" class="text-white">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="admin-orders.php" class="text-white">Comenzi</a></li>
                            <li class="breadcrumb-item active text-white" aria-current="page">Detalii Comandă #<?php echo $order['numar_comanda']; ?></li>
                        </ol>
                    </nav>
                </div>
                <div class="col-md-4 text-md-end mt-3 mt-md-0">
                    <a href="admin-orders.php" class="btn btn-outline-light btn-sm">
                        <i class="bi bi-arrow-left"></i> Înapoi la Lista Comenzilor
                    </a>
                    <a href="javascript:window.print();" class="btn btn-light btn-sm ms-2">
                        <i class="bi bi-printer"></i> Printează
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Order Details Content -->
    <section class="py-5">
        <div class="container">
            <?php if ($status_updated): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $status_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php elseif (!empty($status_message)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $status_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <div class="row">
                <!-- Order Summary -->
                <div class="col-lg-8">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-light d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Detalii Comandă #<?php echo $order['numar_comanda']; ?></h5>
                            <span class="badge bg-primary"><?php echo date('d.m.Y H:i', strtotime($order['data_plasare'])); ?></span>
                        </div>
                        <div class="card-body">
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <h6 class="fw-bold">Informații Client</h6>
                                    <p class="mb-1"><strong>Nume:</strong> <?php echo htmlspecialchars($order['client_name']); ?></p>
                                    <p class="mb-1"><strong>Email:</strong> <?php echo htmlspecialchars($order['client_email']); ?></p>
                                    <p class="mb-0"><strong>Telefon:</strong> <?php echo htmlspecialchars($order['client_phone']); ?></p>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="fw-bold">Informații Comandă</h6>
                                    <p class="mb-1"><strong>Număr Comandă:</strong> <?php echo htmlspecialchars($order['numar_comanda']); ?></p>
                                    <p class="mb-1"><strong>Status:</strong> <?php echo getStatusBadge($order['status']); ?></p>
                                    <p class="mb-1"><strong>Metoda de Plată:</strong> <?php echo getPaymentMethod($order['metoda_plata']); ?></p>
                                    <p class="mb-0"><strong>Status Plată:</strong> <?php echo getPaymentStatus($order['status_plata']); ?></p>
                                </div>
                            </div>
                            
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <h6 class="fw-bold">Adresa de Livrare</h6>
                                    <p class="mb-1"><?php echo htmlspecialchars($order['adresa_livrare']); ?></p>
                                    <p class="mb-1"><?php echo htmlspecialchars($order['oras_livrare']) . ', ' . htmlspecialchars($order['judet_livrare']) . ', ' . htmlspecialchars($order['cod_postal_livrare']); ?></p>
                                    <p class="mb-0"><strong>Telefon:</strong> <?php echo htmlspecialchars($order['telefon_livrare']); ?></p>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="fw-bold">Adresa de Facturare</h6>
                                    <p class="mb-1"><?php echo htmlspecialchars($order['adresa_facturare']); ?></p>
                                    <p class="mb-1"><?php echo htmlspecialchars($order['oras_facturare']) . ', ' . htmlspecialchars($order['judet_facturare']) . ', ' . htmlspecialchars($order['cod_postal_facturare']); ?></p>
                                    <p class="mb-0"><strong>Telefon:</strong> <?php echo htmlspecialchars($order['telefon_facturare']); ?></p>
                                </div>
                            </div>
                            
                            <?php if (!empty($order['observatii'])): ?>
                            <div class="mb-4">
                                <h6 class="fw-bold">Observații Client</h6>
                                <p class="mb-0"><?php echo nl2br(htmlspecialchars($order['observatii'])); ?></p>
                            </div>
                            <?php endif; ?>
                            
                            <h6 class="fw-bold">Produse Comandate</h6>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th style="width: 60px;">Imagine</th>
                                            <th>Produs</th>
                                            <th class="text-center">Preț</th>
                                            <th class="text-center">Cantitate</th>
                                            <th class="text-end">Subtotal</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($item = mysqli_fetch_assoc($items_result)): ?>
                                            <tr>
                                                <td>
                                                    <?php if (!empty($item['imagine'])): ?>
                                                        <img src="<?php echo htmlspecialchars($item['imagine']); ?>" alt="<?php echo htmlspecialchars($item['nume_produs']); ?>" class="img-thumbnail" style="width: 50px; height: 50px; object-fit: cover;">
                                                    <?php else: ?>
                                                        <div class="bg-light text-center" style="width: 50px; height: 50px; line-height: 50px;">
                                                            <i class="bi bi-image text-muted"></i>
                                                        </div>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($item['nume_produs']); ?></td>
                                                <td class="text-center"><?php echo number_format($item['pret'], 2); ?> RON</td>
                                                <td class="text-center"><?php echo $item['cantitate']; ?></td>
                                                <td class="text-end"><?php echo number_format($item['subtotal'], 2); ?> RON</td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <th colspan="4" class="text-end">Subtotal:</th>
                                            <th class="text-end"><?php echo number_format($order['subtotal'], 2); ?> RON</th>
                                        </tr>
                                        <?php if ($order['discount'] > 0): ?>
                                            <tr>
                                                <th colspan="4" class="text-end">Reducere:</th>
                                                <th class="text-end text-success">-<?php echo number_format($order['discount'], 2); ?> RON</th>
                                            </tr>
                                        <?php endif; ?>
                                        <tr>
                                            <th colspan="4" class="text-end">Transport:</th>
                                            <th class="text-end">
                                                <?php echo $order['transport'] > 0 ? number_format($order['transport'], 2) . ' RON' : 'Gratuit'; ?>
                                            </th>
                                        </tr>
                                        <tr>
                                            <th colspan="4" class="text-end">Total:</th>
                                            <th class="text-end"><?php echo number_format($order['total'], 2); ?> RON</th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                            
                            <?php if ($order['puncte_folosite'] > 0 || $order['puncte_castigate'] > 0): ?>
                                <div class="mt-3">
                                    <h6 class="fw-bold">Puncte de Fidelitate</h6>
                                    <?php if ($order['puncte_folosite'] > 0): ?>
                                        <p class="mb-1"><strong>Puncte folosite:</strong> <?php echo $order['puncte_folosite']; ?> puncte</p>
                                    <?php endif; ?>
                                    <?php if ($order['puncte_castigate'] > 0): ?>
                                        <p class="mb-0"><strong>Puncte câștigate:</strong> <?php echo $order['puncte_castigate']; ?> puncte</p>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($voucher): ?>
                                <div class="mt-3">
                                    <h6 class="fw-bold">Voucher Aplicat</h6>
                                    <p class="mb-1"><strong>Cod:</strong> <?php echo htmlspecialchars($voucher['cod']); ?></p>
                                    <p class="mb-0"><strong>Valoare:</strong> 
                                        <?php echo $voucher['tip'] === 'procent' ? $voucher['valoare'] . '%' : number_format($voucher['valoare'], 2) . ' RON'; ?>
                                    </p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Order Status and Actions -->
                <div class="col-lg-4">
                    <!-- Status Update -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">Actualizare Status</h5>
                        </div>
                        <div class="card-body">
                            <form method="post" action="">
                                <div class="mb-3">
                                    <label for="status" class="form-label">Status Comandă</label>
                                    <select class="form-select" id="status" name="status">
                                        <option value="plasata" <?php echo $order['status'] === 'plasata' ? 'selected' : ''; ?>>Plasată</option>
                                        <option value="procesata" <?php echo $order['status'] === 'procesata' ? 'selected' : ''; ?>>Procesată</option>
                                        <option value="in_livrare" <?php echo $order['status'] === 'in_livrare' ? 'selected' : ''; ?>>În livrare</option>
                                        <option value="livrata" <?php echo $order['status'] === 'livrata' ? 'selected' : ''; ?>>Livrată</option>
                                        <option value="anulata" <?php echo $order['status'] === 'anulata' ? 'selected' : ''; ?>>Anulată</option>
                                    </select>
                                </div>
                                <button type="submit" name="update_status" class="btn btn-primary w-100">
                                    <i class="bi bi-check-circle"></i> Actualizează Status
                                </button>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Order Timeline -->
                    <div class="card shadow-sm">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">Istoric Comandă</h5>
                        </div>
                        <div class="card-body">
                            <div class="timeline">
                                <div class="timeline-item <?php echo in_array($order['status'], ['plasata', 'procesata', 'in_livrare', 'livrata']) ? 'completed' : ($order['status'] === 'anulata' ? 'canceled' : ''); ?>">
                                    <h6 class="mb-1">Comandă plasată</h6>
                                    <p class="text-muted small mb-0">
                                        <?php echo date('d.m.Y H:i', strtotime($order['data_plasare'])); ?>
                                    </p>
                                </div>
                                
                                <div class="timeline-item <?php echo in_array($order['status'], ['procesata', 'in_livrare', 'livrata']) ? 'completed' : ($order['status'] === 'anulata' ? 'canceled' : ''); ?>">
                                    <h6 class="mb-1">Comandă procesată</h6>
                                    <p class="text-muted small mb-0">
                                        <?php echo $order['data_procesare'] ? date('d.m.Y H:i', strtotime($order['data_procesare'])) : 'În așteptare'; ?>
                                    </p>
                                </div>
                                
                                <div class="timeline-item <?php echo in_array($order['status'], ['in_livrare', 'livrata']) ? 'completed' : ($order['status'] === 'anulata' ? 'canceled' : ''); ?>">
                                    <h6 class="mb-1">În livrare</h6>
                                    <p class="text-muted small mb-0">
                                        <?php echo $order['status'] === 'in_livrare' ? 'În curs' : ($order['status'] === 'livrata' ? 'Finalizat' : 'În așteptare'); ?>
                                    </p>
                                </div>
                                
                                <div class="timeline-item <?php echo $order['status'] === 'livrata' ? 'completed' : ($order['status'] === 'anulata' ? 'canceled' : ''); ?>">
                                    <h6 class="mb-1">Comandă livrată</h6>
                                    <p class="text-muted small mb-0">
                                        <?php echo $order['data_livrare'] ? date('d.m.Y H:i', strtotime($order['data_livrare'])) : 'În așteptare'; ?>
                                    </p>
                                </div>
                                
                                <?php if ($order['status'] === 'anulata'): ?>
                                    <div class="timeline-item canceled">
                                        <h6 class="mb-1">Comandă anulată</h6>
                                        <p class="text-muted small mb-0">
                                            <?php echo date('d.m.Y H:i', strtotime($order['data_actualizare'])); ?>
                                        </p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-dark text-light py-4">
        <div class="container">
            <div class="text-center">
                <p class="mb-0">&copy; 2024 Gusturi Românești. Panou Administrare.</p>
            </div>
        </div>
    </footer>

    <!-- Bootstrap 5.3 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>