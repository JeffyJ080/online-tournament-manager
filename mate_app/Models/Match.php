<?php

require_once __DIR__ . '/../Core/Model.php';

class MatchModel extends Model
{
    public function create(array $data): int
    {
        $sql = "
            INSERT INTO matches (
                tournament_id,
                round_id,
                white_registration_id,
                black_registration_id,
                white_score,
                black_score,
                result,
                board_number,
                status
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            $data['tournament_id'],
            $data['round_id'],
            $data['white_registration_id'] ?? null,
            $data['black_registration_id'] ?? null,
            $data['white_score'] ?? null,
            $data['black_score'] ?? null,
            $data['result'] ?? 'pending',
            $data['board_number'] ?? null,
            $data['status'] ?? 'scheduled',
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function forTournament(int $tournamentId): array
    {
        $sql = "
            SELECT
                matches.*,
                rounds.round_number,
                rounds.name AS round_name,
                white_registration.display_name AS white_display_name,
                white_registration.full_name AS white_full_name,
                black_registration.display_name AS black_display_name,
                black_registration.full_name AS black_full_name
            FROM matches
            LEFT JOIN rounds
                ON matches.round_id = rounds.id
            LEFT JOIN event_registrations AS white_registration
                ON matches.white_registration_id = white_registration.id
            LEFT JOIN event_registrations AS black_registration
                ON matches.black_registration_id = black_registration.id
            WHERE matches.tournament_id = ?
            ORDER BY rounds.round_number ASC, matches.board_number ASC, matches.id ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$tournamentId]);

        return $stmt->fetchAll();
    }

    public function forRound(int $roundId): array
    {
        $sql = "
            SELECT *
            FROM matches
            WHERE round_id = ?
            ORDER BY board_number ASC, id ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$roundId]);

        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $sql = "
            SELECT *
            FROM matches
            WHERE id = ?
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);

        $match = $stmt->fetch();

        return $match ?: null;
    }

    public function updateResult(int $id, string $result, int $reportedBy): bool
    {
        [$whiteScore, $blackScore] = $this->scoresForResult($result);

        $sql = "
            UPDATE matches
            SET result = ?,
                white_score = ?,
                black_score = ?,
                status = 'completed',
                reported_by = ?,
                reported_at = NOW()
            WHERE id = ?
        ";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            $result,
            $whiteScore,
            $blackScore,
            $reportedBy,
            $id,
        ]);
    }

    public function roundIsComplete(int $roundId): bool
    {
        $sql = "
            SELECT COUNT(*) AS total
            FROM matches
            WHERE round_id = ?
              AND status <> 'completed'
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$roundId]);

        $result = $stmt->fetch();

        return (int) ($result['total'] ?? 0) === 0;
    }

    public function existsForRound(int $roundId): bool
    {
        $sql = "
            SELECT id
            FROM matches
            WHERE round_id = ?
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$roundId]);

        return (bool) $stmt->fetch();
    }

    public function completedForTournament(int $tournamentId): array
    {
        $sql = "
            SELECT *
            FROM matches
            WHERE tournament_id = ?
              AND status = 'completed'
            ORDER BY id ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$tournamentId]);

        return $stmt->fetchAll();
    }

    public function playerHadBye(int $tournamentId, int $registrationId): bool
    {
        $sql = "
            SELECT id
            FROM matches
            WHERE tournament_id = ?
              AND result = 'bye'
              AND white_registration_id = ?
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$tournamentId, $registrationId]);

        return (bool) $stmt->fetch();
    }

    public function playersHavePlayed(int $tournamentId, int $firstRegistrationId, int $secondRegistrationId): bool
    {
        $sql = "
            SELECT id
            FROM matches
            WHERE tournament_id = ?
              AND (
                    (white_registration_id = ? AND black_registration_id = ?)
                 OR (white_registration_id = ? AND black_registration_id = ?)
              )
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $tournamentId,
            $firstRegistrationId,
            $secondRegistrationId,
            $secondRegistrationId,
            $firstRegistrationId,
        ]);

        return (bool) $stmt->fetch();
    }

    public function winnersForRound(int $roundId): array
    {
        $matches = $this->forRound($roundId);
        $winners = [];

        foreach ($matches as $match) {
            if (($match['result'] ?? '') === 'white_win' || ($match['result'] ?? '') === 'bye') {
                $winners[] = (int) $match['white_registration_id'];
            }

            if (($match['result'] ?? '') === 'black_win') {
                $winners[] = (int) $match['black_registration_id'];
            }
        }

        return array_values(array_filter($winners));
    }

    private function scoresForResult(string $result): array
    {
        return match ($result) {
            'white_win', 'bye' => [1.0, 0.0],
            'black_win' => [0.0, 1.0],
            'draw' => [0.5, 0.5],
            'forfeit' => [0.0, 0.0],
            default => [null, null],
        };
    }
}
