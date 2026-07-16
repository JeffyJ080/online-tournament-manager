<?php

require_once __DIR__ . '/../Core/Controller.php';
require_once __DIR__ . '/../Middleware/RequireAuth.php';
require_once __DIR__ . '/../Helpers/Auth.php';
require_once __DIR__ . '/../Models/Event.php';
require_once __DIR__ . '/../Models/EventHost.php';
require_once __DIR__ . '/../Models/EventRegistration.php';
require_once __DIR__ . '/../Models/Tournament.php';
require_once __DIR__ . '/../Models/AuditLog.php';
require_once __DIR__ . '/../Models/TournamentParticipant.php';
require_once __DIR__ . '/../Models/LeaderboardSeason.php';
require_once __DIR__ . '/../Models/Player.php';
require_once __DIR__ . '/../Models/Round.php';
require_once __DIR__ . '/../Models/Match.php';
require_once __DIR__ . '/../Helpers/LiveTimer.php';

class TournamentManagerController extends Controller
{
    public function index(): void
    {
        RequireAuth::anyRole(['host', 'event_manager', 'admin', 'super_admin']);

        $eventId = (int) ($_GET['id'] ?? 0);

        if ($eventId <= 0) {
            header('Location: index.php?page=host-events');
            exit;
        }

        $hostModel = new EventHost();

        if (
            !Auth::hasAnyRole(['admin', 'super_admin']) &&
            !$hostModel->isAssigned($eventId, Auth::id())
        ) {
            http_response_code(403);
            echo '<h1>403 - Access denied</h1>';
            return;
        }

        $eventModel = new Event();
        $event = $eventModel->findById($eventId);

        if (!$event) {
            http_response_code(404);
            echo '<h1>404 - Event not found</h1>';
            return;
        }

        $registrationModel = new EventRegistration();

        $checkedInPlayers = array_filter(
            $registrationModel->forEvent($eventId),
            fn ($registration) => ($registration['registration_status'] ?? '') === 'checked_in'
        );

        $tournamentModel = new Tournament();
        $tournament = $tournamentModel->findByEventId($eventId);
        $participants = [];

        if ($tournament) {
            $participantModel = new TournamentParticipant();
            $participants = $participantModel->forTournament((int) $tournament['id']);
            $lateEntries = $participantModel->missingCheckedInRegistrations((int) $tournament['id'], $eventId);
        }

        $rounds = [];
        $matches = [];
        $standings = [];
        $latestRound = null;

        if ($tournament) {
            $roundModel = new Round();
            $rounds = $roundModel->forTournament((int) $tournament['id']);
            $latestRound = $roundModel->latestForTournament((int) $tournament['id']);

            $matchModel = new MatchModel();
            $matches = $matchModel->forTournament((int) $tournament['id']);

            $participantModel = new TournamentParticipant();
            $standings = $participantModel->standings((int) $tournament['id']);
        }

        $this->view('tournament/manager', [
            'title' => 'Tournament Manager',
            'heading' => 'Tournament Manager',

            'event' => $event,
            'tournament' => $tournament,
            'rounds' => $rounds,
            'matches' => $matches,
            'standings' => $standings,
            'latestRound' => $latestRound,
            'participants' => $participants,
            'lateEntries' => $lateEntries ?? [],
            'checkedInPlayers' => $checkedInPlayers,
        ]);
    }

    public function timerControl(): void
    {
        RequireAuth::anyRole(['host', 'event_manager', 'admin', 'super_admin']);

        $eventId = (int) ($_GET['event'] ?? $_GET['id'] ?? 0);

        if ($eventId <= 0) {
            header('Location: index.php?page=host-events');
            exit;
        }

        $hostModel = new EventHost();

        if (
            !Auth::hasAnyRole(['admin', 'super_admin']) &&
            !$hostModel->isAssigned($eventId, Auth::id())
        ) {
            http_response_code(403);
            echo '<h1>403 - Access denied</h1>';
            return;
        }

        $eventModel = new Event();
        $event = $eventModel->findById($eventId);

        if (!$event) {
            http_response_code(404);
            echo '<h1>404 - Event not found</h1>';
            return;
        }

        $tournamentModel = new Tournament();
        $tournament = $tournamentModel->findByEventId($eventId);
        $latestRound = null;

        if ($tournament) {
            $roundModel = new Round();
            $latestRound = $roundModel->latestForTournament((int) $tournament['id']);
        }

        $roundNumber = (int) ($tournament['current_round'] ?? 0);
        $timer = new LiveTimer();
        $timerState = $latestRound && ($latestRound['status'] ?? '') === 'completed'
            ? $timer->completeRound($eventId, $roundNumber)
            : $timer->state($eventId, $roundNumber);

        $this->view('tournament/timer_control', [
            'title' => 'Live Timer Remote',
            'heading' => 'Live Timer Remote',
            'event' => $event,
            'tournament' => $tournament,
            'latestRound' => $latestRound,
            'timerState' => $timerState,
        ]);
    }

