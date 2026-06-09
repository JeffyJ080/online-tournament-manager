<?php

require_once __DIR__ . '/../Core/Model.php';

class EventReport extends Model
{
    public function create(array $data): int
    {
        $sql = "
            INSERT INTO event_reports (
                event_id,
                submitted_by,
                attendance_count,
                cash_collected,
                cash_handed_over,
                winner_notes,
                issue_notes,
                general_notes
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            $data['event_id'],
            $data['submitted_by'] ?? null,
            $data['attendance_count'] ?? 0,
            $data['cash_collected'] ?? 0,
            $data['cash_handed_over'] ?? 0,
            $data['winner_notes'] ?? null,
            $data['issue_notes'] ?? null,
            $data['general_notes'] ?? null,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function findByEventId(int $eventId): ?array
    {
        $sql = "
            SELECT *
            FROM event_reports
            WHERE event_id = ?
            ORDER BY submitted_at DESC
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$eventId]);

        $report = $stmt->fetch();

        return $report ?: null;
    }
}