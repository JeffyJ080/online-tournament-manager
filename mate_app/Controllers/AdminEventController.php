<?php

require_once __DIR__ . '/../Core/Controller.php';
require_once __DIR__ . '/../Middleware/RequireAuth.php';
require_once __DIR__ . '/../Models/Event.php';
require_once __DIR__ . '/../Models/Venue.php';
require_once __DIR__ . '/../Models/EventSeries.php';
require_once __DIR__ . '/../Models/AuditLog.php';
require_once __DIR__ . '/../Helpers/Auth.php';
require_once __DIR__ . '/../Helpers/slug.php';

class AdminEventController extends Controller
{
    public function index(): void
    {
        RequireAuth::anyRole(['admin', 'super_admin']);

        $eventModel = new Event();
        $events = $eventModel->all();

        $this->view('admin/events/index', [
            'title' => 'Events',
            'heading' => 'Manage Events',
            'events' => $events,
        ]);
    }

    public function create(): void
    {
        RequireAuth::anyRole(['admin', 'super_admin']);

        $venueModel = new Venue();
        $seriesModel = new EventSeries();

        $this->view('admin/events/create', [
            'title' => 'Create Event',
            'heading' => 'Create Event',
            'venues' => $venueModel->active(),
            'seriesList' => $seriesModel->active(),
            'errors' => [],
            'old' => [],
        ]);
    }

    public function store(): void
    {
        RequireAuth::anyRole(['admin', 'super_admin']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=admin-events-create');
            exit;
        }

        $seriesId = ($_POST['series_id'] ?? '') !== '' ? (int) $_POST['series_id'] : null;
        $venueId = (int) ($_POST['venue_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $eventDate = trim($_POST['event_date'] ?? '');
        $startTime = trim($_POST['start_time'] ?? '');
        $endTime = trim($_POST['end_time'] ?? '');
        $entryFee = trim($_POST['entry_fee'] ?? '0');
        $prizeInfo = trim($_POST['prize_info'] ?? '');
        $format = trim($_POST['format'] ?? 'knockout');
        $maxPlayers = (int) ($_POST['max_players'] ?? 32);
        $registrationStatus = trim($_POST['registration_status'] ?? 'open');
        $eventStatus = trim($_POST['event_status'] ?? 'draft');
        $notes = trim($_POST['notes'] ?? '');

        $errors = [];

        if ($venueId <= 0) {
            $errors[] = 'Venue is required.';
        }

        if ($title === '') {
            $errors[] = 'Event title is required.';
        }

        if ($eventDate === '') {
            $errors[] = 'Event date is required.';
        }

        if ($startTime === '') {
            $errors[] = 'Start time is required.';
        }

        if (!is_numeric($entryFee) || (float) $entryFee < 0) {
            $errors[] = 'Entry fee must be a valid amount.';
        }

        if ($maxPlayers <= 0) {
            $errors[] = 'Max players must be greater than 0.';
        }

        $validFormats = ['knockout', 'swiss', 'round_robin', 'weekly_points', 'team', 'doubles'];
        $validRegistrationStatuses = ['open', 'closed', 'full', 'cancelled'];
        $validEventStatuses = ['draft', 'published', 'running', 'completed', 'cancelled'];

        if (!in_array($format, $validFormats, true)) {
            $errors[] = 'Invalid tournament format.';
        }

        if (!in_array($registrationStatus, $validRegistrationStatuses, true)) {
            $errors[] = 'Invalid registration status.';
        }

        if (!in_array($eventStatus, $validEventStatuses, true)) {
            $errors[] = 'Invalid event status.';
        }

        $eventModel = new Event();

        $slug = makeSlug($title . '-' . $eventDate);
        $baseSlug = $slug;
        $counter = 2;

        while ($eventModel->slugExists($slug)) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        $old = [
            'series_id' => $seriesId,
            'venue_id' => $venueId,
            'title' => $title,
            'description' => $description,
            'event_date' => $eventDate,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'entry_fee' => $entryFee,
            'prize_info' => $prizeInfo,
            'format' => $format,
            'max_players' => $maxPlayers,
            'registration_status' => $registrationStatus,
            'event_status' => $eventStatus,
            'notes' => $notes,
        ];

        if (!empty($errors)) {
            $venueModel = new Venue();
            $seriesModel = new EventSeries();

            $this->view('admin/events/create', [
                'title' => 'Create Event',
                'heading' => 'Create Event',
                'venues' => $venueModel->active(),
                'seriesList' => $seriesModel->active(),
                'errors' => $errors,
                'old' => $old,
            ]);
            return;
        }

        $eventId = $eventModel->create([
            'series_id' => $seriesId,
            'venue_id' => $venueId,
            'title' => $title,
            'slug' => $slug,
            'description' => $description !== '' ? $description : null,
            'event_date' => $eventDate,
            'start_time' => $startTime,
            'end_time' => $endTime !== '' ? $endTime : null,
            'entry_fee' => (float) $entryFee,
            'prize_info' => $prizeInfo !== '' ? $prizeInfo : null,
            'format' => $format,
            'max_players' => $maxPlayers,
            'registration_status' => $registrationStatus,
            'event_status' => $eventStatus,
            'notes' => $notes !== '' ? $notes : null,
            'created_by' => Auth::id(),
        ]);

        $auditLog = new AuditLog();
        $auditLog->create(
            Auth::id(),
            'event_created',
            'event',
            $eventId,
            'Event created: ' . $title
        );

        header('Location: index.php?page=admin-events');
        exit;
    }
}