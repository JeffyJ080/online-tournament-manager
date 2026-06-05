<?php

require_once __DIR__ . '/../Core/Controller.php';
require_once __DIR__ . '/../Models/Event.php';
require_once __DIR__ . '/../Models/EventRegistration.php';
require_once __DIR__ . '/../Models/Player.php';
require_once __DIR__ . '/../Models/AuditLog.php';
require_once __DIR__ . '/../Helpers/Auth.php';

class PublicController extends Controller
{
    public function home(): void
    {
        $this->view('public/home', [
            'title' => 'Home',
            'heading' => 'Mate Tournaments',
        ]);
    }

    public function events(): void
    {
        $eventModel = new Event();
        $events = $eventModel->publishedUpcoming();

        $this->view('public/events', [
            'title' => 'Events',
            'heading' => 'Upcoming Events',
            'events' => $events,
        ]);
    }

    public function about(): void
    {
        $this->view('public/about', [
            'title' => 'About',
            'heading' => 'About Mate Tournaments',
        ]);
    }

    public function contact(): void
    {
        $this->view('public/contact', [
            'title' => 'Contact',
            'heading' => 'Contact Mate Tournaments',
        ]);
    }

    public function eventDetails(): void
    {
        $slug = trim($_GET['slug'] ?? '');

        if ($slug === '') {
            header('Location: index.php?page=events');
            exit;
        }

        $eventModel = new Event();
        $event = $eventModel->findBySlug($slug);

        if (!$event || $event['event_status'] !== 'published') {
            http_response_code(404);

            $this->view('public/404', [
                'title' => 'Event Not Found',
                'heading' => 'Event not found',
            ]);
            return;
        }

        $this->view('public/event_details', [
            'title' => $event['title'],
            'heading' => $event['title'],
            'event' => $event,
        ]);
    }

    public function registerEvent(): void
    {
        $eventId = (int) ($_GET['event'] ?? 0);

        if ($eventId <= 0) {
            header('Location: index.php?page=events');
            exit;
        }

        $eventModel = new Event();
        $registrationModel = new EventRegistration();

        $event = $eventModel->findById($eventId);

        if (!$event || $event['event_status'] !== 'published') {
            http_response_code(404);

            $this->view('public/404', [
                'title' => 'Event Not Found',
                'heading' => 'Event not found',
            ]);
            return;
        }

        $activeRegistrations = $registrationModel->countActiveForEvent($eventId);
        $spotsLeft = max(0, (int) $event['max_players'] - $activeRegistrations);

        $old = [];

        if (Auth::check()) {
            $playerModel = new Player();
            $player = $playerModel->findByUserId(Auth::id());

            if ($player) {
                $old = [
                    'full_name' => $player['real_name'] ?? '',
                    'display_name' => $player['display_name'] ?? '',
                    'email' => $player['email'] ?? Auth::user()['email'],
                    'phone' => $player['phone'] ?? '',
                    'rating_category' => $player['rating_category'] ?? 'beginner',
                ];
            }
        }

        $this->view('public/register_event', [
            'title' => 'Register for Event',
            'heading' => 'Register for Event',
            'event' => $event,
            'spots_left' => $spotsLeft,
            'active_registrations' => $activeRegistrations,
            'errors' => [],
            'old' => $old,
        ]);
    }

    public function storeEventRegistration(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=events');
            exit;
        }

        $eventId = (int) ($_POST['event_id'] ?? 0);
        $fullName = trim($_POST['full_name'] ?? '');
        $displayName = trim($_POST['display_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $ratingCategory = trim($_POST['rating_category'] ?? 'beginner');
        $paymentMethod = trim($_POST['payment_method'] ?? 'cash');

        $errors = [];

        $eventModel = new Event();
        $registrationModel = new EventRegistration();

        $event = $eventModel->findById($eventId);

        if (!$event || $event['event_status'] !== 'published') {
            $errors[] = 'This event is not available for registration.';
        }

        if ($fullName === '') {
            $errors[] = 'Full name is required.';
        }

        if ($email === '') {
            $errors[] = 'Email address is required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        }

        if (!in_array($ratingCategory, ['beginner', 'casual', 'standard'], true)) {
            $errors[] = 'Invalid rating category selected.';
        }

        if (!in_array($paymentMethod, ['cash', 'eft'], true)) {
            $errors[] = 'Invalid payment method selected.';
        }

        $activeRegistrations = 0;
        $spotsLeft = 0;

        if ($event) {
            $activeRegistrations = $registrationModel->countActiveForEvent($eventId);
            $spotsLeft = max(0, (int) $event['max_players'] - $activeRegistrations);

            if ($event['registration_status'] !== 'open') {
                $errors[] = 'Registration is not open for this event.';
            }

            if ($spotsLeft <= 0) {
                $errors[] = 'This event is full.';
            }

            if (Auth::check() && $registrationModel->userAlreadyRegistered($eventId, Auth::id())) {
                $errors[] = 'You are already registered for this event.';
            }

            if ($email !== '' && $registrationModel->emailAlreadyRegistered($eventId, $email)) {
                $errors[] = 'This email is already registered for this event.';
            }
        }

        $old = [
            'full_name' => $fullName,
            'display_name' => $displayName,
            'email' => $email,
            'phone' => $phone,
            'rating_category' => $ratingCategory,
            'payment_method' => $paymentMethod,
        ];

        if (!empty($errors)) {
            $this->view('public/register_event', [
                'title' => 'Register for Event',
                'heading' => 'Register for Event',
                'event' => $event ?? [],
                'spots_left' => $spotsLeft,
                'active_registrations' => $activeRegistrations,
                'errors' => $errors,
                'old' => $old,
            ]);
            return;
        }

        $playerId = null;
        $userId = null;

        if (Auth::check()) {
            $userId = Auth::id();

            $playerModel = new Player();
            $player = $playerModel->findByUserId($userId);

            if ($player) {
                $playerId = (int) $player['id'];
            }
        }

        $registrationStatus = 'pending';
        $paymentStatus = 'unpaid';

        $registrationId = $registrationModel->create([
            'event_id' => $eventId,
            'player_id' => $playerId,
            'user_id' => $userId,
            'full_name' => $fullName,
            'display_name' => $displayName !== '' ? $displayName : null,
            'email' => $email,
            'phone' => $phone !== '' ? $phone : null,
            'rating_category' => $ratingCategory,
            'registration_status' => $registrationStatus,
            'payment_status' => $paymentStatus,
            'payment_method' => $paymentMethod,
            'notes' => null,
        ]);

        $auditLog = new AuditLog();
        $auditLog->create(
            $userId,
            'event_registration_created',
            'event_registration',
            $registrationId,
            'Registration created for event: ' . $event['title']
        );

        $this->view('public/register_event_success', [
            'title' => 'Registration Submitted',
            'heading' => 'Registration submitted',
            'event' => $event,
            'registration_id' => $registrationId,
            'payment_method' => $paymentMethod,
        ]);
    }
}