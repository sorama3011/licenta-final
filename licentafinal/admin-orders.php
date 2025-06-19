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

// Get filter parameters
$search = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';
$status = isset($_GET['status']) ? sanitize_input($_GET['status']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10; // Items per page
$offset = ($page - 1) * $limit;

// Build query
$query = "SELECT c.id, c.numar_comanda, c.status, c.total, c.data_plasare, 
          CONCAT(u.prenume, ' ', u.nume) as client_name
          FROM comenzi c
          JOIN utilizatori u ON c.user_id = u.id
          WHERE 1=1";

$count_query = "SELECT COUNT(*) as total FROM comenzi c 
               JOIN utilizatori u ON c.user_id = u.id
               WHERE 1=1";

$params = [];
$types = "";

// Add search filter
if (!empty($search)) {
    $query .= " AND (c.numar_comanda LIKE ? OR CONCAT(u.prenume, ' ', u.nume) LIKE ?)";
    $count_query .= " AND (c.numar_comanda LIKE ? OR CONCAT(u.prenume, ' ', u.nume) LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= "ss";
}

// Add status filter
if (!empty($status)) {
    $query .= " AND c.status = ?";
    $count_query .= " AND c.status = ?";
    $params[] = $status;
    $types .= "s";
}

// Add sorting
$query .= " ORDER BY c.data_plasare DESC";

// Add pagination
$query .= " LIMIT ?, ?";
$params[] = $offset;
$params[] = $limit;
$types .= "ii";

// Prepare and execute count query
$count_stmt = mysqli_prepare($conn, $count_query);
if (!empty($params) && !empty($types)) {
    // Remove the last two parameters (offset and limit) for count query
    $count_params = array_slice($params, 0, -2);
    $count_types = substr($types, 0, -2);
    if (!empty($count_params)) {
        mysqli_stmt_bind_param($count_stmt, $count_types, ...$count_params);
    }
}
mysqli_stmt_execute($count_stmt);
$count_result = mysqli_stmt_get_result($count_stmt);
$count_row = mysqli_fetch_assoc($count_result);
$total_records = $count_row['total'];
$total_pages = ceil($total_records / $limit);

// Prepare and execute main query
$stmt = mysqli_prepare($conn, $query);
if (!empty($params) && !empty($types)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
?>

<!doctype html>
<html lang="ro">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Gestionare Comenzi - Gusturi Românești</title>
    <meta name="description" content="Panou de administrare pentru gestionarea comenzilor pe platforma Gusturi Românești.">
    
    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="admin-dashboard.css">
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
                    <h1 class="h3 mb-0">Gestionare Comenzi</h1>
                </div>
                <div class="col-md-4 text-md-end mt-3 mt-md-0">
                    <span class="badge bg-light text-dark">
                        <i class="bi bi-person-circle me-1"></i>
                        <?php echo $_SESSION['user_name']; ?>
                    </span>
                </div>
            </div>
        </div>
    </section>

    <!-- Orders Content -->
    <section class="py-5">
        <div class="container">
            <!-- Filters -->
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <form action="admin-orders.php" method="GET" class="row g-3">
                        <div class="col-md-5">
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-search"></i></span>
                                <input type="text" class="form-control" name="search" placeholder="Caută după nr. comandă sau client" value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <select class="form-select" name="status">
                                <option value="">Toate statusurile</option>
                                <option value="plasata" <?php echo $status === 'plasata' ? 'selected' : ''; ?>>Plasată</option>
                                <option value="procesata" <?php echo $status === 'procesata' ? 'selected' : ''; ?>>Procesată</option>
                                <option value="in_livrare" <?php echo $status === 'in_livrare' ? 'selected' : ''; ?>>În livrare</option>
                                <option value="livrata" <?php echo $status === 'livrata' ? 'selected' : ''; ?>>Livrată</option>
                                <option value="anulata" <?php echo $status === 'anulata' ? 'selected' : ''; ?>>Anulată</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-filter"></i> Filtrează
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Orders Table -->
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Lista Comenzilor</h5>
                        <span class="badge bg-primary"><?php echo $total_records; ?> comenzi</span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nr. Comandă</th>
                                    <th>Client</th>
                                    <th>Data</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                    <th class="text-center">Acțiuni</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (mysqli_num_rows($result) > 0): ?>
                                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                        <tr class="order-row" data-order-id="<?php echo $row['id']; ?>">
                                            <td><?php echo $row['id']; ?></td>
                                            <td><?php echo htmlspecialchars($row['numar_comanda']); ?></td>
                                            <td><?php echo htmlspecialchars($row['client_name']); ?></td>
                                            <td><?php echo date('d.m.Y H:i', strtotime($row['data_plasare'])); ?></td>
                                            <td><?php echo number_format($row['total'], 2); ?> RON</td>
                                            <td>
                                                <?php
                                                $status_class = '';
                                                $status_text = '';
                                                
                                                switch ($row['status']) {
                                                    case 'plasata':
                                                        $status_class = 'bg-info text-dark';
                                                        $status_text = 'Plasată';
                                                        break;
                                                    case 'procesata':
                                                        $status_class = 'bg-primary';
                                                        $status_text = 'Procesată';
                                                        break;
                                                    case 'in_livrare':
                                                        $status_class = 'bg-warning text-dark';
                                                        $status_text = 'În livrare';
                                                        break;
                                                    case 'livrata':
                                                        $status_class = 'bg-success';
                                                        $status_text = 'Livrată';
                                                        break;
                                                    case 'anulata':
                                                        $status_class = 'bg-danger';
                                                        $status_text = 'Anulată';
                                                        break;
                                                    default:
                                                        $status_class = 'bg-secondary';
                                                        $status_text = 'Necunoscut';
                                                }
                                                ?>
                                                <span class="badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                                            </td>
                                            <td class="text-center">
                                                <a href="detalii-comanda.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-primary me-1" title="Vezi detalii">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <a href="javascript:void(0);" onclick="printOrder(<?php echo $row['id']; ?>)" class="btn btn-sm btn-outline-secondary" title="Printează comanda">
                                                    <i class="bi bi-printer"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-4">
                                            <i class="bi bi-inbox text-muted" style="font-size: 2rem;"></i>
                                            <p class="mt-2">Nu s-au găsit comenzi care să corespundă criteriilor de căutare.</p>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <nav aria-label="Page navigation" class="mt-4">
                            <ul class="pagination justify-content-center">
                                <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>" aria-label="Previous">
                                        <span aria-hidden="true">&laquo;</span>
                                    </a>
                                </li>
                                
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                                
                                <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>" aria-label="Next">
                                        <span aria-hidden="true">&raquo;</span>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                    <?php endif; ?>
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
    <script>
        // Make entire row clickable
        document.querySelectorAll('.order-row').forEach(row => {
            row.addEventListener('click', function(e) {
                // Don't trigger if clicking on action buttons
                if (!e.target.closest('a')) {
                    const orderId = this.getAttribute('data-order-id');
                    window.location.href = 'detalii-comanda.php?id=' + orderId;
                }
            });
            
            // Add pointer cursor
            row.style.cursor = 'pointer';
        });
        
        // Print order function
        function printOrder(orderId) {
            // Open print view in new window
            window.open('print-comanda.php?id=' + orderId, '_blank');
        }
    </script>
</body>
</html>