<?php
// Include database configuration
require_once 'db-config.php';

// Initialize variables
$error = '';
$success = '';
$email = '';

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get email from form
    $email = sanitize_input($_POST['email']);
    
    // Validate email
    if (empty($email)) {
        $error = "Adresa de email este obligatorie.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Adresa de email nu este validă.";
    } else {
        // Check if email exists in database
        $sql = "SELECT id FROM utilizatori WHERE email = '$email' AND activ = 1";
        $result = mysqli_query($conn, $sql);
        
        if (mysqli_num_rows($result) == 1) {
            // Generate reset token
            $token = generate_token();
            $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Update user with reset token
            $update_sql = "UPDATE utilizatori SET token_resetare = '$token', expirare_token = '$expiry' WHERE email = '$email'";
            
            if (mysqli_query($conn, $update_sql)) {
                // In a real application, send email with reset link
                $reset_link = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/reset-password.php?token=$token";
                
                // For demo purposes, just show the link
                $success = "Un email cu instrucțiuni pentru resetarea parolei a fost trimis la adresa $email.<br><br>
                           <strong>Demo:</strong> <a href='$reset_link'>$reset_link</a>";
                
                // Log the action
                $user_row = mysqli_fetch_assoc($result);
                log_action($user_row['id'], 'solicitare_resetare_parola', "Solicitare resetare parolă pentru email: $email");
            } else {
                $error = "A apărut o eroare. Vă rugăm să încercați din nou.";
            }
        } else {
            // Don't reveal if email exists or not for security
            $success = "Dacă adresa de email există în baza noastră de date, veți primi instrucțiuni pentru resetarea parolei.";
        }
    }
}

// Return JSON response for AJAX requests
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    $response = ['success' => empty($error), 'message' => empty($error) ? $success : $error];
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resetare Parolă - Gusturi Românești</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6 col-xl-5">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white text-center py-3">
                        <h3 class="mb-0">
                            <i class="bi bi-key me-2"></i>
                            Resetare Parolă
                        </h3>
                    </div>
                    <div class="card-body p-4">
                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger" role="alert">
                                <?php echo $error; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($success)): ?>
                            <div class="alert alert-success" role="alert">
                                <?php echo $success; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-muted mb-4">Introdu adresa de email asociată contului tău și îți vom trimite un link pentru resetarea parolei.</p>
                            
                            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                                <div class="mb-3">
                                    <label for="email" class="form-label">Adresa de email</label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="bi bi-envelope"></i>
                                        </span>
                                        <input type="email" class="form-control" id="email" name="email" required placeholder="exemplu@email.com" value="<?php echo $email; ?>">
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary w-100 mb-3">
                                    <i class="bi bi-send"></i> Trimite Link de Resetare
                                </button>
                            </form>
                        <?php endif; ?>
                        
                        <div class="text-center mt-3">
                            <a href="login.php" class="text-decoration-none">
                                <i class="bi bi-arrow-left me-1"></i> Înapoi la Autentificare
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>