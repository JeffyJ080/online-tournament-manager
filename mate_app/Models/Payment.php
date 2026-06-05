<?php

require_once __DIR__ . '/../Core/Model.php';

class Payment extends Model
{
    public function createForRegistration(array $data): int
    {
        $sql = "
            INSERT INTO payments (
                registration_id,
                event_id,
                user_id,
                player_id,
                amount,
                payment_method,
                payment_status,
                notes
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            $data['registration_id'],
            $data['event_id'],
            $data['user_id'] ?? null,
            $data['player_id'] ?? null,
            $data['amount'] ?? 0,
            $data['payment_method'] ?? 'cash',
            $data['payment_status'] ?? 'unpaid',
            $data['notes'] ?? null,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function findByRegistrationId(int $registrationId): ?array
    {
        $sql = "
            SELECT *
            FROM payments
            WHERE registration_id = ?
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$registrationId]);

        $payment = $stmt->fetch();

        return $payment ?: null;
    }

    public function updateStatus(
        int $paymentId,
        string $paymentStatus,
        string $paymentMethod,
        ?int $verifiedBy = null,
        ?string $notes = null
    ): bool {
        $verifiedAt = null;

        if (in_array($paymentStatus, ['verified', 'paid_cash', 'paid_eft', 'comped'], true)) {
            $verifiedAt = date('Y-m-d H:i:s');
        }

        $sql = "
            UPDATE payments
            SET
                payment_status = ?,
                payment_method = ?,
                verified_by = ?,
                verified_at = ?,
                notes = ?
            WHERE id = ?
        ";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            $paymentStatus,
            $paymentMethod,
            $verifiedBy,
            $verifiedAt,
            $notes,
            $paymentId,
        ]);
    }
}