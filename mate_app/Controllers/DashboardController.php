<?php

require_once __DIR__ . '/../Core/Controller.php';
require_once __DIR__ . '/../Helpers/Auth.php';
require_once __DIR__ . '/../Middleware/RequireAuth.php';
require_once __DIR__ . '/../Models/Player.php';
require_once __DIR__ . '/../Models/EventRegistration.php';
require_once __DIR__ . '/../Models/Event.php';
require_once __DIR__ . '/../Models/PaymentProof.php';
require_once __DIR__ . '/../Models/Venue.php';
require_once __DIR__ . '/../Models/EventHost.php';

class DashboardController extends Controller
{
    public function index(): void
    {
        RequireAuth::check();

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
        $playerModel = new Player();
        $registrationModel = new EventRegistration();

        $player = $playerModel->findByUserId(Auth::id());
        $upcomingRegistrations = $registrationModel->countUpcomingForUser(Auth::id());

        $this->view('dashboard/player', [
            'title' => 'Player Dashboard',
            'heading' => 'Player Dashboard',
            'user' => Auth::user(),
            'player' => $player,
            'upcomingRegistrations' => $upcomingRegistrations,
            'matchesPlayed' => $registrationModel->countMatchesForUser(Auth::id()),
        ]);
    }

    public function host(): void
    {
        $hostModel = new EventHost();

        $this->view('dashboard/host', [
            'title' => 'Host Dashboard',
            'heading' => 'Host Dashboard',
            'user' => Auth::user(),
            'assignedEvents' => $hostModel->countAssignedEvents(Auth::id()),
            'checkedInPlayers' => $hostModel->countCheckedInPlayers(Auth::id()),
            'cashToHandOver' => $hostModel->cashToHandOver(Auth::id()),
        ]);
    }

    public function venueManager(): void
    {
        $venueModel = new Venue();

        $this->view('dashboard/venue_manager', [
            'title' => 'Venue Dashboard',
            'heading' => 'Venue Manager Dashboard',
            'user' => Auth::user(),
            'upcomingEvents' => $venueModel->countUpcomingForManager(Auth::id()),
            'totalEventsHosted' => $venueModel->countHostedForManager(Auth::id()),
            'averageAttendance' => $venueModel->averageAttendanceForManager(Auth::id()),
        ]);
    }

    public function admin(): void
    {
        $eventModel = new Event();
        $registrationModel = new EventRegistration();
        $proofModel = new PaymentProof();
        $venueModel = new Venue();

        $this->view('dashboard/admin', [
            'title' => 'Admin Dashboard',
            'heading' => 'Admin Dashboard',
            'user' => Auth::user(),

            'upcomingEvents' => $eventModel->countUpcoming(),
            'openRegistrations' => $registrationModel->countOpenRegistrations(),
            'pendingProofs' => $proofModel->countPendingReview(),
            'activeVenues' => $venueModel->countActive(),
        ]);
    }
}
