<?php
$heading = $heading ?? 'Review Payment Proof';
$proof = $proof ?? [];
$errors = $errors ?? [];
$old = $old ?? [];

function proofReviewLabel(?string $value): string
{
    return $value ? ucwords(str_replace('_', ' ', $value)) : '-';
}

function proofReviewMoney($amount): string
{
    return 'R' . number_format((float) $amount, 0);
}

function proofReviewSelected(array $old, string $key, string $value): string
{
    return (($old[$key] ?? '') === $value) ? 'selected' : '';
}
?>

<section class="dashboard-header">
    <div>
        <div class="hero-kicker">Admin</div>
        <h1><?= htmlspecialchars($heading) ?></h1>
        <p>
            Approve or reject an uploaded EFT proof of payment.
        </p>
    </div>
</section>

<section class="event-detail-hero">
    <div class="card auth-card no-margin">
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <strong>Review failed:</strong>
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" action="index.php?page=admin-payment-proofs-update">
            <input type="hidden" name="proof_id" value="<?= (int) ($proof['id'] ?? 0) ?>">

            <div class="form-group">
                <label>Player</label>
                <input type="text" value="<?= htmlspecialchars($proof['full_name'] ?? '-') ?>" disabled>
            </div>

            <div class="form-group">
                <label>Event</label>
                <input type="text" value="<?= htmlspecialchars($proof['event_title'] ?? '-') ?>" disabled>
            </div>

            <div class="form-group">
                <label>Uploaded file</label>
                <a class="btn btn-outline" href="<?= htmlspecialchars($proof['file_path'] ?? '#') ?>" target="_blank">
                    Open Proof File
                </a>
            </div>

            <div class="form-group">
                <label for="decision">Decision</label>
                <select id="decision" name="decision" required>
                    <option value="">Select decision</option>
                    <option value="approved" <?= proofReviewSelected($old, 'decision', 'approved') ?>>Approve</option>
                    <option value="rejected" <?= proofReviewSelected($old, 'decision', 'rejected') ?>>Reject</option>
                </select>
            </div>

            <div class="form-group">
                <label for="review_notes">Review notes optional</label>
                <textarea id="review_notes" name="review_notes" rows="4"><?= htmlspecialchars($old['review_notes'] ?? '') ?></textarea>
            </div>

            <div class="hero-actions">
                <button class="btn" type="submit">Save Review</button>
                <a class="btn btn-outline" href="index.php?page=admin-payment-proofs">Cancel</a>
            </div>
        </form>
    </div>

    <aside class="card event-summary-card">
        <h2>Proof Summary</h2>

        <div class="summary-list">
            <div>
                <span>Amount</span>
                <strong><?= htmlspecialchars(proofReviewMoney($proof['amount'] ?? 0)) ?></strong>
            </div>

            <div>
                <span>Payment Method</span>
                <strong><?= htmlspecialchars(proofReviewLabel($proof['payment_method'] ?? '-')) ?></strong>
            </div>

            <div>
                <span>Payment Status</span>
                <strong><?= htmlspecialchars(proofReviewLabel($proof['payment_status'] ?? '-')) ?></strong>
            </div>

            <div>
                <span>Proof Status</span>
                <strong><?= htmlspecialchars(proofReviewLabel($proof['status'] ?? '-')) ?></strong>
            </div>

            <div>
                <span>Original Filename</span>
                <strong><?= htmlspecialchars($proof['original_filename'] ?? '-') ?></strong>
            </div>
        </div>
    </aside>
</section>