<?php
// Include database configuration
require_once 'db-config.php';

// Start session
session_start();

// Initialize response array
$response = [
    'success' => false,
    'message' => '',
    'stats' => null
];

// Check if user is logged in and has appropriate role
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['administrator', 'angajat'])) {
    $response['message'] = 'Nu aveți permisiunea de a accesa aceste statistici.';
    echo json_encode($response);
    exit;
}

// Get basic statistics
try {
    // Total products
    $products_sql = "SELECT COUNT(*) as total FROM produse WHERE activ = 1";
    $products_result = mysqli_query($conn, $products_sql);
    $products_row = mysqli_fetch_assoc($products_result);
    $total_products = (int)$products_row['total'];
    
    // Products out of stock
    $out_of_stock_sql = "SELECT COUNT(*) as total FROM produse WHERE activ = 1 AND stoc = 0";
    $out_of_stock_result = mysqli_query($conn, $out_of_stock_sql);
    $out_of_stock_row = mysqli_fetch_assoc($out_of_stock_result);
    $out_of_stock = (int)$out_of_stock_row['total'];
    
    // Total customers
    $customers_sql = "SELECT COUNT(*) as total FROM utilizatori WHERE rol = 'client' AND activ = 1";
    $customers_result = mysqli_query($conn, $customers_sql);
    $customers_row = mysqli_fetch_assoc($customers_result);
    $total_customers = (int)$customers_row['total'];
    
    // Total orders
    $orders_sql = "SELECT COUNT(*) as total FROM comenzi";
    $orders_result = mysqli_query($conn, $orders_sql);
    $orders_row = mysqli_fetch_assoc($orders_result);
    $total_orders = (int)$orders_row['total'];
    
    // Orders by status
    $orders_status_sql = "SELECT status, COUNT(*) as count FROM comenzi GROUP BY status";
    $orders_status_result = mysqli_query($conn, $orders_status_sql);
    
    $orders_by_status = [];
    while ($status_row = mysqli_fetch_assoc($orders_status_result)) {
        $orders_by_status[$status_row['status']] = (int)$status_row['count'];
    }
    
    // Total sales
    $sales_sql = "SELECT SUM(total) as total FROM comenzi WHERE status != 'anulata'";
    $sales_result = mysqli_query($conn, $sales_sql);
    $sales_row = mysqli_fetch_assoc($sales_result);
    $total_sales = $sales_row['total'] ? (float)$sales_row['total'] : 0;
    
    // Sales this month
    $month_sales_sql = "SELECT SUM(total) as total FROM comenzi 
                       WHERE status != 'anulata' 
                       AND YEAR(data_plasare) = YEAR(CURRENT_DATE) 
                       AND MONTH(data_plasare) = MONTH(CURRENT_DATE)";
    $month_sales_result = mysqli_query($conn, $month_sales_sql);
    $month_sales_row = mysqli_fetch_assoc($month_sales_result);
    $month_sales = $month_sales_row['total'] ? (float)$month_sales_row['total'] : 0;
    
    // New customers this month
    $new_customers_sql = "SELECT COUNT(*) as total FROM utilizatori 
                         WHERE rol = 'client' AND activ = 1 
                         AND YEAR(data_inregistrare) = YEAR(CURRENT_DATE) 
                         AND MONTH(data_inregistrare) = MONTH(CURRENT_DATE)";
    $new_customers_result = mysqli_query($conn, $new_customers_sql);
    $new_customers_row = mysqli_fetch_assoc($new_customers_result);
    $new_customers = (int)$new_customers_row['total'];
    
    // Top 5 products
    $top_products_sql = "SELECT p.id, p.nume, SUM(cp.cantitate) as total_quantity
                        FROM comenzi_produse cp
                        JOIN comenzi c ON cp.comanda_id = c.id
                        JOIN produse p ON cp.produs_id = p.id
                        WHERE c.status != 'anulata'
                        GROUP BY p.id, p.nume
                        ORDER BY total_quantity DESC
                        LIMIT 5";
    $top_products_result = mysqli_query($conn, $top_products_sql);
    
    $top_products = [];
    while ($product = mysqli_fetch_assoc($top_products_result)) {
        $top_products[] = [
            'id' => (int)$product['id'],
            'name' => $product['nume'],
            'quantity' => (int)$product['total_quantity']
        ];
    }
    
    // Compile statistics
    $stats = [
        'products' => [
            'total' => $total_products,
            'out_of_stock' => $out_of_stock,
            'in_stock_percent' => $total_products > 0 ? round(($total_products - $out_of_stock) / $total_products * 100) : 0
        ],
        'customers' => [
            'total' => $total_customers,
            'new_this_month' => $new_customers
        ],
        'orders' => [
            'total' => $total_orders,
            'by_status' => $orders_by_status
        ],
        'sales' => [
            'total' => $total_sales,
            'this_month' => $month_sales,
            'average_order' => $total_orders > 0 ? $total_sales / $total_orders : 0
        ],
        'top_products' => $top_products
    ];
    
    // Update response
    $response['success'] = true;
    $response['stats'] = $stats;
    
} catch (Exception $e) {
    $response['message'] = 'A apărut o eroare la generarea statisticilor: ' . $e->getMessage();
}

// Return response
echo json_encode($response);
exit;
?>