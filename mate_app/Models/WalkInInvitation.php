<?php

require_once __DIR__ . '/../Core/Model.php';

class WalkInInvitation extends Model
{
    public function create(int $registrationId, string $email, string $token, ?int $invitedBy, DateTime $expiresAt): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO walkin_account_invitations (
                event_registration_id,
                email,
                token_hash,
                invited_by,
                expires_at
            )
            VALUES (?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $registrationId,
            strtolower(trim($email)),
            hash('sha256', $token),
            $invitedBy,
            $expiresAt->format('Y-m-d H:i:s'),
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function findValidByToken(string $token): ?array
    {
        $stmt = $this->db->prepare("
            SELECT
                walkin_account_invitations.*,
                event_registrations.full_name,
                event_registrations.display_name,
                event_registrations.event_id,
                events.title AS event_title
            FROM walkin_account_invitations
            INNER JOIN event_registrations
                ON walkin_account_invitations.event_registration_id = event_registrations.id
            INNER JOIN events ON event_registrations.event_id = events.id
            WHERE walkin_account_invitations.token_hash = ?
              AND walkin_account_invitations.accepted_at IS NULL
              AND walkin_account_invitations.expires_at > NOW()
            LIMIT 1
        ");

        $stmt->execute([hash('sha256', $token)]);
        $invitation = $stmt->fetch();

        return $invitation ?: null;
    }

    public function markAccepted(int $id, int $userId): bool
    {
        $stmt = $this->db->prepare("
            UPDATE walkin_account_invitations
            SET accepted_by = ?,
                accepted_at = NOW()
            WHERE id = ?
        ");

        return $stmt->execute([$userId, $id]);
    }
}
