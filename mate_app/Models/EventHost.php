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

    public function isAssigned(int $eventId, int $userId): bool
    {
        $sql = "
            SELECT id
            FROM event_hosts
            WHERE event_id = ?
            AND user_id = ?
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$eventId, $userId]);

        return (bool) $stmt->fetch();
    }

    public function assignedUserIds(int $eventId): array
    {
        $sql = "
            SELECT user_id
            FROM event_hosts
            WHERE event_id = ?
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$eventId]);

        return array_map('intval', array_column($stmt->fetchAll(), 'user_id'));
    }

    public function syncAssignments(int $eventId, array $userIds, int $assignedBy): void
    {
        $userIds = array_values(array_unique(array_filter(array_map('intval', $userIds))));

        $delete = $this->db->prepare("DELETE FROM event_hosts WHERE event_id = ?");
        $delete->execute([$eventId]);

        if (empty($userIds)) {
            return;
        }

        $insert = $this->db->prepare("
            INSERT INTO event_hosts (event_id, user_id, assigned_by)
            VALUES (?, ?, ?)
        ");

        foreach ($userIds as $userId) {
            $insert->execute([$eventId, $userId, $assignedBy]);
        }
    }

    public function countAssignedEvents(int $userId): int
    {
        $sql = "
            SELECT COUNT(*) AS total
            FROM event_hosts
            INNER JOIN events ON event_hosts.event_id = events.id
            WHERE event_hosts.user_id = ?
              AND events.event_status IN ('published', 'running')
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);

        $result = $stmt->fetch();

        return (int) ($result['total'] ?? 0);
    }

    public function countCheckedInPlayers(int $userId): int
    {
        $sql = "
            SELECT COUNT(event_registrations.id) AS total
            FROM event_hosts
            INNER JOIN events ON event_hosts.event_id = events.id
            INNER JOIN event_registrations ON event_registrations.event_id = events.id
            WHERE event_hosts.user_id = ?
              AND events.event_status IN ('published', 'running')
              AND event_registrations.registration_status = 'checked_in'
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);

        $result = $stmt->fetch();

        return (int) ($result['total'] ?? 0);
    }

    public function cashToHandOver(int $userId): float
    {
        $sql = "
            SELECT COALESCE(SUM(payments.amount), 0) AS total
            FROM event_hosts
            INNER JOIN events ON event_hosts.event_id = events.id
            INNER JOIN payments ON payments.event_id = events.id
            WHERE event_hosts.user_id = ?
              AND events.event_status IN ('published', 'running')
              AND payments.payment_method = 'cash'
              AND payments.payment_status IN ('paid_cash', 'verified')
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);

        $result = $stmt->fetch();

        return (float) ($result['total'] ?? 0);
    }
}
