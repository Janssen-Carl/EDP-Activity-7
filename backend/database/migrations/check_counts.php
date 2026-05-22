<?php
require_once __DIR__ . '/../../src/Config.php';
require_once __DIR__ . '/../../src/Database.php';

Backend\Config::loadEnv(__DIR__ . '/../../.env');

try {
    $db = Backend\Database::connection();
    
    $tables = [
        'user_elibrary', 'category', 'book', 'physical_book', 'digital_book', 
        'book_inventory', 'borrowing_record', 'return_transaction', 'inventory_count'
    ];
    
    foreach ($tables as $table) {
        $stmt = $db->query("SELECT COUNT(*) FROM $table");
        $count = $stmt->fetchColumn();
        echo str_pad($table, 25) . ": $count rows\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
