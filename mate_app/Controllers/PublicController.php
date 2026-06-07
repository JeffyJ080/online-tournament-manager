<?php

require_once __DIR__ . '/../Core/Controller.php';
require_once __DIR__ . '/../Core/Database.php';
require_once __DIR__ . '/../Core/Mailer.php';
require_once __DIR__ . '/../Models/Event.php';
require_once __DIR__ . '/../Models/EventRegistration.php';
require_once __DIR__ . '/../Models/Player.php';
require_once __DIR__ . '/../Models/AuditLog.php';
require_once __DIR__ . '/../Helpers/Auth.php';
require_once __DIR__ . '/../Models/Payment.php';

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

        $db = Database::connect();

        try {
            $db->beginTransaction();

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

            $paymentModel = new Payment();

            $paymentId = $paymentModel->createForRegistration([
                'registration_id' => $registrationId,
                'event_id' => $eventId,
                'user_id' => $userId,
                'player_id' => $playerId,
                'amount' => $event['entry_fee'],
                'payment_method' => $paymentMethod,
                'payment_status' => $paymentStatus,
                'notes' => 'Payment record created during event registration.',
            ]);

            $auditLog = new AuditLog();
            $auditLog->create(
                $userId,
                'event_registration_created',
                'event_registration',
                $registrationId,
                'Registration created for event: ' . $event['title']
            );

            $auditLog->create(
                $userId,
                'payment_record_created',
                'payment',
                $paymentId,
                'Payment record created for registration #' . $registrationId
            );

            $playerEmailBody = '
                <p>Hi ' . htmlspecialchars($fullName) . ',</p>

                <p>Your registration for <strong>' . htmlspecialchars($event['title']) . '</strong> has been received.</p>

                <p>
                    <strong>Venue:</strong> ' . htmlspecialchars($event['venue_name']) . '<br>
                    <strong>Date:</strong> ' . htmlspecialchars(date('D, d M Y', strtotime($event['event_date']))) . '<br>
                    <strong>Time:</strong> ' . htmlspecialchars(date('H:i', strtotime($event['start_time']))) . '<br>
                    <strong>Payment method:</strong> ' . htmlspecialchars(ucwords($paymentMethod)) . '
                </p>

                <p>Your registration reference is <strong>#' . (int) $registrationId . '</strong>.</p>
            ';

            if ($paymentMethod === 'eft') {
                $playerEmailBody .= '
                    <p>Please upload your proof of payment so admin can verify your spot.</p>
                ';
            } else {
                $playerEmailBody .= '
                    <p>Please pay at the event during check-in.</p>
                ';
            }

            Mailer::send(
                $email,
                'Registration received - ' . $event['title'],
                $playerEmailBody,
                $fullName
            );

            $adminEmailBody = '
                <p>A new event registration was created.</p>

                <p>
                    <strong>Event:</strong> ' . htmlspecialchars($event['title']) . '<br>
                    <strong>Venue:</strong> ' . htmlspecialchars($event['venue_name']) . '<br>
                    <strong>Player:</strong> ' . htmlspecialchars($fullName) . '<br>
                    <strong>Email:</strong> ' . htmlspecialchars($email) . '<br>
                    <strong>Phone:</strong> ' . htmlspecialchars($phone !== '' ? $phone : '-') . '<br>
                    <strong>Payment method:</strong> ' . htmlspecialchars(ucwords($paymentMethod)) . '<br>
                    <strong>Registration reference:</strong> #' . (int) $registrationId . '
                </p>
            ';

            Mailer::sendToAdmin(
                'New registration - ' . $event['title'],
                $adminEmailBody
            );

            $db->commit();
        } catch (Exception $e) {
            $db->rollBack();

            $this->view('public/register_event', [
                'title' => 'Register for Event',
                'heading' => 'Register for Event',
                'event' => $event,
                'spots_left' => $spotsLeft,
                'active_registrations' => $activeRegistrations,
                'errors' => ['Something went wrong while saving your registration. Please try again.'],
                'old' => $old,
            ]);
            return;
        }

        $this->view('public/register_event_success', [
            'title' => 'Registration Submitted',
            'heading' => 'Registration submitted',
            'event' => $event,
            'registration_id' => $registrationId,
            'payment_method' => $paymentMethod,
        ]);
    }
}