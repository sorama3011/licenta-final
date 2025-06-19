<?php
// sync_images_from_json.php
// Script to sync product image URLs from products.json into the database.

require_once 'db-config.php';

try {
    // Read JSON file
    $json = file_get_contents(__DIR__ . '/products.json');
    $products = json_decode($json, true);

    if (!is_array($products)) {
        throw new Exception('Invalid JSON structure in products.json');
    }

    // Prepare update statement
    $stmt = $pdo->prepare('UPDATE produse SET imagine = :image WHERE id = :id');

    $updatedCount = 0;

    foreach ($products as $product) {
        if (!isset($product['id']) || !isset($product['image'])) {
            continue;
        }

        $stmt->execute([
            ':image' => $product['image'],
            ':id'    => $product['id'],
        ]);

        if ($stmt->rowCount() > 0) {
            $updatedCount++;
        }
    }

    echo "Sync complete. Updated {$updatedCount} product image URLs.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}