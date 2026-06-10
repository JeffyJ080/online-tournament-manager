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
        }

        $this->view('tournament/manager', [
            'title' => 'Tournament Manager',
            'heading' => 'Tournament Manager',

            'event' => $event,
            'tournament' => $tournament,
            'participants' => $participants,
            'checkedInPlayers' => $checkedInPlayers,
        ]);
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

        $tournamentModel = new Tournament();

        $existing = $tournamentModel->findByEventId($eventId);

        if (!$existing) {
            $tournamentId = $tournamentModel->create([
                'event_id' => $eventId,
                'format' => $event['format'],
                'status' => 'setup',
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

        $registrationModel = new EventRegistration();
        $registrations = $registrationModel->forEvent($eventId);

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
}