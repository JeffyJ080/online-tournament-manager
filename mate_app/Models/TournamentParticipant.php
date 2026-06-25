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

            $rating = $this->ratingFromCategory($registration['rating_category'] ?? 'beginner');

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
                event_registrations.full_name,
                event_registrations.display_name,
                event_registrations.email,
                event_registrations.rating_category
            FROM tournament_participants
            INNER JOIN event_registrations
                ON tournament_participants.event_registration_id = event_registrations.id
            WHERE tournament_participants.tournament_id = ?
            ORDER BY tournament_participants.seed_number ASC, tournament_participants.id ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$tournamentId]);

        return $stmt->fetchAll();
    }

    public function activeForTournament(int $tournamentId): array
    {
        $sql = "
            SELECT
                tournament_participants.*,
                event_registrations.full_name,
                event_registrations.display_name,
                event_registrations.email,
                event_registrations.rating_category
            FROM tournament_participants
            INNER JOIN event_registrations
                ON tournament_participants.event_registration_id = event_registrations.id
            WHERE tournament_participants.tournament_id = ?
              AND tournament_participants.status = 'active'
            ORDER BY tournament_participants.current_score DESC,
                     tournament_participants.seed_number ASC,
                     tournament_participants.id ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$tournamentId]);

        return $stmt->fetchAll();
    }

    public function standings(int $tournamentId): array
    {
        $sql = "
            SELECT
                tournament_participants.*,
                event_registrations.full_name,
                event_registrations.display_name,
                event_registrations.email,
                event_registrations.rating_category
            FROM tournament_participants
            INNER JOIN event_registrations
                ON tournament_participants.event_registration_id = event_registrations.id
            WHERE tournament_participants.tournament_id = ?
            ORDER BY tournament_participants.current_score DESC,
                     tournament_participants.seed_number ASC,
                     tournament_participants.id ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$tournamentId]);

        return $stmt->fetchAll();
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
                event_registrations.full_name,
                event_registrations.display_name,
                event_registrations.email,
                event_registrations.rating_category
            FROM tournament_participants
            INNER JOIN event_registrations
                ON tournament_participants.event_registration_id = event_registrations.id
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
}