    public function updateTimerControl(): void
    {
        RequireAuth::anyRole(['host', 'event_manager', 'admin', 'super_admin']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=host-events');
            exit;
        }

        $eventId = (int) ($_POST['event_id'] ?? 0);
        $action = trim((string) ($_POST['timer_action'] ?? ''));

        if ($eventId <= 0) {
            header('Location: index.php?page=host-events');
            exit;
        }

        $hostModel = new EventHost();

        if (
            !Auth::hasAnyRole(['admin', 'super_admin']) &&
            !$hostModel->isAssigned($eventId, Auth::id())
        ) {
            http_response_code(403);
            echo '<h1>403 - Access denied</h1>';
            return;
        }

        $tournamentModel = new Tournament();
        $tournament = $tournamentModel->findByEventId($eventId);
        $roundNumber = (int) ($tournament['current_round'] ?? 0);
        $latestRound = null;

        if ($tournament) {
            $roundModel = new Round();
            $latestRound = $roundModel->latestForTournament((int) $tournament['id']);
        }

        $timer = new LiveTimer();

        if ($action === 'set_duration') {
            $minutes = max(1, min(240, (int) ($_POST['duration_minutes'] ?? 20)));
            $timer->setDuration($eventId, $roundNumber, $minutes * 60);
        } elseif ($action === 'start' && $roundNumber > 0 && ($latestRound['status'] ?? '') !== 'completed') {
            $timer->start($eventId, $roundNumber);
        } elseif ($action === 'pause') {
            $timer->pause($eventId, $roundNumber);
        } elseif ($action === 'reset') {
            $timer->reset($eventId, $roundNumber);
        }

        header('Location: index.php?page=timer-control&event=' . $eventId);
        exit;
    }

    public function create(): void
    {
        RequireAuth::anyRole(['host', 'event_manager', 'admin', 'super_admin']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=host-events');
            exit;
        }

        $eventId = (int) ($_POST['event_id'] ?? 0);

        if ($eventId <= 0) {
            header('Location: index.php?page=host-events');
            exit;
        }

        $hostModel = new EventHost();

        if (
            !Auth::hasAnyRole(['admin', 'super_admin']) &&
            !$hostModel->isAssigned($eventId, Auth::id())
        ) {
            http_response_code(403);
            echo '<h1>403 - Access denied</h1>';
            return;
        }

        $eventModel = new Event();
        $event = $eventModel->findById($eventId);

        if (!$event) {
            http_response_code(404);
            echo '<h1>404 - Event not found</h1>';
            return;
        }

        if ($this->eventIsClosedForOperations($event)) {
            header('Location: index.php?page=tournament-manager&id=' . $eventId);
            exit;
        }

        $tournamentModel = new Tournament();

        $existing = $tournamentModel->findByEventId($eventId);

        if (!$existing) {
            $tournamentId = $tournamentModel->create([
                'event_id' => $eventId,
                'format' => $event['format'],
                'status' => 'setup',
                'total_rounds' => 5,
                'current_round' => 0,
                'notes' => 'Tournament created from event manager.',
            ]);

            $auditLog = new AuditLog();
            $auditLog->create(
                Auth::id(),
                'tournament_created',
                'tournament',
                $tournamentId,
                'Tournament created for event #' . $eventId
            );
        }

        header('Location: index.php?page=tournament-manager&id=' . $eventId);
        exit;
    }

