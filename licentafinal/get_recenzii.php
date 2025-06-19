<?php
// Include database configuration
require_once 'db-config.php';

// Start session
session_start();

// Initialize response array
$response = [
    'success' => false,
    'message' => '',
    'reviews' => [],
    'average_rating' => 0,
    'total_reviews' => 0
];

// Check if product ID is provided
if (!isset($_GET['product_id']) || empty($_GET['product_id'])) {
    $response['message'] = 'ID-ul produsului lipsește.';
    echo json_encode($response);
    exit;
}

// Get product ID
$product_id = (int)$_GET['product_id'];

// Check if product exists
$product_sql = "SELECT id, nume FROM produse WHERE id = $product_id AND activ = 1";
$product_result = mysqli_query($conn, $product_sql);

if (mysqli_num_rows($product_result) == 0) {
    $response['message'] = 'Produsul nu există sau nu este disponibil.';
    echo json_encode($response);
    exit;
}

// Get approved reviews for this product
$reviews_sql = "SELECT r.id, r.rating, r.titlu, r.comentariu, r.data_adaugare, 
               u.prenume, u.nume
               FROM recenzii r
               JOIN utilizatori u ON r.user_id = u.id
               WHERE r.produs_id = $product_id AND r.aprobat = 1
               ORDER BY r.data_adaugare DESC";
$reviews_result = mysqli_query($conn, $reviews_sql);

// Get average rating and total reviews
$stats_sql = "SELECT AVG(rating) as average, COUNT(*) as total 
             FROM recenzii 
             WHERE produs_id = $product_id AND aprobat = 1";
$stats_result = mysqli_query($conn, $stats_sql);
$stats = mysqli_fetch_assoc($stats_result);

$response['average_rating'] = $stats['average'] ? round((float)$stats['average'], 1) : 0;
$response['total_reviews'] = (int)$stats['total'];

// Process reviews
$reviews = [];

while ($review = mysqli_fetch_assoc($reviews_result)) {
    $reviews[] = [
        'id' => (int)$review['id'],
        'rating' => (int)$review['rating'],
        'title' => $review['titlu'],
        'comment' => $review['comentariu'],
        'date' => date('d.m.Y', strtotime($review['data_adaugare'])),
        'author' => $review['prenume'] . ' ' . substr($review['nume'], 0, 1) . '.'
    ];
}

// Check if user has already reviewed this product
$user_review = null;

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $user_review_sql = "SELECT id, rating, titlu, comentariu, aprobat, data_adaugare 
                       FROM recenzii 
                       WHERE produs_id = $product_id AND user_id = $user_id";
    $user_review_result = mysqli_query($conn, $user_review_sql);
    
    if (mysqli_num_rows($user_review_result) > 0) {
        $review = mysqli_fetch_assoc($user_review_result);
        $user_review = [
            'id' => (int)$review['id'],
            'rating' => (int)$review['rating'],
            'title' => $review['titlu'],
            'comment' => $review['comentariu'],
            'approved' => (bool)$review['aprobat'],
            'date' => date('d.m.Y', strtotime($review['data_adaugare']))
        ];
    }
}

// Update response
$response['success'] = true;
$response['reviews'] = $reviews;
$response['user_review'] = $user_review;

// Return response
echo json_encode($response);
exit;
?>