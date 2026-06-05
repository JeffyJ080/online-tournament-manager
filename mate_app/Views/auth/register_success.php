<?php
$heading = $heading ?? 'Account created successfully';
$email = $email ?? '';
?>

<section class="card auth-card">
    <h1><?= htmlspecialchars($heading) ?></h1>

    <p>
        Your Mate Tournaments account has been created.
    </p>

    <?php if ($email !== ''): ?>
        <p>
            Account email: <strong><?= htmlspecialchars($email) ?></strong>
        </p>
    <?php endif; ?>

    <div class="hero-actions">
        <a class="btn" href="index.php?page=login">Go to Login</a>
        <a class="btn btn-outline" href="index.php?page=events">View Events</a>
    </div>
</section>