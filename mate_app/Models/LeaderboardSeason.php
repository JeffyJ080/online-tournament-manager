<?php

require_once __DIR__ . '/../Core/Model.php';
require_once __DIR__ . '/TournamentParticipant.php';

class LeaderboardSeason extends Model
{
    public function all(): array
    {
        $sql = "
            SELECT
                leaderboard_seasons.*,
                venues.name AS venue_name,
                event_series.title AS series_title
            FROM leaderboard_seasons
            INNER JOIN venues ON leaderboard_seasons.venue_id = venues.id
            LEFT JOIN event_series ON leaderboard_seasons.series_id = event_series.id
            ORDER BY leaderboard_seasons.starts_on DESC, leaderboard_seasons.name ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function active(): array
    {
        $sql = "
            SELECT
                leaderboard_seasons.*,
                venues.name AS venue_name,
                event_series.title AS series_title
            FROM leaderboard_seasons
            INNER JOIN venues ON leaderboard_seasons.venue_id = venues.id
            LEFT JOIN event_series ON leaderboard_seasons.series_id = event_series.id
            WHERE leaderboard_seasons.status = 'active'
            ORDER BY leaderboard_seasons.starts_on DESC, leaderboard_seasons.name ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function findBySlug(string $slug): ?array
    {
        $sql = "
            SELECT
                leaderboard_seasons.*,
                venues.name AS venue_name,
                event_series.title AS series_title
            FROM leaderboard_seasons
            INNER JOIN venues ON leaderboard_seasons.venue_id = venues.id
            LEFT JOIN event_series ON leaderboard_seasons.series_id = event_series.id
            WHERE leaderboard_seasons.slug = ?
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$slug]);

        $season = $stmt->fetch();

        return $season ?: null;
    }

    public function findById(int $id): ?array
    {
        $sql = "
            SELECT
                leaderboard_seasons.*,
                venues.name AS venue_name,
                event_series.title AS series_title
            FROM leaderboard_seasons
            INNER JOIN venues ON leaderboard_seasons.venue_id = venues.id
            LEFT JOIN event_series ON leaderboard_seasons.series_id = event_series.id
            WHERE leaderboard_seasons.id = ?
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);

        $season = $stmt->fetch();

