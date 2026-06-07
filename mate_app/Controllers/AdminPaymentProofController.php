<?php

require_once __DIR__ . '/../Core/Controller.php';
require_once __DIR__ . '/../Core/Database.php';
require_once __DIR__ . '/../Middleware/RequireAuth.php';
require_once __DIR__ . '/../Helpers/Auth.php';
require_once __DIR__ . '/../Models/PaymentProof.php';
require_once __DIR__ . '/../Models/Payment.php';
require_once __DIR__ . '/../Models/EventRegistration.php';
require_once __DIR__ . '/../Models/AuditLog.php';

class AdminPaymentProofController extends Controller
{
    public function index(): void
    {
        RequireAuth::anyRole(['admin', 'super_admin']);

        $proofModel = new PaymentProof();
        $proofs = $proofModel->allWithDetails();

        $this->view('admin/payment_proofs/index', [
            'title' => 'Payment Proofs',
            'heading' => 'Payment Proofs',
            'proofs' => $proofs,
        ]);
    }

    public function review(): void
    {
        RequireAuth::anyRole(['admin', 'super_admin']);

        $id = (int) ($_GET['id'] ?? 0);

        if ($id <= 0) {
            header('Location: index.php?page=admin-payment-proofs');
            exit;
        }

        $proofModel = new PaymentProof();
        $proof = $proofModel->findByIdWithDetails($id);

        if (!$proof) {
            http_response_code(404);
            echo '<h1>404 - Payment proof not found</h1>';
            return;
        }

        $this->view('admin/payment_proofs/review', [
            'title' => 'Review Payment Proof',
            'heading' => 'Review Payment Proof',
            'proof' => $proof,
            'errors' => [],
            'old' => [],
        ]);
    }

    public function update(): void
    {
        RequireAuth::anyRole(['admin', 'super_admin']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=admin-payment-proofs');
            exit;
        }

        $proofId = (int) ($_POST['proof_id'] ?? 0);
        $decision = trim($_POST['decision'] ?? '');
        $reviewNotes = trim($_POST['review_notes'] ?? '');

        $errors = [];

        if ($proofId <= 0) {
            $errors[] = 'Invalid proof selected.';
        }

        if (!in_array($decision, ['approved', 'rejected'], true)) {
            $errors[] = 'Invalid review decision.';
        }

        $proofModel = new PaymentProof();
        $proof = $proofModel->findByIdWithDetails($proofId);

        if (!$proof) {
            http_response_code(404);
            echo '<h1>404 - Payment proof not found</h1>';
            return;
        }

        if (!empty($errors)) {
            $this->view('admin/payment_proofs/review', [
                'title' => 'Review Payment Proof',
                'heading' => 'Review Payment Proof',
                'proof' => $proof,
                'errors' => $errors,
                'old' => [
                    'decision' => $decision,
                    'review_notes' => $reviewNotes,
                ],
            ]);
            return;
        }

        $db = Database::connect();

        try {
            $db->beginTransaction();

            $paymentModel = new Payment();
            $registrationModel = new EventRegistration();

            if ($decision === 'approved') {
                $proofModel->approve($proofId, Auth::id(), $reviewNotes !== '' ? $reviewNotes : null);

                $paymentModel->updateStatus(
                    (int) $proof['payment_id'],
                    'verified',
                    $proof['payment_method'],
                    Auth::id(),
                    $reviewNotes !== '' ? $reviewNotes : null
                );

                $registrationModel->updatePaymentStatus(
                    (int) $proof['registration_id'],
                    'verified'
                );

                $auditAction = 'payment_proof_approved';
                $auditDescription = 'Payment proof approved for registration #' . $proof['registration_id'];
            } else {
                $proofModel->reject($proofId, Auth::id(), $reviewNotes !== '' ? $reviewNotes : null);

                $paymentModel->updateStatus(
                    (int) $proof['payment_id'],
                    'unpaid',
                    $proof['payment_method'],
                    null,
                    $reviewNotes !== '' ? $reviewNotes : null
                );

                $registrationModel->updatePaymentStatus(
                    (int) $proof['registration_id'],
                    'unpaid'
                );

                $auditAction = 'payment_proof_rejected';
                $auditDescription = 'Payment proof rejected for registration #' . $proof['registration_id'];
            }

            $auditLog = new AuditLog();
            $auditLog->create(
                Auth::id(),
                $auditAction,
                'payment_proof',
                $proofId,
                $auditDescription
            );

            $db->commit();
        } catch (Exception $e) {
            $db->rollBack();

            $this->view('admin/payment_proofs/review', [
                'title' => 'Review Payment Proof',
                'heading' => 'Review Payment Proof',
                'proof' => $proof,
                'errors' => ['Something went wrong while reviewing the proof.'],
                'old' => [
                    'decision' => $decision,
                    'review_notes' => $reviewNotes,
                ],
            ]);
            return;
        }

        header('Location: index.php?page=admin-payment-proofs');
        exit;
    }
}