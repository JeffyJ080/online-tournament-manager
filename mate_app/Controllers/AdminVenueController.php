<?php

require_once __DIR__ . '/../Core/Controller.php';
require_once __DIR__ . '/../Middleware/RequireAuth.php';
require_once __DIR__ . '/../Models/Venue.php';

class AdminVenueController extends Controller
{
    public function index(): void
    {
        RequireAuth::anyRole(['admin', 'super_admin']);

        $venueModel = new Venue();
        $venues = $venueModel->all();

        $this->view('admin/venues/index', [
            'title' => 'Venues',
            'heading' => 'Manage Venues',
            'venues' => $venues,
        ]);
    }
}