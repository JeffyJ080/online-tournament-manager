<?php

require_once __DIR__ . '/../Core/Controller.php';
require_once __DIR__ . '/../Middleware/RequireAuth.php';
require_once __DIR__ . '/../Models/Event.php';

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
}