<?php

require_once __DIR__ . '/../Core/Model.php';
require_once __DIR__ . '/Venue.php';

class VenueUpdateRequest extends Model
{
    public const ALLOWED_FIELDS = [
        'contact_person',
        'contact_email',
        'contact_phone',
        'address',
        'city',
        'food_deal_description',
    ];

    public function create(int $venueId, int $requestedBy, array $changes): int
    {
        $sql = "
            INSERT INTO venue_update_requests (
                venue_id,
                requested_by,
                requested_changes,
                status
            )
            VALUES (?, ?, ?, 'pending')
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $venueId,
            $requestedBy,
            json_encode($this->filterAllowed($changes), JSON_THROW_ON_ERROR),
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function pendingForVenue(int $venueId): array
    {
        $sql = "
            SELECT *
            FROM venue_update_requests
            WHERE venue_id = ?
              AND status = 'pending'
            ORDER BY created_at DESC, id DESC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$venueId]);

        return $this->decodeRows($stmt->fetchAll());
    }

    public function allForRequester(int $userId): array
    {
        $sql = "
            SELECT venue_update_requests.*, venues.name AS venue_name
            FROM venue_update_requests
            INNER JOIN venues ON venue_update_requests.venue_id = venues.id
            WHERE venue_update_requests.requested_by = ?
            ORDER BY venue_update_requests.created_at DESC, venue_update_requests.id DESC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);

        return $this->decodeRows($stmt->fetchAll());
    }

    public function pendingForAdmin(): array
    {
        $sql = "
            SELECT
                venue_update_requests.*,
                venues.name AS venue_name,
                users.email AS requester_email
            FROM venue_update_requests
            INNER JOIN venues ON venue_update_requests.venue_id = venues.id
            INNER JOIN users ON venue_update_requests.requested_by = users.id
            WHERE venue_update_requests.status = 'pending'
            ORDER BY venue_update_requests.created_at ASC, venue_update_requests.id ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return $this->decodeRows($stmt->fetchAll());
    }

    public function countPending(): int
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) AS total
            FROM venue_update_requests
            WHERE status = 'pending'
        ");
        $stmt->execute();
        $result = $stmt->fetch();

        return (int) ($result['total'] ?? 0);
    }

    public function findById(int $id): ?array
    {
        $sql = "
            SELECT venue_update_requests.*, venues.name AS venue_name
            FROM venue_update_requests
            INNER JOIN venues ON venue_update_requests.venue_id = venues.id
            WHERE venue_update_requests.id = ?
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        $request = $stmt->fetch();

        if (!$request) {
            return null;
        }

        return $this->decodeRow($request);
    }

    public function approve(int $id, int $reviewedBy, ?string $reviewNotes = null): bool
    {
        $request = $this->findById($id);

        if (!$request || ($request['status'] ?? '') !== 'pending') {
            return false;
        }

        $venueModel = new Venue();
        $venueModel->updatePublicDetails((int) $request['venue_id'], $request['requested_changes'] ?? []);

        $stmt = $this->db->prepare("
            UPDATE venue_update_requests
            SET status = 'approved',
                review_notes = ?,
                reviewed_by = ?,
                reviewed_at = NOW()
            WHERE id = ?
              AND status = 'pending'
        ");

        return $stmt->execute([$reviewNotes, $reviewedBy, $id]);
    }

    public function reject(int $id, int $reviewedBy, ?string $reviewNotes = null): bool
    {
        $stmt = $this->db->prepare("
            UPDATE venue_update_requests
            SET status = 'rejected',
                review_notes = ?,
                reviewed_by = ?,
                reviewed_at = NOW()
            WHERE id = ?
              AND status = 'pending'
        ");

        return $stmt->execute([$reviewNotes, $reviewedBy, $id]);
    }

    public function filterAllowed(array $data): array
    {
        $changes = [];

        foreach (self::ALLOWED_FIELDS as $field) {
            if (array_key_exists($field, $data)) {
                $value = trim((string) $data[$field]);
                $changes[$field] = $value !== '' ? $value : null;
            }
        }

        return $changes;
    }

    private function decodeRows(array $rows): array
    {
        return array_map(fn (array $row): array => $this->decodeRow($row), $rows);
    }

    private function decodeRow(array $row): array
    {
        $changes = json_decode((string) ($row['requested_changes'] ?? '{}'), true);
        $row['requested_changes'] = is_array($changes) ? $changes : [];

        return $row;
    }
}
