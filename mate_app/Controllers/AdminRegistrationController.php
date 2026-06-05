<?php

require_once __DIR__ . '/../Core/Controller.php';
require_once __DIR__ . '/../Middleware/RequireAuth.php';
require_once __DIR__ . '/../Models/EventRegistration.php';
require_once __DIR__ . '/../Models/AuditLog.php';
require_once __DIR__ . '/../Helpers/Auth.php';

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

    public function edit(): void
    {
        RequireAuth::anyRole(['admin', 'super_admin']);

        $id = (int) ($_GET['id'] ?? 0);

        if ($id <= 0) {
            header('Location: index.php?page=admin-registrations');
            exit;
        }

        $registrationModel = new EventRegistration();
        $registration = $registrationModel->findByIdWithEventDetails($id);

        if (!$registration) {
            http_response_code(404);
            echo '<h1>404 - Registration not found</h1>';
            return;
        }

        $this->view('admin/registrations/edit', [
            'title' => 'Edit Registration',
            'heading' => 'Edit Registration',
            'registration' => $registration,
            'errors' => [],
            'old' => $registration,
        ]);
    }

    public function update(): void
    {
        RequireAuth::anyRole(['admin', 'super_admin']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=admin-registrations');
            exit;
        }

        $id = (int) ($_POST['id'] ?? 0);
        $registrationStatus = trim($_POST['registration_status'] ?? '');
        $paymentStatus = trim($_POST['payment_status'] ?? '');
        $paymentMethod = trim($_POST['payment_method'] ?? '');
        $notes = trim($_POST['notes'] ?? '');

        $validRegistrationStatuses = [
            'pending',
            'confirmed',
            'cancelled',
            'waitlisted',
            'checked_in',
            'no_show',
        ];

        $validPaymentStatuses = [
            'unpaid',
            'paid_cash',
            'paid_eft',
            'proof_uploaded',
            'verified',
            'refunded',
            'comped',
        ];

        $validPaymentMethods = [
            'cash',
            'eft',
            'comped',
            'other',
        ];

        $errors = [];

        if ($id <= 0) {
            $errors[] = 'Invalid registration selected.';
        }

        if (!in_array($registrationStatus, $validRegistrationStatuses, true)) {
            $errors[] = 'Invalid registration status.';
        }

        if (!in_array($paymentStatus, $validPaymentStatuses, true)) {
            $errors[] = 'Invalid payment status.';
        }

        if (!in_array($paymentMethod, $validPaymentMethods, true)) {
            $errors[] = 'Invalid payment method.';
        }

        $registrationModel = new EventRegistration();
        $registration = $registrationModel->findByIdWithEventDetails($id);

        if (!$registration) {
            http_response_code(404);
            echo '<h1>404 - Registration not found</h1>';
            return;
        }

        $old = [
            ...$registration,
            'registration_status' => $registrationStatus,
            'payment_status' => $paymentStatus,
            'payment_method' => $paymentMethod,
            'notes' => $notes,
        ];

        if (!empty($errors)) {
            $this->view('admin/registrations/edit', [
                'title' => 'Edit Registration',
                'heading' => 'Edit Registration',
                'registration' => $registration,
                'errors' => $errors,
                'old' => $old,
            ]);
            return;
        }

        $registrationModel->updateStatuses(
            $id,
            $registrationStatus,
            $paymentStatus,
            $paymentMethod,
            $notes !== '' ? $notes : null
        );

        $auditLog = new AuditLog();
        $auditLog->create(
            Auth::id(),
            'registration_status_updated',
            'event_registration',
            $id,
            'Registration/payment status updated for ' . $registration['full_name']
        );

        header('Location: index.php?page=admin-registrations');
        exit;
    }
}