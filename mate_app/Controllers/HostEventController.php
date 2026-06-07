<?php

require_once __DIR__ . '/../Core/Controller.php';
require_once __DIR__ . '/../Middleware/RequireAuth.php';
require_once __DIR__ . '/../Helpers/Auth.php';
require_once __DIR__ . '/../Models/EventHost.php';
require_once __DIR__ . '/../Models/Event.php';
require_once __DIR__ . '/../Models/EventRegistration.php';
require_once __DIR__ . '/../Models/AuditLog.php';

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
}