<?php
$heading = $heading ?? 'Proof uploaded successfully';
$registration = $registration ?? [];
?>

<section class="card auth-card">
    <div class="hero-kicker">Upload Complete</div>

    <h1><?= htmlspecialchars($heading) ?></h1>

    <p>
        Your proof of payment for
        <strong><?= htmlspecialchars($registration['event_title'] ?? 'this event') ?></strong>
        has been uploaded.
    </p>

    <p>
        Admin will review the proof and update your payment status.
    </p>

    <div class="hero-actions">
        <a class="btn" href="index.php?page=events">View Events</a>
        <a class="btn btn-outline" href="index.php?page=dashboard">Go to Dashboard</a>
    </div>
</section>