        return $season ?: null;
    }

    public function create(array $data, array $rules): int
    {
        $sql = "
            INSERT INTO leaderboard_seasons (
                venue_id,
                series_id,
                name,
                slug,
                starts_on,
                ends_on,
                status,
                notes
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $data['venue_id'],
            $data['series_id'] ?? null,
            $data['name'],
            $data['slug'],
            $data['starts_on'],
            $data['ends_on'],
            $data['status'] ?? 'active',
            $data['notes'] ?? null,
        ]);

        $seasonId = (int) $this->db->lastInsertId();
        $this->replaceRules($seasonId, $rules);

        return $seasonId;
    }

    public function replaceRules(int $seasonId, array $rules): void
    {
        $this->db->prepare("DELETE FROM leaderboard_point_rules WHERE season_id = ?")->execute([$seasonId]);

        $stmt = $this->db->prepare("
            INSERT INTO leaderboard_point_rules (season_id, placement_from, placement_to, points)
            VALUES (?, ?, ?, ?)
        ");

        foreach ($rules as $rule) {
            $stmt->execute([
                $seasonId,
                (int) $rule['placement_from'],
                (int) $rule['placement_to'],
                (int) $rule['points'],
            ]);
        }
    }

    public function rulesForSeason(int $seasonId): array
    {
        $sql = "
            SELECT *
            FROM leaderboard_point_rules
            WHERE season_id = ?
            ORDER BY placement_from ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$seasonId]);

        return $stmt->fetchAll();
    }

    public function seasonForEvent(int $eventId): ?array
    {
        $sql = "
            SELECT
                leaderboard_seasons.*,
                venues.name AS venue_name,
                event_series.title AS series_title
            FROM events
            INNER JOIN leaderboard_seasons
                ON leaderboard_seasons.venue_id = events.venue_id
                AND events.event_date BETWEEN leaderboard_seasons.starts_on AND leaderboard_seasons.ends_on
                AND leaderboard_seasons.status = 'active'
                AND (
                    leaderboard_seasons.series_id = events.series_id
                    OR leaderboard_seasons.series_id IS NULL
                )
            INNER JOIN venues ON leaderboard_seasons.venue_id = venues.id
            LEFT JOIN event_series ON leaderboard_seasons.series_id = event_series.id
            WHERE events.id = ?
            ORDER BY
                CASE WHEN leaderboard_seasons.series_id = events.series_id THEN 0 ELSE 1 END,
                leaderboard_seasons.starts_on DESC
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$eventId]);

        $season = $stmt->fetch();

        return $season ?: null;
    }

    public function recordTournamentResults(int $tournamentId): bool
    {
        $sql = "
            SELECT tournaments.*, events.id AS event_id, events.event_date
            FROM tournaments
            INNER JOIN events ON tournaments.event_id = events.id
            WHERE tournaments.id = ?
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$tournamentId]);
        $tournament = $stmt->fetch();

        if (!$tournament) {
            return false;
        }

        $season = $this->seasonForEvent((int) $tournament['event_id']);

        if (!$season) {
            return false;
        }

        return $this->recordTournamentResultsForSeason($tournamentId, $season, $tournament);
    }

    public function recalculateSeason(int $seasonId): int
    {
        $season = $this->findById($seasonId);

        if (!$season) {
            return 0;
        }

        $tournamentIds = $this->completedTournamentIdsForSeason($seasonId);
        $recorded = 0;

        foreach ($tournamentIds as $tournamentId) {
            if ($this->hasAuthoritativeImportedResults($seasonId, $tournamentId)) {
                continue;
            }

            $tournament = $this->findTournamentForResults($tournamentId);

            if ($tournament && $this->recordTournamentResultsForSeason($tournamentId, $season, $tournament)) {
                $recorded++;
            }
        }

        return $recorded;
    }

    public function resultCountForSeason(int $seasonId): int
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) AS total
            FROM leaderboard_results
            WHERE season_id = ?
        ");

        $stmt->execute([$seasonId]);
        $result = $stmt->fetch();

        return (int) ($result['total'] ?? 0);
    }

    private function findTournamentForResults(int $tournamentId): ?array
    {
        $sql = "
            SELECT tournaments.*, events.id AS event_id, events.event_date
            FROM tournaments
            INNER JOIN events ON tournaments.event_id = events.id
            WHERE tournaments.id = ?
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$tournamentId]);
        $tournament = $stmt->fetch();

        return $tournament ?: null;
    }

    private function recordTournamentResultsForSeason(int $tournamentId, array $season, array $tournament): bool
    {
        $rules = $this->rulesForSeason((int) $season['id']);

        if (empty($rules)) {
            return false;
        }

        $participantModel = new TournamentParticipant();
        $standings = $participantModel->standings($tournamentId);

        if (empty($standings)) {
            return false;
        }

        $this->db->prepare("
            DELETE FROM leaderboard_results
            WHERE season_id = ?
              AND tournament_id = ?
        ")->execute([(int) $season['id'], $tournamentId]);

        $insert = $this->db->prepare("
            INSERT INTO leaderboard_results (
                season_id,
                event_id,
                tournament_id,
                event_registration_id,
                player_name,
                email,
                placement,
                tournament_score,
                points,
                notes
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        foreach ($standings as $index => $player) {
            $placement = $index + 1;
            $points = $this->pointsForPlacement($placement, $rules);

            $insert->execute([
                (int) $season['id'],
                (int) $tournament['event_id'],
                $tournamentId,
                (int) $player['event_registration_id'],
                $player['display_name'] ?: $player['full_name'],
                strtolower(trim($player['email'] ?? '')),
                $placement,
                (float) $player['current_score'],
                $points,
                null,
            ]);
        }

        return true;
    }

    public function standings(int $seasonId): array
    {
        $sql = "
            SELECT
                CASE
                    WHEN event_registrations.player_id IS NOT NULL THEN CONCAT('player:', event_registrations.player_id)
                    WHEN event_registrations.user_id IS NOT NULL THEN CONCAT('user:', event_registrations.user_id)
                    ELSE CONCAT('email:', LOWER(TRIM(leaderboard_results.email)))
                END AS participant_key,
                COALESCE(
                    MAX(NULLIF(players.display_name, '')),
                    MAX(NULLIF(players.real_name, '')),
                    MAX(NULLIF(event_registrations.display_name, '')),
                    MAX(NULLIF(leaderboard_results.player_name, ''))
                ) AS player_name,
                COALESCE(
                    MAX(NULLIF(users.email, '')),
                    MAX(NULLIF(players.email, '')),
                    MAX(NULLIF(event_registrations.email, '')),
                    MAX(NULLIF(leaderboard_results.email, ''))
                ) AS email,
                SUM(leaderboard_results.points) AS total_points,
                COUNT(DISTINCT leaderboard_results.event_id) AS events_played,
                MIN(leaderboard_results.placement) AS best_finish,
                ROUND(AVG(leaderboard_results.points), 2) AS average_points
            FROM leaderboard_results
            INNER JOIN event_registrations
                ON leaderboard_results.event_registration_id = event_registrations.id
            LEFT JOIN players
                ON event_registrations.player_id = players.id
            LEFT JOIN users
                ON event_registrations.user_id = users.id
            WHERE leaderboard_results.season_id = ?
            GROUP BY participant_key
            ORDER BY total_points DESC,
                     best_finish ASC,
                     events_played DESC,
                     average_points DESC,
                     player_name ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$seasonId]);

        return $stmt->fetchAll();
    }

    public function resultsForSeason(int $seasonId): array
    {
        $sql = "
            SELECT
                leaderboard_results.*,
                events.title AS event_title,
                events.event_date
            FROM leaderboard_results
            INNER JOIN events ON leaderboard_results.event_id = events.id
            WHERE leaderboard_results.season_id = ?
            ORDER BY events.event_date DESC,
                     leaderboard_results.placement ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$seasonId]);

        return $stmt->fetchAll();
    }

    private function completedTournamentIdsForSeason(int $seasonId): array
    {
        $sql = "
            SELECT tournaments.id
            FROM leaderboard_seasons
            INNER JOIN events
                ON events.venue_id = leaderboard_seasons.venue_id
                AND events.event_date BETWEEN leaderboard_seasons.starts_on AND leaderboard_seasons.ends_on
                AND (
                    leaderboard_seasons.series_id = events.series_id
                    OR leaderboard_seasons.series_id IS NULL
                )
            INNER JOIN tournaments ON tournaments.event_id = events.id
            WHERE leaderboard_seasons.id = ?
              AND leaderboard_seasons.status = 'active'
              AND events.event_status = 'completed'
              AND tournaments.status = 'completed'
            ORDER BY events.event_date ASC,
                     tournaments.id ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$seasonId]);

        return array_map('intval', array_column($stmt->fetchAll(), 'id'));
    }

    private function hasAuthoritativeImportedResults(int $seasonId, int $tournamentId): bool
    {
        $stmt = $this->db->prepare("
            SELECT id
            FROM leaderboard_results
            WHERE season_id = ?
              AND tournament_id = ?
              AND notes LIKE 'Historical%'
            LIMIT 1
        ");

        $stmt->execute([$seasonId, $tournamentId]);

        return (bool) $stmt->fetch();
    }

    private function pointsForPlacement(int $placement, array $rules): int
    {
        foreach ($rules as $rule) {
            if (
                $placement >= (int) $rule['placement_from'] &&
                $placement <= (int) $rule['placement_to']
            ) {
                return (int) $rule['points'];
            }
        }

        return 0;
    }
}
