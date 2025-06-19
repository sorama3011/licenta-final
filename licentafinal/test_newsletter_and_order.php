
<?php
require_once 'db-config.php';
session_start();

echo "<h2>Newsletter and Order Functionality Test</h2>";

// Test 1: Newsletter Subscribe
echo "<h3>1. Testing Newsletter Subscribe</h3>";

// Check if newsletter table exists
$newsletter_check = "SHOW TABLES LIKE 'newsletter%'";
$result = mysqli_query($conn, $newsletter_check);
echo "Newsletter tables found: " . mysqli_num_rows($result) . "<br>";

while ($table = mysqli_fetch_array($result)) {
    echo "Table: " . $table[0] . "<br>";
}

// Test newsletter subscribe functionality
echo "<h4>Testing Newsletter Subscribe API</h4>";
$test_email = "test@example.com";

// Test API call simulation
$_POST['action'] = 'subscribe';
$_POST['email'] = $test_email;

// Include and test newsletter API
ob_start();
include 'api/newsletter.php';
$newsletter_output = ob_get_clean();

echo "Newsletter API Response: " . $newsletter_output . "<br>";

// Check if email was added to database
$check_sql = "SELECT * FROM newsletter WHERE email = '$test_email'";
$check_result = mysqli_query($conn, $check_sql);
if ($check_result) {
    echo "Email found in newsletter table: " . (mysqli_num_rows($check_result) > 0 ? "YES" : "NO") . "<br>";
} else {
    echo "Error checking newsletter table: " . mysqli_error($conn) . "<br>";
}

echo "<hr>";

// Test 2: Newsletter Unsubscribe
echo "<h3>2. Testing Newsletter Unsubscribe</h3>";

// Test unsubscribe
$_POST['action'] = 'unsubscribe';
$_POST['email'] = $test_email;

ob_start();
include 'api/newsletter.php';
$unsubscribe_output = ob_get_clean();

echo "Unsubscribe API Response: " . $unsubscribe_output . "<br>";

echo "<hr>";

// Test 3: Order Placement Dependencies
echo "<h3>3. Testing Order Placement Dependencies</h3>";

// Check required tables for orders
$required_tables = [
    'utilizatori', 'adrese', 'cos_cumparaturi', 'comenzi', 
    'comenzi_produse', 'produse', 'vouchere', 'puncte_fidelitate'
];

foreach ($required_tables as $table) {
    $table_check = "SHOW TABLES LIKE '$table'";
    $result = mysqli_query($conn, $table_check);
    echo "Table '$table': " . (mysqli_num_rows($result) > 0 ? "EXISTS" : "MISSING") . "<br>";
}

echo "<hr>";

// Test 4: User Session and Cart for Order
echo "<h3>4. Testing User Session and Cart</h3>";

if (!isset($_SESSION['user_id'])) {
    echo "No user logged in. Creating test session...<br>";
    
    // Get first user from database
    $user_sql = "SELECT id FROM utilizatori LIMIT 1";
    $user_result = mysqli_query($conn, $user_sql);
    
    if (mysqli_num_rows($user_result) > 0) {
        $user = mysqli_fetch_assoc($user_result);
        $_SESSION['user_id'] = $user['id'];
        echo "Test user ID set: " . $_SESSION['user_id'] . "<br>";
    } else {
        echo "No users found in database<br>";
    }
}

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    
    // Check user's cart
    $cart_sql = "SELECT COUNT(*) as count FROM cos_cumparaturi WHERE user_id = $user_id";
    $cart_result = mysqli_query($conn, $cart_sql);
    $cart_count = mysqli_fetch_assoc($cart_result)['count'];
    echo "Cart items for user $user_id: $cart_count<br>";
    
    // Check user's addresses
    $addr_sql = "SELECT COUNT(*) as count FROM adrese WHERE user_id = $user_id";
    $addr_result = mysqli_query($conn, $addr_sql);
    $addr_count = mysqli_fetch_assoc($addr_result)['count'];
    echo "Addresses for user $user_id: $addr_count<br>";
    
    if ($addr_count == 0) {
        echo "Adding test address...<br>";
        $test_addr_sql = "INSERT INTO adrese (user_id, adresa, oras, judet, cod_postal, telefon) 
                         VALUES ($user_id, 'Test Street 123', 'Test City', 'Test County', '123456', '0123456789')";
        if (mysqli_query($conn, $test_addr_sql)) {
            echo "Test address added successfully<br>";
        } else {
            echo "Error adding test address: " . mysqli_error($conn) . "<br>";
        }
    }
    
    if ($cart_count == 0) {
        echo "Adding test product to cart...<br>";
        $product_sql = "SELECT id, pret FROM produse WHERE activ = 1 LIMIT 1";
        $product_result = mysqli_query($conn, $product_sql);
        
        if (mysqli_num_rows($product_result) > 0) {
            $product = mysqli_fetch_assoc($product_result);
            $cart_insert_sql = "INSERT INTO cos_cumparaturi (user_id, produs_id, cantitate, pret) 
                               VALUES ($user_id, {$product['id']}, 1, {$product['pret']})";
            if (mysqli_query($conn, $cart_insert_sql)) {
                echo "Test product added to cart<br>";
            } else {
                echo "Error adding product to cart: " . mysqli_error($conn) . "<br>";
            }
        } else {
            echo "No active products found<br>";
        }
    }
}

