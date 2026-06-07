<?php
$heading = $heading ?? 'Registration submitted';
$event = $event ?? [];
$registrationId = $registration_id ?? null;
$paymentMethod = $payment_method ?? 'cash';
?>

<section class="card auth-card">
    <div class="hero-kicker">Seat Reserved</div>

    <h1><?= htmlspecialchars($heading) ?></h1>

    <p>
        Your registration for
        <strong><?= htmlspecialchars($event['title'] ?? 'this event') ?></strong>
        has been submitted successfully.
    </p>

    <?php if ($registrationId): ?>
        <p>
            Registration reference:
            <strong>#<?= (int) $registrationId ?></strong>
        </p>
    <?php endif; ?>

    <?php if ($paymentMethod === 'eft'): ?>
        <div class="alert">
            <strong>Payment method: EFT</strong>
            <p>
                Proof of payment upload will be added next. For now, your registration
                is saved as unpaid until admin verifies payment.
            </p>
        </div>
        <div class="hero-actions">
            <a class="btn" href="index.php?page=upload-proof&registration=<?= (int) $registrationId ?>">
                Upload Proof of Payment
            </a>
        </div>
    <?php else: ?>
        <div class="alert">
            <strong>Payment method: Cash</strong>
            <p>
                Please pay at the event during check-in.
            </p>
        </div>
    <?php endif; ?>

    <div class="hero-actions">
        <a class="btn" href="index.php?page=events">View Events</a>
        <a class="btn btn-outline" href="index.php?page=dashboard">Go to Dashboard</a>
    </div>
</section>