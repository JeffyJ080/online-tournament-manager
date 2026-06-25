<?php
$heading = $heading ?? 'Reset your password';
$errors = $errors ?? [];
$old = $old ?? [];
$sent = $sent ?? false;
?>

<section class="card auth-card">
    <h1><?= htmlspecialchars($heading) ?></h1>

    <p>Enter your account email and we will send you a password reset link.</p>

    <?php if ($sent): ?>
        <div class="alert alert-success">
            If that email exists, a reset link has been sent.
        </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <strong>Password reset could not be requested:</strong>
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" action="index.php?page=forgot-password-submit">
        <div class="form-group">
            <label for="email">Email address</label>
            <input
                type="email"
                id="email"
                name="email"
                value="<?= htmlspecialchars($old['email'] ?? '') ?>"
                required
            >
        </div>

        <button class="btn" type="submit">Send Reset Link</button>
    </form>
</section>
