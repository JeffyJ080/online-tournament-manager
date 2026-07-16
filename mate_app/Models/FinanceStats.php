<?php

require_once __DIR__ . '/../Core/Model.php';

class FinanceStats extends Model
{
    public const PLAYER_FUND_AMOUNT = 5.00;

    public function summary(int $year, ?int $month, string $role, int $userId): array
    {
        $where = $this->periodWhere($year, $month);
        $scope = $this->scopeWhere($role);
        $params = $this->periodParams($year, $month);
        $this->addScopeParams($params, $role, $userId);

        $sql = "
            SELECT
                COUNT(DISTINCT events.id) AS total_events,
                COUNT(DISTINCT CASE
                    WHEN event_registrations.registration_status NOT IN ('cancelled', 'no_show', 'waitlisted')
                    THEN event_registrations.id
                END) AS active_players,
                COALESCE(SUM(CASE
                    WHEN COALESCE(payments.payment_status, event_registrations.payment_status) IN ('paid_cash', 'paid_eft', 'verified')
                    THEN COALESCE(NULLIF(payments.amount, 0), events.entry_fee)
                    ELSE 0
                END), 0) AS revenue_received,
                COALESCE(SUM(CASE
                    WHEN event_registrations.registration_status NOT IN ('cancelled', 'no_show', 'waitlisted')
                    AND COALESCE(payments.payment_status, event_registrations.payment_status) IN ('unpaid', 'proof_uploaded')
                    THEN events.entry_fee
                    ELSE 0
                END), 0) AS outstanding_amount,
                COUNT(DISTINCT CASE
                    WHEN COALESCE(payments.payment_status, event_registrations.payment_status) = 'comped'
                    THEN event_registrations.id
                END) AS comped_players
            FROM events
            INNER JOIN venues ON events.venue_id = venues.id
            LEFT JOIN event_registrations ON event_registrations.event_id = events.id
            LEFT JOIN payments ON payments.registration_id = event_registrations.id
            WHERE {$where}
            {$scope}
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $summary = $stmt->fetch() ?: [];
        $activePlayers = (int) ($summary['active_players'] ?? 0);

        $summary['total_events'] = (int) ($summary['total_events'] ?? 0);
        $summary['active_players'] = $activePlayers;
        $summary['revenue_received'] = (float) ($summary['revenue_received'] ?? 0);
        $summary['outstanding_amount'] = (float) ($summary['outstanding_amount'] ?? 0);
        $summary['comped_players'] = (int) ($summary['comped_players'] ?? 0);
        $summary['player_fund_generated'] = $activePlayers * self::PLAYER_FUND_AMOUNT;
        $summary['year_end_fund_generated'] = $this->yearEndFund($year, $role, $userId);

        return $summary;
    }

    public function monthlyBreakdown(int $year, string $role, int $userId): array
    {
        $scope = $this->scopeWhere($role);
        $params = ['year' => $year];
        $this->addScopeParams($params, $role, $userId);

        $sql = "
            SELECT
                MONTH(events.event_date) AS month_number,
                COUNT(DISTINCT events.id) AS total_events,
                COUNT(DISTINCT CASE
                    WHEN event_registrations.registration_status NOT IN ('cancelled', 'no_show', 'waitlisted')
                    THEN event_registrations.id
                END) AS active_players,
                COALESCE(SUM(CASE
                    WHEN COALESCE(payments.payment_status, event_registrations.payment_status) IN ('paid_cash', 'paid_eft', 'verified')
                    THEN COALESCE(NULLIF(payments.amount, 0), events.entry_fee)
                    ELSE 0
                END), 0) AS revenue_received
            FROM events
            INNER JOIN venues ON events.venue_id = venues.id
            LEFT JOIN event_registrations ON event_registrations.event_id = events.id
            LEFT JOIN payments ON payments.registration_id = event_registrations.id
            WHERE YEAR(events.event_date) = :year
              AND events.event_status IN ('published', 'running', 'completed')
            {$scope}
            GROUP BY MONTH(events.event_date)
            ORDER BY month_number ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function eventBreakdown(int $year, ?int $month, string $role, int $userId): array
    {
        $where = $this->periodWhere($year, $month);
        $scope = $this->scopeWhere($role);
        $params = $this->periodParams($year, $month);
        $this->addScopeParams($params, $role, $userId);

        $sql = "
            SELECT
                events.id,
                events.title,
                events.event_date,
                events.entry_fee,
                venues.name AS venue_name,
                COUNT(DISTINCT CASE
                    WHEN event_registrations.registration_status NOT IN ('cancelled', 'no_show', 'waitlisted')
                    THEN event_registrations.id
                END) AS active_players,
                COALESCE(SUM(CASE
                    WHEN COALESCE(payments.payment_status, event_registrations.payment_status) IN ('paid_cash', 'paid_eft', 'verified')
                    THEN COALESCE(NULLIF(payments.amount, 0), events.entry_fee)
                    ELSE 0
                END), 0) AS revenue_received,
                COUNT(DISTINCT CASE
                    WHEN COALESCE(payments.payment_status, event_registrations.payment_status) = 'comped'
                    THEN event_registrations.id
                END) AS comped_players
            FROM events
            INNER JOIN venues ON events.venue_id = venues.id
            LEFT JOIN event_registrations ON event_registrations.event_id = events.id
            LEFT JOIN payments ON payments.registration_id = event_registrations.id
            WHERE {$where}
            {$scope}
            GROUP BY events.id
            ORDER BY events.event_date DESC, events.start_time DESC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function yearEndFund(int $year, string $role, int $userId): float
    {
        $scope = $this->scopeWhere($role);
        $params = ['year' => $year];
        $this->addScopeParams($params, $role, $userId);

        $sql = "
            SELECT COUNT(DISTINCT event_registrations.id) AS active_players
            FROM events
            INNER JOIN venues ON events.venue_id = venues.id
            INNER JOIN event_registrations ON event_registrations.event_id = events.id
            WHERE YEAR(events.event_date) = :year
              AND events.event_status IN ('published', 'running', 'completed')
              AND event_registrations.registration_status NOT IN ('cancelled', 'no_show', 'waitlisted')
            {$scope}
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();

        return ((int) ($result['active_players'] ?? 0)) * self::PLAYER_FUND_AMOUNT;
    }

    private function periodWhere(int $year, ?int $month): string
    {
        $where = "YEAR(events.event_date) = :year AND events.event_status IN ('published', 'running', 'completed')";

        if ($month !== null) {
            $where .= ' AND MONTH(events.event_date) = :month';
        }

        return $where;
    }

    private function periodParams(int $year, ?int $month): array
    {
        $params = ['year' => $year];

        if ($month !== null) {
            $params['month'] = $month;
        }

        return $params;
    }

    private function scopeWhere(string $role): string
    {
        if ($role === 'venue_manager') {
            return 'AND venues.venue_manager_user_id = :scope_user_id';
        }

        if (in_array($role, ['host', 'event_manager'], true)) {
            return 'AND EXISTS (
                SELECT 1
                FROM event_hosts
                WHERE event_hosts.event_id = events.id
                  AND event_hosts.user_id = :scope_user_id
            )';
        }

        return '';
    }

    private function addScopeParams(array &$params, string $role, int $userId): void
    {
        if (in_array($role, ['venue_manager', 'host', 'event_manager'], true)) {
            $params['scope_user_id'] = $userId;
        }
    }
}
