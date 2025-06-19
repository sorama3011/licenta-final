<?php
// Include database configuration
require_once 'db-config.php';

// Initialize response array
$response = [
    'success' => false,
    'message' => '',
    'packages' => []
];

// Get packages (mock data for now)
// In a real application, this would be fetched from a packages table
$packages = [
    [
        'id' => 108,
        'name' => 'Pachetul Familiei',
        'description' => 'Pachet complet cu produse tradiționale pentru toată familia',
        'regular_price' => 179.99,
        'sale_price' => 129.99,
        'discount_percent' => 28,
        'image' => 'https://via.placeholder.com/300x200/8B0000/FFFFFF?text=Pachet+Familie',
        'weight' => 'Pachet complet',
        'items' => [
            '2x Dulcețuri (căpșuni, caise)',
            '1x Zacuscă tradițională',
            '1x Miere de salcâm',
            '1x Telemea de oaie',
            '1x Cârnați de țară'
        ]
    ],
    [
        'id' => 109,
        'name' => 'Pachetul Cadou',
        'description' => 'Pachet premium pentru cadouri speciale',
        'regular_price' => 259.99,
        'sale_price' => 199.99,
        'discount_percent' => 23,
        'image' => 'https://via.placeholder.com/300x200/8B0000/FFFFFF?text=Pachet+Cadou',
        'weight' => 'Pachet cadou',
        'items' => [
            '1x Țuică de prune premium',
            '1x Miere de tei',
            '1x Dulceață de trandafiri',
            '1x Brânză de burduf',
            'Ambalaj cadou elegant'
        ]
    ]
];

// Update response
$response['success'] = true;
$response['packages'] = $packages;

// Return response
echo json_encode($response);
exit;
?>