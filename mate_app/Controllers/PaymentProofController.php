<?php

require_once __DIR__ . '/../Core/Controller.php';
require_once __DIR__ . '/../Core/Database.php';
require_once __DIR__ . '/../Helpers/Auth.php';
require_once __DIR__ . '/../Middleware/RequireAuth.php';
require_once __DIR__ . '/../Models/EventRegistration.php';
require_once __DIR__ . '/../Models/Payment.php';
require_once __DIR__ . '/../Models/PaymentProof.php';
require_once __DIR__ . '/../Models/AuditLog.php';

class PaymentProofController extends Controller
{
    public function create(): void
    {
        RequireAuth::check();

        $registrationId = (int) ($_GET['registration'] ?? 0);

        if ($registrationId <= 0) {
            header('Location: index.php?page=events');
            exit;
        }

        $registrationModel = new EventRegistration();
        $registration = $registrationModel->findByIdWithPaymentDetails($registrationId);

        if (!$registration || empty($registration['payment_id'])) {
            http_response_code(404);

            $this->view('public/404', [
                'title' => 'Registration Not Found',
                'heading' => 'Registration not found',
            ]);
            return;
        }

        if (!$this->canUploadForRegistration($registration)) {
            http_response_code(403);
            echo '<h1>403 - Access denied</h1>';
            return;
        }

        if (($registration['payment_method'] ?? '') !== 'eft') {
            $this->view('payments/upload_proof', [
                'title' => 'Upload Proof',
                'heading' => 'Upload Proof of Payment',
                'registration' => $registration,
                'errors' => ['Proof upload is only needed for EFT payments.'],
            ]);
            return;
        }

        $this->view('payments/upload_proof', [
            'title' => 'Upload Proof',
            'heading' => 'Upload Proof of Payment',
            'registration' => $registration,
            'errors' => [],
        ]);
    }

    public function store(): void
    {
        RequireAuth::check();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=events');
            exit;
        }

        $registrationId = (int) ($_POST['registration_id'] ?? 0);

        $registrationModel = new EventRegistration();
        $registration = $registrationModel->findByIdWithPaymentDetails($registrationId);

        $errors = [];

        if (!$registration || empty($registration['payment_id'])) {
            $errors[] = 'Registration or payment record not found.';
        }

        if ($registration && !$this->canUploadForRegistration($registration)) {
            $errors[] = 'You do not have permission to upload proof for this registration.';
        }

        if ($registration && ($registration['payment_method'] ?? '') !== 'eft') {
            $errors[] = 'Proof upload is only available for EFT payments.';
        }

        if (!isset($_FILES['proof']) || $_FILES['proof']['error'] === UPLOAD_ERR_NO_FILE) {
            $errors[] = 'Please select a proof of payment file.';
        }

        if (!empty($errors)) {
            $this->view('payments/upload_proof', [
                'title' => 'Upload Proof',
                'heading' => 'Upload Proof of Payment',
                'registration' => $registration ?? [],
                'errors' => $errors,
            ]);
            return;
        }

        $file = $_FILES['proof'];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $this->view('payments/upload_proof', [
                'title' => 'Upload Proof',
                'heading' => 'Upload Proof of Payment',
                'registration' => $registration,
                'errors' => ['File upload failed. Please try again.'],
            ]);
            return;
        }

        $maxSize = 5 * 1024 * 1024;

        if ($file['size'] > $maxSize) {
            $this->view('payments/upload_proof', [
                'title' => 'Upload Proof',
                'heading' => 'Upload Proof of Payment',
                'registration' => $registration,
                'errors' => ['File is too large. Maximum size is 5MB.'],
            ]);
            return;
        }

        $allowedTypes = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'application/pdf' => 'pdf',
        ];

        $fileType = mime_content_type($file['tmp_name']);

        if (!array_key_exists($fileType, $allowedTypes)) {
            $this->view('payments/upload_proof', [
                'title' => 'Upload Proof',
                'heading' => 'Upload Proof of Payment',
                'registration' => $registration,
                'errors' => ['Invalid file type. Please upload JPG, PNG, WebP, or PDF.'],
            ]);
            return;
        }

        $extension = $allowedTypes[$fileType];
        $storedFilename = 'proof_' . $registrationId . '_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $extension;

        $uploadDir = __DIR__ . '/../../public_html/uploads/payment_proofs/';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $destination = $uploadDir . $storedFilename;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            $this->view('payments/upload_proof', [
                'title' => 'Upload Proof',
                'heading' => 'Upload Proof of Payment',
                'registration' => $registration,
                'errors' => ['Could not save uploaded file. Please try again.'],
            ]);
            return;
        }

        $relativePath = 'uploads/payment_proofs/' . $storedFilename;

        $db = Database::connect();

        try {
            $db->beginTransaction();

            $proofModel = new PaymentProof();
            $proofId = $proofModel->create([
                'payment_id' => (int) $registration['payment_id'],
                'registration_id' => $registrationId,
                'uploaded_by' => Auth::id(),
                'original_filename' => $file['name'],
                'stored_filename' => $storedFilename,
                'file_path' => $relativePath,
                'file_type' => $fileType,
                'file_size' => (int) $file['size'],
                'status' => 'uploaded',
            ]);

            $paymentModel = new Payment();
            $paymentModel->markProofUploaded((int) $registration['payment_id']);

            $registrationModel->updatePaymentStatus($registrationId, 'proof_uploaded');

            $auditLog = new AuditLog();
            $auditLog->create(
                Auth::id(),
                'payment_proof_uploaded',
                'payment_proof',
                $proofId,
                'Proof of payment uploaded for registration #' . $registrationId
            );

            $db->commit();
        } catch (Exception $e) {
            $db->rollBack();

            if (file_exists($destination)) {
                unlink($destination);
            }

            $this->view('payments/upload_proof', [
                'title' => 'Upload Proof',
                'heading' => 'Upload Proof of Payment',
                'registration' => $registration,
                'errors' => ['Something went wrong while saving proof of payment.'],
            ]);
            return;
        }

        $this->view('payments/upload_success', [
            'title' => 'Proof Uploaded',
            'heading' => 'Proof uploaded successfully',
            'registration' => $registration,
        ]);
    }

    private function canUploadForRegistration(array $registration): bool
    {
        if (Auth::hasAnyRole(['admin', 'super_admin'])) {
            return true;
        }

        return (int) ($registration['user_id'] ?? 0) === (int) Auth::id();
    }
}
