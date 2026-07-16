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
                notes,
                total_rounds,
                current_round
            )
            VALUES (?, ?, ?, ?, ?, ?)
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            $data['event_id'],
            $data['format'],
            $data['status'] ?? 'setup',
            $data['notes'] ?? null,
            $data['total_rounds'] ?? 5,
            $data['current_round'] ?? 0,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function markRoundGenerated(int $id, int $roundNumber): bool
    {
        $sql = "
            UPDATE tournaments
            SET status = 'running',
                current_round = ?,
                started_at = COALESCE(started_at, ?)
            WHERE id = ?
        ";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([$roundNumber, $this->currentTimestamp(), $id]);
    }

    public function updateSettings(int $id, int $totalRounds): bool
    {
        $sql = "
            UPDATE tournaments
            SET total_rounds = ?
            WHERE id = ?
        ";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([$totalRounds, $id]);
    }

    public function complete(int $id): bool
    {
        $sql = "
            UPDATE tournaments
            SET status = 'completed',
                completed_at = ?
            WHERE id = ?
        ";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([$this->currentTimestamp(), $id]);
    }
}
