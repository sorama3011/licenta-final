
<?php
require_once '../db-config.php';

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'subscribe':
        $email = sanitize_input($_POST['email']);
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'Adresa de email nu este validă']);
            exit;
        }
        
        // Check if already subscribed
        $check_sql = "SELECT id FROM newsletter WHERE email = '$email'";
        $result = mysqli_query($conn, $check_sql);
        
        if (mysqli_num_rows($result) > 0) {
            // Update subscription status if exists
            $sql = "UPDATE newsletter SET activ = 1, data_actualizarii = NOW() WHERE email = '$email'";
            $message = 'Abonarea ta a fost reactivată';
        } else {
            // Insert new subscription
            $sql = "INSERT INTO newsletter (email, activ) VALUES ('$email', 1)";
            $message = 'Te-ai abonat cu succes la newsletter';
        }
        
        if (mysqli_query($conn, $sql)) {
            echo json_encode(['success' => true, 'message' => $message]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Eroare la abonarea la newsletter']);
        }
        break;

    case 'unsubscribe':
        $email = sanitize_input($_POST['email'] ?? $_GET['email'] ?? '');
        $token = sanitize_input($_POST['token'] ?? $_GET['token'] ?? '');
        
        if (empty($email)) {
            echo json_encode(['success' => false, 'message' => 'Adresa de email este obligatorie']);
            exit;
        }
        
        // If token is provided, verify it (for unsubscribe links)
        if ($token) {
            $expected_token = md5($email . 'unsubscribe_salt');
            if ($token !== $expected_token) {
                echo json_encode(['success' => false, 'message' => 'Token invalid']);
                exit;
            }
        }
        
        $sql = "UPDATE newsletter SET activ = 0, data_actualizarii = NOW() WHERE email = '$email'";
        
        if (mysqli_query($conn, $sql)) {
            if (mysqli_affected_rows($conn) > 0) {
                echo json_encode(['success' => true, 'message' => 'Te-ai dezabonat cu succes de la newsletter']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Adresa de email nu a fost găsită']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Eroare la dezabonarea de la newsletter']);
        }
        break;

    case 'check_status':
        $email = sanitize_input($_GET['email']);
        
        $sql = "SELECT activ FROM newsletter WHERE email = '$email'";
        $result = mysqli_query($conn, $sql);
        
        if (mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);
            $subscribed = (bool)$row['activ'];
        } else {
            $subscribed = false;
        }
        
        echo json_encode(['success' => true, 'subscribed' => $subscribed]);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Acțiune necunoscută']);
        break;
}
?>
