
<?php
require_once 'db-config.php';

echo "<h2>Testing Product Database</h2>";

// Test database connection
if (!$conn) {
    echo "❌ Database connection failed: " . mysqli_connect_error() . "<br>";
    exit;
} else {
    echo "✅ Database connection successful<br><br>";
}

// Check if products table exists and has data
$sql = "SELECT COUNT(*) as count FROM produse WHERE activ = 1";
$result = mysqli_query($conn, $sql);

if (!$result) {
    echo "❌ Error querying products table: " . mysqli_error($conn) . "<br>";
    exit;
}

$row = mysqli_fetch_assoc($result);
echo "📊 Active products in database: " . $row['count'] . "<br><br>";

if ($row['count'] == 0) {
    echo "⚠️ No active products found. You may need to add products to the database.<br>";
    exit;
}

// Test getting first few products
echo "<h3>Sample Products:</h3>";
$sql = "SELECT id, nume, pret, imagine, activ FROM produse LIMIT 5";
$result = mysqli_query($conn, $sql);

if ($result && mysqli_num_rows($result) > 0) {
    echo "<table border='1' style='border-collapse: collapse; margin-bottom: 20px;'>";
    echo "<tr><th>ID</th><th>Nume</th><th>Preț</th><th>Imagine</th><th>Activ</th></tr>";
    
    while ($product = mysqli_fetch_assoc($result)) {
        echo "<tr>";
        echo "<td>" . $product['id'] . "</td>";
        echo "<td>" . htmlspecialchars($product['nume']) . "</td>";
        echo "<td>" . $product['pret'] . " RON</td>";
        echo "<td>" . (strlen($product['imagine']) > 50 ? substr($product['imagine'], 0, 50) . '...' : $product['imagine']) . "</td>";
        echo "<td>" . ($product['activ'] ? 'Da' : 'Nu') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "❌ No products found or error occurred<br>";
}

// Test nutritional data
echo "<h3>Nutritional Information Test:</h3>";
$sql = "SELECT COUNT(*) as count FROM produse_nutritionale";
$result = mysqli_query($conn, $sql);

if ($result) {
    $row = mysqli_fetch_assoc($result);
    echo "📊 Products with nutritional info: " . $row['count'] . "<br>";
} else {
    echo "❌ Error checking nutritional table: " . mysqli_error($conn) . "<br>";
}

// Test API endpoint
echo "<h3>Testing API Endpoint:</h3>";
$test_url = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/api/catalog.php?type=product&id=1';
echo "Testing URL: <a href='$test_url' target='_blank'>$test_url</a><br>";

// Test with first available product
$sql = "SELECT id FROM produse WHERE activ = 1 LIMIT 1";
$result = mysqli_query($conn, $sql);
if ($result && mysqli_num_rows($result) > 0) {
    $product = mysqli_fetch_assoc($result);
    $product_id = $product['id'];
    $test_url = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/api/catalog.php?type=product&id=' . $product_id;
    echo "Testing with product ID $product_id: <a href='$test_url' target='_blank'>$test_url</a><br>";
}

echo "<br><h3>Troubleshooting Steps:</h3>";
echo "1. ✅ Database connection working<br>";
echo "2. " . ($row['count'] > 0 ? "✅" : "❌") . " Products exist in database<br>";
echo "3. 🔗 Click the API test links above to verify API responses<br>";
echo "4. 🌐 If API returns errors, check browser console for detailed error messages<br>";
?>
