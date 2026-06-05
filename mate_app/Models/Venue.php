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

    public function create(array $data): int
    {
        $sql = "
            INSERT INTO venues (
                name,
                slug,
                address,
                city,
                contact_person,
                contact_email,
                contact_phone,
                food_deal_description,
                notes,
                status
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            $data['name'],
            $data['slug'],
            $data['address'] ?? null,
            $data['city'] ?? null,
            $data['contact_person'] ?? null,
            $data['contact_email'] ?? null,
            $data['contact_phone'] ?? null,
            $data['food_deal_description'] ?? null,
            $data['notes'] ?? null,
            $data['status'] ?? 'active',
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function slugExists(string $slug): bool
    {
        $sql = "SELECT id FROM venues WHERE slug = ? LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$slug]);

        return (bool) $stmt->fetch();
    }
}