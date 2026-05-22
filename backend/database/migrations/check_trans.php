<?php
require_once __DIR__ . '/../../src/Config.php';
require_once __DIR__ . '/../../src/Database.php';

Backend\Config::loadEnv(__DIR__ . '/../../.env');

try {
    $db = Backend\Database::connection();
    
    $tables = [
        'borrowing_transactions', 'return_transactions', 'inventory_counts', 'inventory_count_details'
    ];
    
    foreach ($tables as $table) {
        $stmt = $db->query("SELECT COUNT(*) FROM $table");
        $count = $stmt->fetchColumn();
        echo str_pad($table, 25) . ": $count rows\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