    public function importParticipants(): void
    {
        RequireAuth::anyRole(['host', 'event_manager', 'admin', 'super_admin']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=host-events');
            exit;
        }

        $eventId = (int) ($_POST['event_id'] ?? 0);

        if ($eventId <= 0) {
            header('Location: index.php?page=host-events');
            exit;
        }

        $hostModel = new EventHost();

        if (
            !Auth::hasAnyRole(['admin', 'super_admin']) &&
            !$hostModel->isAssigned($eventId, Auth::id())
        ) {
            http_response_code(403);
            echo '<h1>403 - Access denied</h1>';
            return;
        }

        $tournamentModel = new Tournament();
        $tournament = $tournamentModel->findByEventId($eventId);

        if (!$tournament) {
            header('Location: index.php?page=tournament-manager&id=' . $eventId);
            exit;
        }

        if ($this->eventOrTournamentIsClosedForOperations($eventId, $tournament)) {
            header('Location: index.php?page=tournament-manager&id=' . $eventId);
            exit;
        }

        $registrationModel = new EventRegistration();
        $registrations = $registrationModel->forEvent($eventId);

        $matchModel = new MatchModel();

        if (!empty($matchModel->forTournament((int) $tournament['id']))) {
            header('Location: index.php?page=tournament-manager&id=' . $eventId);
            exit;
        }

        $participantModel = new TournamentParticipant();
        $importedCount = $participantModel->importFromRegistrations((int) $tournament['id'], $registrations);

        $auditLog = new AuditLog();
        $auditLog->create(
            Auth::id(),
            'tournament_participants_imported',
            'tournament',
            (int) $tournament['id'],
            'Imported ' . $importedCount . ' checked-in participants.'
        );

        header('Location: index.php?page=tournament-manager&id=' . $eventId);
        exit;
    }

    public function addLateEntries(): void
    {
        RequireAuth::anyRole(['host', 'event_manager', 'admin', 'super_admin']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=host-events');
            exit;
        }

        $eventId = (int) ($_POST['event_id'] ?? 0);

        if ($eventId <= 0) {
            header('Location: index.php?page=host-events');
            exit;
        }

        $hostModel = new EventHost();

        if (
            !Auth::hasAnyRole(['admin', 'super_admin']) &&
            !$hostModel->isAssigned($eventId, Auth::id())
        ) {
            http_response_code(403);
            echo '<h1>403 - Access denied</h1>';
            return;
        }

        $tournamentModel = new Tournament();
        $tournament = $tournamentModel->findByEventId($eventId);

        if (!$tournament || $this->eventOrTournamentIsClosedForOperations($eventId, $tournament)) {
            header('Location: index.php?page=tournament-manager&id=' . $eventId);
            exit;
        }

        if (!in_array(($tournament['format'] ?? ''), ['swiss', 'weekly_points'], true)) {
            header('Location: index.php?page=tournament-manager&id=' . $eventId);
            exit;
        }

        $participantModel = new TournamentParticipant();
        $lateEntries = $participantModel->missingCheckedInRegistrations((int) $tournament['id'], $eventId);
        $importedCount = $participantModel->importFromRegistrations((int) $tournament['id'], $lateEntries);

        if ($importedCount > 0) {
            $auditLog = new AuditLog();
            $auditLog->create(
                Auth::id(),
                'tournament_late_entries_added',
                'tournament',
                (int) $tournament['id'],
                'Added ' . $importedCount . ' late checked-in participant(s).'
            );
        }

        header('Location: index.php?page=tournament-manager&id=' . $eventId);
        exit;
    }

    public function generateRoundOne(): void
    {
        RequireAuth::anyRole(['host', 'event_manager', 'admin', 'super_admin']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=host-events');
            exit;
        }

        $eventId = (int) ($_POST['event_id'] ?? 0);

        if ($eventId <= 0) {
            header('Location: index.php?page=host-events');
            exit;
        }

        $hostModel = new EventHost();

        if (
            !Auth::hasAnyRole(['admin', 'super_admin']) &&
            !$hostModel->isAssigned($eventId, Auth::id())
        ) {
            http_response_code(403);
            echo '<h1>403 - Access denied</h1>';
            return;
        }

        $tournamentModel = new Tournament();
        $tournament = $tournamentModel->findByEventId($eventId);

        if (!$tournament || !in_array(($tournament['format'] ?? ''), ['swiss', 'weekly_points'], true)) {
            header('Location: index.php?page=tournament-manager&id=' . $eventId);
            exit;
        }

        if ($this->eventOrTournamentIsClosedForOperations($eventId, $tournament)) {
            header('Location: index.php?page=tournament-manager&id=' . $eventId);
            exit;
        }

        $roundModel = new Round();
        $round = $roundModel->findByTournamentAndNumber((int) $tournament['id'], 1);

        $matchModel = new MatchModel();

        if ($round && $matchModel->existsForRound((int) $round['id'])) {
            header('Location: index.php?page=tournament-manager&id=' . $eventId);
            exit;
        }

        $participantModel = new TournamentParticipant();
        $participants = $participantModel->forTournament((int) $tournament['id']);

        if (count($participants) < 2) {
            header('Location: index.php?page=tournament-manager&id=' . $eventId);
            exit;
        }

        if (!$round) {
            $roundId = $roundModel->create([
                'tournament_id' => (int) $tournament['id'],
                'round_number' => 1,
                'name' => 'Round 1',
                'status' => 'running',
                'started_at' => date('Y-m-d H:i:s'),
            ]);
        } else {
            $roundId = (int) $round['id'];
            $roundModel->markRunning($roundId);
        }

        $boardNumber = 1;

        for ($index = 0; $index < count($participants); $index += 2) {
            $white = $participants[$index];
            $black = $participants[$index + 1] ?? null;

            $matchModel->create([
                'tournament_id' => (int) $tournament['id'],
                'round_id' => $roundId,
                'white_registration_id' => (int) $white['event_registration_id'],
                'black_registration_id' => $black ? (int) $black['event_registration_id'] : null,
                'white_score' => $black ? null : 1.0,
                'black_score' => null,
                'result' => $black ? 'pending' : 'bye',
                'board_number' => $boardNumber,
                'status' => $black ? 'scheduled' : 'completed',
            ]);

            $boardNumber++;
        }

        $participantModel->recalculateScores(
            (int) $tournament['id'],
            $matchModel->completedForTournament((int) $tournament['id'])
        );

        $tournamentModel->markRoundGenerated((int) $tournament['id'], 1);

        $auditLog = new AuditLog();
        $auditLog->create(
            Auth::id(),
            'swiss_round_one_generated',
            'tournament',
            (int) $tournament['id'],
            'Generated Swiss Round 1 pairings.'
        );

        header('Location: index.php?page=tournament-manager&id=' . $eventId);
        exit;
    }

