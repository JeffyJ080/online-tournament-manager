<?php

require_once __DIR__ . '/../Core/Controller.php';
require_once __DIR__ . '/../Core/Mailer.php';
require_once __DIR__ . '/../Helpers/Auth.php';
require_once __DIR__ . '/../Middleware/RequireAuth.php';
require_once __DIR__ . '/../Models/FinanceStats.php';

class FinanceController extends Controller
{
    private array $financeRoles = ['event_manager', 'venue_manager', 'admin', 'super_admin'];

    public function index(): void
    {
        RequireAuth::anyRole($this->financeRoles);

        $filters = $this->filters();
        $statsModel = new FinanceStats();
        $role = Auth::role() ?? '';
        $userId = (int) Auth::id();

        $this->view('finance/index', [
            'title' => 'Finance Stats',
            'heading' => 'Finance Stats',
            'user' => Auth::user(),
            'filters' => $filters,
            'summary' => $statsModel->summary($filters['year'], $filters['month'], $role, $userId),
            'monthlyBreakdown' => $statsModel->monthlyBreakdown($filters['year'], $role, $userId),
            'eventBreakdown' => $statsModel->eventBreakdown($filters['year'], $filters['month'], $role, $userId),
            'emailSent' => isset($_GET['sent']),
            'emailFailed' => isset($_GET['email_failed']),
        ]);
    }

    public function emailSummary(): void
    {
        RequireAuth::anyRole($this->financeRoles);

        $filters = $this->filters();
        $statsModel = new FinanceStats();
        $role = Auth::role() ?? '';
        $user = Auth::user() ?? [];
        $summary = $statsModel->summary($filters['year'], $filters['month'], $role, (int) Auth::id());
        $recipient = trim($user['email'] ?? '');

        if ($recipient === '') {
            header('Location: index.php?page=finance-stats&email_failed=1');
            exit;
        }

        $period = $filters['month'] ? $filters['year'] . '-' . str_pad((string) $filters['month'], 2, '0', STR_PAD_LEFT) : (string) $filters['year'];
        $body = '
            <p>Your private finance snapshot for ' . htmlspecialchars($period) . ' is ready.</p>
            <ul>
                <li>Revenue received: R' . number_format((float) $summary['revenue_received'], 2) . '</li>
                <li>Outstanding amount: R' . number_format((float) $summary['outstanding_amount'], 2) . '</li>
                <li>Active players: ' . (int) $summary['active_players'] . '</li>
                <li>Player fund generated this period: R' . number_format((float) $summary['player_fund_generated'], 2) . '</li>
                <li>Year-end fund generated: R' . number_format((float) $summary['year_end_fund_generated'], 2) . '</li>
            </ul>
        ';

        $sent = Mailer::send($recipient, 'Mate Finance Stats - ' . $period, $body);
        $query = $sent ? '&sent=1' : '&email_failed=1';

        header('Location: index.php?page=finance-stats&year=' . $filters['year'] . ($filters['month'] ? '&month=' . $filters['month'] : '') . $query);
        exit;
    }

    private function filters(): array
    {
        $year = (int) ($_GET['year'] ?? date('Y'));
        $month = isset($_GET['month']) && $_GET['month'] !== '' ? (int) $_GET['month'] : null;

        if ($year < 2020 || $year > 2100) {
            $year = (int) date('Y');
        }

        if ($month !== null && ($month < 1 || $month > 12)) {
            $month = null;
        }

        return [
            'year' => $year,
            'month' => $month,
        ];
    }
}
