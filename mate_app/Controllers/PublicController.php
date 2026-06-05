<?php

require_once __DIR__ . '/../Core/Controller.php';
require_once __DIR__ . '/../Models/Event.php';
require_once __DIR__ . '/../Models/EventRegistration.php';
require_once __DIR__ . '/../Models/Player.php';
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
}