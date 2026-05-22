<?php

declare(strict_types=1);

namespace Backend;

use DateInterval;
use DateTimeImmutable;
use PDO;

final class AuthService
{
    public function __construct(private PDO $db)
    {
    }

    public function login(string $email, string $password, ?string $userType = null): ?array
    {
        $sql = 'SELECT u.User_ID, u.FullName, u.Email, u.UserType, u.AccountStatus, c.PasswordHash
                FROM user_elibrary u
                INNER JOIN user_credentials c ON c.User_ID = u.User_ID
                WHERE u.Email = :email
                LIMIT 1';

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        if (!$user) {
            return null;
        }

        if (!password_verify($password, (string) $user['PasswordHash'])) {
            return null;
        }

        if ($userType !== null && strcasecmp((string) $user['UserType'], $userType) !== 0) {
            return null;
        }

        if (strcasecmp((string) ($user['AccountStatus'] ?? ''), 'Active') !== 0) {
            return null;
        }

        $token = bin2hex(random_bytes(32));
        $expiresAt = (new DateTimeImmutable())->add(new DateInterval('PT24H'))->format('Y-m-d H:i:s');

        $insert = $this->db->prepare(
            'INSERT INTO api_sessions (SessionToken, User_ID, ExpiresAt) VALUES (:token, :userId, :expiresAt)'
        );
        $insert->execute([
            'token' => $token,
            'userId' => (int) $user['User_ID'],
            'expiresAt' => $expiresAt,
        ]);

        $this->log((int) $user['User_ID'], 'Logged In (API)');

        return [
            'token' => $token,
            'expiresAt' => $expiresAt,
            'user' => [
                'id' => (int) $user['User_ID'],
                'fullName' => $user['FullName'],
                'email' => $user['Email'],
                'userType' => $user['UserType'],
                'accountStatus' => $user['AccountStatus'],
            ],
        ];
    }

    public function getUserFromToken(string $token): ?array
    {
        $sql = 'SELECT s.User_ID, s.ExpiresAt, u.FullName, u.Email, u.UserType, u.AccountStatus
                FROM api_sessions s
                INNER JOIN user_elibrary u ON u.User_ID = s.User_ID
                WHERE s.SessionToken = :token
                LIMIT 1';

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['token' => $token]);
        $session = $stmt->fetch();

        if (!$session) {
            return null;
        }

        if (new DateTimeImmutable((string) $session['ExpiresAt']) < new DateTimeImmutable()) {
            return null;
        }

        return [
            'id' => (int) $session['User_ID'],
            'fullName' => $session['FullName'],
            'email' => $session['Email'],
            'userType' => $session['UserType'],
            'accountStatus' => $session['AccountStatus'],
        ];
    }

    public function forgotPassword(string $email): array
    {
        $stmt = $this->db->prepare('SELECT User_ID FROM user_elibrary WHERE Email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        if (!$user) {
            return ['ok' => true];
        }

        $userId = (int) $user['User_ID'];
        $rawToken = bin2hex(random_bytes(24));
        $hashedToken = hash('sha256', $rawToken);
        $expiresAt = (new DateTimeImmutable())->add(new DateInterval('PT30M'))->format('Y-m-d H:i:s');

        $invalidate = $this->db->prepare('UPDATE password_reset_tokens SET Used = 1 WHERE User_ID = :userId');
        $invalidate->execute(['userId' => $userId]);

        $insert = $this->db->prepare(
            'INSERT INTO password_reset_tokens (User_ID, TokenHash, ExpiresAt) VALUES (:userId, :tokenHash, :expiresAt)'
        );
        $insert->execute([
            'userId' => $userId,
            'tokenHash' => $hashedToken,
            'expiresAt' => $expiresAt,
        ]);

        $this->log($userId, 'Requested Password Reset (API)');

        $response = ['ok' => true];
        if (Config::get('APP_ENV', 'development') !== 'production') {
            $response['devResetToken'] = $rawToken;
            $response['expiresAt'] = $expiresAt;
        }

        return $response;
    }

    public function resetPassword(string $token, string $newPassword): bool
    {
        $tokenHash = hash('sha256', $token);

        $sql = 'SELECT ResetID, User_ID, ExpiresAt, Used
                FROM password_reset_tokens
                WHERE TokenHash = :tokenHash
                ORDER BY ResetID DESC
                LIMIT 1';

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['tokenHash' => $tokenHash]);
        $row = $stmt->fetch();

        if (!$row) {
            return false;
        }

        if ((int) $row['Used'] === 1) {
            return false;
        }

        if (new DateTimeImmutable((string) $row['ExpiresAt']) < new DateTimeImmutable()) {
            return false;
        }

        $this->updatePassword((int) $row['User_ID'], $newPassword);

        $markUsed = $this->db->prepare('UPDATE password_reset_tokens SET Used = 1 WHERE ResetID = :resetId');
        $markUsed->execute(['resetId' => (int) $row['ResetID']]);

        $this->log((int) $row['User_ID'], 'Reset Password (API)');
        return true;
    }

    public function changePassword(int $userId, string $currentPassword, string $newPassword): bool
    {
        $stmt = $this->db->prepare('SELECT PasswordHash FROM user_credentials WHERE User_ID = :userId LIMIT 1');
        $stmt->execute(['userId' => $userId]);
        $cred = $stmt->fetch();

        if (!$cred || !password_verify($currentPassword, (string) $cred['PasswordHash'])) {
            return false;
        }

        $this->updatePassword($userId, $newPassword);
        $this->log($userId, 'Changed Password (API)');
        return true;
    }

    private function updatePassword(int $userId, string $newPassword): void
    {
        $hash = password_hash($newPassword, PASSWORD_DEFAULT);

        $sql = 'INSERT INTO user_credentials (User_ID, PasswordHash, LastPasswordChange)
                VALUES (:userId, :hash, NOW())
                ON DUPLICATE KEY UPDATE PasswordHash = VALUES(PasswordHash), LastPasswordChange = NOW()';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'userId' => $userId,
            'hash' => $hash,
        ]);

        $deleteSessions = $this->db->prepare('DELETE FROM api_sessions WHERE User_ID = :userId');
        $deleteSessions->execute(['userId' => $userId]);
    }

    private function log(int $userId, string $action): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO logger (User_ID, DateLog, TimeLog, Action) VALUES (:userId, CURDATE(), CURTIME(), :action)'
        );
        $stmt->execute([
            'userId' => $userId,
            'action' => $action,
        ]);
    }
}
