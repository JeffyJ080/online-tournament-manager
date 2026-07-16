<?php
$success = $success ?? false;
$message = $message ?? '';
$invitation = $invitation ?? null;
?>

<section class="dashboard-header">
    <div>
        <div class="hero-kicker">Player Account</div>
        <h1><?= htmlspecialchars($heading ?? 'Walk-in invitation') ?></h1>
        <p><?= htmlspecialchars($message) ?></p>
    </div>
</section>

<section class="card auth-card">
    <?php if ($success): ?>
        <div class="alert alert-success">Result linked successfully.</div>
    <?php else: ?>
        <div class="alert"><?= htmlspecialchars($invitation['event_title'] ?? 'Walk-in result') ?></div>
    <?php endif; ?>

    <div class="hero-actions">
        <?php if (Auth::check()): ?>
            <a class="btn" href="index.php?page=my-profile">Go to Profile</a>
        <?php else: ?>
            <a class="btn" href="index.php?page=login">Login</a>
            <a class="btn btn-outline" href="index.php?page=register">Create Account</a>
        <?php endif; ?>
    </div>
</section>
