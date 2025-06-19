<?php
// Include database configuration
require_once 'db-config.php';

// Start session
session_start();

// Check if order number is provided
if (!isset($_GET['order']) && !isset($_SESSION['order_number'])) {
    header("Location: index.html");
    exit;
}

// Get order number
$order_number = isset($_GET['order']) ? sanitize_input($_GET['order']) : $_SESSION['order_number'];

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Get order details
$order_sql = "SELECT c.id, c.numar_comanda, c.status, c.subtotal, c.transport, c.discount, c.total, 
             c.metoda_plata, c.status_plata, c.puncte_castigate, c.data_plasare
             FROM comenzi c
             WHERE c.numar_comanda = '$order_number' AND c.user_id = $user_id";
$order_result = mysqli_query($conn, $order_sql);

// Check if order exists
if (mysqli_num_rows($order_result) == 0) {
    $_SESSION['error_message'] = 'Comanda nu a fost găsită.';
    header("Location: index.html");
    exit;
}

$order = mysqli_fetch_assoc($order_result);
$order_id = $order['id'];

// Get order items
$items_sql = "SELECT cp.nume_produs, cp.pret, cp.cantitate, cp.subtotal
             FROM comenzi_produse cp
             WHERE cp.comanda_id = $order_id";
$items_result = mysqli_query($conn, $items_sql);

// Clear session variables
unset($_SESSION['order_placed']);
unset($_SESSION['order_number']);
unset($_SESSION['checkout_error']);
?>

