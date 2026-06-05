<?php

require_once __DIR__ . '/../Core/Controller.php';
require_once __DIR__ . '/../Models/Event.php';

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
}