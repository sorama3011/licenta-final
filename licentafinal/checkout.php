
<?php
require_once 'db-config.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?redirect=checkout.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Get user information
$user_sql = "SELECT * FROM utilizatori WHERE id = $user_id";
$user_result = mysqli_query($conn, $user_sql);
$user = mysqli_fetch_assoc($user_result);

// Get user addresses
$addresses_sql = "SELECT * FROM adrese WHERE user_id = $user_id ORDER BY id";
$addresses_result = mysqli_query($conn, $addresses_sql);
$addresses = [];
while ($addr = mysqli_fetch_assoc($addresses_result)) {
    $addresses[] = $addr;
}

// If no addresses, redirect to add address
if (empty($addresses)) {
    header('Location: account.html?tab=addresses&message=Adaugă o adresă pentru a continua');
    exit;
}
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finalizare Comandă - Gusturi Românești</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-danger">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.html">
                <i class="bi bi-basket-fill me-2"></i>Gusturi Românești
            </a>
        </div>
    </nav>

    <div class="container my-5">
        <div class="row">
            <div class="col-lg-8">
                <h2>Finalizare Comandă</h2>
                
                <form id="checkout-form" method="post" action="api/order.php">
                    <input type="hidden" name="action" value="place_order">
                    
                    <!-- Delivery Address -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5><i class="bi bi-truck me-2"></i>Adresa de Livrare</h5>
                        </div>
                        <div class="card-body">
                            <?php foreach ($addresses as $index => $addr): ?>
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="radio" name="adresa_livrare" 
                                       id="addr_<?php echo $addr['id']; ?>" value="<?php echo $addr['id']; ?>"
                                       <?php echo $index === 0 ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="addr_<?php echo $addr['id']; ?>">
                                    <strong><?php echo htmlspecialchars($addr['nume_complet'] ?? $user['prenume'] . ' ' . $user['nume']); ?></strong><br>
                                    <?php echo htmlspecialchars($addr['adresa']); ?><br>
                                    <?php echo htmlspecialchars($addr['oras'] . ', ' . $addr['judet'] . ' ' . $addr['cod_postal']); ?><br>
                                    <small class="text-muted">Tel: <?php echo htmlspecialchars($addr['telefon']); ?></small>
                                </label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Payment Method -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5><i class="bi bi-credit-card me-2"></i>Metoda de Plată</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="radio" name="metoda_plata" id="ramburs" value="ramburs" checked>
                                <label class="form-check-label" for="ramburs">
                                    <i class="bi bi-cash me-2"></i>Ramburs (plata la livrare)
                                </label>
                            </div>
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="radio" name="metoda_plata" id="card" value="card">
                                <label class="form-check-label" for="card">
                                    <i class="bi bi-credit-card me-2"></i>Card bancar
                                </label>
                            </div>
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="radio" name="metoda_plata" id="transfer" value="transfer">
                                <label class="form-check-label" for="transfer">
                                    <i class="bi bi-bank me-2"></i>Transfer bancar
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Order Notes -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5><i class="bi bi-chat-text me-2"></i>Observații</h5>
                        </div>
                        <div class="card-body">
                            <textarea class="form-control" name="observatii" rows="3" 
                                      placeholder="Observații speciale pentru comandă (opțional)"></textarea>
                        </div>
                    </div>
                </form>
            </div>

            <div class="col-lg-4">
                <!-- Order Summary -->
                <div class="card">
                    <div class="card-header">
                        <h5><i class="bi bi-receipt me-2"></i>Sumar Comandă</h5>
                    </div>
                    <div class="card-body">
                        <div id="order-summary">
                            <div class="text-center">
                                <div class="spinner-border" role="status">
                                    <span class="visually-hidden">Se încarcă...</span>
                                </div>
                                <p class="mt-2">Se încarcă coșul...</p>
                            </div>
                        </div>
                        
                        <div id="order-totals" style="display: none;">
                            <hr>
                            <div class="d-flex justify-content-between">
                                <span>Subtotal:</span>
                                <span id="subtotal">0.00 RON</span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span>Transport:</span>
                                <span id="shipping">15.00 RON</span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span>Discount:</span>
                                <span id="discount">0.00 RON</span>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between fw-bold">
                                <span>Total:</span>
                                <span id="total">0.00 RON</span>
                            </div>
                            
                            <button type="submit" form="checkout-form" class="btn btn-danger w-100 mt-3" id="place-order-btn">
                                <i class="bi bi-check-circle me-2"></i>Plasează Comanda
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="main.js"></script>
    <script>
        // Load cart summary
        document.addEventListener('DOMContentLoaded', async function() {
            try {
                const response = await fetch('api/cart.php?action=get_cart');
                const result = await response.json();
                
                if (result.success) {
                    displayOrderSummary(result);
                } else {
                    document.getElementById('order-summary').innerHTML = 
                        '<div class="alert alert-warning">Coșul este gol</div>';
                }
            } catch (error) {
                console.error('Error loading cart:', error);
                document.getElementById('order-summary').innerHTML = 
                    '<div class="alert alert-danger">Eroare la încărcarea coșului</div>';
            }
        });

        function displayOrderSummary(data) {
            const container = document.getElementById('order-summary');
            
            if (data.items.length === 0) {
                container.innerHTML = '<div class="alert alert-warning">Coșul este gol</div>';
                return;
            }

            let html = '';
            data.items.forEach(item => {
                html += `
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div>
                            <small class="fw-bold">${item.nume}</small><br>
                            <small class="text-muted">${item.cantitate} x ${parseFloat(item.pret).toFixed(2)} RON</small>
                        </div>
                        <span class="fw-bold">${(item.pret * item.cantitate).toFixed(2)} RON</span>
                    </div>
                `;
            });

            container.innerHTML = html;
            
            // Update totals
            document.getElementById('subtotal').textContent = data.subtotal.toFixed(2) + ' RON';
            document.getElementById('shipping').textContent = (data.subtotal >= 150 ? 'Gratuit' : '15.00 RON');
            document.getElementById('discount').textContent = data.discount.toFixed(2) + ' RON';
            document.getElementById('total').textContent = data.total.toFixed(2) + ' RON';
            
            document.getElementById('order-totals').style.display = 'block';
        }

        // Handle form submission
        document.getElementById('checkout-form').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const btn = document.getElementById('place-order-btn');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Se procesează...';
            btn.disabled = true;

            try {
                const formData = new FormData(this);
                const response = await fetch('api/order.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    window.location.href = result.redirect || 'order-success.php?order=' + result.order_number;
                } else {
                    alert('Eroare: ' + result.message);
                }
            } catch (error) {
                console.error('Error placing order:', error);
                alert('A apărut o eroare. Vă rugăm să încercați din nou.');
            } finally {
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        });
    </script>
</body>
</html>
