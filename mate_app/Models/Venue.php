<?php

require_once __DIR__ . '/../Core/Model.php';

class Venue extends Model
{
    public function all(): array
    {
        $sql = "
            SELECT *
            FROM venues
            ORDER BY name ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function active(): array
    {
        $sql = "
            SELECT *
            FROM venues
            WHERE status = 'active'
            ORDER BY name ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $sql = "
            SELECT *
            FROM venues
            WHERE id = ?
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);

        $venue = $stmt->fetch();

        return $venue ?: null;
    }

    public function findBySlug(string $slug): ?array
    {
        $sql = "
            SELECT *
            FROM venues
            WHERE slug = ?
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$slug]);

        $venue = $stmt->fetch();

        return $venue ?: null;
    }
}