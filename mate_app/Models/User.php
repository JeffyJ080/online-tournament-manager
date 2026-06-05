<?php

require_once __DIR__ . '/../Core/Model.php';

class User extends Model
{
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
}