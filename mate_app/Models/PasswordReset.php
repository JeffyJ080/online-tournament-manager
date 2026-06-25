<?php

require_once __DIR__ . '/../Core/Model.php';

class PasswordReset extends Model
{
    public function create(int $userId, string $token, DateTime $expiresAt): void
    {
        $this->db->prepare("
            UPDATE password_resets
            SET used_at = NOW()
            WHERE user_id = ?
              AND used_at IS NULL
        ")->execute([$userId]);

        $stmt = $this->db->prepare("
            INSERT INTO password_resets (user_id, token_hash, expires_at)
            VALUES (?, ?, ?)
        ");

        $stmt->execute([
            $userId,
            hash('sha256', $token),
            $expiresAt->format('Y-m-d H:i:s'),
        ]);
    }

    public function findValidByToken(string $token): ?array
    {
        $stmt = $this->db->prepare("
            SELECT password_resets.*, users.email, users.status
            FROM password_resets
            INNER JOIN users ON password_resets.user_id = users.id
            WHERE password_resets.token_hash = ?
              AND password_resets.used_at IS NULL
              AND password_resets.expires_at > NOW()
            LIMIT 1
        ");

        $stmt->execute([hash('sha256', $token)]);
        $reset = $stmt->fetch();

        return $reset ?: null;
    }

    public function markUsed(int $id): bool
    {
        $stmt = $this->db->prepare("
            UPDATE password_resets
            SET used_at = NOW()
            WHERE id = ?
        ");

        return $stmt->execute([$id]);
    }
}
