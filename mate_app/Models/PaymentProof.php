<?php

require_once __DIR__ . '/../Core/Model.php';

class PaymentProof extends Model
{
    public function create(array $data): int
    {
        $sql = "
            INSERT INTO payment_proofs (
                payment_id,
                registration_id,
                uploaded_by,
                original_filename,
                stored_filename,
                file_path,
                file_type,
                file_size,
                status
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            $data['payment_id'],
            $data['registration_id'],
            $data['uploaded_by'] ?? null,
            $data['original_filename'],
            $data['stored_filename'],
            $data['file_path'],
            $data['file_type'] ?? null,
            $data['file_size'] ?? null,
            $data['status'] ?? 'uploaded',
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function findByPaymentId(int $paymentId): ?array
    {
        $sql = "
            SELECT *
            FROM payment_proofs
            WHERE payment_id = ?
            ORDER BY created_at DESC
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$paymentId]);

        $proof = $stmt->fetch();

        return $proof ?: null;
    }

    public function findByRegistrationId(int $registrationId): ?array
    {
        $sql = "
            SELECT *
            FROM payment_proofs
            WHERE registration_id = ?
            ORDER BY created_at DESC
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$registrationId]);

        $proof = $stmt->fetch();

        return $proof ?: null;
    }

    public function allWithDetails(): array
    {
        $sql = "
            SELECT
                payment_proofs.*,
                payments.amount,
                payments.payment_method,
                payments.payment_status,
                event_registrations.full_name,
                event_registrations.email,
                events.title AS event_title,
                venues.name AS venue_name
            FROM payment_proofs
            INNER JOIN payments ON payment_proofs.payment_id = payments.id
            INNER JOIN event_registrations ON payment_proofs.registration_id = event_registrations.id
            INNER JOIN events ON event_registrations.event_id = events.id
            INNER JOIN venues ON events.venue_id = venues.id
            ORDER BY payment_proofs.created_at DESC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function review(
        int $proofId,
        string $status,
        ?int $reviewedBy,
        ?string $reviewNotes = null
    ): bool {
        $sql = "
            UPDATE payment_proofs
            SET
                status = ?,
                reviewed_by = ?,
                reviewed_at = ?,
                review_notes = ?
            WHERE id = ?
        ";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            $status,
            $reviewedBy,
            date('Y-m-d H:i:s'),
            $reviewNotes,
            $proofId,
        ]);
    }

    public function findByIdWithDetails(int $id): ?array
    {
        $sql = "
            SELECT
                payment_proofs.*,
                payments.amount,
                payments.payment_method,
                payments.payment_status,
                event_registrations.full_name,
                event_registrations.email,
                event_registrations.phone,
                event_registrations.event_id,
                event_registrations.registration_status,
                event_registrations.payment_status AS registration_payment_status,
                events.title AS event_title,
                events.event_date,
                venues.name AS venue_name
            FROM payment_proofs
            INNER JOIN payments ON payment_proofs.payment_id = payments.id
            INNER JOIN event_registrations ON payment_proofs.registration_id = event_registrations.id
            INNER JOIN events ON event_registrations.event_id = events.id
            INNER JOIN venues ON events.venue_id = venues.id
            WHERE payment_proofs.id = ?
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);

        $proof = $stmt->fetch();

        return $proof ?: null;
    }

    public function approve(int $proofId, int $reviewedBy, ?string $reviewNotes = null): bool
    {
        return $this->review($proofId, 'approved', $reviewedBy, $reviewNotes);
    }

    public function reject(int $proofId, int $reviewedBy, ?string $reviewNotes = null): bool
    {
        return $this->review($proofId, 'rejected', $reviewedBy, $reviewNotes);
    }
}