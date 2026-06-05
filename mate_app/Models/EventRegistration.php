<?php

require_once __DIR__ . '/../Core/Model.php';

class EventRegistration extends Model
{
    public function create(array $data): int
    {
        $sql = "
            INSERT INTO event_registrations (
                event_id,
                player_id,
                user_id,
                full_name,
                display_name,
                email,
                phone,
                rating_category,
                registration_status,
                payment_status,
                payment_method,
                notes
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            $data['event_id'],
            $data['player_id'] ?? null,
            $data['user_id'] ?? null,
            $data['full_name'],
            $data['display_name'] ?? null,
            $data['email'],
            $data['phone'] ?? null,
            $data['rating_category'] ?? 'beginner',
            $data['registration_status'] ?? 'pending',
            $data['payment_status'] ?? 'unpaid',
            $data['payment_method'] ?? 'cash',
            $data['notes'] ?? null,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function countActiveForEvent(int $eventId): int
    {
        $sql = "
            SELECT COUNT(*) AS total
            FROM event_registrations
            WHERE event_id = ?
              AND registration_status IN ('pending', 'confirmed', 'checked_in')
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$eventId]);

        $result = $stmt->fetch();

        return (int) ($result['total'] ?? 0);
    }

    public function userAlreadyRegistered(int $eventId, int $userId): bool
    {
        $sql = "
            SELECT id
            FROM event_registrations
            WHERE event_id = ?
              AND user_id = ?
              AND registration_status NOT IN ('cancelled', 'no_show')
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$eventId, $userId]);

        return (bool) $stmt->fetch();
    }

    public function emailAlreadyRegistered(int $eventId, string $email): bool
    {
        $sql = "
            SELECT id
            FROM event_registrations
            WHERE event_id = ?
              AND email = ?
              AND registration_status NOT IN ('cancelled', 'no_show')
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$eventId, $email]);

        return (bool) $stmt->fetch();
    }

    public function forEvent(int $eventId): array
    {
        $sql = "
            SELECT *
            FROM event_registrations
            WHERE event_id = ?
            ORDER BY registered_at ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$eventId]);

        return $stmt->fetchAll();
    }

    public function forUser(int $userId): array
    {
        $sql = "
            SELECT
                event_registrations.*,
                events.title AS event_title,
                events.event_date,
                events.start_time,
                venues.name AS venue_name
            FROM event_registrations
            INNER JOIN events ON event_registrations.event_id = events.id
            INNER JOIN venues ON events.venue_id = venues.id
            WHERE event_registrations.user_id = ?
            ORDER BY events.event_date DESC, events.start_time DESC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);

        return $stmt->fetchAll();
    }

    public function allWithEventDetails(): array
    {
        $sql = "
            SELECT
                event_registrations.*,
                events.title AS event_title,
                events.event_date,
                events.start_time,
                venues.name AS venue_name
            FROM event_registrations
            INNER JOIN events ON event_registrations.event_id = events.id
            INNER JOIN venues ON events.venue_id = venues.id
            ORDER BY events.event_date DESC, event_registrations.registered_at DESC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll();
    }
}