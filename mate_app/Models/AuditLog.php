<?php

require_once __DIR__ . '/../Core/Model.php';

class AuditLog extends Model
{
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