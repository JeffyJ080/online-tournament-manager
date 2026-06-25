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
                current_rating
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
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
            $startingRating
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

    public function updateForUser(
        int $userId,
        string $realName,
        ?string $displayName,
        ?string $phone,
        string $ratingCategory
    ): bool {
        $sql = "
            UPDATE players
            SET real_name = ?,
                display_name = ?,
                phone = ?,
                rating_category = ?
            WHERE user_id = ?
        ";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            $realName,
            $displayName,
            $phone,
            $ratingCategory,
            $userId,
        ]);
    }
}
