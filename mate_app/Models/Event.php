<?php

require_once __DIR__ . '/../Core/Model.php';

class Event extends Model
{
    public function all(): array
    {
        $sql = "
            SELECT
                events.*,
                venues.name AS venue_name,
                venues.city AS venue_city
            FROM events
            INNER JOIN venues ON events.venue_id = venues.id
            ORDER BY events.event_date DESC, events.start_time DESC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function publishedUpcoming(): array
    {
        $sql = "
            SELECT
                events.*,
                venues.name AS venue_name,
                venues.city AS venue_city,
                COUNT(event_registrations.id) AS active_registrations
            FROM events
            INNER JOIN venues ON events.venue_id = venues.id
            LEFT JOIN event_registrations
                ON event_registrations.event_id = events.id
                AND event_registrations.registration_status IN ('pending', 'confirmed', 'checked_in')
            WHERE events.event_status = 'published'
            AND events.registration_status IN ('open', 'full')
            AND events.event_date >= CURDATE()
            GROUP BY events.id
            ORDER BY events.event_date ASC, events.start_time ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $sql = "
            SELECT
                events.*,
                venues.name AS venue_name,
                venues.city AS venue_city,
                COUNT(event_registrations.id) AS active_registrations
            FROM events
            INNER JOIN venues ON events.venue_id = venues.id
            LEFT JOIN event_registrations
                ON event_registrations.event_id = events.id
                AND event_registrations.registration_status IN ('pending', 'confirmed', 'checked_in')
            WHERE events.id = ?
            GROUP BY events.id
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);

        $event = $stmt->fetch();

        return $event ?: null;
    }

    public function findBySlug(string $slug): ?array
    {
        $sql = "
            SELECT
                events.*,
                venues.name AS venue_name,
                venues.city AS venue_city,
                COUNT(event_registrations.id) AS active_registrations
            FROM events
            INNER JOIN venues ON events.venue_id = venues.id
            LEFT JOIN event_registrations
                ON event_registrations.event_id = events.id
                AND event_registrations.registration_status IN ('pending', 'confirmed', 'checked_in')
            WHERE events.slug = ?
            GROUP BY events.id
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$slug]);

        $event = $stmt->fetch();

        return $event ?: null;
    }
}