<!doctype html>
<html lang="ro">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Comandă Finalizată - Gusturi Românești</title>
    <meta name="description" content="Comanda ta a fost plasată cu succes. Mulțumim pentru achiziție!">
    
    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="assistant-bot.css">
    
    <!-- Confetti JS for celebration effect -->
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.5.1/dist/confetti.browser.min.js"></script>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.html">
                🇷🇴 Gusturi Românești
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.html">Acasă</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="products.html">Produse</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="offers.html">Oferte</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="about.html">Despre Noi</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="contact.html">Contact</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="login.html">
                            <i class="bi bi-person"></i> Cont
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link position-relative" href="cart.html">
                            <i class="bi bi-basket"></i> Coș
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-warning text-dark" id="cart-count">0</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Page Header -->
    <section class="bg-primary text-white py-4" style="margin-top: 76px;">
        <div class="container">
            <h1 class="h3 mb-0">Comandă Finalizată</h1>
        </div>
    </section>

    <!-- Order Success Content -->
    <section class="py-5">
        <div class="container">
            <div class="card shadow-sm border-success">
                <div class="card-body text-center py-5">
                    <div class="mb-4">
                        <i class="bi bi-check-circle-fill text-success" style="font-size: 4rem;"></i>
                    </div>
                    <h2 class="mb-3">Comandă Plasată cu Succes!</h2>
                    <p class="lead">Mulțumim pentru comanda ta. Numărul comenzii este: <strong><?php echo $order_number; ?></strong></p>
                    <p>Vei primi în curând un email cu confirmarea și detaliile comenzii.</p>
                    
                    <?php if ($order['metoda_plata'] === 'transfer'): ?>
                    <div class="alert alert-info mt-4 mx-auto" style="max-width: 500px;">
                        <h5><i class="bi bi-info-circle me-2"></i>Detalii pentru plata prin transfer bancar:</h5>
                        <p class="mb-1">Beneficiar: <strong>SC Gusturi Românești SRL</strong></p>
                        <p class="mb-1">IBAN: <strong>RO49AAAA1B31007593840000</strong></p>
                        <p class="mb-1">Banca: <strong>Banca Tradițională Română</strong></p>
                        <p class="mb-0">Mențiune: <strong>Comandă <?php echo $order_number; ?></strong></p>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($order['puncte_castigate'] > 0): ?>
                    <div class="alert alert-success mt-4 mx-auto" style="max-width: 500px;">
                        <h5><i class="bi bi-star-fill me-2"></i>Ai câștigat puncte de fidelitate!</h5>
                        <p class="mb-0">Pentru această comandă ai primit <strong><?php echo $order['puncte_castigate']; ?> puncte</strong> de fidelitate care vor fi adăugate în contul tău.</p>
                    </div>
                    <?php endif; ?>
                    
                    <div class="mt-4">
                        <a href="client-dashboard.html#order-history" class="btn btn-primary me-2">
                            <i class="bi bi-clock-history me-1"></i> Vezi Istoricul Comenzilor
                        </a>
                        <a href="products.html" class="btn btn-outline-primary">
                            <i class="bi bi-basket me-1"></i> Continuă Cumpărăturile
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Order Details -->
            <div class="card shadow-sm mt-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Detalii Comandă</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Număr Comandă:</strong> <?php echo $order_number; ?></p>
                            <p class="mb-1"><strong>Data Plasării:</strong> <?php echo date('d.m.Y H:i', strtotime($order['data_plasare'])); ?></p>
                            <p class="mb-1"><strong>Status:</strong> <span class="badge bg-primary"><?php echo ucfirst($order['status']); ?></span></p>
                            <p class="mb-0"><strong>Metoda de Plată:</strong> 
                                <?php 
                                    switch($order['metoda_plata']) {
                                        case 'card': echo 'Card de credit/debit'; break;
                                        case 'transfer': echo 'Transfer bancar'; break;
                                        case 'ramburs': echo 'Plata la livrare (Ramburs)'; break;
                                        default: echo ucfirst($order['metoda_plata']);
                                    }
                                ?>
                            </p>
                        </div>
                        <div class="col-md-6 text-md-end">
                            <p class="mb-1"><strong>Subtotal:</strong> <?php echo number_format($order['subtotal'], 2); ?> RON</p>
                            <?php if ($order['discount'] > 0): ?>
                            <p class="mb-1"><strong>Reducere:</strong> <?php echo number_format($order['discount'], 2); ?> RON</p>
                            <?php endif; ?>
                            <p class="mb-1"><strong>Transport:</strong> <?php echo $order['transport'] > 0 ? number_format($order['transport'], 2) . ' RON' : 'Gratuit'; ?></p>
                            <p class="mb-0"><strong>Total:</strong> <span class="fw-bold"><?php echo number_format($order['total'], 2); ?> RON</span></p>
                        </div>
                    </div>
                    
                    <h6 class="mb-3">Produse Comandate</h6>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Produs</th>
                                    <th class="text-center">Preț</th>
                                    <th class="text-center">Cantitate</th>
                                    <th class="text-end">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
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
                                    <th class="text-end text-success">-<?php echo number_format($order['discount'], 2); ?> RON</th>
                                </tr>
                                <?php endif; ?>
                                <tr>
                                    <th colspan="3" class="text-end">Transport:</th>
                                    <th class="text-end"><?php echo $order['transport'] > 0 ? number_format($order['transport'], 2) . ' RON' : 'Gratuit'; ?></th>
                                </tr>
                                <tr>
                                    <th colspan="3" class="text-end">Total:</th>
                                    <th class="text-end"><?php echo number_format($order['total'], 2); ?> RON</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-dark text-light py-5">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-4">
                    <h5>Gusturi Românești</h5>
                    <p>Cea mai mare platformă online de produse tradiționale românești.</p>
                    <div class="social-links">
                        <a href="#" class="text-light me-3"><i class="bi bi-facebook"></i></a>
                        <a href="#" class="text-light me-3"><i class="bi bi-instagram"></i></a>
                        <a href="#" class="text-light me-3"><i class="bi bi-youtube"></i></a>
                    </div>
                </div>
                <div class="col-lg-2">
                    <h6>Navigare</h6>
                    <ul class="list-unstyled">
                        <li><a href="index.html" class="text-light text-decoration-none">Acasă</a></li>
                        <li><a href="products.html" class="text-light text-decoration-none">Produse</a></li>
                        <li><a href="offers.html" class="text-light text-decoration-none">Oferte</a></li>
                        <li><a href="about.html" class="text-light text-decoration-none">Despre Noi</a></li>
                        <li><a href="contact.html" class="text-light text-decoration-none">Contact</a></li>
                    </ul>
                </div>
                <div class="col-lg-2">
                    <h6>Cont</h6>
                    <ul class="list-unstyled">
                        <li><a href="login.html" class="text-light text-decoration-none">Autentificare</a></li>
                        <li><a href="cart.html" class="text-light text-decoration-none">Coșul Meu</a></li>
                    </ul>
                </div>
                <div class="col-lg-2">
                    <h6>Informații</h6>
                    <ul class="list-unstyled">
                        <li><a href="privacy.html" class="text-light text-decoration-none">Politica de Confidențialitate</a></li>
                        <li><a href="terms.html" class="text-light text-decoration-none">Termeni și Condiții</a></li>
                    </ul>
                </div>
                <div class="col-lg-2">
                    <h6>Contact</h6>
                    <p class="small mb-1">📍 București, România</p>
                    <p class="small mb-1">📞 +40 721 234 567</p>
                    <p class="small">✉️ contact@gusturi-romanesti.ro</p>
                </div>
            </div>
            <hr class="my-4">
            <div class="text-center">
                <p class="mb-0">&copy; 2024 Gusturi Românești. Toate drepturile rezervate.</p>
            </div>
        </div>
    </footer>

    <!-- Bootstrap 5.3 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="main.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Clear cart in localStorage
            localStorage.setItem('cart', JSON.stringify([]));
            
            // Reset progress bar in localStorage
            localStorage.setItem('cartSubtotal', '0');
            localStorage.setItem('freeShippingCelebrated', 'false');
            localStorage.setItem('previousSubtotal', '0');
            
            // Update cart count
            document.getElementById('cart-count').textContent = '0';
            
            // Confetti celebration effect
            confetti({
                particleCount: 100,
                spread: 70,
                origin: { y: 0.6 }
            });
        });
    </script>
</body>
</html>