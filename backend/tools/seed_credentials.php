<?php

declare(strict_types=1);

use Backend\Database;
use Backend\Config;

require_once __DIR__ . '/../src/Config.php';
require_once __DIR__ . '/../src/Database.php';

Config::loadEnv(__DIR__ . '/../.env');

$db = Database::connection();

$users = $db->query('SELECT User_ID, Email, UserType FROM user_elibrary ORDER BY User_ID ASC')->fetchAll(PDO::FETCH_ASSOC);

if (!$users) {
    echo "No users found in user_elibrary.\n";
    exit(0);
}

$stmt = $db->prepare(
    'INSERT INTO user_credentials (User_ID, PasswordHash, LastPasswordChange)
     VALUES (:id, :hash, NOW())
     ON DUPLICATE KEY UPDATE PasswordHash = VALUES(PasswordHash), LastPasswordChange = NOW()'
);

foreach ($users as $user) {
    $type = strtolower((string) $user['UserType']);
    $defaultPassword = match ($type) {
        'librarian' => 'Lib@123456',
        'guest' => 'Guest@123',
        default => 'Member@123',
    };

    if (($user['Email'] ?? '') === 'member@library.edu') {
        $defaultPassword = 'Member@123';
    }
    if (($user['Email'] ?? '') === 'librarian@library.edu') {
        $defaultPassword = 'Lib@123456';
    }

    $stmt->execute([
        'id' => (int) $user['User_ID'],
        'hash' => password_hash($defaultPassword, PASSWORD_DEFAULT),
    ]);

    echo sprintf("Seeded credentials for user %d (%s).\n", (int) $user['User_ID'], (string) $user['Email']);
}

echo "Done.\n";
