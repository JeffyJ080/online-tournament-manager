<?php

require_once __DIR__ . '/../Core/Controller.php';
require_once __DIR__ . '/../Middleware/RequireAuth.php';
require_once __DIR__ . '/../Helpers/Auth.php';
require_once __DIR__ . '/../Models/EventHost.php';
require_once __DIR__ . '/../Models/Event.php';
require_once __DIR__ . '/../Models/EventRegistration.php';
require_once __DIR__ . '/../Models/Payment.php';
require_once __DIR__ . '/../Models/AuditLog.php';
require_once __DIR__ . '/../Models/EventReport.php';

class HostEventController extends Controller
{
    public function assigned(): void
    {
        RequireAuth::anyRole(['host', 'event_manager', 'admin', 'super_admin']);

        $hostModel = new EventHost();

        $events = $hostModel->assignedEvents(Auth::id());

        $this->view('host/assigned_events', [
            'title' => 'Assigned Events',
            'heading' => 'Assigned Events',
            'events' => $events,
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

        $registrationModel = new EventRegistration();

        $registrationModel->checkIn($registrationId);

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
}