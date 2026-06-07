<?php

require_once __DIR__ . '/../Core/Controller.php';
require_once __DIR__ . '/../Middleware/RequireAuth.php';
require_once __DIR__ . '/../Helpers/Auth.php';
require_once __DIR__ . '/../Models/EventHost.php';

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
}