<?php

require_once __DIR__ . '/../Middleware/RequireAuth.php';
require_once __DIR__ . '/../Helpers/Auth.php';
require_once __DIR__ . '/../Models/Event.php';
require_once __DIR__ . '/../Models/EventHost.php';
require_once __DIR__ . '/../Models/EventRegistration.php';
require_once __DIR__ . '/../Models/PaymentProof.php';
require_once __DIR__ . '/../Models/LeaderboardSeason.php';
require_once __DIR__ . '/../Models/Tournament.php';
require_once __DIR__ . '/../Models/TournamentParticipant.php';
require_once __DIR__ . '/../Models/Match.php';
require_once __DIR__ . '/../Models/FinanceStats.php';

class ExportController
{
    public function registrations(): void
    {
        RequireAuth::anyRole(['admin', 'super_admin']);

        $registrationModel = new EventRegistration();
        $rows = [];

        foreach ($registrationModel->allWithEventDetails() as $registration) {
            $rows[] = [
                'event' => $registration['event_title'] ?? '',
                'venue' => $registration['venue_name'] ?? '',
                'event_date' => $registration['event_date'] ?? '',
                'start_time' => $registration['start_time'] ?? '',
                'player' => $registration['full_name'] ?? '',
                'display_name' => $registration['display_name'] ?? '',
                'email' => $registration['email'] ?? '',
                'phone' => $registration['phone'] ?? '',
                'rating_category' => $registration['rating_category'] ?? '',
                'registration_status' => $registration['registration_status'] ?? '',
                'payment_status' => $registration['payment_status'] ?? '',
                'payment_method' => $registration['payment_method'] ?? '',
                'registered_at' => $registration['registered_at'] ?? '',
                'notes' => $registration['notes'] ?? '',
            ];
        }

        $this->csv('mate-registrations-' . date('Ymd-His') . '.csv', $rows);
    }

    public function eventRegistrations(): void
    {
        RequireAuth::anyRole(['host', 'event_manager', 'admin', 'super_admin']);

        $event = $this->authorizedEvent();
        $registrationModel = new EventRegistration();
        $rows = [];

        foreach ($registrationModel->forEvent((int) $event['id']) as $registration) {
            $rows[] = [
                'player' => $registration['full_name'] ?? '',
                'display_name' => $registration['display_name'] ?? '',
                'email' => $registration['email'] ?? '',
                'phone' => $registration['phone'] ?? '',
                'rating_category' => $registration['rating_category'] ?? '',
                'registration_status' => $registration['registration_status'] ?? '',
                'payment_status' => $registration['payment_status'] ?? '',
                'payment_method' => $registration['payment_method'] ?? '',
                'registered_at' => $registration['registered_at'] ?? '',
                'notes' => $registration['notes'] ?? '',
            ];
        }

        $this->csv($this->slug($event['title'] ?? 'event') . '-registrations.csv', $rows);
    }

    public function paymentProofs(): void
    {
        RequireAuth::anyRole(['admin', 'super_admin']);

        $proofModel = new PaymentProof();
        $rows = [];

        foreach ($proofModel->allWithDetails() as $proof) {
            $rows[] = [
                'event' => $proof['event_title'] ?? '',
                'venue' => $proof['venue_name'] ?? '',
                'player' => $proof['full_name'] ?? '',
                'email' => $proof['email'] ?? '',
                'amount' => $proof['amount'] ?? '',
                'payment_method' => $proof['payment_method'] ?? '',
                'payment_status' => $proof['payment_status'] ?? '',
                'proof_status' => $proof['status'] ?? '',
                'original_filename' => $proof['original_filename'] ?? '',
                'file_path' => $proof['file_path'] ?? '',
                'uploaded_at' => $proof['created_at'] ?? '',
                'reviewed_at' => $proof['reviewed_at'] ?? '',
                'review_notes' => $proof['review_notes'] ?? '',
            ];
        }

        $this->csv('mate-payment-proofs-' . date('Ymd-His') . '.csv', $rows);
    }

