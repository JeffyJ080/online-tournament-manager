<?php

require_once __DIR__ . '/../Core/Controller.php';
require_once __DIR__ . '/../Core/Mailer.php';
require_once __DIR__ . '/../Middleware/RequireAuth.php';
require_once __DIR__ . '/../Helpers/Auth.php';
require_once __DIR__ . '/../Models/EventHost.php';
require_once __DIR__ . '/../Models/Event.php';
require_once __DIR__ . '/../Models/EventRegistration.php';
require_once __DIR__ . '/../Models/Payment.php';
require_once __DIR__ . '/../Models/AuditLog.php';
require_once __DIR__ . '/../Models/EventReport.php';
require_once __DIR__ . '/../Models/Tournament.php';
require_once __DIR__ . '/../Models/TournamentParticipant.php';
require_once __DIR__ . '/../Models/Round.php';
require_once __DIR__ . '/../Models/Match.php';
require_once __DIR__ . '/../Models/User.php';
require_once __DIR__ . '/../Models/Player.php';
require_once __DIR__ . '/../Models/WalkInInvitation.php';
require_once __DIR__ . '/../Helpers/app_url.php';

class HostEventController extends Controller
{
    public function assigned(): void
    {
        RequireAuth::anyRole(['host', 'event_manager', 'admin', 'super_admin']);

        if (Auth::hasAnyRole(['admin', 'super_admin'])) {
            $eventModel = new Event();
            $events = $eventModel->all();
        } else {
            $hostModel = new EventHost();
            $events = $hostModel->assignedEvents(Auth::id());
        }

        $readiness = [];

        foreach ($events as $event) {
            $readiness[(int) $event['id']] = $this->eventReadiness($event);
        }

        $this->view('host/assigned_events', [
            'title' => 'Assigned Events',
            'heading' => Auth::hasAnyRole(['admin', 'super_admin']) ? 'All Events' : 'Assigned Events',
            'events' => $events,
            'readiness' => $readiness,
        ]);
    }

    public function registrations(): void
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

