<?php
require_once 'db-config.php';
session_start();

if (!function_exists('sanitize_input')) {
    function sanitize_input($data) {
        return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
    }
}

$response = [
    'loggedIn' => false
];

if (isset($_SESSION['user_id'])) {
    $response['loggedIn'] = true;
    $response['id'] = $_SESSION['user_id'];
    $response['name'] = $_SESSION['user_name'];
    $response['email'] = $_SESSION['user_email'];
    $response['role'] = $_SESSION['user_role'];
    $response['phone'] = $_SESSION['user_phone'] ?? '';
    $response['points'] = $_SESSION['loyalty_points'] ?? 0;
    $response['totalOrders'] = 0; // poți schimba cu interogare reală
    $response['totalSpent'] = 0;

    // Redirecționare după rol, dacă nu are acces la pagină
    if (isset($_GET['page'])) {
        $page = sanitize_input($_GET['page']);
        $page_access = [
            'admin-dashboard.html' => ['administrator'],
            'employee-dashboard.html' => ['angajat'],
            'client-dashboard.html' => ['client']
        ];

        if (isset($page_access[$page]) && !in_array($_SESSION['user_role'], $page_access[$page])) {
            $response['redirect'] = $page_access[$_SESSION['user_role']][0] . "-dashboard.html";
        }
    }
} else {
    if (isset($_GET['page'])) {
        $page = sanitize_input($_GET['page']);
        $auth_required = ['admin-dashboard.html', 'employee-dashboard.html', 'client-dashboard.html', 'cart.html', 'checkout.html'];

        if (in_array($page, $auth_required)) {
            $response['redirect'] = 'login.php?redirect=' . urlencode($page);
        }
    }
}

header('Content-Type: application/json');
echo json_encode($response);
exit;
