
<?php
require_once 'db-config.php';

// Test single product fetch
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 1;

echo "<h2>Testing Single Product (ID: $product_id)</h2>";

// Test direct database query
$sql = "SELECT p.id, p.nume, p.descriere, p.descriere_lunga, p.ingrediente, p.pret, p.imagine, 
               p.categorie, p.regiune, p.cantitate, p.stoc, p.producator, p.recomandat, 
               p.restrictie_varsta, p.data_adaugarii, p.activ,
               pn.valoare_energetica, pn.grasimi, pn.grasimi_saturate, pn.glucide, 
               pn.zaharuri, pn.fibre, pn.proteine, pn.sare, pn.ingrediente as ingrediente_nutritionale
        FROM produse p
        LEFT JOIN produse_nutritionale pn ON p.id = pn.produs_id
        WHERE p.id = $product_id";

echo "<h3>SQL Query:</h3>";
echo "<code>" . htmlspecialchars($sql) . "</code><br><br>";

$result = mysqli_query($conn, $sql);

if (!$result) {
    echo "<div class='alert alert-danger'>Database Error: " . mysqli_error($conn) . "</div>";
} else {
    $num_rows = mysqli_num_rows($result);
    echo "<div class='alert alert-info'>Query returned $num_rows rows</div>";
    
    if ($num_rows > 0) {
        $product = mysqli_fetch_assoc($result);
        echo "<h3>Product Data:</h3>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        foreach ($product as $key => $value) {
            echo "<tr><td><strong>$key</strong></td><td>" . htmlspecialchars($value ?? 'NULL') . "</td></tr>";
        }
        echo "</table>";
        
        // Test API call
        echo "<h3>API Test:</h3>";
        echo "<a href='api/catalog.php?type=product&id=$product_id' target='_blank'>Test API Call</a><br>";
        echo "<a href='product.html?id=$product_id' target='_blank'>Test Product Page</a>";
    } else {
        echo "<div class='alert alert-warning'>No product found with ID $product_id</div>";
        
        // Show available products
        $available_sql = "SELECT id, nume FROM produse WHERE activ = 1 LIMIT 10";
        $available_result = mysqli_query($conn, $available_sql);
        
        if ($available_result && mysqli_num_rows($available_result) > 0) {
            echo "<h4>Available Products:</h4>";
            echo "<ul>";
            while ($available = mysqli_fetch_assoc($available_result)) {
                echo "<li><a href='?id={$available['id']}'>{$available['nume']} (ID: {$available['id']})</a></li>";
            }
            echo "</ul>";
        }
    }
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
table { margin: 10px 0; }
td { padding: 5px 10px; }
.alert { padding: 10px; margin: 10px 0; border-radius: 4px; }
.alert-danger { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
.alert-info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
.alert-warning { background: #fff3cd; color: #856404; border: 1px solid #ffeaa7; }
</style>
