<?php
ob_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'db-config.php';
session_start();

$error = '';
$email = '';
$redirect = isset($_GET['redirect']) ? sanitize_input($_GET['redirect']) : '';


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = sanitize_input($_POST['email']);
    $password = $_POST['password'];
    $redirect = isset($_POST['redirect']) ? sanitize_input($_POST['redirect']) : '';

    if (empty($email) || empty($password)) {
        $error = "Toate câmpurile sunt obligatorii.";
    } else {
        $sql = "SELECT id, prenume, nume, email, parola, telefon, rol FROM utilizatori WHERE email = '$email' AND activ = 1";
        $result = mysqli_query($conn, $sql);

        if (mysqli_num_rows($result) == 1) {
            $user = mysqli_fetch_assoc($result);
            if (password_verify($password, $user['parola'])) {
                session_regenerate_id();
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['prenume'] . ' ' . $user['nume'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['rol'];
                $_SESSION['user_phone'] = $user['telefon'];

                // Puncte client
                if ($user['rol'] == 'client') {
                    $points_sql = "SELECT puncte FROM puncte_fidelitate WHERE user_id = {$user['id']}";
                    $points_result = mysqli_query($conn, $points_sql);
                    $_SESSION['loyalty_points'] = (mysqli_num_rows($points_result) == 1)
                        ? mysqli_fetch_assoc($points_result)['puncte'] : 0;
                }

                if (!empty($redirect)) {
                    header("Location: $redirect");
                    exit;
                }
                // Redirect în funcție de rol
                switch ($user['rol']) {
                    case 'administrator':
                        header("Location: admin-dashboard.html");
                        break;
                    case 'angajat':
                        header("Location: employee-dashboard.html");
                        break;
                    case 'client':
                        header("Location: client-dashboard.html");
                        break;
                    default:
                        header("Location: index.html");
                        break;
                }
                exit;
            } else {
                $error = "Parolă incorectă.";
            }
        } else {
            $error = "Utilizator inexistent sau inactiv.";
        }
    }
}

// Poți include aici HTML-ul formularului de login dacă este necesar
?>

<!-- HTML login UI -->
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <title>Autentificare - Gusturi Românești</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6 col-xl-5">
            <div class="card shadow-lg">
                <div class="card-header bg-primary text-white text-center py-4">
                    <h3><i class="bi bi-person-circle me-2"></i>Autentificare</h3>
                </div>
                <div class="card-body p-5">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                        <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($redirect); ?>">
                        <div class="mb-3">
                            <label for="email" class="form-label">Adresa de email</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                <input type="email" class="form-control" name="email" required value="<?php echo $email; ?>">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Parola</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                <input type="password" class="form-control" name="password" required id="password">
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword()">
                                    <i class="bi bi-eye" id="toggleIcon"></i>
                                </button>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary btn-lg w-100 mb-3">
                            <i class="bi bi-box-arrow-in-right"></i> Autentifică-te
                        </button>
                    </form>
                    <div class="text-center"><a href="forgot-password.php">Ai uitat parola?</a></div>
                    <hr>
                    <div class="text-center">
                        <p>Nu ai un cont încă?</p>
                        <a href="signup.php" class="btn btn-outline-primary w-100">Creează Cont Nou</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
function togglePassword() {
    const input = document.getElementById('password');
    const icon = document.getElementById('toggleIcon');
    input.type = (input.type === 'password') ? 'text' : 'password';
    icon.className = (input.type === 'text') ? 'bi bi-eye-slash' : 'bi bi-eye';
}
</script>
</body>
</html>
<?php ob_end_flush(); ?>
