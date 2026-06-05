<?php

require_once __DIR__ . '/../Core/Controller.php';
require_once __DIR__ . '/../Helpers/Auth.php';

class DashboardController extends Controller
{
    public function index(): void
    {
        if (!Auth::check()) {
            header('Location: index.php?page=login');
            exit;
        }

        $role = Auth::role();

        if ($role === 'player') {
            $this->player();
            return;
        }

        if ($role === 'host') {
            $this->host();
            return;
        }

        if ($role === 'venue_manager') {
            $this->venueManager();
            return;
        }

        if (in_array($role, ['admin', 'super_admin'], true)) {
            $this->admin();
            return;
        }

        header('Location: index.php?page=login');
        exit;
    }

    public function player(): void
    {
        $this->view('dashboard/player', [
            'title' => 'Player Dashboard',
            'heading' => 'Player Dashboard',
            'user' => Auth::user(),
        ]);
    }

    public function host(): void
    {
        $this->view('dashboard/host', [
            'title' => 'Host Dashboard',
            'heading' => 'Host Dashboard',
            'user' => Auth::user(),
        ]);
    }

    public function venueManager(): void
    {
        $this->view('dashboard/venue_manager', [
            'title' => 'Venue Dashboard',
            'heading' => 'Venue Manager Dashboard',
            'user' => Auth::user(),
        ]);
    }

    public function admin(): void
    {
        $this->view('dashboard/admin', [
            'title' => 'Admin Dashboard',
            'heading' => 'Admin Dashboard',
            'user' => Auth::user(),
        ]);
    }
}