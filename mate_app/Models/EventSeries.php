<?php

require_once __DIR__ . '/../Core/Model.php';

class EventSeries extends Model
{
    public function all(): array
    {
        $sql = "
            SELECT
                event_series.*,
                venues.name AS venue_name,
                venues.city AS venue_city
            FROM event_series
            INNER JOIN venues ON event_series.venue_id = venues.id
            ORDER BY event_series.title ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function active(): array
    {
        $sql = "
            SELECT
                event_series.*,
                venues.name AS venue_name,
                venues.city AS venue_city
            FROM event_series
            INNER JOIN venues ON event_series.venue_id = venues.id
            WHERE event_series.status = 'active'
            ORDER BY event_series.title ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $sql = "
            SELECT
                event_series.*,
                venues.name AS venue_name,
                venues.city AS venue_city
            FROM event_series
            INNER JOIN venues ON event_series.venue_id = venues.id
            WHERE event_series.id = ?
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);

        $series = $stmt->fetch();

        return $series ?: null;
    }

    public function findBySlug(string $slug): ?array
    {
        $sql = "
            SELECT
                event_series.*,
                venues.name AS venue_name,
                venues.city AS venue_city
            FROM event_series
            INNER JOIN venues ON event_series.venue_id = venues.id
            WHERE event_series.slug = ?
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$slug]);

        $series = $stmt->fetch();

        return $series ?: null;
    }
}