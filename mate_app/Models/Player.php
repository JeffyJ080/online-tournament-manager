<?php

require_once __DIR__ . '/../Core/Model.php';

class Player extends Model
{
    public function createForUser(
        int $userId,
        string $realName,
        ?string $displayName,
        ?string $phone,
        string $email,
        string $ratingCategory = 'beginner'
    ): int {
        $startingRating = $this->getStartingRating($ratingCategory);

        $sql = "
            INSERT INTO players (
                user_id,
                real_name,
                display_name,
                phone,
                email,
                rating_category,
                starting_rating,
                current_rating,
                peak_rating,
                public_slug
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            $userId,
            $realName,
            $displayName,
            $phone,
            $email,
            $ratingCategory,
            $startingRating,
            $startingRating,
            $startingRating,
            $this->uniqueSlug($displayName ?: $realName)
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function getStartingRating(string $ratingCategory): int
    {
        return match ($ratingCategory) {
            'casual' => 1000,
            'standard' => 1200,
            default => 800,
        };
    }

    public function findByUserId(int $userId): ?array
    {
        $sql = "SELECT * FROM players WHERE user_id = ? LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);

        $player = $stmt->fetch();

        return $player ?: null;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM players WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $player = $stmt->fetch();

        return $player ?: null;
    }

    public function searchWithAccountDetails(string $query = '', int $limit = 100): array
    {
        $sql = "
            SELECT
                players.*,
                users.email AS account_email,
                users.status AS account_status,
                roles.name AS account_role,
                (
                    SELECT COUNT(*)
                    FROM event_registrations
                    WHERE event_registrations.player_id = players.id
                ) AS registration_count,
                (
                    SELECT COUNT(*)
                    FROM rating_adjustments
                    WHERE rating_adjustments.player_id = players.id
                ) AS rating_adjustment_count,
                (
                    SELECT MAX(events.event_date)
                    FROM event_registrations
                    INNER JOIN events ON event_registrations.event_id = events.id
                    WHERE event_registrations.player_id = players.id
                ) AS last_event_date
            FROM players
            LEFT JOIN users ON players.user_id = users.id
            LEFT JOIN roles ON users.role_id = roles.id
            WHERE (
                ? = ''
                OR players.real_name LIKE ?
                OR players.display_name LIKE ?
                OR players.email LIKE ?
                OR users.email LIKE ?
            )
            ORDER BY
                registration_count DESC,
                last_event_date DESC,
                players.real_name ASC
            LIMIT ?
        ";

        $like = '%' . $query . '%';
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(1, $query);
        $stmt->bindValue(2, $like);
        $stmt->bindValue(3, $like);
        $stmt->bindValue(4, $like);
        $stmt->bindValue(5, $like);
        $stmt->bindValue(6, $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function countUnlinkedRegistrations(): int
    {
        $stmt = $this->db->query("
            SELECT COUNT(*) AS total
            FROM event_registrations
            WHERE player_id IS NULL
              AND registration_status <> 'cancelled'
        ");

        $row = $stmt->fetch();

        return (int) ($row['total'] ?? 0);
    }

    public function materializeUnlinkedRegistrations(): int
    {
        $stmt = $this->db->query("
            SELECT
                event_registrations.*,
                events.event_date
            FROM event_registrations
            INNER JOIN events ON event_registrations.event_id = events.id
            WHERE event_registrations.player_id IS NULL
              AND event_registrations.registration_status <> 'cancelled'
            ORDER BY events.event_date ASC, event_registrations.id ASC
        ");

        $registrations = $stmt->fetchAll();
        $created = 0;

        $this->db->beginTransaction();

        try {
            foreach ($registrations as $registration) {
                $displayName = trim((string) ($registration['display_name'] ?? ''));
                $realName = trim((string) ($registration['full_name'] ?? ''));
                $email = trim((string) ($registration['email'] ?? ''));
                $ratingCategory = $registration['rating_category'] ?? 'beginner';
                $startingRating = $this->getStartingRating($ratingCategory);

                $insert = $this->db->prepare("
                    INSERT INTO players (
                        user_id,
                        real_name,
                        display_name,
                        phone,
                        email,
                        rating_category,
                        starting_rating,
                        current_rating,
                        peak_rating,
                        public_slug,
                        profile_visibility
                    )
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'private')
                ");

                $insert->execute([
                    $registration['user_id'] ?: null,
                    $realName !== '' ? $realName : ($displayName !== '' ? $displayName : 'Imported Player'),
                    $displayName !== '' ? $displayName : null,
                    $registration['phone'] ?: null,
                    $email !== '' ? $email : null,
                    $ratingCategory,
                    $startingRating,
                    $startingRating,
                    $startingRating,
                    $this->uniqueSlug(($displayName !== '' ? $displayName : $realName) ?: 'Imported Player')
                ]);

                $playerId = (int) $this->db->lastInsertId();

                $this->db->prepare("
                    UPDATE event_registrations
                    SET player_id = ?
                    WHERE id = ?
                      AND player_id IS NULL
                ")->execute([$playerId, (int) $registration['id']]);

                $this->db->prepare("
                    UPDATE payments
                    SET player_id = ?
                    WHERE registration_id = ?
                      AND player_id IS NULL
                ")->execute([$playerId, (int) $registration['id']]);

                $created++;
            }

            $this->db->commit();
            return $created;
        } catch (Throwable $exception) {
            $this->db->rollBack();
            return 0;
        }
    }

    public function linkToUser(int $playerId, int $userId): bool
    {
        $existing = $this->findByUserId($userId);

        if ($existing && (int) $existing['id'] !== $playerId) {
            return false;
        }

        $this->db->beginTransaction();

        try {
            $stmt = $this->db->prepare("
                UPDATE players
                SET user_id = ?,
                    email = COALESCE(NULLIF(email, ''), (SELECT email FROM users WHERE id = ?))
                WHERE id = ?
                  AND (user_id IS NULL OR user_id = ?)
            ");

            $stmt->execute([$userId, $userId, $playerId, $userId]);

            $this->db->prepare("
                UPDATE event_registrations
                SET user_id = ?
                WHERE player_id = ?
                  AND user_id IS NULL
            ")->execute([$userId, $playerId]);

            $this->db->prepare("
                UPDATE payments
                SET user_id = ?
                WHERE player_id = ?
                  AND user_id IS NULL
            ")->execute([$userId, $playerId]);

            $this->db->commit();
            return $stmt->rowCount() > 0;
        } catch (Throwable $exception) {
            $this->db->rollBack();
            return false;
        }
    }

    public function mergeInto(int $sourcePlayerId, int $targetPlayerId): bool
    {
        if ($sourcePlayerId === $targetPlayerId) {
            return false;
        }

        $source = $this->findById($sourcePlayerId);
        $target = $this->findById($targetPlayerId);

        if (!$source || !$target) {
            return false;
        }

        $sourceUserId = (int) ($source['user_id'] ?? 0);
        $targetUserId = (int) ($target['user_id'] ?? 0);

        if ($sourceUserId > 0 && $targetUserId > 0 && $sourceUserId !== $targetUserId) {
            return false;
        }

        $effectiveUserId = $targetUserId > 0 ? $targetUserId : $sourceUserId;

        $this->db->beginTransaction();

        try {
            if ($targetUserId <= 0 && $sourceUserId > 0) {
                $this->db->prepare("
                    UPDATE players
                    SET user_id = ?,
                        email = COALESCE(NULLIF(email, ''), ?)
                    WHERE id = ?
                ")->execute([$sourceUserId, $source['email'] ?? null, $targetPlayerId]);
            }

            if ($effectiveUserId > 0) {
                $this->db->prepare("
                    UPDATE event_registrations
                    SET player_id = ?,
                        user_id = COALESCE(user_id, ?)
                    WHERE player_id = ?
                ")->execute([$targetPlayerId, $effectiveUserId, $sourcePlayerId]);

                $this->db->prepare("
                    UPDATE payments
                    SET player_id = ?,
                        user_id = COALESCE(user_id, ?)
                    WHERE player_id = ?
                ")->execute([$targetPlayerId, $effectiveUserId, $sourcePlayerId]);
            } else {
                $this->db->prepare("
                    UPDATE event_registrations
                    SET player_id = ?
                    WHERE player_id = ?
                ")->execute([$targetPlayerId, $sourcePlayerId]);

                $this->db->prepare("
                    UPDATE payments
                    SET player_id = ?
                    WHERE player_id = ?
                ")->execute([$targetPlayerId, $sourcePlayerId]);
            }

            $this->db->prepare("
                UPDATE rating_adjustments
                SET opponent_player_id = ?
                WHERE opponent_player_id = ?
            ")->execute([$targetPlayerId, $sourcePlayerId]);

            $this->db->prepare("
                DELETE source_adjustments
                FROM rating_adjustments AS source_adjustments
                INNER JOIN rating_adjustments AS target_adjustments
                    ON target_adjustments.match_id = source_adjustments.match_id
                    AND target_adjustments.player_id = ?
                WHERE source_adjustments.player_id = ?
            ")->execute([$targetPlayerId, $sourcePlayerId]);

            $this->db->prepare("
                UPDATE rating_adjustments
                SET player_id = ?
                WHERE player_id = ?
            ")->execute([$targetPlayerId, $sourcePlayerId]);

            $this->db->prepare("DELETE FROM players WHERE id = ?")->execute([$sourcePlayerId]);

            $this->db->commit();
            return true;
        } catch (Throwable $exception) {
            $this->db->rollBack();
            return false;
        }
    }

    public function findPublicBySlug(string $slug): ?array
    {
        $stmt = $this->db->prepare("
            SELECT *
            FROM players
            WHERE public_slug = ?
              AND profile_visibility = 'public'
            LIMIT 1
        ");

        $stmt->execute([$slug]);
        $player = $stmt->fetch();

        return $player ?: null;
    }

    public function updateForUser(
        int $userId,
        string $realName,
        ?string $displayName,
        ?string $phone,
        string $ratingCategory,
        string $profileVisibility = 'private'
    ): bool {
        $sql = "
            UPDATE players
            SET real_name = ?,
                display_name = ?,
                phone = ?,
                rating_category = ?,
                profile_visibility = ?
            WHERE user_id = ?
        ";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            $realName,
            $displayName,
            $phone,
            $ratingCategory,
            $profileVisibility,
            $userId,
        ]);
    }

    public function publicProfileStats(int $playerId): array
    {
        $stmt = $this->db->prepare("
            SELECT
                COUNT(matches.id) AS matches_played,
                SUM(CASE
                    WHEN white_registration.player_id = ? AND matches.result = 'white_win' THEN 1
                    WHEN black_registration.player_id = ? AND matches.result = 'black_win' THEN 1
                    ELSE 0
                END) AS wins,
                SUM(CASE WHEN matches.result = 'draw' THEN 1 ELSE 0 END) AS draws,
                SUM(CASE
                    WHEN white_registration.player_id = ? AND matches.result = 'black_win' THEN 1
                    WHEN black_registration.player_id = ? AND matches.result = 'white_win' THEN 1
                    ELSE 0
                END) AS losses
            FROM matches
            INNER JOIN event_registrations AS white_registration
                ON matches.white_registration_id = white_registration.id
            LEFT JOIN event_registrations AS black_registration
                ON matches.black_registration_id = black_registration.id
            WHERE matches.status = 'completed'
              AND matches.result <> 'bye'
              AND (
                    white_registration.player_id = ?
                 OR black_registration.player_id = ?
              )
        ");

        $stmt->execute([$playerId, $playerId, $playerId, $playerId, $playerId, $playerId]);
        $stats = $stmt->fetch() ?: [];

        return [
            'matches_played' => (int) ($stats['matches_played'] ?? 0),
            'wins' => (int) ($stats['wins'] ?? 0),
            'draws' => (int) ($stats['draws'] ?? 0),
            'losses' => (int) ($stats['losses'] ?? 0),
        ];
    }

    public function ratingHistoryForUser(int $userId): array
    {
        $stmt = $this->db->prepare("
            SELECT
                rating_adjustments.*,
                events.title AS event_title,
                events.event_date,
                opponent.display_name AS opponent_display_name,
                opponent.real_name AS opponent_real_name
            FROM rating_adjustments
            INNER JOIN players ON rating_adjustments.player_id = players.id
            INNER JOIN tournaments ON rating_adjustments.tournament_id = tournaments.id
            INNER JOIN events ON tournaments.event_id = events.id
            LEFT JOIN players AS opponent ON rating_adjustments.opponent_player_id = opponent.id
            WHERE players.user_id = ?
            ORDER BY rating_adjustments.created_at DESC, rating_adjustments.id DESC
        ");

        $stmt->execute([$userId]);

        return $stmt->fetchAll();
    }

    public function applyTournamentRatings(int $tournamentId): int
    {
        $matches = $this->completedRatingMatches($tournamentId);
        $updates = 0;
        $kFactors = [];

        foreach ($matches as $match) {
            $whitePlayerId = (int) ($match['white_player_id'] ?? 0);
            $blackPlayerId = (int) ($match['black_player_id'] ?? 0);

            if ($whitePlayerId <= 0 && $blackPlayerId <= 0) {
                continue;
            }

            [$whiteScore, $blackScore] = $this->scoreForResult($match['result'] ?? 'pending');
            $whiteRating = $whitePlayerId > 0
                ? $this->currentRating($whitePlayerId)
                : $this->getStartingRating($match['white_rating_category'] ?? 'beginner');
            $blackRating = $blackPlayerId > 0
                ? $this->currentRating($blackPlayerId)
                : $this->getStartingRating($match['black_rating_category'] ?? 'beginner');

            if ($whitePlayerId > 0) {
                $kFactors[$whitePlayerId] ??= $this->ratingKFactorForTournament(
                    $whitePlayerId,
                    $whiteRating,
                    $tournamentId
                );
                $updates += $this->applyMatchRating(
                    $tournamentId,
                    (int) $match['id'],
                    $whitePlayerId,
                    $blackPlayerId > 0 ? $blackPlayerId : null,
                    $whiteRating,
                    $blackRating,
                    $whiteScore,
                    $kFactors[$whitePlayerId]
                );
            }

            if ($blackPlayerId > 0) {
                $kFactors[$blackPlayerId] ??= $this->ratingKFactorForTournament(
                    $blackPlayerId,
                    $blackRating,
                    $tournamentId
                );
                $updates += $this->applyMatchRating(
                    $tournamentId,
                    (int) $match['id'],
                    $blackPlayerId,
                    $whitePlayerId > 0 ? $whitePlayerId : null,
                    $blackRating,
                    $whiteRating,
                    $blackScore,
                    $kFactors[$blackPlayerId]
                );
            }
        }

        return $updates;
    }

    private function completedRatingMatches(int $tournamentId): array
    {
        $sql = "
            SELECT
                matches.id,
                matches.result,
                white_registration.player_id AS white_player_id,
                white_registration.rating_category AS white_rating_category,
                black_registration.player_id AS black_player_id,
                black_registration.rating_category AS black_rating_category
            FROM matches
            INNER JOIN event_registrations AS white_registration
                ON matches.white_registration_id = white_registration.id
            LEFT JOIN event_registrations AS black_registration
                ON matches.black_registration_id = black_registration.id
            WHERE matches.tournament_id = ?
              AND matches.status = 'completed'
              AND matches.result IN ('white_win', 'black_win', 'draw')
            ORDER BY matches.id ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$tournamentId]);

        return $stmt->fetchAll();
    }

    private function applyMatchRating(
        int $tournamentId,
        int $matchId,
        int $playerId,
        ?int $opponentPlayerId,
        int $playerRating,
        int $opponentRating,
        float $score,
        int $kFactor
    ): int {
        if ($this->ratingAlreadyApplied($matchId, $playerId)) {
            return 0;
        }

        $expected = 1 / (1 + (10 ** (($opponentRating - $playerRating) / 400)));
        $newRating = (int) round($playerRating + ($kFactor * ($score - $expected)));
        $change = $newRating - $playerRating;

        $this->db->prepare("
            UPDATE players
            SET current_rating = ?,
                peak_rating = GREATEST(peak_rating, ?)
            WHERE id = ?
        ")->execute([$newRating, $newRating, $playerId]);

        $stmt = $this->db->prepare("
            INSERT INTO rating_adjustments (
                tournament_id,
                match_id,
                player_id,
                opponent_player_id,
                old_rating,
                new_rating,
                change_amount,
                result_score,
                k_factor
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $tournamentId,
            $matchId,
            $playerId,
            $opponentPlayerId,
            $playerRating,
            $newRating,
            $change,
            $score,
            $kFactor,
        ]);

        return 1;
    }

    private function ratingKFactorForTournament(
        int $playerId,
        int $currentRating,
        int $tournamentId
    ): int
    {
        $existing = $this->db->prepare("
            SELECT k_factor
            FROM rating_adjustments
            WHERE player_id = ?
              AND tournament_id = ?
            ORDER BY id ASC
            LIMIT 1
        ");
        $existing->execute([$playerId, $tournamentId]);
        $recordedKFactor = $existing->fetchColumn();

        if ($recordedKFactor !== false) {
            return (int) $recordedKFactor;
        }

        $stmt = $this->db->prepare("
            SELECT
                players.peak_rating,
                COUNT(rating_adjustments.id) AS rated_games
            FROM players
            LEFT JOIN rating_adjustments
                ON rating_adjustments.player_id = players.id
                AND rating_adjustments.tournament_id <> ?
            WHERE players.id = ?
            GROUP BY players.id, players.peak_rating
        ");
        $stmt->execute([$tournamentId, $playerId]);
        $ratingState = $stmt->fetch();

        $peakRating = max(
            $currentRating,
            (int) ($ratingState['peak_rating'] ?? $currentRating)
        );

        if ($peakRating >= 2400) {
            return 10;
        }

        if ((int) ($ratingState['rated_games'] ?? 0) < 30) {
            return 40;
        }

        return 20;
    }

    private function ratingAlreadyApplied(int $matchId, int $playerId): bool
    {
        $stmt = $this->db->prepare("
            SELECT id
            FROM rating_adjustments
            WHERE match_id = ?
              AND player_id = ?
            LIMIT 1
        ");

        $stmt->execute([$matchId, $playerId]);

        return (bool) $stmt->fetch();
    }

    private function currentRating(int $playerId): int
    {
        $stmt = $this->db->prepare("SELECT current_rating FROM players WHERE id = ? LIMIT 1");
        $stmt->execute([$playerId]);
        $player = $stmt->fetch();

        return (int) ($player['current_rating'] ?? 800);
    }

    private function scoreForResult(string $result): array
    {
        return match ($result) {
            'white_win' => [1.0, 0.0],
            'black_win' => [0.0, 1.0],
            'draw' => [0.5, 0.5],
            default => [0.0, 0.0],
        };
    }

    private function uniqueSlug(string $name): string
    {
        $base = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $name), '-')) ?: 'player';
        $slug = $base;
        $counter = 2;

        while ($this->slugExists($slug)) {
            $slug = $base . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    private function slugExists(string $slug): bool
    {
        $stmt = $this->db->prepare("SELECT id FROM players WHERE public_slug = ? LIMIT 1");
        $stmt->execute([$slug]);

        return (bool) $stmt->fetch();
    }
}
