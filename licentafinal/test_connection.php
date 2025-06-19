<?php
// Include database configuration
require_once 'db-config.php';

// Test if connection is successful
if($conn) {
    echo "Database connection successful!<br>";
    
    // Test if we can query the database
    $result = mysqli_query($conn, "SELECT COUNT(*) as count FROM utilizatori");
    if($result) {
        $row = mysqli_fetch_assoc($result);
        echo "Number of users in database: " . $row['count'];
    } else {
        echo "Error querying database: " . mysqli_error($conn);
    }
} else {
    echo "Database connection failed: " . mysqli_connect_error();
}
?> 