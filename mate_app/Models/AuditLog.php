<?php

require_once __DIR__ . '/../Core/Model.php';

class AuditLog extends Model
{
    public function latest(int $limit = 200): array
    {
        $sql = "
            SELECT
                audit_logs.*,
                users.email AS user_email,
                roles.name AS user_role
            FROM audit_logs
            LEFT JOIN users ON audit_logs.user_id = users.id
            INNER JOIN roles ON users.role_id = roles.id
            ORDER BY audit_logs.created_at DESC, audit_logs.id DESC
            LIMIT ?
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function create(
        ?int $userId,
        string $action,
        ?string $entityType = null,
        ?int $entityId = null,
        ?string $description = null
    ): int {
        $sql = "
            INSERT INTO audit_logs (
                user_id,
                action,
                entity_type,
                entity_id,
                description,
                ip_address,
                user_agent
            )
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            $userId,
            $action,
            $entityType,
            $entityId,
            $description,
            $this->getIpAddress(),
            $this->getUserAgent()
        ]);

        return (int) $this->db->lastInsertId();
    }

    private function getIpAddress(): ?string
    {
        return $_SERVER['REMOTE_ADDR'] ?? null;
    }

    private function getUserAgent(): ?string
    {
        return $_SERVER['HTTP_USER_AGENT'] ?? null;
    }
}
