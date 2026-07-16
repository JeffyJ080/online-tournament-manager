<?php

require_once __DIR__ . '/../Core/Controller.php';
require_once __DIR__ . '/../Middleware/RequireAuth.php';
require_once __DIR__ . '/../Models/Event.php';
require_once __DIR__ . '/../Models/Venue.php';
require_once __DIR__ . '/../Models/EventSeries.php';
require_once __DIR__ . '/../Models/EventHost.php';
require_once __DIR__ . '/../Models/User.php';
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
        $seriesModel = new EventSeries();

        $this->view('admin/events/index', [
            'title' => 'Events',
            'heading' => 'Manage Events',
            'events' => $events,
            'seriesList' => $seriesModel->active(),
            'generationMessage' => $_SESSION['generation_message'] ?? null,
        ]);

        unset($_SESSION['generation_message']);
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

    public function edit(): void
    {
        RequireAuth::anyRole(['admin', 'super_admin']);

        $id = (int) ($_GET['id'] ?? 0);

        if ($id <= 0) {
            header('Location: index.php?page=admin-events');
            exit;
        }

        $eventModel = new Event();
        $event = $eventModel->findById($id);

        if (!$event) {
            http_response_code(404);
            echo '<h1>404 - Event not found</h1>';
            return;
        }

        $venueModel = new Venue();
        $seriesModel = new EventSeries();
        $userModel = new User();
        $hostModel = new EventHost();

        $this->view('admin/events/edit', [
            'title' => 'Edit Event',
            'heading' => 'Edit Event',
            'event' => $event,
            'venues' => $venueModel->active(),
            'seriesList' => $seriesModel->active(),
            'eventStaff' => $userModel->usersByRole(['host', 'event_manager']),
            'assignedStaffIds' => $hostModel->assignedUserIds($id),
            'errors' => [],
            'old' => $event,
        ]);
    }

    public function update(): void
    {
        RequireAuth::anyRole(['admin', 'super_admin']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=admin-events');
            exit;
        }

        $id = (int) ($_POST['id'] ?? 0);

        $eventModel = new Event();
        $event = $eventModel->findById($id);

        if (!$event) {
            http_response_code(404);
            echo '<h1>404 - Event not found</h1>';
            return;
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
        $assignedStaffIds = $_POST['assigned_staff_ids'] ?? [];

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

        $old = [
            'id' => $id,
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
            $userModel = new User();
            $hostModel = new EventHost();

            $this->view('admin/events/edit', [
                'title' => 'Edit Event',
                'heading' => 'Edit Event',
                'event' => $event,
                'venues' => $venueModel->active(),
                'seriesList' => $seriesModel->active(),
                'eventStaff' => $userModel->usersByRole(['host', 'event_manager']),
                'assignedStaffIds' => array_map('intval', $assignedStaffIds),
                'errors' => $errors,
                'old' => $old,
            ]);
            return;
        }

        $eventModel->update($id, [
            'series_id' => $seriesId,
            'venue_id' => $venueId,
            'title' => $title,
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
        ]);

        $hostModel = new EventHost();
        $hostModel->syncAssignments($id, $assignedStaffIds, Auth::id());

        $auditLog = new AuditLog();
        $auditLog->create(
            Auth::id(),
            'event_updated',
            'event',
            $id,
            'Event updated: ' . $title
        );

        header('Location: index.php?page=admin-events');
        exit;
    }

    public function generateRecurring(): void
    {
        RequireAuth::anyRole(['admin', 'super_admin']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=admin-events');
            exit;
        }

        $seriesId = (int) ($_POST['series_id'] ?? 0);
        $startsOn = trim($_POST['starts_on'] ?? '');
        $endsOn = trim($_POST['ends_on'] ?? '');

        $seriesModel = new EventSeries();
        $eventModel = new Event();
        $series = $seriesModel->findById($seriesId);

        if (!$series || $startsOn === '' || $endsOn === '' || strtotime($startsOn) === false || strtotime($endsOn) === false) {
            $_SESSION['generation_message'] = 'Choose a valid active series and date range.';
            header('Location: index.php?page=admin-events');
            exit;
        }

        if (strtotime($endsOn) < strtotime($startsOn)) {
            $_SESSION['generation_message'] = 'The end date must be after the start date.';
            header('Location: index.php?page=admin-events');
            exit;
        }

        $dates = $this->datesForSeries($series, $startsOn, $endsOn);
        $created = 0;
        $skipped = 0;

        foreach ($dates as $date) {
            if ($eventModel->existsForSeriesDate((int) $series['id'], $date)) {
                $skipped++;
                continue;
            }

            $title = $series['title'] . ' - ' . date('d M Y', strtotime($date));
            $slug = makeSlug($series['title'] . '-' . $date);
            $baseSlug = $slug;
            $counter = 2;

            while ($eventModel->slugExists($slug)) {
                $slug = $baseSlug . '-' . $counter;
                $counter++;
            }

            $eventModel->create([
                'series_id' => (int) $series['id'],
                'venue_id' => (int) $series['venue_id'],
                'title' => $title,
                'slug' => $slug,
                'description' => $series['description'] ?? null,
                'event_date' => $date,
                'start_time' => $series['default_start_time'] ?? '18:00:00',
                'end_time' => $series['default_end_time'] ?? null,
                'entry_fee' => (float) ($series['default_entry_fee'] ?? 0),
                'prize_info' => $series['default_prize_info'] ?? null,
                'format' => $series['default_format'] ?? 'knockout',
                'max_players' => (int) ($series['default_max_players'] ?? 32),
                'registration_status' => 'open',
                'event_status' => 'published',
                'notes' => 'Generated from recurring series.',
                'created_by' => Auth::id(),
            ]);

            $created++;
        }

        $auditLog = new AuditLog();
        $auditLog->create(
            Auth::id(),
            'recurring_events_generated',
            'event_series',
            (int) $series['id'],
            'Generated ' . $created . ' events; skipped ' . $skipped . ' existing dates.'
        );

        $_SESSION['generation_message'] = 'Generated ' . $created . ' events. Skipped ' . $skipped . ' existing dates.';
        header('Location: index.php?page=admin-events');
        exit;
    }

    private function datesForSeries(array $series, string $startsOn, string $endsOn): array
    {
        $dates = [];
        $start = new DateTime($startsOn);
        $end = new DateTime($endsOn);
        $type = $series['recurrence_type'] ?? 'none';

        if ($type === 'weekly') {
            $targetDay = (int) ($series['day_of_week'] ?? 0);

            if ($targetDay < 1 || $targetDay > 7) {
                return [];
            }

            for ($date = clone $start; $date <= $end; $date->modify('+1 day')) {
                if ((int) $date->format('N') === $targetDay) {
                    $dates[] = $date->format('Y-m-d');
                }
            }

            return $dates;
        }

        if ($type === 'monthly') {
            for ($cursor = new DateTime($start->format('Y-m-01')); $cursor <= $end; $cursor->modify('+1 month')) {
                $date = $this->monthlySeriesDate($series, $cursor);

                if ($date && $date >= $start && $date <= $end) {
                    $dates[] = $date->format('Y-m-d');
                }
            }
        }

        return $dates;
    }

    private function monthlySeriesDate(array $series, DateTime $month): ?DateTime
    {
        if (!empty($series['day_of_month'])) {
            $day = (int) $series['day_of_month'];
            $lastDay = (int) $month->format('t');

            if ($day < 1 || $day > $lastDay) {
                return null;
            }

            return new DateTime($month->format('Y-m-') . str_pad((string) $day, 2, '0', STR_PAD_LEFT));
        }

        if (empty($series['monthly_week']) || empty($series['monthly_weekday'])) {
            return null;
        }

        $week = $series['monthly_week'];
        $weekday = (int) $series['monthly_weekday'];
        $matches = [];

        for ($date = new DateTime($month->format('Y-m-01')); $date->format('m') === $month->format('m'); $date->modify('+1 day')) {
            if ((int) $date->format('N') === $weekday) {
                $matches[] = clone $date;
            }
        }

        return match ($week) {
            'first' => $matches[0] ?? null,
            'second' => $matches[1] ?? null,
            'third' => $matches[2] ?? null,
            'fourth' => $matches[3] ?? null,
            'last' => !empty($matches) ? $matches[count($matches) - 1] : null,
            default => null,
        };
    }
}
