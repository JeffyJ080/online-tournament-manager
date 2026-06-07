<?php
$heading = $heading ?? 'Upload Proof of Payment';
$registration = $registration ?? [];
$errors = $errors ?? [];
?>

<section class="event-detail-hero">
    <div class="card auth-card no-margin">
        <div class="hero-kicker">EFT Payment</div>
        <h1><?= htmlspecialchars($heading) ?></h1>

        <p>
            Upload your proof of payment for
            <strong><?= htmlspecialchars($registration['event_title'] ?? 'this event') ?></strong>.
        </p>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <strong>Upload issue:</strong>
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" action="index.php?page=upload-proof-submit" enctype="multipart/form-data">
            <input type="hidden" name="registration_id" value="<?= (int) ($registration['id'] ?? 0) ?>">

            <div class="form-group">
                <label for="proof">Proof file</label>
                <input
                    type="file"
                    id="proof"
                    name="proof"
                    accept=".jpg,.jpeg,.png,.webp,.pdf"
                    required
                >
                <span class="muted">Accepted: JPG, PNG, WebP, PDF. Max 5MB.</span>
            </div>

            <div class="hero-actions">
                <button class="btn" type="submit">Upload Proof</button>
                <a class="btn btn-outline" href="index.php?page=events">Back to Events</a>
            </div>
        </form>
    </div>

    <aside class="card event-summary-card">
        <h2>Payment Summary</h2>

        <div class="summary-list">
            <div>
                <span>Event</span>
                <strong><?= htmlspecialchars($registration['event_title'] ?? '-') ?></strong>
            </div>

            <div>
                <span>Venue</span>
                <strong><?= htmlspecialchars($registration['venue_name'] ?? '-') ?></strong>
            </div>

            <div>
                <span>Amount</span>
                <strong>R<?= number_format((float) ($registration['amount'] ?? 0), 0) ?></strong>
            </div>

            <div>
                <span>Payment Status</span>
                <strong><?= htmlspecialchars($registration['payment_record_status'] ?? '-') ?></strong>
            </div>
        </div>
    </aside>
</section>