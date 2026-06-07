<?php

require_once __DIR__ . '/../Core/Controller.php';
require_once __DIR__ . '/../Middleware/RequireAuth.php';
require_once __DIR__ . '/../Helpers/Auth.php';
require_once __DIR__ . '/../Models/EventRegistration.php';

class PlayerRegistrationController extends Controller
{
    public function index(): void
    {
        RequireAuth::role('player');

        $registrationModel = new EventRegistration();
        $registrations = $registrationModel->forUser(Auth::id());

        $this->view('player/registrations/index', [
            'title' => 'My Registrations',
            'heading' => 'My Registrations',
            'registrations' => $registrations,
        ]);
    }
}