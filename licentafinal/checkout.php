<?php
// DEBUG: Show all errors
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include database configuration
require_once 'db-config.php';

// Start session
session_start();

// Debug: Show session contents
var_dump($_SESSION);

if (!isset($_SESSION['user_id'])) {
    die('<div style="color:red">[DEBUG] User not logged in! Cannot proceed to checkout.</div>');
}
$user_id = $_SESSION['user_id'];

// Get user's addresses
$addresses_sql = "SELECT id, nume_adresa, adresa, oras, judet, cod_postal, telefon, implicit 
                 FROM adrese 
                 WHERE user_id = $user_id 
                 ORDER BY implicit DESC, data_adaugare DESC";
$addresses_result = mysqli_query($conn, $addresses_sql);

// Check if cart is empty
$cart_check_sql = "SELECT COUNT(*) as count FROM cos_cumparaturi WHERE user_id = $user_id";
$cart_check_result = mysqli_query($conn, $cart_check_sql);
$cart_check_row = mysqli_fetch_assoc($cart_check_result);

if ($cart_check_row['count'] == 0) {
    // Redirect to cart page if cart is empty
    header("Location: cart.html");
    exit;
}

// Get cart items and calculate totals
$cart_sql = "SELECT c.produs_id, c.cantitate, p.nume, p.pret, p.imagine, p.cantitate as greutate
            FROM cos_cumparaturi c
            JOIN produse p ON c.produs_id = p.id
            WHERE c.user_id = $user_id";
$cart_result = mysqli_query($conn, $cart_sql);

$cart_items = [];
$subtotal = 0;
$item_count = 0;

while ($item = mysqli_fetch_assoc($cart_result)) {
    $item_subtotal = $item['pret'] * $item['cantitate'];
    $subtotal += $item_subtotal;
    $item_count += $item['cantitate'];
    
    $cart_items[] = [
        'id' => $item['produs_id'],
        'name' => $item['nume'],
        'price' => $item['pret'],
        'quantity' => $item['cantitate'],
        'subtotal' => $item_subtotal,
        'image' => $item['imagine'],
        'weight' => $item['greutate']
    ];
}

// Calculate shipping
$free_shipping_threshold = 150;
$shipping = ($subtotal >= $free_shipping_threshold) ? 0 : 15;

// Check for applied voucher
$voucher_discount = 0;
$voucher_id = null;

if (isset($_SESSION['applied_voucher'])) {
    $voucher = $_SESSION['applied_voucher'];
    $voucher_id = $voucher['id'];
    $voucher_discount = $voucher['discount'];
}

// Calculate total
$total = $subtotal - $voucher_discount + $shipping;