    public function submitResult(): void
    {
        RequireAuth::anyRole(['host', 'event_manager', 'admin', 'super_admin']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=host-events');
            exit;
        }

        $eventId = (int) ($_POST['event_id'] ?? 0);
        $matchId = (int) ($_POST['match_id'] ?? 0);
        $result = trim($_POST['result'] ?? '');

        if ($eventId <= 0 || $matchId <= 0 || !in_array($result, ['white_win', 'black_win', 'draw', 'forfeit'], true)) {
            header('Location: index.php?page=tournament-manager&id=' . $eventId);
            exit;
        }

        $hostModel = new EventHost();

        if (
            !Auth::hasAnyRole(['admin', 'super_admin']) &&
            !$hostModel->isAssigned($eventId, Auth::id())
        ) {
            http_response_code(403);
            echo '<h1>403 - Access denied</h1>';
            return;
        }

        $tournamentModel = new Tournament();
        $tournament = $tournamentModel->findByEventId($eventId);

        if (!$tournament) {
            header('Location: index.php?page=tournament-manager&id=' . $eventId);
            exit;
        }

        if (($tournament['format'] ?? '') === 'knockout' && !in_array($result, ['white_win', 'black_win'], true)) {
            header('Location: index.php?page=tournament-manager&id=' . $eventId);
            exit;
        }

        if ($this->eventOrTournamentIsClosedForOperations($eventId, $tournament)) {
            header('Location: index.php?page=tournament-manager&id=' . $eventId);
            exit;
        }

        $matchModel = new MatchModel();
        $match = $matchModel->findById($matchId);

        if (!$match || (int) $match['tournament_id'] !== (int) $tournament['id'] || !$match['black_registration_id']) {
            header('Location: index.php?page=tournament-manager&id=' . $eventId);
            exit;
        }

        $matchModel->updateResult($matchId, $result, Auth::id());

        $participantModel = new TournamentParticipant();
        $participantModel->recalculateScores(
            (int) $tournament['id'],
            $matchModel->completedForTournament((int) $tournament['id'])
        );

        $roundModel = new Round();

        if ($matchModel->roundIsComplete((int) $match['round_id'])) {
            $roundModel->markCompleted((int) $match['round_id']);
        }

        $auditLog = new AuditLog();
        $auditLog->create(
            Auth::id(),
            'match_result_submitted',
            'match',
            $matchId,
            'Submitted match result: ' . $result
        );

        header('Location: index.php?page=tournament-manager&id=' . $eventId);
        exit;
    }

