<?php

require_once __DIR__ . '/../Core/Model.php';

class Tournament extends Model
{
    public function findByEventId(int $eventId): ?array
    {
        $sql = "
            SELECT *
            FROM tournaments
            WHERE event_id = ?
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$eventId]);

        $tournament = $stmt->fetch();

        return $tournament ?: null;
    }

    public function create(array $data): int
    {
        $sql = "
            INSERT INTO tournaments (
                event_id,
                format,
                status,
                notes
            )
            VALUES (?, ?, ?, ?)
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            $data['event_id'],
            $data['format'],
            $data['status'] ?? 'setup',
            $data['notes'] ?? null,
        ]);

        return (int) $this->db->lastInsertId();
    }
}