// Process form submission
$order_placed = false;
$order_number = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // DEBUG: Log POST data to file
    file_put_contents('debug_post.txt', print_r($_POST, true));

    // Get form data
    $address_id = isset($_POST['address_id']) ? (int)$_POST['address_id'] : 0;
    $payment_method = isset($_POST['payment_method']) ? $_POST['payment_method'] : '';
    $notes = isset($_POST['notes']) ? mysqli_real_escape_string($conn, $_POST['notes']) : '';
    
    // Validate input
    if ($address_id <= 0) {
        $error_message = 'Vă rugăm să selectați o adresă de livrare.';
    } elseif (empty($payment_method)) {
        $error_message = 'Vă rugăm să selectați o metodă de plată.';
    } else {
        // Start transaction
        mysqli_begin_transaction($conn);
        
        try {
            // Generate order number
            $order_number = 'GR-' . date('Ymd') . '-' . sprintf('%04d', rand(1, 9999));
            
            // Create order
            $order_sql = "INSERT INTO comenzi (user_id, numar_comanda, status, subtotal, transport, discount, total, 
                         metoda_plata, status_plata, adresa_livrare_id, adresa_facturare_id, voucher_id, 
                         observatii) 
                         VALUES ($user_id, '$order_number', 'plasata', $subtotal, $shipping, $voucher_discount, 
                         $total, '$payment_method', 'in_asteptare', $address_id, $address_id, " . 
                         ($voucher_id ? $voucher_id : "NULL") . ", '$notes')";
            
            if (!mysqli_query($conn, $order_sql)) {
                throw new Exception("Eroare la crearea comenzii: " . mysqli_error($conn));
            }
            
            $order_id = mysqli_insert_id($conn);
            
            // Add order items
            foreach ($cart_items as $item) {
                $order_item_sql = "INSERT INTO comenzi_produse (comanda_id, produs_id, nume_produs, pret, cantitate, subtotal) 
                                  VALUES ($order_id, {$item['id']}, '{$item['name']}', {$item['price']}, 
                                  {$item['quantity']}, {$item['subtotal']})";
                
                if (!mysqli_query($conn, $order_item_sql)) {
                    throw new Exception("Eroare la adăugarea produselor în comandă: " . mysqli_error($conn));
                }
                
                // Update product stock
                $update_stock_sql = "UPDATE produse SET stoc = stoc - {$item['quantity']} WHERE id = {$item['id']}";
                mysqli_query($conn, $update_stock_sql);
            }
            
            // Mark voucher as used if applied
            if ($voucher_id) {
                $update_voucher_sql = "UPDATE vouchere_utilizatori 
                                      SET utilizat = 1, comanda_id = $order_id, data_utilizare = NOW() 
                                      WHERE voucher_id = $voucher_id AND user_id = $user_id";
                mysqli_query($conn, $update_voucher_sql);
                
                // Update voucher usage count
                $update_voucher_count_sql = "UPDATE vouchere 
                                           SET utilizari_curente = utilizari_curente + 1 
                                           WHERE id = $voucher_id";
                mysqli_query($conn, $update_voucher_count_sql);
                
                // Clear session voucher
                unset($_SESSION['applied_voucher']);
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
            
            // Set success flag
            $order_placed = true;
            
        } catch (Exception $e) {
            // Rollback transaction on error
            mysqli_rollback($conn);
            $error_message = 'A apărut o eroare la plasarea comenzii: ' . $e->getMessage();
        }
    }
}
?>

