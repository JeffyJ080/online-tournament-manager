<?php

require_once __DIR__ . '/../Core/Controller.php';
require_once __DIR__ . '/../Middleware/RequireAuth.php';
require_once __DIR__ . '/../Helpers/Auth.php';
require_once __DIR__ . '/../Helpers/slug.php';
require_once __DIR__ . '/../Models/LeaderboardSeason.php';
require_once __DIR__ . '/../Models/Venue.php';
require_once __DIR__ . '/../Models/EventSeries.php';
require_once __DIR__ . '/../Models/AuditLog.php';

class AdminLeaderboardController extends Controller
{
    public function index(): void
    {
        RequireAuth::anyRole(['admin', 'super_admin']);

        $seasonModel = new LeaderboardSeason();
        $venueModel = new Venue();
        $seriesModel = new EventSeries();
        $seasons = $seasonModel->all();
        $resultCounts = $this->resultCounts($seasonModel, $seasons);
        $flashSuccess = $_SESSION['flash_success'] ?? null;
        unset($_SESSION['flash_success']);

        $this->view('admin/leaderboards/index', [
            'title' => 'Leaderboard Seasons',
            'heading' => 'Leaderboard Seasons',
            'seasons' => $seasons,
            'venues' => $venueModel->active(),
            'seriesList' => $seriesModel->active(),
            'errors' => [],
            'old' => $this->defaultOldValues(),
            'resultCounts' => $resultCounts,
            'flashSuccess' => $flashSuccess,
        ]);
    }

    public function store(): void
    {
        RequireAuth::anyRole(['admin', 'super_admin']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=admin-leaderboards');
            exit;
        }

        $old = [
            'venue_id' => (int) ($_POST['venue_id'] ?? 0),
            'series_id' => (int) ($_POST['series_id'] ?? 0),
            'name' => trim($_POST['name'] ?? ''),
            'starts_on' => trim($_POST['starts_on'] ?? ''),
            'ends_on' => trim($_POST['ends_on'] ?? ''),
            'status' => trim($_POST['status'] ?? 'active'),
            'rules_text' => trim($_POST['rules_text'] ?? ''),
            'notes' => trim($_POST['notes'] ?? ''),
        ];

        $errors = [];

        if ($old['venue_id'] <= 0) {
            $errors[] = 'Choose a venue for this leaderboard season.';
        }

        if ($old['series_id'] > 0) {
            $seriesModel = new EventSeries();
            $series = $seriesModel->findById($old['series_id']);

            if (!$series || (int) $series['venue_id'] !== $old['venue_id']) {
                $errors[] = 'The selected event series must belong to the selected venue.';
            }
        }

        if ($old['name'] === '') {
            $errors[] = 'Season name is required.';
        }

        if (!$this->validDate($old['starts_on'])) {
            $errors[] = 'Start date must be a valid date.';
        }

        if (!$this->validDate($old['ends_on'])) {
            $errors[] = 'End date must be a valid date.';
        }

        if ($this->validDate($old['starts_on']) && $this->validDate($old['ends_on']) && $old['ends_on'] < $old['starts_on']) {
            $errors[] = 'End date must be after the start date.';
        }

        if (!in_array($old['status'], ['active', 'inactive', 'archived'], true)) {
            $errors[] = 'Invalid season status.';
        }

        $rules = $this->parseRules($old['rules_text']);

        if (empty($rules)) {
            $errors[] = 'Add at least one valid points rule.';
        }

        if (!empty($errors)) {
            $seasonModel = new LeaderboardSeason();
            $venueModel = new Venue();
            $seriesModel = new EventSeries();
            $seasons = $seasonModel->all();

            $this->view('admin/leaderboards/index', [
                'title' => 'Leaderboard Seasons',
                'heading' => 'Leaderboard Seasons',
                'seasons' => $seasons,
                'venues' => $venueModel->active(),
                'seriesList' => $seriesModel->active(),
                'errors' => $errors,
                'old' => $old,
                'resultCounts' => $this->resultCounts($seasonModel, $seasons),
                'flashSuccess' => null,
            ]);
            return;
        }

        $seasonModel = new LeaderboardSeason();
        $slug = makeSlug($old['name']);

        $seasonId = $seasonModel->create([
            'venue_id' => $old['venue_id'],
            'series_id' => $old['series_id'] > 0 ? $old['series_id'] : null,
            'name' => $old['name'],
            'slug' => $slug . '-' . date('His'),
            'starts_on' => $old['starts_on'],
            'ends_on' => $old['ends_on'],
            'status' => $old['status'],
            'notes' => $old['notes'] !== '' ? $old['notes'] : null,
        ], $rules);

        $auditLog = new AuditLog();
        $auditLog->create(
            Auth::id(),
            'leaderboard_season_created',
            'leaderboard_season',
            $seasonId,
            'Created leaderboard season: ' . $old['name']
        );

        header('Location: index.php?page=admin-leaderboards');
        exit;
    }

    public function recalculate(): void
    {
        RequireAuth::anyRole(['admin', 'super_admin']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=admin-leaderboards');
            exit;
        }

        $seasonId = (int) ($_POST['season_id'] ?? 0);
        $seasonModel = new LeaderboardSeason();
        $season = $seasonModel->findById($seasonId);

        if (!$season) {
            header('Location: index.php?page=admin-leaderboards');
            exit;
        }

        $recorded = $seasonModel->recalculateSeason($seasonId);

        $auditLog = new AuditLog();
        $auditLog->create(
            Auth::id(),
            'leaderboard_season_recalculated',
            'leaderboard_season',
            $seasonId,
            'Recalculated leaderboard season: ' . $season['name'] . ' (' . $recorded . ' tournaments)'
        );

        $_SESSION['flash_success'] = 'Leaderboard recalculated from ' . $recorded . ' completed tournament(s).';

        header('Location: index.php?page=admin-leaderboards');
        exit;
    }

    private function parseRules(string $rulesText): array
    {
        $rules = [];
        $lines = preg_split('/\R/', $rulesText) ?: [];

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            if (!preg_match('/^(\d+)(?:\s*-\s*(\d+))?\s*[:=]\s*(\d+)$/', $line, $matches)) {
                continue;
            }

            $from = (int) $matches[1];
            $to = isset($matches[2]) && $matches[2] !== '' ? (int) $matches[2] : $from;
            $points = (int) $matches[3];

            if ($from <= 0 || $to < $from) {
                continue;
            }

            $rules[] = [
                'placement_from' => $from,
                'placement_to' => $to,
                'points' => $points,
            ];
        }

        return $rules;
    }

    private function validDate(string $date): bool
    {
        $parsed = DateTime::createFromFormat('Y-m-d', $date);

        return $parsed instanceof DateTime && $parsed->format('Y-m-d') === $date;
    }

    private function resultCounts(LeaderboardSeason $seasonModel, array $seasons): array
    {
        $counts = [];

        foreach ($seasons as $season) {
            $counts[(int) $season['id']] = $seasonModel->resultCountForSeason((int) $season['id']);
        }

        return $counts;
    }

    private function defaultOldValues(): array
    {
        $year = (int) date('Y');

        return [
            'venue_id' => '',
            'series_id' => '',
            'name' => '',
            'starts_on' => $year . '-01-01',
            'ends_on' => $year . '-12-31',
            'status' => 'active',
            'rules_text' => "1:10\n2:7\n3:5\n4:3\n5-6:2\n7-999:1",
            'notes' => '',
        ];
    }
}
