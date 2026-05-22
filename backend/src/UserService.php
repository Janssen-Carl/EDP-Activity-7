<?php

declare(strict_types=1);

namespace Backend;

use PDO;

final class UserService
{
    public function __construct(private PDO $db)
    {
    }

    public function list(array $filters): array
    {
        $where = [];
        $params = [];

        if (!empty($filters['search'])) {
            $where[] = '(u.FullName LIKE :search OR u.Email LIKE :search)';
            $params['search'] = '%' . $filters['search'] . '%';
        }

        if (!empty($filters['type'])) {
            $where[] = 'u.UserType = :type';
            $params['type'] = $filters['type'];
        }

        if (!empty($filters['status'])) {
            $where[] = 'u.AccountStatus = :status';
            $params['status'] = $filters['status'];
        }

        $sql = 'SELECT u.User_ID, u.FullName, u.PhoneNumber, u.Email, u.AccountStatus, u.UserType,
                       COALESCE(ma.DateJoined, ga.DateJoined, la.DateHired) AS DateJoined
                FROM user_elibrary u
                LEFT JOIN member_account ma ON ma.User_ID = u.User_ID
                LEFT JOIN guest_account ga ON ga.User_ID = u.User_ID
                LEFT JOIN librarian_account la ON la.User_ID = u.User_ID';

        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' ORDER BY u.User_ID ASC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT u.User_ID, u.FullName, u.PhoneNumber, u.Email, u.AccountStatus, u.UserType
             FROM user_elibrary u
             WHERE u.User_ID = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function create(array $payload): array
    {
        $this->db->beginTransaction();
        try {
            $nextId = (int) $this->db->query('SELECT COALESCE(MAX(User_ID), 0) + 1 FROM user_elibrary')->fetchColumn();

            $insert = $this->db->prepare(
                'INSERT INTO user_elibrary (User_ID, FullName, PhoneNumber, Email, AccountStatus, UserType)
                 VALUES (:id, :name, :phone, :email, :status, :type)'
            );

            $insert->execute([
                'id' => $nextId,
                'name' => $payload['fullName'],
                'phone' => $payload['phoneNumber'] ?? null,
                'email' => $payload['email'],
                'status' => $payload['accountStatus'] ?? 'Active',
                'type' => $payload['userType'],
            ]);

            $password = (string) ($payload['password'] ?? 'ChangeMe@123');
            $cred = $this->db->prepare(
                'INSERT INTO user_credentials (User_ID, PasswordHash, LastPasswordChange) VALUES (:id, :hash, NOW())'
            );
            $cred->execute([
                'id' => $nextId,
                'hash' => password_hash($password, PASSWORD_DEFAULT),
            ]);

            $this->upsertSubtype($nextId, $payload['userType']);

            $this->db->commit();
            return ['userId' => $nextId];
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function update(int $id, array $payload): bool
    {
        $current = $this->getById($id);
        if (!$current) {
            return false;
        }

        $newType = $payload['userType'] ?? $current['UserType'];

        $stmt = $this->db->prepare(
            'UPDATE user_elibrary
             SET FullName = :name,
                 PhoneNumber = :phone,
                 Email = :email,
                 AccountStatus = :status,
                 UserType = :type
             WHERE User_ID = :id'
        );

        $stmt->execute([
            'id' => $id,
            'name' => $payload['fullName'] ?? $current['FullName'],
            'phone' => $payload['phoneNumber'] ?? $current['PhoneNumber'],
            'email' => $payload['email'] ?? $current['Email'],
            'status' => $payload['accountStatus'] ?? $current['AccountStatus'],
            'type' => $newType,
        ]);

        $this->upsertSubtype($id, (string) $newType);
        return true;
    }

    public function updateStatus(int $id, string $status): bool
    {
        $stmt = $this->db->prepare('UPDATE user_elibrary SET AccountStatus = :status WHERE User_ID = :id');
        $stmt->execute([
            'id' => $id,
            'status' => $status,
        ]);

        return $stmt->rowCount() > 0;
    }

    private function upsertSubtype(int $userId, string $userType): void
    {
        $this->db->prepare('DELETE FROM member_account WHERE User_ID = :id')->execute(['id' => $userId]);
        $this->db->prepare('DELETE FROM librarian_account WHERE User_ID = :id')->execute(['id' => $userId]);
        $this->db->prepare('DELETE FROM guest_account WHERE User_ID = :id')->execute(['id' => $userId]);

        $today = date('Y-m-d');
        $normalized = strtolower(trim($userType));

        if ($normalized === 'member') {
            $stmt = $this->db->prepare(
                'INSERT INTO member_account (User_ID, MembershipLevel, DateJoined)
                 VALUES (:id, :level, :dateJoined)'
            );
            $stmt->execute([
                'id' => $userId,
                'level' => 'Silver',
                'dateJoined' => $today,
            ]);
            return;
        }

        if ($normalized === 'librarian') {
            $stmt = $this->db->prepare(
                'INSERT INTO librarian_account (User_ID, Role, DateHired)
                 VALUES (:id, :role, :dateHired)'
            );
            $stmt->execute([
                'id' => $userId,
                'role' => 'Librarian',
                'dateHired' => $today,
            ]);
            return;
        }

        $stmt = $this->db->prepare(
            'INSERT INTO guest_account (User_ID, AccessLevel, DateJoined)
             VALUES (:id, :accessLevel, :dateJoined)'
        );
        $stmt->execute([
            'id' => $userId,
            'accessLevel' => 'Read-Only',
            'dateJoined' => $today,
        ]);
    }
}
