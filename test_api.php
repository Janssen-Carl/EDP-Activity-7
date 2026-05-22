<?php
$endpoints = [
    'transaction-statistics',
    'borrowing-transactions',
    'return-transactions',
    'inventory-counts'
];

foreach ($endpoints as $endpoint) {
    echo "--- Testing endpoint: $endpoint ---\n";
    $url = "http://localhost:8000/transactions-api.php?endpoint=$endpoint";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "HTTP Code: $httpCode\n";
    echo substr($response, 0, 500) . "\n\n";
}