    public function submitRoundResults(): void
    {
        RequireAuth::anyRole(['host', 'event_manager', 'admin', 'super_admin']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=host-events');
            exit;
        }

        $eventId = (int) ($_POST['event_id'] ?? 0);
        $results = $_POST['results'] ?? [];

        if ($eventId <= 0 || !is_array($results)) {
            header('Location: index.php?page=tournament-manager&id=' . $eventId);
            exit;
        }

        $hostModel = new EventHost();

        if (
            !Auth::hasAnyRole(['admin', 'super_admin']) &&
            !$hostModel->isAssigned($eventId, Auth::id())
        ) {
            http_response_code(403);
            echo '<h1>403 - Access denied</h1>';
            return;
        }

        $tournamentModel = new Tournament();
        $tournament = $tournamentModel->findByEventId($eventId);

        if (!$tournament || $this->eventOrTournamentIsClosedForOperations($eventId, $tournament)) {
            header('Location: index.php?page=tournament-manager&id=' . $eventId);
            exit;
        }

        $matchModel = new MatchModel();
        $updatedRoundIds = [];
        $updatedCount = 0;
        $validResults = ($tournament['format'] ?? '') === 'knockout'
            ? ['white_win', 'black_win']
            : ['white_win', 'black_win', 'draw', 'forfeit'];

        foreach ($results as $matchId => $result) {
            $matchId = (int) $matchId;
            $result = trim((string) $result);

            if ($matchId <= 0 || !in_array($result, $validResults, true)) {
                continue;
            }

            $match = $matchModel->findById($matchId);

            if (
                !$match ||
                (int) $match['tournament_id'] !== (int) $tournament['id'] ||
                empty($match['black_registration_id'])
            ) {
                continue;
            }

            $matchModel->updateResult($matchId, $result, Auth::id());
            $updatedRoundIds[(int) $match['round_id']] = (int) $match['round_id'];
            $updatedCount++;
        }

        if ($updatedCount > 0) {
            $participantModel = new TournamentParticipant();
            $participantModel->recalculateScores(
                (int) $tournament['id'],
                $matchModel->completedForTournament((int) $tournament['id'])
            );

            $roundModel = new Round();

            foreach ($updatedRoundIds as $roundId) {
                if ($roundId > 0 && $matchModel->roundIsComplete($roundId)) {
                    $roundModel->markCompleted($roundId);
                }
            }

            $auditLog = new AuditLog();
            $auditLog->create(
                Auth::id(),
                'round_results_submitted',
                'tournament',
                (int) $tournament['id'],
                'Submitted ' . $updatedCount . ' round results.'
            );
        }

        header('Location: index.php?page=tournament-manager&id=' . $eventId);
        exit;
    }

    public function adminCorrectRoundResults(): void
    {
        RequireAuth::role('super_admin');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=admin-events');
            exit;
        }

        $eventId = (int) ($_POST['event_id'] ?? 0);
        $results = $_POST['results'] ?? [];

        if ($eventId <= 0 || !is_array($results)) {
            header('Location: index.php?page=tournament-manager&id=' . $eventId);
            exit;
        }

        $tournamentModel = new Tournament();
        $tournament = $tournamentModel->findByEventId($eventId);

        if (!$tournament) {
            header('Location: index.php?page=tournament-manager&id=' . $eventId);
            exit;
        }

        $matchModel = new MatchModel();
        $updatedRoundIds = [];
        $updatedCount = 0;
        $validResults = ($tournament['format'] ?? '') === 'knockout'
            ? ['white_win', 'black_win']
            : ['white_win', 'black_win', 'draw', 'forfeit'];

        foreach ($results as $matchId => $result) {
            $matchId = (int) $matchId;
            $result = trim((string) $result);

            if ($matchId <= 0 || !in_array($result, $validResults, true)) {
                continue;
            }

            $match = $matchModel->findById($matchId);

            if (
                !$match ||
                (int) $match['tournament_id'] !== (int) $tournament['id'] ||
                empty($match['black_registration_id']) ||
                ($match['result'] ?? '') === $result
            ) {
                continue;
            }

            $matchModel->updateResult($matchId, $result, Auth::id());
            $updatedRoundIds[(int) $match['round_id']] = (int) $match['round_id'];
            $updatedCount++;
        }

        if ($updatedCount > 0) {
            $participantModel = new TournamentParticipant();
            $participantModel->recalculateScores(
                (int) $tournament['id'],
                $matchModel->completedForTournament((int) $tournament['id'])
            );

            $roundModel = new Round();

            foreach ($updatedRoundIds as $roundId) {
                if ($roundId > 0 && $matchModel->roundIsComplete($roundId)) {
                    $roundModel->markCompleted($roundId);
                }
            }

            $seasonModel = new LeaderboardSeason();
            $seasonModel->recordTournamentResults((int) $tournament['id']);

            $auditLog = new AuditLog();
            $auditLog->create(
                Auth::id(),
                'admin_tournament_results_corrected',
                'tournament',
                (int) $tournament['id'],
                'Admin corrected ' . $updatedCount . ' completed tournament result(s).'
            );
        }

