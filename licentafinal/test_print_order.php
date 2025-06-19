
<?php
require_once 'db-config.php';
session_start();

// Get the first order for testing
$order_sql = "SELECT id, numar_comanda FROM comenzi LIMIT 1";
$order_result = mysqli_query($conn, $order_sql);

if (mysqli_num_rows($order_result) > 0) {
    $order = mysqli_fetch_assoc($order_result);
    echo "<h3>Testing Print Order Functionality</h3>";
    echo "Found order: " . $order['numar_comanda'] . " (ID: " . $order['id'] . ")<br>";
    echo "<a href='print-comanda.php?id=" . $order['id'] . "' target='_blank'>Test Print Order</a><br>";
} else {
    echo "No orders found in database. Create an order first.";
}

// Test database connection for print functionality
echo "<hr>";
echo "<h4>Print Order Database Test</h4>";

$test_sql = "SELECT c.*, 
             CONCAT(u.prenume, ' ', u.nume) as client_name, 
             u.email as client_email
             FROM comenzi c
             JOIN utilizatori u ON c.user_id = u.id
             LIMIT 1";

$test_result = mysqli_query($conn, $test_sql);

if ($test_result) {
    echo "Database query for print order: SUCCESS<br>";
    if (mysqli_num_rows($test_result) > 0) {
        echo "Order data available for printing: YES<br>";
    } else {
        echo "Order data available for printing: NO (no orders)<br>";
    }
} else {
    echo "Database query for print order: FAILED<br>";
    echo "Error: " . mysqli_error($conn) . "<br>";
}
?>
