<?php

declare(strict_types=1);

use Backend\AuthService;
use Backend\Config;
use Backend\Database;
use Backend\Http;
use Backend\UserService;

require_once __DIR__ . '/../src/Config.php';
require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/Http.php';
require_once __DIR__ . '/../src/AuthService.php';
require_once __DIR__ . '/../src/UserService.php';

Backend\Config::loadEnv(__DIR__ . '/../.env');

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, OPTIONS');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$path = Http::routePath();

try {
    $db = Database::connection();
    $authService = new AuthService($db);
    $userService = new UserService($db);

    if ($method === 'GET' && $path === '/api/health') {
        Http::json([
            'ok' => true,
            'service' => 'elibrary-php-api',
            'timestamp' => gmdate('c'),
        ]);
        exit;
    }

    // --------------------
    // 1) Authentication
    // --------------------
    if ($method === 'POST' && $path === '/api/auth/login') {
        $body = Http::jsonBody();
        $email = strtolower(trim((string) ($body['email'] ?? '')));
        $password = (string) ($body['password'] ?? '');
        $userType = isset($body['userType']) ? trim((string) $body['userType']) : null;

        if ($email === '' || $password === '') {
            Http::json(['ok' => false, 'message' => 'Email and password are required.'], 422);
            exit;
        }

        $result = $authService->login($email, $password, $userType);
        if (!$result) {
            Http::json(['ok' => false, 'message' => 'Invalid credentials or inactive account.'], 401);
            exit;
        }

        Http::json(['ok' => true, 'data' => $result]);
        exit;
    }

    // --------------------
    // 2) Password Recovery
    // --------------------
    if ($method === 'POST' && $path === '/api/auth/forgot-password') {
        $body = Http::jsonBody();
        $email = strtolower(trim((string) ($body['email'] ?? '')));

        if ($email === '') {
            Http::json(['ok' => false, 'message' => 'Email is required.'], 422);
            exit;
        }

        $result = $authService->forgotPassword($email);
        Http::json([
            'ok' => true,
            'message' => 'If the email exists, a reset link has been created.',
            'data' => $result,
        ]);
        exit;
    }

    if ($method === 'POST' && $path === '/api/auth/reset-password') {
        $body = Http::jsonBody();
        $token = trim((string) ($body['token'] ?? ''));
        $newPassword = (string) ($body['newPassword'] ?? '');

        if ($token === '' || $newPassword === '') {
            Http::json(['ok' => false, 'message' => 'Token and newPassword are required.'], 422);
            exit;
        }

        if (strlen($newPassword) < 8) {
            Http::json(['ok' => false, 'message' => 'Password must be at least 8 characters.'], 422);
            exit;
        }

        $ok = $authService->resetPassword($token, $newPassword);
        if (!$ok) {
            Http::json(['ok' => false, 'message' => 'Invalid or expired reset token.'], 400);
            exit;
        }

        Http::json(['ok' => true, 'message' => 'Password has been reset successfully.']);
        exit;
    }

    if ($method === 'POST' && $path === '/api/auth/change-password') {
        $token = Http::bearerToken();
        if (!$token) {
            Http::json(['ok' => false, 'message' => 'Missing bearer token.'], 401);
            exit;
        }

        $user = $authService->getUserFromToken($token);
        if (!$user) {
            Http::json(['ok' => false, 'message' => 'Invalid or expired session.'], 401);
            exit;
        }

        $body = Http::jsonBody();
        $currentPassword = (string) ($body['currentPassword'] ?? '');
        $newPassword = (string) ($body['newPassword'] ?? '');

        if ($currentPassword === '' || $newPassword === '') {
            Http::json(['ok' => false, 'message' => 'currentPassword and newPassword are required.'], 422);
            exit;
        }

        if (strlen($newPassword) < 8) {
            Http::json(['ok' => false, 'message' => 'New password must be at least 8 characters.'], 422);
            exit;
        }

        $ok = $authService->changePassword((int) $user['id'], $currentPassword, $newPassword);
        if (!$ok) {
            Http::json(['ok' => false, 'message' => 'Current password is incorrect.'], 400);
            exit;
        }

        Http::json(['ok' => true, 'message' => 'Password changed successfully.']);
        exit;
    }

    // --------------------
    // 3) User Management
    // --------------------
    if ($method === 'GET' && $path === '/api/users') {
        $users = $userService->list([
            'search' => trim((string) ($_GET['search'] ?? '')),
            'type' => trim((string) ($_GET['type'] ?? '')),
            'status' => trim((string) ($_GET['status'] ?? '')),
        ]);
        Http::json(['ok' => true, 'data' => $users]);
        exit;
    }

    if ($method === 'GET' && preg_match('#^/api/users/(\d+)$#', $path, $m) === 1) {
        $user = $userService->getById((int) $m[1]);
        if (!$user) {
            Http::json(['ok' => false, 'message' => 'User not found.'], 404);
            exit;
        }

        Http::json(['ok' => true, 'data' => $user]);
        exit;
    }

    if ($method === 'POST' && $path === '/api/users') {
        $body = Http::jsonBody();
        $required = ['fullName', 'email', 'userType'];
        foreach ($required as $field) {
            if (trim((string) ($body[$field] ?? '')) === '') {
                Http::json(['ok' => false, 'message' => "$field is required."], 422);
                exit;
            }
        }

        $result = $userService->create($body);
        Http::json(['ok' => true, 'message' => 'User created.', 'data' => $result], 201);
        exit;
    }

    if ($method === 'PUT' && preg_match('#^/api/users/(\d+)$#', $path, $m) === 1) {
        $id = (int) $m[1];
        $body = Http::jsonBody();

        $ok = $userService->update($id, $body);
        if (!$ok) {
            Http::json(['ok' => false, 'message' => 'User not found.'], 404);
            exit;
        }

        Http::json(['ok' => true, 'message' => 'User updated successfully.']);
        exit;
    }

    if ($method === 'PATCH' && preg_match('#^/api/users/(\d+)/status$#', $path, $m) === 1) {
        $id = (int) $m[1];
        $body = Http::jsonBody();
        $status = trim((string) ($body['accountStatus'] ?? ''));

        $allowed = ['Active', 'Inactive', 'Suspended'];
        if (!in_array($status, $allowed, true)) {
            Http::json(['ok' => false, 'message' => 'Invalid accountStatus value.'], 422);
            exit;
        }

        $ok = $userService->updateStatus($id, $status);
        if (!$ok) {
            Http::json(['ok' => false, 'message' => 'User not found.'], 404);
            exit;
        }

        Http::json(['ok' => true, 'message' => 'User status updated.']);
        exit;
    }

    Http::json(['ok' => false, 'message' => 'Route not found.'], 404);
} catch (Throwable $e) {
    Http::json([
        'ok' => false,
        'message' => 'Server error',
        'error' => Config::get('APP_ENV', 'development') === 'production' ? null : $e->getMessage(),
    ], 500);
}