        header('Location: index.php?page=tournament-manager&id=' . $eventId);
        exit;
    }

    public function generateNextRound(): void
    {
        RequireAuth::anyRole(['host', 'event_manager', 'admin', 'super_admin']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=host-events');
            exit;
        }

        $eventId = (int) ($_POST['event_id'] ?? 0);

        if ($eventId <= 0) {
            header('Location: index.php?page=host-events');
            exit;
        }

        $hostModel = new EventHost();

        if (
            !Auth::hasAnyRole(['admin', 'super_admin']) &&
            !$hostModel->isAssigned($eventId, Auth::id())
        ) {
            http_response_code(403);
            echo '<h1>403 - Access denied</h1>';
            return;
        }

        $tournamentModel = new Tournament();
        $tournament = $tournamentModel->findByEventId($eventId);

        if (!$tournament) {
            header('Location: index.php?page=tournament-manager&id=' . $eventId);
            exit;
        }

        if ($this->eventOrTournamentIsClosedForOperations($eventId, $tournament)) {
            header('Location: index.php?page=tournament-manager&id=' . $eventId);
            exit;
        }

        $roundModel = new Round();
        $matchModel = new MatchModel();
        $participantModel = new TournamentParticipant();
        $latestRound = $roundModel->latestForTournament((int) $tournament['id']);
        $format = $tournament['format'] ?? 'swiss';

        if ($format === 'round_robin') {
            if (!$latestRound) {
                $this->generateRoundRobinRounds((int) $tournament['id'], $participantModel->activeForTournament((int) $tournament['id']));
                $tournamentModel->markRoundGenerated((int) $tournament['id'], 1);
            }

            header('Location: index.php?page=tournament-manager&id=' . $eventId);
            exit;
        }

        if ($latestRound && !$matchModel->roundIsComplete((int) $latestRound['id'])) {
            header('Location: index.php?page=tournament-manager&id=' . $eventId);
            exit;
        }

        $nextRoundNumber = $latestRound ? (int) $latestRound['round_number'] + 1 : 1;

        if (in_array($format, ['swiss', 'weekly_points'], true)) {
            if ($nextRoundNumber > (int) $tournament['total_rounds']) {
                $this->completeTournamentAndRecordSeason((int) $tournament['id'], $eventId);
                header('Location: index.php?page=tournament-manager&id=' . $eventId);
                exit;
            }

            $participants = $participantModel->activeForTournament((int) $tournament['id']);
            $roundId = $this->createRoundWithPairings((int) $tournament['id'], $nextRoundNumber, $participants, true);
            $roundModel->markRunning($roundId);
            $tournamentModel->markRoundGenerated((int) $tournament['id'], $nextRoundNumber);
        } elseif ($format === 'knockout') {
            $registrationIds = $latestRound
                ? $matchModel->winnersForRound((int) $latestRound['id'])
                : array_column($participantModel->activeForTournament((int) $tournament['id']), 'event_registration_id');

            if (count($registrationIds) <= 1) {
                $this->completeTournamentAndRecordSeason((int) $tournament['id'], $eventId);
                header('Location: index.php?page=tournament-manager&id=' . $eventId);
                exit;
            }

            $participants = $participantModel->forRegistrationIds((int) $tournament['id'], $registrationIds);
            $roundId = $this->createRoundWithPairings((int) $tournament['id'], $nextRoundNumber, $participants, false);
            $roundModel->markRunning($roundId);
            $tournamentModel->markRoundGenerated((int) $tournament['id'], $nextRoundNumber);
        }

        $auditLog = new AuditLog();
        $auditLog->create(
            Auth::id(),
            'tournament_round_generated',
            'tournament',
            (int) $tournament['id'],
            'Generated round ' . $nextRoundNumber . '.'
        );

        header('Location: index.php?page=tournament-manager&id=' . $eventId);
        exit;
    }

    public function completeTournament(): void
    {
        RequireAuth::anyRole(['host', 'event_manager', 'admin', 'super_admin']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=host-events');
            exit;
        }

        $eventId = (int) ($_POST['event_id'] ?? 0);

        $hostModel = new EventHost();

        if (
            !Auth::hasAnyRole(['admin', 'super_admin']) &&
            !$hostModel->isAssigned($eventId, Auth::id())
        ) {
            http_response_code(403);
            echo '<h1>403 - Access denied</h1>';
            return;
        }

        $tournamentModel = new Tournament();
        $tournament = $tournamentModel->findByEventId($eventId);

        if ($tournament && !$this->eventOrTournamentIsClosedForOperations($eventId, $tournament)) {
            $roundModel = new Round();
            $matchModel = new MatchModel();
            $latestRound = $roundModel->latestForTournament((int) $tournament['id']);

            if ($latestRound && !$matchModel->roundIsComplete((int) $latestRound['id'])) {
                header('Location: index.php?page=tournament-manager&id=' . $eventId);
                exit;
            }

            $this->completeTournamentAndRecordSeason((int) $tournament['id'], $eventId);
        }

        header('Location: index.php?page=tournament-manager&id=' . $eventId);
        exit;
    }

    private function createRoundWithPairings(int $tournamentId, int $roundNumber, array $participants, bool $avoidRepeats): int
    {
        $roundModel = new Round();
        $matchModel = new MatchModel();

        $existingRound = $roundModel->findByTournamentAndNumber($tournamentId, $roundNumber);

        if ($existingRound) {
            return (int) $existingRound['id'];
        }

        $roundId = $roundModel->create([
            'tournament_id' => $tournamentId,
            'round_number' => $roundNumber,
            'name' => 'Round ' . $roundNumber,
            'status' => 'running',
            'started_at' => date('Y-m-d H:i:s'),
        ]);

        $players = array_values($participants);
        $byePlayer = null;

        if (count($players) % 2 === 1) {
            for ($index = count($players) - 1; $index >= 0; $index--) {
                $registrationId = (int) $players[$index]['event_registration_id'];

                if (!$matchModel->playerHadBye($tournamentId, $registrationId)) {
                    $byePlayer = $players[$index];
                    array_splice($players, $index, 1);
                    break;
                }
            }

            if (!$byePlayer) {
                $byePlayer = array_pop($players);
            }
        }

        $priorMatches = $matchModel->forTournament($tournamentId);
        $pairingHistory = $this->pairingHistory($priorMatches);
        $colorCounts = $this->colorCounts($priorMatches);
        $pairings = $this->buildPairings($players, $pairingHistory, $avoidRepeats);
        $boardNumber = 1;

        foreach ($pairings as [$first, $second]) {
            [$white, $black] = $this->chooseColors($first, $second, $colorCounts);

            $matchModel->create([
                'tournament_id' => $tournamentId,
                'round_id' => $roundId,
                'white_registration_id' => (int) $white['event_registration_id'],
                'black_registration_id' => (int) $black['event_registration_id'],
                'result' => 'pending',
                'board_number' => $boardNumber,
                'status' => 'scheduled',
            ]);

            $boardNumber++;
        }

        if ($byePlayer) {
            $matchModel->create([
                'tournament_id' => $tournamentId,
                'round_id' => $roundId,
                'white_registration_id' => (int) $byePlayer['event_registration_id'],
                'black_registration_id' => null,
                'white_score' => 1.0,
                'black_score' => null,
                'result' => 'bye',
                'board_number' => $boardNumber,
                'status' => 'completed',
            ]);

            $participantModel = new TournamentParticipant();
            $participantModel->recalculateScores($tournamentId, $matchModel->completedForTournament($tournamentId));
        }

        return $roundId;
    }

    private function buildPairings(array $players, array $pairingHistory, bool $avoidRepeats): array
    {
        $players = array_values($players);
        $pairings = $this->findPairings($players, $pairingHistory, $avoidRepeats);

        if ($pairings !== null) {
            return $pairings;
        }

        return $this->findPairings($players, $pairingHistory, false) ?? [];
    }

    private function findPairings(array $players, array $pairingHistory, bool $avoidRepeats): ?array
    {
        $players = array_values($players);

        if (empty($players)) {
            return [];
        }

        $first = array_shift($players);
        $firstId = (int) $first['event_registration_id'];

        foreach ($players as $index => $candidate) {
            $candidateId = (int) $candidate['event_registration_id'];

            if ($avoidRepeats && $this->playersAlreadyPaired($firstId, $candidateId, $pairingHistory)) {
                continue;
            }

            $remaining = $players;
            array_splice($remaining, $index, 1);

            $rest = $this->findPairings($remaining, $pairingHistory, $avoidRepeats);

            if ($rest !== null) {
                array_unshift($rest, [$first, $candidate]);
                return $rest;
            }
        }

        return null;
    }

    private function pairingHistory(array $matches): array
    {
        $history = [];

        foreach ($matches as $match) {
            $whiteId = (int) ($match['white_registration_id'] ?? 0);
            $blackId = (int) ($match['black_registration_id'] ?? 0);

            if ($whiteId <= 0 || $blackId <= 0) {
                continue;
            }

            $key = $this->pairingKey($whiteId, $blackId);
            $history[$key] = true;
        }

        return $history;
    }

    private function playersAlreadyPaired(int $firstId, int $secondId, array $pairingHistory): bool
    {
        return isset($pairingHistory[$this->pairingKey($firstId, $secondId)]);
    }

    private function pairingKey(int $firstId, int $secondId): string
    {
        $ids = [$firstId, $secondId];
        sort($ids);

        return $ids[0] . ':' . $ids[1];
    }

    private function colorCounts(array $matches): array
    {
        $counts = [];

        foreach ($matches as $match) {
            $whiteId = (int) ($match['white_registration_id'] ?? 0);
            $blackId = (int) ($match['black_registration_id'] ?? 0);

            if ($whiteId > 0 && $blackId > 0) {
                $counts[$whiteId]['white'] = ($counts[$whiteId]['white'] ?? 0) + 1;
                $counts[$whiteId]['black'] = $counts[$whiteId]['black'] ?? 0;
                $counts[$blackId]['black'] = ($counts[$blackId]['black'] ?? 0) + 1;
                $counts[$blackId]['white'] = $counts[$blackId]['white'] ?? 0;
            }
        }

        return $counts;
    }

    private function chooseColors(array $first, array $second, array $colorCounts): array
    {
        $firstId = (int) $first['event_registration_id'];
        $secondId = (int) $second['event_registration_id'];
        $firstBalance = ($colorCounts[$firstId]['white'] ?? 0) - ($colorCounts[$firstId]['black'] ?? 0);
        $secondBalance = ($colorCounts[$secondId]['white'] ?? 0) - ($colorCounts[$secondId]['black'] ?? 0);

        if ($firstBalance > $secondBalance) {
            return [$second, $first];
        }

        return [$first, $second];
    }

    private function completeTournamentAndRecordSeason(int $tournamentId, int $eventId): void
    {
        $tournamentModel = new Tournament();
        $tournamentModel->complete($tournamentId);

        $eventModel = new Event();
        $eventModel->completeAfterTournament($eventId);

        $seasonModel = new LeaderboardSeason();
        $seasonModel->recordTournamentResults($tournamentId);

        $playerModel = new Player();
        $ratingUpdates = $playerModel->applyTournamentRatings($tournamentId);

        if ($ratingUpdates > 0) {
            $auditLog = new AuditLog();
            $auditLog->create(
                Auth::id(),
                'ratings_updated',
                'tournament',
                $tournamentId,
                'Applied ' . $ratingUpdates . ' player rating adjustments.'
            );
        }
    }

    private function eventOrTournamentIsClosedForOperations(int $eventId, array $tournament): bool
    {
        if (($tournament['status'] ?? '') === 'completed') {
            return true;
        }

        $eventModel = new Event();
        $event = $eventModel->findById($eventId);

        return !$event || $this->eventIsClosedForOperations($event);
    }

    private function eventIsClosedForOperations(array $event): bool
    {
        return in_array(($event['event_status'] ?? ''), ['completed', 'cancelled'], true);
    }

    private function generateRoundRobinRounds(int $tournamentId, array $participants): void
    {
        $players = array_values($participants);

        if (count($players) < 2) {
            return;
        }

        if (count($players) % 2 === 1) {
            $players[] = null;
        }

        $rounds = count($players) - 1;
        $half = count($players) / 2;

        for ($roundNumber = 1; $roundNumber <= $rounds; $roundNumber++) {
            $roundPlayers = $players;
            $pairings = [];

            for ($index = 0; $index < $half; $index++) {
                $first = $roundPlayers[$index];
                $second = $roundPlayers[count($roundPlayers) - 1 - $index];

                if ($first && $second) {
                    $pairings[] = [$first, $second];
                }
            }

            $roundModel = new Round();
            $matchModel = new MatchModel();
            $roundId = $roundModel->create([
                'tournament_id' => $tournamentId,
                'round_number' => $roundNumber,
                'name' => 'Round ' . $roundNumber,
                'status' => $roundNumber === 1 ? 'running' : 'pending',
                'started_at' => $roundNumber === 1 ? date('Y-m-d H:i:s') : null,
            ]);

            $boardNumber = 1;

            foreach ($pairings as [$white, $black]) {
                $matchModel->create([
                    'tournament_id' => $tournamentId,
                    'round_id' => $roundId,
                    'white_registration_id' => (int) $white['event_registration_id'],
                    'black_registration_id' => (int) $black['event_registration_id'],
                    'result' => 'pending',
                    'board_number' => $boardNumber,
                    'status' => 'scheduled',
                ]);

                $boardNumber++;
            }

            $fixed = array_shift($players);
            $last = array_pop($players);
            array_unshift($players, $fixed, $last);
        }
    }
}
