
<?php
require_once 'db-config.php';
session_start();

// Simple test to check order API
if (!isset($_SESSION['user_id'])) {
    echo "Please log in first";
    exit;
}

echo "User ID: " . $_SESSION['user_id'] . "<br>";

// Check cart
$cart_sql = "SELECT COUNT(*) as count FROM cos_cumparaturi WHERE user_id = " . $_SESSION['user_id'];
$cart_result = mysqli_query($conn, $cart_sql);
$cart_count = mysqli_fetch_assoc($cart_result)['count'];
echo "Cart items: " . $cart_count . "<br>";

// Check addresses
$addr_sql = "SELECT * FROM adrese WHERE user_id = " . $_SESSION['user_id'];
$addr_result = mysqli_query($conn, $addr_sql);
echo "Addresses: " . mysqli_num_rows($addr_result) . "<br>";

while ($addr = mysqli_fetch_assoc($addr_result)) {
    echo "Address ID: " . $addr['id'] . " - " . $addr['adresa'] . "<br>";
}

echo "<hr>";
echo "Test form:";
?>
<form method="post" action="api/order.php">
    <input type="hidden" name="action" value="place_order">
    <input type="hidden" name="address_id" value="1">
    <input type="hidden" name="payment_method" value="ramburs">
    <input type="hidden" name="notes" value="Test order">
    <button type="submit">Test Order</button>
</form>
