<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// Include database configuration
require_once 'db-config.php';

// Initialize variables
$error = '';
$success = '';

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    echo '<div style="color:blue">[DEBUG] Form submitted</div>';
    // Get form data
    $prenume = sanitize_input($_POST['firstName']);
    $nume = sanitize_input($_POST['lastName']);
    $email = sanitize_input($_POST['registerEmail']);
    $telefon = isset($_POST['phone']) ? sanitize_input($_POST['phone']) : '';
    $parola = $_POST['registerPassword'];
    $confirma_parola = $_POST['confirmPassword'];
    $newsletter = isset($_POST['newsletter']) ? 1 : 0;
    
    // Validate input
    if (empty($prenume) || empty($nume) || empty($email) || empty($parola) || empty($confirma_parola)) {
        $error = "Toate câmpurile obligatorii trebuie completate.";
        echo '<div style="color:red">[DEBUG] Validation failed: missing fields</div>';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Adresa de email nu este validă.";
        echo '<div style="color:red">[DEBUG] Validation failed: invalid email</div>';
    } elseif ($parola !== $confirma_parola) {
        $error = "Parolele nu se potrivesc.";
        echo '<div style="color:red">[DEBUG] Validation failed: passwords do not match</div>';
    } elseif (strlen($parola) < 8) {
        $error = "Parola trebuie să aibă cel puțin 8 caractere.";
        echo '<div style="color:red">[DEBUG] Validation failed: password too short</div>';
    } else {
        // Check if email already exists
        $check_sql = "SELECT id FROM utilizatori WHERE email = '$email'";
        $result = mysqli_query($conn, $check_sql);
        if (mysqli_num_rows($result) > 0) {
            $error = "Această adresă de email este deja înregistrată.";
            echo '<div style="color:red">[DEBUG] Email already exists</div>';
        } else {
            // Hash password
            $hashed_password = password_hash($parola, PASSWORD_DEFAULT);
            // Insert new user
            $insert_sql = "INSERT INTO utilizatori (prenume, nume, email, parola, telefon, rol, newsletter) 
                          VALUES ('$prenume', '$nume', '$email', '$hashed_password', '$telefon', 'client', $newsletter)";
            if (mysqli_query($conn, $insert_sql)) {
                echo '<div style="color:green">[DEBUG] User inserted successfully</div>';
                // Get the new user ID
                $user_id = mysqli_insert_id($conn);
                // Create loyalty points record
                $points_sql = "INSERT INTO puncte_fidelitate (user_id, puncte) VALUES ($user_id, 0)";
                if (mysqli_query($conn, $points_sql)) {
                    echo '<div style="color:green">[DEBUG] Loyalty points record created</div>';
                } else {
                    echo '<div style="color:red">[DEBUG] Error creating loyalty points: '.mysqli_error($conn).'</div>';
                }
                // Log the action
                log_action($user_id, 'inregistrare', 'Utilizator nou înregistrat');
                // Set success message
                $success = "Contul a fost creat cu succes! Te poți autentifica acum.";
                // Redirect to login page after 2 seconds
                header("refresh:2;url=login.php");
            } else {
                $error = "Eroare la înregistrare: " . mysqli_error($conn);
                echo '<div style="color:red">[DEBUG] User insert failed: '.mysqli_error($conn).'</div>';
            }
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
    <title>Înregistrare - Gusturi Românești</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white text-center py-3">
                        <h3 class="mb-0">
                            <i class="bi bi-person-plus-fill me-2"></i>
                            Creează Cont Nou
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
                        <?php endif; ?>
                        
                        <form id="registerForm" method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="firstName" class="form-label">Prenume</label>
                                    <input type="text" class="form-control" id="firstName" name="firstName" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="lastName" class="form-label">Nume</label>
                                    <input type="text" class="form-control" id="lastName" name="lastName" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="registerEmail" class="form-label">Adresa de email</label>
                                <input type="email" class="form-control" id="registerEmail" name="registerEmail" required>
                            </div>
                            <div class="mb-3">
                                <label for="phone" class="form-label">Telefon</label>
                                <input type="tel" class="form-control" id="phone" name="phone" placeholder="+40 7XX XXX XXX">
                            </div>
                            <div class="mb-3">
                                <label for="registerPassword" class="form-label">Parola</label>
                                <input type="password" class="form-control" id="registerPassword" name="registerPassword" required>
                                <div class="form-text">Parola trebuie să aibă cel puțin 8 caractere</div>
                            </div>
                            <div class="mb-3">
                                <label for="confirmPassword" class="form-label">Confirmă Parola</label>
                                <input type="password" class="form-control" id="confirmPassword" name="confirmPassword" required>
                            </div>
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="agreeTerms" name="agreeTerms" required>
                                <label class="form-check-label" for="agreeTerms">
                                    Sunt de acord cu <a href="terms.html" class="text-primary">Termenii și Condițiile</a> și <a href="privacy.html" class="text-primary">Politica de Confidențialitate</a>
                                </label>
                            </div>
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="newsletter" name="newsletter">
                                <label class="form-check-label" for="newsletter">
                                    Vreau să primesc oferte și noutăți pe email
                                </label>
                            </div>
                            <button type="submit" class="btn btn-primary btn-lg w-100 mb-3">
                                <i class="bi bi-person-plus"></i> Creează Contul
                            </button>
                        </form>

                        <div class="text-center mt-3">
                            <p>Ai deja un cont? <a href="login.php" class="text-primary">Autentifică-te</a></p>
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