<?php

require_once __DIR__ . '/../Core/Model.php';

class Event extends Model
{
    public function all(): array
    {
        $sql = "
            SELECT
                events.*,
                event_series.title AS series_title,
                venues.name AS venue_name,
                venues.city AS venue_city,
                COUNT(event_registrations.id) AS active_registrations
            FROM events
            INNER JOIN venues ON events.venue_id = venues.id
            LEFT JOIN event_series ON events.series_id = event_series.id
            LEFT JOIN event_registrations
                ON event_registrations.event_id = events.id
                AND event_registrations.registration_status IN ('pending', 'confirmed', 'checked_in')
            GROUP BY events.id
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

    public function create(array $data): int
    {
        $sql = "
            INSERT INTO events (
                series_id,
                venue_id,
                title,
                slug,
                description,
                event_date,
                start_time,
                end_time,
                entry_fee,
                prize_info,
                format,
                max_players,
                registration_status,
                event_status,
                poster_path,
                notes,
                created_by
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            $data['series_id'] ?? null,
            $data['venue_id'],
            $data['title'],
            $data['slug'],
            $data['description'] ?? null,
            $data['event_date'],
            $data['start_time'],
            $data['end_time'] ?? null,
            $data['entry_fee'] ?? 0,
            $data['prize_info'] ?? null,
            $data['format'] ?? 'knockout',
            $data['max_players'] ?? 32,
            $data['registration_status'] ?? 'open',
            $data['event_status'] ?? 'draft',
            $data['poster_path'] ?? null,
            $data['notes'] ?? null,
            $data['created_by'] ?? null,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function slugExists(string $slug): bool
    {
        $sql = "SELECT id FROM events WHERE slug = ? LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$slug]);

        return (bool) $stmt->fetch();
    }
    
    public function update(int $id, array $data): bool
    {
        $sql = "
            UPDATE events
            SET
                series_id = ?,
                venue_id = ?,
                title = ?,
                description = ?,
                event_date = ?,
                start_time = ?,
                end_time = ?,
                entry_fee = ?,
                prize_info = ?,
                format = ?,
                max_players = ?,
                registration_status = ?,
                event_status = ?,
                notes = ?
            WHERE id = ?
        ";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            $data['series_id'],
            $data['venue_id'],
            $data['title'],
            $data['description'],
            $data['event_date'],
            $data['start_time'],
            $data['end_time'],
            $data['entry_fee'],
            $data['prize_info'],
            $data['format'],
            $data['max_players'],
            $data['registration_status'],
            $data['event_status'],
            $data['notes'],
            $id,
        ]);
    }
}