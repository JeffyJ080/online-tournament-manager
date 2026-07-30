<?php

require_once __DIR__ . '/../Core/Model.php';

class TournamentParticipant extends Model
{
    public function importFromRegistrations(int $tournamentId, array $registrations): int
    {
        $imported = 0;
        $seed = $this->nextSeedNumber($tournamentId);

        foreach ($registrations as $registration) {
            if (($registration['registration_status'] ?? '') !== 'checked_in') {
                continue;
            }

            if ($this->exists($tournamentId, (int) $registration['id'])) {
                continue;
            }

            $rating = $this->ratingAtTournamentEntry($registration);

            $sql = "
                INSERT INTO tournament_participants (
                    tournament_id,
                    event_registration_id,
                    seed_number,
                    starting_rating,
                    current_score,
                    status
                )
                VALUES (?, ?, ?, ?, 0.0, 'active')
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $tournamentId,
                (int) $registration['id'],
                $seed,
                $rating,
            ]);

            $imported++;
            $seed++;
        }

        return $imported;
    }

    public function missingCheckedInRegistrations(int $tournamentId, int $eventId): array
    {
        $sql = "
            SELECT
                event_registrations.*
            FROM event_registrations
            LEFT JOIN tournament_participants
                ON tournament_participants.event_registration_id = event_registrations.id
                AND tournament_participants.tournament_id = ?
            WHERE event_registrations.event_id = ?
              AND event_registrations.registration_status = 'checked_in'
              AND tournament_participants.id IS NULL
            ORDER BY event_registrations.registered_at ASC,
                     event_registrations.id ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$tournamentId, $eventId]);

        return $stmt->fetchAll();
    }

    public function exists(int $tournamentId, int $registrationId): bool
    {
        $sql = "
            SELECT id
            FROM tournament_participants
            WHERE tournament_id = ?
              AND event_registration_id = ?
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$tournamentId, $registrationId]);

        return (bool) $stmt->fetch();
    }

    private function nextSeedNumber(int $tournamentId): int
    {
        $sql = "
            SELECT COALESCE(MAX(seed_number), 0) + 1 AS next_seed
            FROM tournament_participants
            WHERE tournament_id = ?
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$tournamentId]);

        $result = $stmt->fetch();

        return (int) ($result['next_seed'] ?? 1);
    }

    public function forTournament(int $tournamentId): array
    {
        $sql = "
            SELECT
                tournament_participants.*,
                COALESCE(NULLIF(players.real_name, ''), event_registrations.full_name) AS full_name,
                COALESCE(NULLIF(players.display_name, ''), NULLIF(event_registrations.display_name, '')) AS display_name,
                event_registrations.email,
                event_registrations.rating_category
            FROM tournament_participants
            INNER JOIN event_registrations
                ON tournament_participants.event_registration_id = event_registrations.id
            LEFT JOIN players
                ON event_registrations.player_id = players.id
            WHERE tournament_participants.tournament_id = ?
            ORDER BY tournament_participants.seed_number ASC, tournament_participants.id ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$tournamentId]);

        return $stmt->fetchAll();
    }

    public function countForTournament(int $tournamentId): int
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) AS total
            FROM tournament_participants
            WHERE tournament_id = ?
        ");

        $stmt->execute([$tournamentId]);
        $result = $stmt->fetch();

        return (int) ($result['total'] ?? 0);
    }

    public function activeForTournament(int $tournamentId): array
    {
        $sql = "
            SELECT
                tournament_participants.*,
                COALESCE(NULLIF(players.real_name, ''), event_registrations.full_name) AS full_name,
                COALESCE(NULLIF(players.display_name, ''), NULLIF(event_registrations.display_name, '')) AS display_name,
                event_registrations.email,
                event_registrations.rating_category
            FROM tournament_participants
            INNER JOIN event_registrations
                ON tournament_participants.event_registration_id = event_registrations.id
            LEFT JOIN players
                ON event_registrations.player_id = players.id
            WHERE tournament_participants.tournament_id = ?
              AND tournament_participants.status = 'active'
            ORDER BY tournament_participants.current_score DESC,
                     tournament_participants.seed_number ASC,
                     tournament_participants.id ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$tournamentId]);

        return $this->applyTieBreaks($tournamentId, $stmt->fetchAll());
    }

    public function standings(int $tournamentId): array
    {
        $sql = "
            SELECT
                tournament_participants.*,
                COALESCE(NULLIF(players.real_name, ''), event_registrations.full_name) AS full_name,
                COALESCE(NULLIF(players.display_name, ''), NULLIF(event_registrations.display_name, '')) AS display_name,
                event_registrations.email,
                event_registrations.rating_category
            FROM tournament_participants
            INNER JOIN event_registrations
                ON tournament_participants.event_registration_id = event_registrations.id
            LEFT JOIN players
                ON event_registrations.player_id = players.id
            WHERE tournament_participants.tournament_id = ?
            ORDER BY tournament_participants.current_score DESC,
                     tournament_participants.seed_number ASC,
                     tournament_participants.id ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$tournamentId]);

        return $this->applyTieBreaks($tournamentId, $stmt->fetchAll());
    }

    public function forRegistrationIds(int $tournamentId, array $registrationIds): array
    {
        $registrationIds = array_values(array_unique(array_map('intval', $registrationIds)));

        if (empty($registrationIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($registrationIds), '?'));

        $sql = "
            SELECT
                tournament_participants.*,
                COALESCE(NULLIF(players.real_name, ''), event_registrations.full_name) AS full_name,
                COALESCE(NULLIF(players.display_name, ''), NULLIF(event_registrations.display_name, '')) AS display_name,
                event_registrations.email,
                event_registrations.rating_category
            FROM tournament_participants
            INNER JOIN event_registrations
                ON tournament_participants.event_registration_id = event_registrations.id
            LEFT JOIN players
                ON event_registrations.player_id = players.id
            WHERE tournament_participants.tournament_id = ?
              AND tournament_participants.event_registration_id IN ($placeholders)
            ORDER BY tournament_participants.seed_number ASC,
                     tournament_participants.id ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(array_merge([$tournamentId], $registrationIds));

        return $stmt->fetchAll();
    }

    public function recalculateScores(int $tournamentId, array $completedMatches): void
    {
        $scores = [];

        foreach ($completedMatches as $match) {
            $whiteRegistrationId = (int) ($match['white_registration_id'] ?? 0);
            $blackRegistrationId = (int) ($match['black_registration_id'] ?? 0);

            if ($whiteRegistrationId > 0) {
                $scores[$whiteRegistrationId] = ($scores[$whiteRegistrationId] ?? 0) + (float) ($match['white_score'] ?? 0);
            }

            if ($blackRegistrationId > 0) {
                $scores[$blackRegistrationId] = ($scores[$blackRegistrationId] ?? 0) + (float) ($match['black_score'] ?? 0);
            }
        }

        $this->db->prepare("
            UPDATE tournament_participants
            SET current_score = 0.0
            WHERE tournament_id = ?
        ")->execute([$tournamentId]);

        $stmt = $this->db->prepare("
            UPDATE tournament_participants
            SET current_score = ?
            WHERE tournament_id = ?
              AND event_registration_id = ?
        ");

        foreach ($scores as $registrationId => $score) {
            $stmt->execute([$score, $tournamentId, $registrationId]);
        }
    }

    public function leaderboard(int $limit = 50): array
    {
        $sql = "
            SELECT
                event_registrations.full_name,
                event_registrations.display_name,
                event_registrations.email,
                SUM(tournament_participants.current_score) AS total_score,
                COUNT(DISTINCT tournament_participants.tournament_id) AS tournaments_played
            FROM tournament_participants
            INNER JOIN event_registrations
                ON tournament_participants.event_registration_id = event_registrations.id
            GROUP BY event_registrations.email,
                     event_registrations.full_name,
                     event_registrations.display_name
            ORDER BY total_score DESC,
                     tournaments_played DESC,
                     event_registrations.full_name ASC
            LIMIT ?
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    private function ratingFromCategory(string $category): int
    {
        return match ($category) {
            'casual' => 1000,
            'standard' => 1200,
            default => 800,
        };
    }

    private function ratingAtTournamentEntry(array $registration): int
    {
        $playerId = (int) ($registration['player_id'] ?? 0);

        if ($playerId > 0) {
            $stmt = $this->db->prepare("
                SELECT current_rating
                FROM players
                WHERE id = ?
                LIMIT 1
            ");
            $stmt->execute([$playerId]);
            $player = $stmt->fetch();

            if ($player) {
                return (int) $player['current_rating'];
            }
        }

        return $this->ratingFromCategory($registration['rating_category'] ?? 'beginner');
    }

    private function applyTieBreaks(int $tournamentId, array $standings): array
    {
        $scores = [];

        foreach ($standings as $standing) {
            $scores[(int) $standing['event_registration_id']] = (float) $standing['current_score'];
        }

        $matches = $this->completedMatchesForTieBreaks($tournamentId);
        $opponents = [];
        $headToHead = [];
        $sonnebornBerger = [];

        foreach ($matches as $match) {
            $whiteId = (int) ($match['white_registration_id'] ?? 0);
            $blackId = (int) ($match['black_registration_id'] ?? 0);

            if ($whiteId <= 0 || $blackId <= 0) {
                continue;
            }

            $whiteScore = (float) ($match['white_score'] ?? 0);
            $blackScore = (float) ($match['black_score'] ?? 0);

            $opponents[$whiteId][] = $blackId;
            $opponents[$blackId][] = $whiteId;
            $headToHead[$whiteId][$blackId] = ($headToHead[$whiteId][$blackId] ?? 0) + $whiteScore;
            $headToHead[$blackId][$whiteId] = ($headToHead[$blackId][$whiteId] ?? 0) + $blackScore;
            $sonnebornBerger[$whiteId] = ($sonnebornBerger[$whiteId] ?? 0) + ($whiteScore * ($scores[$blackId] ?? 0));
            $sonnebornBerger[$blackId] = ($sonnebornBerger[$blackId] ?? 0) + ($blackScore * ($scores[$whiteId] ?? 0));
        }

        foreach ($standings as &$standing) {
            $registrationId = (int) $standing['event_registration_id'];
            $standing['buchholz'] = 0.0;
            $standing['sonneborn_berger'] = round($sonnebornBerger[$registrationId] ?? 0, 2);
            $standing['head_to_head'] = 0.0;

            foreach ($opponents[$registrationId] ?? [] as $opponentId) {
                $standing['buchholz'] += $scores[$opponentId] ?? 0;
                $standing['head_to_head'] += $headToHead[$registrationId][$opponentId] ?? 0;
            }

            $standing['buchholz'] = round($standing['buchholz'], 2);
            $standing['head_to_head'] = round($standing['head_to_head'], 2);
        }
        unset($standing);

        usort($standings, function (array $a, array $b): int {
            foreach (['current_score', 'buchholz', 'sonneborn_berger', 'head_to_head'] as $key) {
                $compare = (float) $b[$key] <=> (float) $a[$key];

                if ($compare !== 0) {
                    return $compare;
                }
            }

            return ((int) $a['seed_number'] <=> (int) $b['seed_number'])
                ?: ((int) $a['id'] <=> (int) $b['id']);
        });

        return $standings;
    }

    private function completedMatchesForTieBreaks(int $tournamentId): array
    {
        $stmt = $this->db->prepare("
            SELECT *
            FROM matches
            WHERE tournament_id = ?
              AND status = 'completed'
              AND result IN ('white_win', 'black_win', 'draw')
        ");

        $stmt->execute([$tournamentId]);

        return $stmt->fetchAll();
    }
}