        $this->view('host/event_registrations', [
            'title' => 'Event Registrations',
            'heading' => 'Event Registrations',
            'event' => $event,
            'registrations' => $registrationModel->forEvent($eventId),
            'playerSuggestions' => $registrationModel->recentPlayerContacts(),
        ]);
    }

    public function checkIn(): void
    {
        RequireAuth::anyRole(['host', 'event_manager', 'admin', 'super_admin']);

        $registrationId = (int) ($_POST['registration_id'] ?? 0);
        $eventId = (int) ($_POST['event_id'] ?? 0);

        if ($registrationId <= 0 || $eventId <= 0) {
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
            header('Location: index.php?page=host-event-registrations&id=' . $eventId);
            exit;
        }

        $registrationModel = new EventRegistration();

        if (!$registrationModel->checkInForEvent($registrationId, $eventId)) {
            http_response_code(404);
            echo '<h1>404 - Registration not found</h1>';
            return;
        }

        $auditLog = new AuditLog();
        $auditLog->create(
            Auth::id(),
            'player_checked_in',
            'event_registration',
            $registrationId,
            'Player checked in for event #' . $eventId
        );

        header('Location: index.php?page=host-event-registrations&id=' . $eventId);
        exit;
    }

    public function addWalkIn(): void
    {
        RequireAuth::anyRole(['host', 'event_manager', 'admin', 'super_admin']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=host-events');
            exit;
        }

        $eventId = (int) ($_POST['event_id'] ?? 0);
        $fullName = trim($_POST['full_name'] ?? '');
        $displayName = trim($_POST['display_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $ratingCategory = trim($_POST['rating_category'] ?? 'beginner');
        $paymentMethod = trim($_POST['payment_method'] ?? 'cash');
        $paymentStatus = trim($_POST['payment_status'] ?? 'unpaid');

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
            header('Location: index.php?page=host-event-registrations&id=' . $eventId);
            exit;
        }

        $validRatingCategories = ['beginner', 'casual', 'standard'];
        $validPaymentMethods = ['cash', 'eft', 'comped', 'other'];
        $validPaymentStatuses = ['unpaid', 'paid_cash', 'paid_eft', 'verified', 'comped'];

        $errors = [];

        if ($fullName === '') {
            $errors[] = 'Full name is required for walk-ins.';
        }

        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Email must be valid if provided.';
        }

        if (!in_array($ratingCategory, $validRatingCategories, true)) {
            $errors[] = 'Invalid rating category.';
        }

        if (!in_array($paymentMethod, $validPaymentMethods, true)) {
            $errors[] = 'Invalid payment method.';
        }

        if (!in_array($paymentStatus, $validPaymentStatuses, true)) {
            $errors[] = 'Invalid payment status.';
        }

        $registrationModel = new EventRegistration();
        $activeRegistrations = $registrationModel->countActiveForEvent($eventId);

        if ($activeRegistrations >= (int) $event['max_players']) {
            $errors[] = 'This event is already full.';
        }

        if (!empty($errors)) {
            $this->view('host/event_registrations', [
                'title' => 'Event Registrations',
                'heading' => 'Event Registrations',
                'event' => $event,
                'registrations' => $registrationModel->forEvent($eventId),
                'errors' => $errors,
                'walkInOld' => [
                    'full_name' => $fullName,
                    'display_name' => $displayName,
                    'email' => $email,
                    'phone' => $phone,
                    'rating_category' => $ratingCategory,
                    'payment_method' => $paymentMethod,
                    'payment_status' => $paymentStatus,
                ],
            ]);
            return;
        }

        $registrationStatus = 'checked_in';

        $registrationId = $registrationModel->create([
            'event_id' => $eventId,
            'player_id' => null,
            'user_id' => null,
            'full_name' => $fullName,
            'display_name' => $displayName !== '' ? $displayName : null,
            'email' => $email !== '' ? $email : 'walkin-' . time() . '-' . random_int(1000, 9999) . '@walkin.local',
            'phone' => $phone !== '' ? $phone : null,
            'rating_category' => $ratingCategory,
            'registration_status' => $registrationStatus,
            'payment_status' => $paymentStatus,
            'payment_method' => $paymentMethod,
            'notes' => 'Walk-in registration added by host.',
        ]);

        $paymentModel = new Payment();
        $paymentId = $paymentModel->createForRegistration([
            'registration_id' => $registrationId,
            'event_id' => $eventId,
            'user_id' => null,
            'player_id' => null,
            'amount' => $event['entry_fee'],
            'payment_method' => $paymentMethod,
            'payment_status' => $paymentStatus,
            'notes' => 'Walk-in payment record created by host.',
        ]);

        if (in_array($paymentStatus, ['paid_cash', 'paid_eft', 'verified', 'comped'], true)) {
            $paymentModel->updateStatus(
                $paymentId,
                $paymentStatus,
                $paymentMethod,
                Auth::id(),
                'Walk-in payment marked by host.'
            );
        }

        $auditLog = new AuditLog();
        $auditLog->create(
            Auth::id(),
            'walk_in_registered',
            'event_registration',
            $registrationId,
            'Walk-in player added to event #' . $eventId . ': ' . $fullName
        );

        header('Location: index.php?page=host-event-registrations&id=' . $eventId);
        exit;
    }

    public function report(): void
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

        $reportModel = new EventReport();
        $existingReport = $reportModel->findByEventId($eventId);

        $this->view('host/event_report', [
            'title' => 'Submit Event Report',
            'heading' => 'Submit Event Report',
            'event' => $event,
            'existingReport' => $existingReport,
            'summary' => $this->eventSummary($eventId),
            'errors' => [],
            'old' => $existingReport ?? [],
        ]);
    }

    public function storeReport(): void
    {
        RequireAuth::anyRole(['host', 'event_manager', 'admin', 'super_admin']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=host-events');
            exit;
        }

        $eventId = (int) ($_POST['event_id'] ?? 0);
        $attendanceCount = (int) ($_POST['attendance_count'] ?? 0);
        $cashCollected = trim($_POST['cash_collected'] ?? '0');
        $cashHandedOver = trim($_POST['cash_handed_over'] ?? '0');
        $winnerNotes = trim($_POST['winner_notes'] ?? '');
        $issueNotes = trim($_POST['issue_notes'] ?? '');
        $generalNotes = trim($_POST['general_notes'] ?? '');

        $errors = [];

        if ($eventId <= 0) {
            $errors[] = 'Invalid event selected.';
        }

        if ($attendanceCount < 0) {
            $errors[] = 'Attendance count cannot be negative.';
        }

        if (!is_numeric($cashCollected) || (float) $cashCollected < 0) {
            $errors[] = 'Cash collected must be a valid amount.';
        }

        if (!is_numeric($cashHandedOver) || (float) $cashHandedOver < 0) {
            $errors[] = 'Cash handed over must be a valid amount.';
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

        $old = [
            'attendance_count' => $attendanceCount,
            'cash_collected' => $cashCollected,
            'cash_handed_over' => $cashHandedOver,
            'winner_notes' => $winnerNotes,
            'issue_notes' => $issueNotes,
            'general_notes' => $generalNotes,
        ];

        if (!empty($errors)) {
            $this->view('host/event_report', [
                'title' => 'Submit Event Report',
                'heading' => 'Submit Event Report',
                'event' => $event,
                'existingReport' => null,
                'summary' => $this->eventSummary($eventId),
                'errors' => $errors,
                'old' => $old,
            ]);
            return;
        }

        $reportModel = new EventReport();

        $reportId = $reportModel->create([
            'event_id' => $eventId,
            'submitted_by' => Auth::id(),
            'attendance_count' => $attendanceCount,
            'cash_collected' => (float) $cashCollected,
            'cash_handed_over' => (float) $cashHandedOver,
            'winner_notes' => $winnerNotes !== '' ? $winnerNotes : null,
            'issue_notes' => $issueNotes !== '' ? $issueNotes : null,
            'general_notes' => $generalNotes !== '' ? $generalNotes : null,
        ]);

        $auditLog = new AuditLog();
        $auditLog->create(
            Auth::id(),
            'event_report_submitted',
            'event_report',
            $reportId,
            'Event report submitted for event #' . $eventId
        );

        header('Location: index.php?page=host-event-registrations&id=' . $eventId);
        exit;
    }

    public function inviteWalkIn(): void
    {
        RequireAuth::anyRole(['host', 'event_manager', 'admin', 'super_admin']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=host-events');
            exit;
        }

        $registrationId = (int) ($_POST['registration_id'] ?? 0);
        $registrationModel = new EventRegistration();
        $registration = $registrationModel->findByIdWithEventDetails($registrationId);

        if (!$registration || !$this->canAccessEvent((int) $registration['event_id'])) {
            http_response_code(403);
            echo '<h1>403 - Access denied</h1>';
            return;
        }

        $email = trim($registration['email'] ?? '');

        if ($email === '' || str_ends_with($email, '@walkin.local')) {
            header('Location: index.php?page=host-event-registrations&id=' . (int) $registration['event_id']);
            exit;
        }

        $token = bin2hex(random_bytes(32));
        $expiresAt = new DateTime('+14 days');
        $invitationModel = new WalkInInvitation();
        $invitationModel->create($registrationId, $email, $token, Auth::id(), $expiresAt);

        $claimUrl = absolute_url('index.php?page=accept-walkin-invite&token=' . urlencode($token));
        $body = '
            <p>You played in ' . htmlspecialchars($registration['event_title']) . ' as a walk-in.</p>
            <p><a href="' . htmlspecialchars($claimUrl) . '">Link this result to your Mate Tournaments account</a></p>
            <p>This link expires in 14 days.</p>
        ';

        Mailer::send($email, 'Link your Mate Tournaments walk-in result', $body);

        $auditLog = new AuditLog();
        $auditLog->create(
            Auth::id(),
            'walkin_invitation_sent',
            'event_registration',
            $registrationId,
            'Walk-in account invitation sent to ' . $email
        );

        header('Location: index.php?page=host-event-registrations&id=' . (int) $registration['event_id']);
        exit;
    }

    public function linkWalkIn(): void
    {
        RequireAuth::anyRole(['host', 'event_manager', 'admin', 'super_admin']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=host-events');
            exit;
        }

        $registrationId = (int) ($_POST['registration_id'] ?? 0);
        $accountEmail = trim($_POST['account_email'] ?? '');
        $registrationModel = new EventRegistration();
        $registration = $registrationModel->findByIdWithEventDetails($registrationId);

        if (!$registration || !$this->canAccessEvent((int) $registration['event_id'])) {
            http_response_code(403);
            echo '<h1>403 - Access denied</h1>';
            return;
        }

        $userModel = new User();
        $playerModel = new Player();
        $user = $accountEmail !== '' ? $userModel->findByEmail($accountEmail) : null;
        $player = $user ? $playerModel->findByUserId((int) $user['id']) : null;

        if ($user && $player) {
            $registrationModel->linkToPlayer($registrationId, (int) $user['id'], (int) $player['id']);

            $auditLog = new AuditLog();
            $auditLog->create(
                Auth::id(),
                'walkin_manually_linked',
                'event_registration',
                $registrationId,
                'Walk-in linked to account ' . $user['email']
            );
        }

        header('Location: index.php?page=host-event-registrations&id=' . (int) $registration['event_id']);
        exit;
    }

    private function eventIsClosedForOperations(array $event): bool
    {
        return in_array(($event['event_status'] ?? ''), ['completed', 'cancelled'], true);
    }

    private function canAccessEvent(int $eventId): bool
    {
        if (Auth::hasAnyRole(['admin', 'super_admin'])) {
            return true;
        }

        $hostModel = new EventHost();

        return $hostModel->isAssigned($eventId, Auth::id());
    }

    private function eventSummary(int $eventId): array
    {
        $registrationModel = new EventRegistration();
        $registrations = $registrationModel->forEvent($eventId);
        $summary = [
            'total' => count($registrations),
            'checked_in' => 0,
            'walk_ins' => 0,
            'no_shows' => 0,
            'cash' => 0,
            'eft' => 0,
        ];

        foreach ($registrations as $registration) {
            if (($registration['registration_status'] ?? '') === 'checked_in') {
                $summary['checked_in']++;
            }

            if (str_contains((string) ($registration['notes'] ?? ''), 'Walk-in')) {
                $summary['walk_ins']++;
            }

            if (($registration['registration_status'] ?? '') === 'no_show') {
                $summary['no_shows']++;
            }

            $method = $registration['payment_record_method'] ?? $registration['payment_method'] ?? '';
            $status = $registration['payment_record_status'] ?? $registration['payment_status'] ?? '';

            if ($method === 'cash' && in_array($status, ['paid_cash', 'verified'], true)) {
                $summary['cash']++;
            }

            if ($method === 'eft' && in_array($status, ['paid_eft', 'proof_uploaded', 'verified'], true)) {
                $summary['eft']++;
            }
        }

        return $summary;
    }

    private function eventReadiness(array $event): array
    {
        $eventId = (int) ($event['id'] ?? 0);
        $items = [];
        $registrationModel = new EventRegistration();
        $registrations = $eventId > 0 ? $registrationModel->forEvent($eventId) : [];
        $checkedIn = 0;
        $walkIns = 0;
        $hostModel = new EventHost();
        $hostCount = $eventId > 0 ? $hostModel->countAssignedUsers($eventId) : 0;

        foreach ($registrations as $registration) {
            if (($registration['registration_status'] ?? '') === 'checked_in') {
                $checkedIn++;
            }

            if (str_contains((string) ($registration['notes'] ?? ''), 'Walk-in')) {
                $walkIns++;
            }
        }

        $eventStatus = $event['event_status'] ?? '';
        $registrationStatus = $event['registration_status'] ?? '';
        $eventClosed = $this->eventIsClosedForOperations($event);

        if ($eventClosed) {
            $items[] = ['state' => 'done', 'label' => 'Event is closed for live operations'];
        } elseif (in_array($eventStatus, ['published', 'running'], true)) {
            $items[] = ['state' => 'done', 'label' => 'Event is published or running'];
        } else {
            $items[] = ['state' => 'risk', 'label' => 'Event is not published/running'];
        }

        $items[] = [
            'state' => in_array($registrationStatus, ['open', 'full', 'closed'], true) ? 'done' : 'risk',
            'label' => 'Registration status: ' . $this->readinessLabel($registrationStatus),
        ];

        $items[] = [
            'state' => $hostCount > 0 ? 'done' : 'risk',
            'label' => $hostCount . ' host/event manager assignment' . ($hostCount === 1 ? '' : 's'),
        ];

        $items[] = [
            'state' => $checkedIn > 0 ? 'done' : 'todo',
            'label' => $checkedIn . ' checked in, ' . $walkIns . ' walk-in' . ($walkIns === 1 ? '' : 's'),
        ];

        $tournamentModel = new Tournament();
        $tournament = $eventId > 0 ? $tournamentModel->findByEventId($eventId) : null;

        if (!$tournament) {
            $items[] = ['state' => $eventClosed ? 'todo' : 'risk', 'label' => 'Tournament not created'];
            return $this->summarizeReadiness($items);
        }

        $items[] = [
            'state' => ($tournament['status'] ?? '') === 'completed' ? 'done' : 'done',
            'label' => 'Tournament: ' . $this->readinessLabel($tournament['status'] ?? 'setup'),
        ];

        $participantModel = new TournamentParticipant();
        $participantCount = $participantModel->countForTournament((int) $tournament['id']);
        $lateEntries = $participantModel->missingCheckedInRegistrations((int) $tournament['id'], $eventId);

        $items[] = [
            'state' => $participantCount > 0 ? 'done' : 'todo',
            'label' => $participantCount . ' tournament participant' . ($participantCount === 1 ? '' : 's'),
        ];

        if (!empty($lateEntries) && in_array(($tournament['format'] ?? ''), ['swiss', 'weekly_points'], true)) {
            $items[] = ['state' => 'risk', 'label' => count($lateEntries) . ' late entr' . (count($lateEntries) === 1 ? 'y' : 'ies') . ' waiting'];
        }

        $roundModel = new Round();
        $latestRound = $roundModel->latestForTournament((int) $tournament['id']);

        if ($latestRound) {
            $matchModel = new MatchModel();
            $roundComplete = $matchModel->roundIsComplete((int) $latestRound['id']);
            $items[] = [
                'state' => $roundComplete ? 'done' : 'todo',
                'label' => ($latestRound['name'] ?? 'Current round') . ($roundComplete ? ' complete' : ' still active'),
            ];
        } else {
            $items[] = ['state' => $participantCount >= 2 ? 'todo' : 'risk', 'label' => 'No round generated yet'];
        }

        $reportModel = new EventReport();
        $report = $reportModel->findByEventId($eventId);
        $items[] = [
            'state' => $report ? 'done' : 'todo',
            'label' => $report ? 'Host report submitted' : 'Host report not submitted',
        ];

        return $this->summarizeReadiness($items);
    }

    private function summarizeReadiness(array $items): array
    {
        $riskCount = count(array_filter($items, fn ($item) => ($item['state'] ?? '') === 'risk'));
        $todoCount = count(array_filter($items, fn ($item) => ($item['state'] ?? '') === 'todo'));

        return [
            'status' => $riskCount > 0 ? 'risk' : ($todoCount > 0 ? 'todo' : 'ready'),
            'items' => $items,
        ];
    }

    private function readinessLabel(string $value): string
    {
        return $value !== '' ? ucwords(str_replace('_', ' ', $value)) : '-';
    }
}
