<?php

require_once __DIR__ . '/../Core/Database.php';

class EventHost
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    public function assignedEvents(int $userId): array
    {
        $sql = "
            SELECT
                events.*,
                venues.name AS venue_name,
                venues.city AS venue_city,
                COUNT(event_registrations.id) AS active_registrations
            FROM event_hosts
            INNER JOIN events ON event_hosts.event_id = events.id
            INNER JOIN venues ON events.venue_id = venues.id
            LEFT JOIN event_registrations
                ON event_registrations.event_id = events.id
                AND event_registrations.registration_status IN ('pending', 'confirmed', 'checked_in')
            WHERE event_hosts.user_id = ?
            GROUP BY events.id
            ORDER BY events.event_date ASC, events.start_time ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);

        return $stmt->fetchAll();
    }
}