echo "<hr>";

// Test 5: Order API Connection
echo "<h3>5. Testing Order API Connection</h3>";

// Check if order.php exists and is accessible
if (file_exists('api/order.php')) {
    echo "Order API file exists: YES<br>";
    
    // Test database connection in order context
    $test_order_sql = "SELECT COUNT(*) as count FROM comenzi";
    $test_result = mysqli_query($conn, $test_order_sql);
    if ($test_result) {
        $order_count = mysqli_fetch_assoc($test_result)['count'];
        echo "Existing orders in database: $order_count<br>";
    } else {
        echo "Error accessing orders table: " . mysqli_error($conn) . "<br>";
    }
} else {
    echo "Order API file exists: NO<br>";
}

echo "<hr>";

// Test 6: Print Order Dependencies
echo "<h3>6. Testing Print Order Dependencies</h3>";

if (file_exists('print-comanda.php')) {
    echo "Print order file exists: YES<br>";
    
    // Test if we can access order data for printing
    $print_test_sql = "SELECT c.*, u.nume, u.prenume, u.email 
                       FROM comenzi c 
                       JOIN utilizatori u ON c.user_id = u.id 
                       LIMIT 1";
    $print_result = mysqli_query($conn, $print_test_sql);
    
    if ($print_result && mysqli_num_rows($print_result) > 0) {
        echo "Can access order data for printing: YES<br>";
        $order_data = mysqli_fetch_assoc($print_result);
        echo "Sample order number: " . $order_data['numar_comanda'] . "<br>";
    } else {
        echo "Can access order data for printing: NO<br>";
        if (mysqli_error($conn)) {
            echo "Database error: " . mysqli_error($conn) . "<br>";
        }
    }
} else {
    echo "Print order file exists: NO<br>";
}

echo "<hr>";

// Test 7: Database Functions
echo "<h3>7. Testing Database Functions</h3>";

// Test sanitize_input function
if (function_exists('sanitize_input')) {
    echo "sanitize_input function: AVAILABLE<br>";
    $test_input = "<script>alert('test')</script>";
    $sanitized = sanitize_input($test_input);
    echo "Sanitization test: '$test_input' -> '$sanitized'<br>";
} else {
    echo "sanitize_input function: NOT AVAILABLE<br>";
}

// Test log_action function
if (function_exists('log_action')) {
    echo "log_action function: AVAILABLE<br>";
} else {
    echo "log_action function: NOT AVAILABLE<br>";
}

echo "<hr>";

// Test 8: Session Management
echo "<h3>8. Testing Session Management</h3>";

echo "Session started: " . (session_status() == PHP_SESSION_ACTIVE ? "YES" : "NO") . "<br>";
echo "User ID in session: " . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : "NOT SET") . "<br>";

// Clean up test data
echo "<hr>";
echo "<h3>Cleanup</h3>";

// Remove test newsletter subscription
$cleanup_newsletter = "DELETE FROM newsletter WHERE email = '$test_email'";
if (mysqli_query($conn, $cleanup_newsletter)) {
    echo "Test newsletter subscription cleaned up<br>";
}

echo "<br><strong>Test completed!</strong>";
?>
