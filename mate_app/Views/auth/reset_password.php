<?php
$heading = $heading ?? 'Reset your password';
$token = $token ?? '';
$errors = $errors ?? [];
$success = $success ?? false;
?>

<section class="card auth-card">
    <h1><?= htmlspecialchars($heading) ?></h1>

    <?php if ($success): ?>
        <div class="alert alert-success">
            Password updated. You can now log in with your new password.
        </div>

        <div class="hero-actions">
            <a class="btn" href="index.php?page=login">Go to Login</a>
        </div>
    <?php else: ?>
        <p>Choose a new password for your Mate Tournaments account.</p>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <strong>Password could not be reset:</strong>
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if ($token !== ''): ?>
            <form method="POST" action="index.php?page=reset-password-submit">
                <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

                <div class="form-group">
                    <label for="password">New password</label>
                    <input type="password" id="password" name="password" minlength="8" required>
                </div>

                <div class="form-group">
                    <label for="password_confirm">Confirm new password</label>
                    <input type="password" id="password_confirm" name="password_confirm" minlength="8" required>
                </div>

                <button class="btn" type="submit">Reset Password</button>
            </form>
        <?php else: ?>
            <div class="hero-actions">
                <a class="btn" href="index.php?page=forgot-password">Request New Link</a>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</section>
