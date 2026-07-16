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
            SELECT
                event_registrations.*,
                payments.payment_status AS payment_record_status,
                payments.payment_method AS payment_record_method
            FROM event_registrations
            LEFT JOIN payments
                ON payments.registration_id = event_registrations.id
            WHERE event_registrations.event_id = ?
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
                events.slug AS event_slug,
                venues.name AS venue_name,
                payments.id AS payment_id,
                payments.amount,
                payments.payment_method AS payment_record_method,
                payments.payment_status AS payment_record_status,
                payment_proofs.id AS proof_id,
                payment_proofs.status AS proof_status
            FROM event_registrations
            INNER JOIN events ON event_registrations.event_id = events.id
            INNER JOIN venues ON events.venue_id = venues.id
            LEFT JOIN payments ON payments.registration_id = event_registrations.id
            LEFT JOIN payment_proofs ON payment_proofs.registration_id = event_registrations.id
            WHERE event_registrations.user_id = ?
            ORDER BY events.event_date DESC, events.start_time DESC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);

        return $stmt->fetchAll();
    }

    public function unlinkedForEmail(string $email): array
    {
        $stmt = $this->db->prepare("
            SELECT
                event_registrations.*,
                events.title AS event_title,
                events.event_date,
                venues.name AS venue_name
            FROM event_registrations
            INNER JOIN events ON event_registrations.event_id = events.id
            INNER JOIN venues ON events.venue_id = venues.id
            WHERE LOWER(event_registrations.email) = LOWER(?)
              AND event_registrations.user_id IS NULL
            ORDER BY events.event_date DESC
        ");

        $stmt->execute([$email]);

        return $stmt->fetchAll();
    }

    public function linkToPlayer(int $registrationId, int $userId, int $playerId): bool
    {
        $stmt = $this->db->prepare("
            UPDATE event_registrations
            SET user_id = ?,
                player_id = ?
            WHERE id = ?
        ");

        return $stmt->execute([$userId, $playerId, $registrationId]);
    }

    public function claimMatchingEmail(string $email, int $userId, int $playerId): int
    {
        $stmt = $this->db->prepare("
            UPDATE event_registrations
            SET user_id = ?,
                player_id = ?
            WHERE LOWER(email) = LOWER(?)
              AND user_id IS NULL
        ");

        $stmt->execute([$userId, $playerId, $email]);

        return $stmt->rowCount();
    }

    public function matchHistoryForUser(int $userId): array
    {
        $stmt = $this->db->prepare("
            SELECT
                matches.*,
                events.title AS event_title,
                events.event_date,
                white_registration.full_name AS white_name,
                white_registration.display_name AS white_display_name,
                black_registration.full_name AS black_name,
                black_registration.display_name AS black_display_name,
                own_registration.id AS own_registration_id
            FROM event_registrations AS own_registration
            INNER JOIN matches
                ON matches.white_registration_id = own_registration.id
                OR matches.black_registration_id = own_registration.id
            INNER JOIN tournaments ON matches.tournament_id = tournaments.id
            INNER JOIN events ON tournaments.event_id = events.id
            LEFT JOIN event_registrations AS white_registration
                ON matches.white_registration_id = white_registration.id
            LEFT JOIN event_registrations AS black_registration
                ON matches.black_registration_id = black_registration.id
            WHERE own_registration.user_id = ?
              AND matches.status = 'completed'
            ORDER BY events.event_date DESC, matches.id DESC
        ");

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

    public function recentPlayerContacts(int $limit = 250): array
    {
        $stmt = $this->db->prepare("
            SELECT
                MAX(full_name) AS full_name,
                MAX(display_name) AS display_name,
                email,
                MAX(phone) AS phone,
                MAX(rating_category) AS rating_category,
                MAX(registered_at) AS last_registered_at
            FROM event_registrations
            WHERE email IS NOT NULL
              AND email <> ''
              AND email NOT LIKE '%@walkin.local'
            GROUP BY email
            ORDER BY last_registered_at DESC
            LIMIT ?
        ");

        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function findByIdWithEventDetails(int $id): ?array
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
            WHERE event_registrations.id = ?
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);

        $registration = $stmt->fetch();

        return $registration ?: null;
    }

    public function updateStatuses(
        int $id,
        string $registrationStatus,
        string $paymentStatus,
        string $paymentMethod,
        ?string $notes = null
    ): bool {
        $sql = "
            UPDATE event_registrations
            SET
                registration_status = ?,
                payment_status = ?,
                payment_method = ?,
                notes = ?
            WHERE id = ?
        ";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            $registrationStatus,
            $paymentStatus,
            $paymentMethod,
            $notes,
            $id,
        ]);
    }

    public function findByIdWithPaymentDetails(int $id): ?array
    {
        $sql = "
            SELECT
                event_registrations.*,
                events.title AS event_title,
                events.event_date,
                events.start_time,
                events.entry_fee,
                venues.name AS venue_name,
                payments.id AS payment_id,
                payments.amount,
                payments.payment_method AS payment_record_method,
                payments.payment_status AS payment_record_status
            FROM event_registrations
            INNER JOIN events ON event_registrations.event_id = events.id
            INNER JOIN venues ON events.venue_id = venues.id
            LEFT JOIN payments ON payments.registration_id = event_registrations.id
            WHERE event_registrations.id = ?
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);

        $registration = $stmt->fetch();

        return $registration ?: null;
    }

    public function updatePaymentStatus(int $id, string $paymentStatus): bool
    {
        $sql = "
            UPDATE event_registrations
            SET payment_status = ?
            WHERE id = ?
        ";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([$paymentStatus, $id]);
    }

    public function countUpcomingForUser(int $userId): int
    {
        $sql = "
            SELECT COUNT(*) AS total
            FROM event_registrations
            INNER JOIN events ON event_registrations.event_id = events.id
            WHERE event_registrations.user_id = ?
            AND event_registrations.registration_status NOT IN ('cancelled', 'no_show')
            AND events.event_date >= CURDATE()
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);

        $result = $stmt->fetch();

        return (int) ($result['total'] ?? 0);
    }

    public function countOpenRegistrations(): int
    {
        $sql = "
            SELECT COUNT(*) AS total
            FROM event_registrations
            WHERE registration_status IN ('pending', 'confirmed')
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        $result = $stmt->fetch();

        return (int) ($result['total'] ?? 0);
    }

    public function checkIn(int $registrationId): bool
    {
        $sql = "
            UPDATE event_registrations
            SET registration_status = 'checked_in'
            WHERE id = ?
        ";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([$registrationId]);
    }

    public function checkInForEvent(int $registrationId, int $eventId): bool
    {
        $sql = "
            UPDATE event_registrations
            SET registration_status = 'checked_in'
            WHERE id = ?
              AND event_id = ?
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$registrationId, $eventId]);

        return $stmt->rowCount() > 0;
    }

    public function countMatchesForUser(int $userId): int
    {
        $sql = "
            SELECT COUNT(matches.id) AS total
            FROM event_registrations
            INNER JOIN matches
                ON matches.white_registration_id = event_registrations.id
                OR matches.black_registration_id = event_registrations.id
            WHERE event_registrations.user_id = ?
              AND matches.status = 'completed'
              AND matches.result <> 'bye'
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);

        $result = $stmt->fetch();

        return (int) ($result['total'] ?? 0);
    }
}
