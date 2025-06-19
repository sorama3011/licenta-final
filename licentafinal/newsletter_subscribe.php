<?php
// Include database configuration
require_once 'db-config.php';

// Initialize response array
$response = [
    'success' => false,
    'message' => ''
];

// Check if request is AJAX
$is_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

// Check if email is provided
if (!isset($_POST['email']) || empty($_POST['email'])) {
    $response['message'] = 'Adresa de email este obligatorie.';
    
    if ($is_ajax) {
        echo json_encode($response);
        exit;
    } else {
        header("Location: index.html");
        exit;
    }
}

// Get email and name
$email = sanitize_input($_POST['email']);
$name = isset($_POST['name']) ? sanitize_input($_POST['name']) : '';

// Validate email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $response['message'] = 'Adresa de email nu este validă.';
    
    if ($is_ajax) {
        echo json_encode($response);
        exit;
    } else {
        header("Location: index.html");
        exit;
    }
}

// Check if email already exists
$check_sql = "SELECT id, activ FROM newsletter_abonati WHERE email = '$email'";
$check_result = mysqli_query($conn, $check_sql);

if (mysqli_num_rows($check_result) > 0) {
    $subscriber = mysqli_fetch_assoc($check_result);
    
    if ($subscriber['activ']) {
        $response['message'] = 'Această adresă de email este deja abonată la newsletter.';
    } else {
        // Reactivate subscription
        $update_sql = "UPDATE newsletter_abonati SET activ = 1, data_abonare = NOW() WHERE id = {$subscriber['id']}";
        
        if (mysqli_query($conn, $update_sql)) {
            $response['success'] = true;
            $response['message'] = 'Te-ai abonat cu succes la newsletter!';
        } else {
            $response['message'] = 'A apărut o eroare la reactivarea abonamentului.';
        }
    }
} else {
    // Generate token for unsubscribe
    $token = generate_token();
    
    // Add new subscriber
    $insert_sql = "INSERT INTO newsletter_abonati (email, nume, token) VALUES ('$email', '$name', '$token')";
    
    if (mysqli_query($conn, $insert_sql)) {
        $response['success'] = true;
        $response['message'] = 'Te-ai abonat cu succes la newsletter!';
        
        // In a real application, send confirmation email
    } else {
        $response['message'] = 'A apărut o eroare la abonare.';
    }
}

// Return response for AJAX requests
if ($is_ajax) {
    echo json_encode($response);
    exit;
}

// Redirect for regular requests
header("Location: index.html");
exit;
?>