    public function financeStats(): void
    {
        RequireAuth::anyRole(['event_manager', 'venue_manager', 'admin', 'super_admin']);

        $year = (int) ($_GET['year'] ?? date('Y'));
        $month = isset($_GET['month']) && $_GET['month'] !== '' ? (int) $_GET['month'] : null;

        if ($year < 2020 || $year > 2100) {
            $year = (int) date('Y');
        }

        if ($month !== null && ($month < 1 || $month > 12)) {
            $month = null;
        }

        $statsModel = new FinanceStats();
        $role = Auth::role() ?? '';
        $userId = (int) Auth::id();
        $summary = $statsModel->summary($year, $month, $role, $userId);
        $period = $month ? $year . '-' . str_pad((string) $month, 2, '0', STR_PAD_LEFT) : (string) $year;
        $rows = [[
            'section' => 'summary',
            'period' => $period,
            'event_date' => '',
            'event' => '',
            'venue' => '',
            'entry_fee' => '',
            'events_included' => $summary['total_events'] ?? 0,
            'players_counted' => $summary['active_players'] ?? 0,
            'revenue_received' => $summary['revenue_received'] ?? 0,
            'outstanding_amount' => $summary['outstanding_amount'] ?? 0,
            'fund_generated' => $summary['player_fund_generated'] ?? 0,
            'year_end_fund_generated' => $summary['year_end_fund_generated'] ?? 0,
        ]];

        foreach ($statsModel->eventBreakdown($year, $month, $role, $userId) as $event) {
            $players = (int) ($event['active_players'] ?? 0);
            $rows[] = [
                'section' => 'event',
                'period' => '',
                'event_date' => $event['event_date'] ?? '',
                'event' => $event['title'] ?? '',
                'venue' => $event['venue_name'] ?? '',
                'entry_fee' => $event['entry_fee'] ?? 0,
                'events_included' => '',
                'players_counted' => $players,
                'revenue_received' => $event['revenue_received'] ?? 0,
                'outstanding_amount' => '',
                'fund_generated' => $players * FinanceStats::PLAYER_FUND_AMOUNT,
                'year_end_fund_generated' => '',
            ];
        }

        $this->csv('mate-finance-stats-' . date('Ymd-His') . '.csv', $rows);
    }

    public function leaderboard(): void
    {
        $seasonModel = new LeaderboardSeason();
        $slug = trim($_GET['season'] ?? '');
        $season = $slug !== '' ? $seasonModel->findBySlug($slug) : null;

        if (!$season) {
            $activeSeasons = $seasonModel->active();
            $season = $activeSeasons[0] ?? null;
        }

        if (!$season) {
            $this->csv('mate-leaderboard.csv', []);
        }

        $rows = [];

        foreach ($seasonModel->standings((int) $season['id']) as $rank => $leader) {
            $rows[] = [
                'rank' => $rank + 1,
                'player' => $leader['player_name'] ?? '',
                'season_points' => $leader['total_points'] ?? 0,
                'events_played' => $leader['events_played'] ?? 0,
                'best_finish' => $leader['best_finish'] ?? '',
                'average_points' => $leader['average_points'] ?? 0,
            ];
        }

        $this->csv($this->slug($season['name'] ?? 'leaderboard') . '-leaderboard.csv', $rows);
    }

