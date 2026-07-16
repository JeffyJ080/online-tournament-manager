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

    public function forManager(int $userId): array
    {
        $sql = "
            SELECT *
            FROM venues
            WHERE venue_manager_user_id = ?
            ORDER BY name ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);

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

    public function findManagedById(int $id, int $userId): ?array
    {
        $sql = "
            SELECT *
            FROM venues
            WHERE id = ?
              AND venue_manager_user_id = ?
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id, $userId]);

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
                venue_manager_user_id,
                food_deal_description,
                notes,
                status
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
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
            $data['venue_manager_user_id'] ?? null,
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

    public function update(int $id, array $data): bool
    {
        $sql = "
            UPDATE venues
            SET
                name = ?,
                address = ?,
                city = ?,
                contact_person = ?,
                contact_email = ?,
                contact_phone = ?,
                venue_manager_user_id = ?,
                food_deal_description = ?,
                notes = ?,
                status = ?
            WHERE id = ?
        ";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            $data['name'],
            $data['address'] ?? null,
            $data['city'] ?? null,
            $data['contact_person'] ?? null,
            $data['contact_email'] ?? null,
            $data['contact_phone'] ?? null,
            $data['venue_manager_user_id'] ?? null,
            $data['food_deal_description'] ?? null,
            $data['notes'] ?? null,
            $data['status'] ?? 'active',
            $id,
        ]);
    }

    public function updatePublicDetails(int $id, array $data): bool
    {
        $sql = "
            UPDATE venues
            SET
                address = ?,
                city = ?,
                contact_person = ?,
                contact_email = ?,
                contact_phone = ?,
                food_deal_description = ?
            WHERE id = ?
        ";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            $data['address'] ?? null,
            $data['city'] ?? null,
            $data['contact_person'] ?? null,
            $data['contact_email'] ?? null,
            $data['contact_phone'] ?? null,
            $data['food_deal_description'] ?? null,
            $id,
        ]);
    }

    public function eventSummariesForManager(int $userId, ?int $venueId = null): array
    {
        $params = [$userId];
        $venueFilter = '';

        if ($venueId !== null) {
            $venueFilter = 'AND venues.id = ?';
            $params[] = $venueId;
        }

        $sql = "
            SELECT
                events.id,
                events.title,
                events.event_date,
                events.start_time,
                events.max_players,
                events.registration_status,
                events.event_status,
                events.entry_fee,
                venues.id AS venue_id,
                venues.name AS venue_name,
                venues.city AS venue_city,
                COUNT(DISTINCT CASE
                    WHEN event_registrations.registration_status NOT IN ('cancelled', 'no_show', 'waitlisted')
                    THEN event_registrations.id
                END) AS active_registrations,
                COUNT(DISTINCT CASE
                    WHEN event_registrations.registration_status = 'checked_in'
                    THEN event_registrations.id
                END) AS checked_in_count,
                COALESCE(MAX(event_reports.attendance_count), 0) AS attendance_count,
                COALESCE(SUM(CASE
                    WHEN COALESCE(payments.payment_status, event_registrations.payment_status) IN ('paid_cash', 'paid_eft', 'verified')
                    THEN COALESCE(NULLIF(payments.amount, 0), events.entry_fee)
                    ELSE 0
                END), 0) AS revenue_received
            FROM venues
            INNER JOIN events ON events.venue_id = venues.id
            LEFT JOIN event_registrations ON event_registrations.event_id = events.id
            LEFT JOIN payments ON payments.registration_id = event_registrations.id
            LEFT JOIN event_reports ON event_reports.event_id = events.id
            WHERE venues.venue_manager_user_id = ?
            {$venueFilter}
            GROUP BY events.id
            ORDER BY events.event_date DESC, events.start_time DESC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function eventSummariesForVenue(int $venueId): array
    {
        $sql = "
            SELECT
                events.id,
                events.title,
                events.event_date,
                events.start_time,
                events.max_players,
                events.registration_status,
                events.event_status,
                events.entry_fee,
                venues.id AS venue_id,
                venues.name AS venue_name,
                venues.city AS venue_city,
                COUNT(DISTINCT CASE
                    WHEN event_registrations.registration_status NOT IN ('cancelled', 'no_show', 'waitlisted')
                    THEN event_registrations.id
                END) AS active_registrations,
                COUNT(DISTINCT CASE
                    WHEN event_registrations.registration_status = 'checked_in'
                    THEN event_registrations.id
                END) AS checked_in_count,
                COALESCE(MAX(event_reports.attendance_count), 0) AS attendance_count,
                COALESCE(SUM(CASE
                    WHEN COALESCE(payments.payment_status, event_registrations.payment_status) IN ('paid_cash', 'paid_eft', 'verified')
                    THEN COALESCE(NULLIF(payments.amount, 0), events.entry_fee)
                    ELSE 0
                END), 0) AS revenue_received
            FROM venues
            INNER JOIN events ON events.venue_id = venues.id
            LEFT JOIN event_registrations ON event_registrations.event_id = events.id
            LEFT JOIN payments ON payments.registration_id = event_registrations.id
            LEFT JOIN event_reports ON event_reports.event_id = events.id
            WHERE venues.id = ?
            GROUP BY events.id
            ORDER BY events.event_date DESC, events.start_time DESC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$venueId]);

        return $stmt->fetchAll();
    }

    public function countActive(): int
    {
        $sql = "
            SELECT COUNT(*) AS total
            FROM venues
            WHERE status = 'active'
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        $result = $stmt->fetch();

        return (int) ($result['total'] ?? 0);
    }

    public function countUpcomingForManager(int $userId): int
    {
        $sql = "
            SELECT COUNT(events.id) AS total
            FROM venues
            INNER JOIN events ON events.venue_id = venues.id
            WHERE venues.venue_manager_user_id = ?
              AND events.event_date >= CURDATE()
              AND events.event_status IN ('published', 'running')
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);

        $result = $stmt->fetch();

        return (int) ($result['total'] ?? 0);
    }

    public function countHostedForManager(int $userId): int
    {
        $sql = "
            SELECT COUNT(events.id) AS total
            FROM venues
            INNER JOIN events ON events.venue_id = venues.id
            WHERE venues.venue_manager_user_id = ?
              AND events.event_status = 'completed'
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);

        $result = $stmt->fetch();

        return (int) ($result['total'] ?? 0);
    }

    public function averageAttendanceForManager(int $userId): int
    {
        $sql = "
            SELECT ROUND(COALESCE(AVG(event_reports.attendance_count), 0)) AS average_attendance
            FROM venues
            INNER JOIN events ON events.venue_id = venues.id
            INNER JOIN event_reports ON event_reports.event_id = events.id
            WHERE venues.venue_manager_user_id = ?
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);

        $result = $stmt->fetch();

        return (int) ($result['average_attendance'] ?? 0);
    }
}
