<?php
// Include database configuration
require_once 'db-config.php';

// Initialize variables
$error = '';
$success = '';
$token = '';
$valid_token = false;
$user_id = 0;

// Check if token is provided
if (isset($_GET['token'])) {
    $token = sanitize_input($_GET['token']);
    
    // Check if token is valid
    $sql = "SELECT id FROM utilizatori WHERE token_resetare = '$token' AND expirare_token > NOW() AND activ = 1";
    $result = mysqli_query($conn, $sql);
    
    if (mysqli_num_rows($result) == 1) {
        $valid_token = true;
        $user_row = mysqli_fetch_assoc($result);
        $user_id = $user_row['id'];
    } else {
        $error = "Token-ul de resetare este invalid sau a expirat.";
    }
} else {
    $error = "Token-ul de resetare lipsește.";
}

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST" && $valid_token) {
    // Get form data
    $password = $_POST['password'];
    $confirm_password = $_POST['confirmPassword'];
    
    // Validate input
    if (empty($password) || empty($confirm_password)) {
        $error = "Toate câmpurile sunt obligatorii.";
    } elseif ($password !== $confirm_password) {
        $error = "Parolele nu se potrivesc.";
    } elseif (strlen($password) < 8) {
        $error = "Parola trebuie să aibă cel puțin 8 caractere.";
    } else {
        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Update user password and clear reset token
        $update_sql = "UPDATE utilizatori SET parola = '$hashed_password', token_resetare = NULL, expirare_token = NULL WHERE token_resetare = '$token'";
        
        if (mysqli_query($conn, $update_sql)) {
            // Log the action
            log_action($user_id, 'resetare_parola', 'Parolă resetată cu succes');
            
            // Set success message
            $success = "Parola a fost resetată cu succes! Te poți autentifica acum.";
            
            // Redirect to login page after 2 seconds
            header("refresh:2;url=login.php");
        } else {
            $error = "A apărut o eroare la resetarea parolei. Vă rugăm să încercați din nou.";
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
                            <i class="bi bi-shield-lock me-2"></i>
                            Resetare Parolă
                        </h3>
                    </div>
                    <div class="card-body p-4">
                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger" role="alert">
                                <?php echo $error; ?>
                                <?php if ($error !== "Token-ul de resetare lipsește."): ?>
                                    <p class="mt-2 mb-0">
                                        <a href="forgot-password.php" class="alert-link">Solicită un nou link de resetare</a>
                                    </p>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($success)): ?>
                            <div class="alert alert-success" role="alert">
                                <?php echo $success; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($valid_token && empty($success)): ?>
                            <p class="text-muted mb-4">Introdu noua parolă pentru contul tău.</p>
                            
                            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . '?token=' . $token; ?>">
                                <div class="mb-3">
                                    <label for="password" class="form-label">Parolă Nouă</label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="bi bi-lock"></i>
                                        </span>
                                        <input type="password" class="form-control" id="password" name="password" required>
                                    </div>
                                    <div class="form-text">Parola trebuie să aibă cel puțin 8 caractere</div>
                                </div>
                                <div class="mb-3">
                                    <label for="confirmPassword" class="form-label">Confirmă Parola</label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="bi bi-lock-fill"></i>
                                        </span>
                                        <input type="password" class="form-control" id="confirmPassword" name="confirmPassword" required>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary w-100 mb-3">
                                    <i class="bi bi-check-lg"></i> Resetează Parola
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