<?php

require_once __DIR__ . '/../Core/Controller.php';
require_once __DIR__ . '/../Middleware/RequireAuth.php';
require_once __DIR__ . '/../Models/EventRegistration.php';

class AdminRegistrationController extends Controller
{
    public function index(): void
    {
        RequireAuth::anyRole(['admin', 'super_admin']);

        $registrationModel = new EventRegistration();
        $registrations = $registrationModel->allWithEventDetails();

        $this->view('admin/registrations/index', [
            'title' => 'Registrations',
            'heading' => 'Event Registrations',
            'registrations' => $registrations,
        ]);
    }
}