<!doctype html>
<html lang="ro">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Finalizare Comandă - Gusturi Românești</title>
    <meta name="description" content="Finalizează comanda ta de produse tradiționale românești.">
    
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
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-warning text-dark" id="cart-count"><?php echo $item_count; ?></span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Page Header -->
    <section class="bg-primary text-white py-4" style="margin-top: 76px;">
        <div class="container">
            <h1 class="h3 mb-0">Finalizare Comandă</h1>
        </div>
    </section>

    <!-- Order Success Message -->
    <?php if ($order_placed): ?>
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
                    
                    <?php if ($payment_method === 'transfer'): ?>
                    <div class="alert alert-info mt-4 mx-auto" style="max-width: 500px;">
                        <h5><i class="bi bi-info-circle me-2"></i>Detalii pentru plata prin transfer bancar:</h5>
                        <p class="mb-1">Beneficiar: <strong>SC Gusturi Românești SRL</strong></p>
                        <p class="mb-1">IBAN: <strong>RO49AAAA1B31007593840000</strong></p>
                        <p class="mb-1">Banca: <strong>Banca Tradițională Română</strong></p>
                        <p class="mb-0">Mențiune: <strong>Comandă <?php echo $order_number; ?></strong></p>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($points_earned > 0): ?>
                    <div class="alert alert-success mt-4 mx-auto" style="max-width: 500px;">
                        <h5><i class="bi bi-star-fill me-2"></i>Ai câștigat puncte de fidelitate!</h5>
                        <p class="mb-0">Pentru această comandă ai primit <strong><?php echo $points_earned; ?> puncte</strong> de fidelitate care vor fi adăugate în contul tău.</p>
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
        </div>
    </section>
    
    <script>
        // Clear cart in localStorage
        localStorage.setItem('cart', JSON.stringify([]));
        
        // Reset progress bar in localStorage
        localStorage.setItem('cartSubtotal', '0');
        
        // Update cart count
        document.getElementById('cart-count').textContent = '0';
        
        // Confetti celebration effect
        confetti({
            particleCount: 100,
            spread: 70,
            origin: { y: 0.6 }
        });
    </script>
    
    <?php else: ?>
    <!-- Checkout Content -->
    <section class="py-5">
        <div class="container">
            <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger mb-4">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <?php echo $error_message; ?>
            </div>
            <?php endif; ?>
            
            <div class="row">
                <!-- Checkout Form -->
                <div class="col-lg-8">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">Adresa de Livrare</h5>
                        </div>
                        <div class="card-body">
                            <form id="checkoutForm" method="post" action="checkout.php">
                                <?php if (mysqli_num_rows($addresses_result) > 0): ?>
                                <div class="mb-4">
                                    <h6>Adresele Mele Salvate</h6>
                                    <div class="row g-3">
                                        <?php while ($address = mysqli_fetch_assoc($addresses_result)): ?>
                                        <div class="col-md-6">
                                            <div class="form-check address-card border rounded p-3">
                                                <input class="form-check-input" type="radio" name="address_id" id="address<?php echo $address['id']; ?>" value="<?php echo $address['id']; ?>" <?php echo $address['implicit'] ? 'checked' : ''; ?>>
                                                <label class="form-check-label w-100" for="address<?php echo $address['id']; ?>">
                                                    <?php if (!empty($address['nume_adresa'])): ?>
                                                    <strong><?php echo htmlspecialchars($address['nume_adresa']); ?></strong><br>
                                                    <?php endif; ?>
                                                    <?php echo htmlspecialchars($address['adresa']); ?><br>
                                                    <?php echo htmlspecialchars($address['oras']) . ', ' . htmlspecialchars($address['judet']) . ', ' . htmlspecialchars($address['cod_postal']); ?>
                                                    <?php if (!empty($address['telefon'])): ?>
                                                    <br>Tel: <?php echo htmlspecialchars($address['telefon']); ?>
                                                    <?php endif; ?>
                                                    <?php if ($address['implicit']): ?>
                                                    <span class="badge bg-primary mt-2">Adresă Implicită</span>
                                                    <?php endif; ?>
                                                </label>
                                            </div>
                                        </div>
                                        <?php endwhile; ?>
                                    </div>
                                </div>
                                <?php else: ?>
                                <div class="alert alert-warning">
                                    <i class="bi bi-exclamation-triangle me-2"></i>
                                    Nu ai nicio adresă salvată. Te rugăm să adaugi o adresă nouă.
                                </div>
                                <?php endif; ?>
                                
                                <div class="mb-3">
                                    <button type="button" class="btn btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#newAddressForm">
                                        <i class="bi bi-plus-lg me-1"></i> Adaugă Adresă Nouă
                                    </button>
                                </div>
                                
                                <div class="collapse" id="newAddressForm">
                                    <div class="card card-body bg-light mb-4">
                                        <h6 class="mb-3">Adresă Nouă</h6>
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label for="addressName" class="form-label">Nume Adresă (opțional)</label>
                                                <input type="text" class="form-control" id="addressName" name="address_name" placeholder="ex: Acasă, Birou">
                                            </div>
                                            <div class="col-md-6">
                                                <label for="phone" class="form-label">Telefon</label>
                                                <input type="tel" class="form-control" id="phone" name="phone" placeholder="+40 7XX XXX XXX">
                                            </div>
                                            <div class="col-12">
                                                <label for="address" class="form-label">Adresă</label>
                                                <input type="text" class="form-control" id="address" name="address" placeholder="Strada, Număr, Bloc, Apartament">
                                            </div>
                                            <div class="col-md-5">
                                                <label for="city" class="form-label">Oraș</label>
                                                <input type="text" class="form-control" id="city" name="city">
                                            </div>
                                            <div class="col-md-4">
                                                <label for="county" class="form-label">Județ</label>
                                                <select class="form-select" id="county" name="county">
                                                    <option value="">Selectează...</option>
                                                    <option value="Alba">Alba</option>
                                                    <option value="Arad">Arad</option>
                                                    <option value="Argeș">Argeș</option>
                                                    <option value="Bacău">Bacău</option>
                                                    <option value="Bihor">Bihor</option>
                                                    <option value="Bistrița-Năsăud">Bistrița-Năsăud</option>
                                                    <option value="Botoșani">Botoșani</option>
                                                    <option value="Brașov">Brașov</option>
                                                    <option value="Brăila">Brăila</option>
                                                    <option value="București">București</option>
                                                    <option value="Buzău">Buzău</option>
                                                    <option value="Caraș-Severin">Caraș-Severin</option>
                                                    <option value="Călărași">Călărași</option>
                                                    <option value="Cluj">Cluj</option>
                                                    <option value="Constanța">Constanța</option>
                                                    <option value="Covasna">Covasna</option>
                                                    <option value="Dâmbovița">Dâmbovița</option>
                                                    <option value="Dolj">Dolj</option>
                                                    <option value="Galați">Galați</option>
                                                    <option value="Giurgiu">Giurgiu</option>
                                                    <option value="Gorj">Gorj</option>
                                                    <option value="Harghita">Harghita</option>
                                                    <option value="Hunedoara">Hunedoara</option>
                                                    <option value="Ialomița">Ialomița</option>
                                                    <option value="Iași">Iași</option>
                                                    <option value="Ilfov">Ilfov</option>
                                                    <option value="Maramureș">Maramureș</option>
                                                    <option value="Mehedinți">Mehedinți</option>
                                                    <option value="Mureș">Mureș</option>
                                                    <option value="Neamț">Neamț</option>
                                                    <option value="Olt">Olt</option>
                                                    <option value="Prahova">Prahova</option>
                                                    <option value="Satu Mare">Satu Mare</option>
                                                    <option value="Sălaj">Sălaj</option>
                                                    <option value="Sibiu">Sibiu</option>
                                                    <option value="Suceava">Suceava</option>
                                                    <option value="Teleorman">Teleorman</option>
                                                    <option value="Timiș">Timiș</option>
                                                    <option value="Tulcea">Tulcea</option>
                                                    <option value="Vaslui">Vaslui</option>
                                                    <option value="Vâlcea">Vâlcea</option>
                                                    <option value="Vrancea">Vrancea</option>
                                                </select>
                                            </div>
                                            <div class="col-md-3">
                                                <label for="postalCode" class="form-label">Cod Poștal</label>
                                                <input type="text" class="form-control" id="postalCode" name="postal_code">
                                            </div>
                                            <div class="col-12">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="defaultAddress" name="default_address" value="1">
                                                    <label class="form-check-label" for="defaultAddress">
                                                        Setează ca adresă implicită
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="card shadow-sm mb-4">
                                    <div class="card-header bg-light">
                                        <h5 class="mb-0">Metoda de Plată</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-check mb-3">
                                            <input class="form-check-input" type="radio" name="payment_method" id="paymentCOD" value="ramburs" checked>
                                            <label class="form-check-label" for="paymentCOD">
                                                <i class="bi bi-cash me-2"></i>Plata la livrare (Ramburs)
                                            </label>
                                        </div>
                                        <div class="form-check mb-3">
                                            <input class="form-check-input" type="radio" name="payment_method" id="paymentTransfer" value="transfer">
                                            <label class="form-check-label" for="paymentTransfer">
                                                <i class="bi bi-bank me-2"></i>Transfer bancar
                                            </label>
                                        </div>
                                        <div class="form-check mb-3 opacity-50">
                                            <input class="form-check-input" type="radio" name="payment_method" id="paymentCard" value="card" disabled>
                                            <label class="form-check-label" for="paymentCard">
                                                <i class="bi bi-credit-card me-2"></i>Plata cu cardul (temporar indisponibil)
                                            </label>
                                        </div>
                                        
                                        <div id="transferDetails" class="alert alert-info mt-3" style="display: none;">
                                            <h6><i class="bi bi-info-circle me-2"></i>Detalii pentru plata prin transfer bancar:</h6>
                                            <p class="mb-1">Beneficiar: <strong>SC Gusturi Românești SRL</strong></p>
                                            <p class="mb-1">IBAN: <strong>RO49AAAA1B31007593840000</strong></p>
                                            <p class="mb-1">Banca: <strong>Banca Tradițională Română</strong></p>
                                            <p class="mb-0">După plasarea comenzii, veți primi un email cu toate detaliile necesare pentru efectuarea plății.</p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-4">
                                    <label for="notes" class="form-label">Observații (opțional)</label>
                                    <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Instrucțiuni speciale pentru livrare, etc."></textarea>
                                </div>
                                
                                <input type="hidden" name="action" value="place_order">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="bi bi-check-circle me-2"></i>Plasează Comanda
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Order Summary -->
                <div class="col-lg-4">
                    <div class="card shadow-sm sticky-top" style="top: 100px;">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Sumar Comandă</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <h6>Produse (<?php echo $item_count; ?>)</h6>
                                <div class="list-group">
                                    <?php foreach ($cart_items as $item): ?>
                                    <div class="list-group-item border-0 px-0 py-2">
                                        <div class="d-flex">
                                            <div class="flex-shrink-0">
                                                <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="img-thumbnail" style="width: 50px; height: 50px; object-fit: cover;">
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <h6 class="mb-0"><?php echo htmlspecialchars($item['name']); ?></h6>
                                                <small class="text-muted"><?php echo $item['quantity']; ?> x <?php echo number_format($item['price'], 2); ?> RON</small>
                                            </div>
                                            <div class="text-end">
                                                <span><?php echo number_format($item['subtotal'], 2); ?> RON</span>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            
                            <hr>
                            
                            <div class="d-flex justify-content-between mb-2">
                                <span>Subtotal:</span>
                                <span><?php echo number_format($subtotal, 2); ?> RON</span>
                            </div>
                            
                            <?php if ($voucher_discount > 0): ?>
                            <div class="d-flex justify-content-between mb-2 text-success">
                                <span>Reducere voucher:</span>
                                <span>-<?php echo number_format($voucher_discount, 2); ?> RON</span>
                            </div>
                            <?php endif; ?>
                            
                            <div class="d-flex justify-content-between mb-2">
                                <span>Transport:</span>
                                <span><?php echo $shipping > 0 ? number_format($shipping, 2) . ' RON' : 'Gratuit'; ?></span>
                            </div>
                            
                            <hr>
                            
                            <div class="d-flex justify-content-between fw-bold">
                                <span>Total:</span>
                                <span><?php echo number_format($total, 2); ?> RON</span>
                            </div>
                            
                            <?php if ($shipping === 0): ?>
                            <div class="alert alert-success mt-3 mb-0">
                                <i class="bi bi-truck me-2"></i>
                                Transport gratuit
                            </div>
                            <?php else: ?>
                            <div class="alert alert-info mt-3 mb-0">
                                <i class="bi bi-info-circle me-2"></i>
                                Adaugă produse de încă <?php echo number_format($free_shipping_threshold - $subtotal, 2); ?> RON pentru transport gratuit
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>

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
    <script>
        // Show/hide transfer bank details
        document.addEventListener('DOMContentLoaded', function() {
            const paymentTransfer = document.getElementById('paymentTransfer');
            const transferDetails = document.getElementById('transferDetails');
            
            if (paymentTransfer && transferDetails) {
                paymentTransfer.addEventListener('change', function() {
                    transferDetails.style.display = this.checked ? 'block' : 'none';
                });
                
                // Check initial state
                transferDetails.style.display = paymentTransfer.checked ? 'block' : 'none';
            }
            
            // Handle form submission via AJAX
            const checkoutForm = document.getElementById('checkoutForm');
            if (checkoutForm) {
                checkoutForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    const formData = new FormData(this);
                    formData.append('action', 'place_order');
                    
                    // Show loading state
                    const submitButton = this.querySelector('button[type="submit"]');
                    const originalText = submitButton.innerHTML;
                    submitButton.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Procesăm comanda...';
                    submitButton.disabled = true;
                    
                    fetch('api/order.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Show success message and redirect
                            alert('Comanda a fost plasată cu succes! Numărul comenzii: ' + data.order_number);
                            window.location.href = 'order-success.php?order=' + data.order_number;
                        } else {
                            // Show error message
                            alert('Eroare: ' + data.message);
                            submitButton.innerHTML = originalText;
                            submitButton.disabled = false;
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('A apărut o eroare la plasarea comenzii. Vă rugăm să încercați din nou.');
                        submitButton.innerHTML = originalText;
                        submitButton.disabled = false;
                    });
                });
            }
        });
    </script>
</body>
</html>