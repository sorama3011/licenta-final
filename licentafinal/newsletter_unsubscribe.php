<?php
// Include database configuration
require_once 'db-config.php';

// Initialize variables
$error = '';
$success = '';
$token = '';

// Check if token is provided
if (isset($_GET['token'])) {
    $token = sanitize_input($_GET['token']);
    
    // Check if token is valid
    $sql = "SELECT id, email FROM newsletter_abonati WHERE token = '$token' AND activ = 1";
    $result = mysqli_query($conn, $sql);
    
    if (mysqli_num_rows($result) == 1) {
        $subscriber = mysqli_fetch_assoc($result);
        
        // Update subscriber status
        $update_sql = "UPDATE newsletter_abonati SET activ = 0 WHERE id = {$subscriber['id']}";
        
        if (mysqli_query($conn, $update_sql)) {
            $success = "Te-ai dezabonat cu succes de la newsletter. Nu vei mai primi emailuri de la noi.";
        } else {
            $error = "A apărut o eroare la dezabonare. Te rugăm să încerci din nou.";
        }
    } else {
        $error = "Token-ul de dezabonare este invalid sau a fost deja folosit.";
    }
} else {
    $error = "Token-ul de dezabonare lipsește.";
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
    <title>Dezabonare Newsletter - Gusturi Românești</title>
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
                            <i class="bi bi-envelope-x me-2"></i>
                            Dezabonare Newsletter
                        </h3>
                    </div>
                    <div class="card-body p-4 text-center">
                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger" role="alert">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                <?php echo $error; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($success)): ?>
                            <div class="alert alert-success" role="alert">
                                <i class="bi bi-check-circle me-2"></i>
                                <?php echo $success; ?>
                            </div>
                        <?php endif; ?>
                        
                        <p class="mb-4">
                            <a href="index.html" class="btn btn-primary">
                                <i class="bi bi-house me-2"></i>Înapoi la Pagina Principală
                            </a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>