    public function tournamentResults(): void
    {
        RequireAuth::anyRole(['host', 'event_manager', 'admin', 'super_admin']);

        $event = $this->authorizedEvent();
        $tournamentModel = new Tournament();
        $tournament = $tournamentModel->findByEventId((int) $event['id']);

        if (!$tournament) {
            $this->csv($this->slug($event['title'] ?? 'event') . '-tournament-results.csv', []);
        }

        $participantModel = new TournamentParticipant();
        $matchModel = new MatchModel();
        $rows = [];

        foreach ($participantModel->standings((int) $tournament['id']) as $rank => $player) {
            $rows[] = [
                'section' => 'standings',
                'rank' => $rank + 1,
                'round' => '',
                'board' => '',
                'player' => $player['display_name'] ?: ($player['full_name'] ?? ''),
                'opponent' => '',
                'result' => '',
                'score' => $player['current_score'] ?? 0,
                'buchholz' => $player['buchholz'] ?? 0,
                'sonneborn_berger' => $player['sonneborn_berger'] ?? 0,
                'head_to_head' => $player['head_to_head'] ?? 0,
                'status' => $player['status'] ?? '',
            ];
        }

        foreach ($matchModel->forTournament((int) $tournament['id']) as $match) {
            $rows[] = [
                'section' => 'matches',
                'rank' => '',
                'round' => $match['round_number'] ?? '',
                'board' => $match['board_number'] ?? '',
                'player' => $match['white_display_name'] ?: ($match['white_full_name'] ?? ''),
                'opponent' => $match['black_display_name'] ?: ($match['black_full_name'] ?? 'Bye'),
                'result' => $match['result'] ?? '',
                'score' => ($match['white_score'] ?? '') . '-' . ($match['black_score'] ?? ''),
                'buchholz' => '',
                'sonneborn_berger' => '',
                'head_to_head' => '',
                'status' => $match['status'] ?? '',
            ];
        }

        $this->csv($this->slug($event['title'] ?? 'event') . '-tournament-results.csv', $rows);
    }

    public function pairings(): void
    {
        RequireAuth::anyRole(['host', 'event_manager', 'admin', 'super_admin']);

        $event = $this->authorizedEvent();
        $tournamentModel = new Tournament();
        $tournament = $tournamentModel->findByEventId((int) $event['id']);

        if (!$tournament) {
            $this->csv($this->slug($event['title'] ?? 'event') . '-pairings.csv', []);
        }

        $matchModel = new MatchModel();
        $rows = [];

        foreach ($matchModel->forTournament((int) $tournament['id']) as $match) {
            $rows[] = [
                'round' => $match['round_number'] ?? '',
                'board' => $match['board_number'] ?? '',
                'white' => $match['white_display_name'] ?: ($match['white_full_name'] ?? ''),
                'black' => $match['black_display_name'] ?: ($match['black_full_name'] ?? 'Bye'),
                'result' => $match['result'] ?? '',
                'status' => $match['status'] ?? '',
            ];
        }

        $this->csv($this->slug($event['title'] ?? 'event') . '-pairings.csv', $rows);
    }

    private function authorizedEvent(): array
    {
        $eventId = (int) ($_GET['id'] ?? 0);

        if ($eventId <= 0) {
            http_response_code(404);
            echo '<h1>404 - Event not found</h1>';
            exit;
        }

        $hostModel = new EventHost();

        if (
            !Auth::hasAnyRole(['admin', 'super_admin']) &&
            !$hostModel->isAssigned($eventId, (int) Auth::id())
        ) {
            http_response_code(403);
            echo '<h1>403 - Access denied</h1>';
            exit;
        }

        $eventModel = new Event();
        $event = $eventModel->findById($eventId);

        if (!$event) {
            http_response_code(404);
            echo '<h1>404 - Event not found</h1>';
            exit;
        }

        return $event;
    }

    private function csv(string $filename, array $rows): void
    {
        if (ob_get_level() > 0) {
            ob_end_clean();
        }

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');

        if (empty($rows)) {
            fputcsv($output, ['No records found']);
            fclose($output);
            exit;
        }

        fputcsv($output, array_keys($rows[0]));

        foreach ($rows as $row) {
            fputcsv($output, $row);
        }

        fclose($output);
        exit;
    }

    private function slug(string $value): string
    {
        $slug = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $value), '-'));

        return $slug !== '' ? $slug : 'export';
    }
}
