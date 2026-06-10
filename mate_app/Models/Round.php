<?php

require_once __DIR__ . '/../Core/Model.php';

class Round extends Model
{
    public function create(array $data): int
    {
        $sql = "
            INSERT INTO rounds (
                tournament_id,
                round_number,
                name,
                status,
                started_at
            )
            VALUES (?, ?, ?, ?, ?)
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            $data['tournament_id'],
            $data['round_number'],
            $data['name'] ?? 'Round ' . $data['round_number'],
            $data['status'] ?? 'pending',
            $data['started_at'] ?? null,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function forTournament(int $tournamentId): array
    {
        $sql = "
            SELECT *
            FROM rounds
            WHERE tournament_id = ?
            ORDER BY round_number ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$tournamentId]);

        return $stmt->fetchAll();
    }

    public function findByTournamentAndNumber(int $tournamentId, int $roundNumber): ?array
    {
        $sql = "
            SELECT *
            FROM rounds
            WHERE tournament_id = ?
              AND round_number = ?
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$tournamentId, $roundNumber]);

        $round = $stmt->fetch();

        return $round ?: null;
    }

    public function markRunning(int $roundId): bool
    {
        $sql = "
            UPDATE rounds
            SET status = 'running',
                started_at = COALESCE(started_at, NOW())
            WHERE id = ?
        ";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([$roundId]);
    }

    public function markCompleted(int $roundId): bool
    {
        $sql = "
            UPDATE rounds
            SET status = 'completed',
                completed_at = NOW()
            WHERE id = ?
        ";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([$roundId]);
    }
}