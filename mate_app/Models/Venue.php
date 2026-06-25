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
