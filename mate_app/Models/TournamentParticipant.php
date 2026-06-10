<?php

require_once __DIR__ . '/../Core/Model.php';

class TournamentParticipant extends Model
{
    public function importFromRegistrations(int $tournamentId, array $registrations): int
    {
        $imported = 0;
        $seed = 1;

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

    private function ratingFromCategory(string $category): int
    {
        return match ($category) {
            'casual' => 1000,
            'standard' => 1200,
            default => 800,
        };
    }
}