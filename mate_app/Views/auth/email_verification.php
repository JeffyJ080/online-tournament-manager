<?php
$success = $success ?? false;
$message = $message ?? '';
?>

<section class="dashboard-header">
    <div>
        <div class="hero-kicker">Account</div>
        <h1><?= htmlspecialchars($heading ?? 'Email verification') ?></h1>
        <p><?= htmlspecialchars($message) ?></p>
    </div>
</section>

<section class="card auth-card">
    <?php if ($success): ?>
        <div class="alert alert-success">Email verified successfully.</div>
    <?php else: ?>
        <div class="alert alert-danger">Email verification could not be completed.</div>
    <?php endif; ?>

    <div class="hero-actions">
        <a class="btn" href="index.php?page=login">Go to Login</a>
    </div>
</section>
