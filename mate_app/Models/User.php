<?php

require_once __DIR__ . '/../Core/Model.php';

class User extends Model
{
    public function allWithRoles(): array
    {
        $sql = "
            SELECT users.*, roles.name AS role_name
            FROM users
            INNER JOIN roles ON users.role_id = roles.id
            ORDER BY roles.name ASC, users.email ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function roles(): array
    {
        $sql = "
            SELECT *
            FROM roles
            ORDER BY name ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function usersByRole(array $roleNames): array
    {
        if (empty($roleNames)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($roleNames), '?'));

        $sql = "
            SELECT users.*, roles.name AS role_name
            FROM users
            INNER JOIN roles ON users.role_id = roles.id
            WHERE roles.name IN ($placeholders)
              AND users.status = 'active'
            ORDER BY users.email ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($roleNames);

        return $stmt->fetchAll();
    }

    public function findByEmail(string $email): ?array
    {
        $sql = "
            SELECT users.*, roles.name AS role_name
            FROM users
            INNER JOIN roles ON users.role_id = roles.id
            WHERE users.email = ?
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$email]);

        $user = $stmt->fetch();

        return $user ?: null;
    }

    public function findById(int $id): ?array
    {
        $sql = "
            SELECT users.*, roles.name AS role_name
            FROM users
            INNER JOIN roles ON users.role_id = roles.id
            WHERE users.id = ?
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);

        $user = $stmt->fetch();

        return $user ?: null;
    }

    public function getRoleIdByName(string $roleName): ?int
    {
        $sql = "SELECT id FROM roles WHERE name = ? LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$roleName]);

        $role = $stmt->fetch();

        return $role ? (int) $role['id'] : null;
    }

    public function create(string $email, string $password, string $roleName = 'player'): int
    {
        $roleId = $this->getRoleIdByName($roleName);

        if ($roleId === null) {
            throw new Exception("Role not found: {$roleName}");
        }

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        $sql = "
            INSERT INTO users (role_id, email, password_hash)
            VALUES (?, ?, ?)
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $roleId,
            $email,
            $passwordHash
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function updateRoleAndStatus(int $id, string $roleName, string $status): bool
    {
        $roleId = $this->getRoleIdByName($roleName);

        if ($roleId === null) {
            throw new Exception("Role not found: {$roleName}");
        }

        $sql = "
            UPDATE users
            SET role_id = ?,
                status = ?
            WHERE id = ?
        ";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([$roleId, $status, $id]);
    }

    public function updatePassword(int $id, string $password): bool
    {
        $sql = "
            UPDATE users
            SET password_hash = ?
            WHERE id = ?
        ";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            password_hash($password, PASSWORD_DEFAULT),
            $id,
        ]);
    }

    public function markEmailVerified(int $id): bool
    {
        $stmt = $this->db->prepare("
            UPDATE users
            SET email_verified_at = COALESCE(email_verified_at, NOW())
            WHERE id = ?
        ");

        return $stmt->execute([$id]);
    }
}
