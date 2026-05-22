<?php
require_once __DIR__ . '/../../src/Config.php';
require_once __DIR__ . '/../../src/Database.php';

Backend\Config::loadEnv(__DIR__ . '/../../.env');

try {
    $db = Backend\Database::connection();

    $files = [
        __DIR__ . '/001_create_transactions_tables.sql',
        __DIR__ . '/002_seed_book_inventory.sql'
    ];

    foreach ($files as $file) {
        if (file_exists($file)) {
            $sql = file_get_contents($file);
            $db->exec($sql);
            echo "Executed " . basename($file) . "\n";
        } else {
            echo "File not found: " . basename($file) . "\n";
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
