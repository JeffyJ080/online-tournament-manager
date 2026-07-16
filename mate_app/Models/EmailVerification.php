<?php

require_once __DIR__ . '/../Core/Model.php';

class EmailVerification extends Model
{
    public function create(int $userId, string $token, DateTime $expiresAt): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO email_verifications (user_id, token_hash, expires_at)
            VALUES (?, ?, ?)
        ");

        $stmt->execute([
            $userId,
            hash('sha256', $token),
            $expiresAt->format('Y-m-d H:i:s'),
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function findValidByToken(string $token): ?array
    {
        $stmt = $this->db->prepare("
            SELECT *
            FROM email_verifications
            WHERE token_hash = ?
              AND used_at IS NULL
              AND expires_at > NOW()
            LIMIT 1
        ");

        $stmt->execute([hash('sha256', $token)]);
        $verification = $stmt->fetch();

        return $verification ?: null;
    }

    public function markUsed(int $id): bool
    {
        $stmt = $this->db->prepare("
            UPDATE email_verifications
            SET used_at = NOW()
            WHERE id = ?
        ");

        return $stmt->execute([$id]);
    }
}
