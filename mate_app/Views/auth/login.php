<?php
$heading = $heading ?? 'Login';
$errors = $errors ?? [];
$old = $old ?? [];
?>

<section class="card auth-card">
    <h1><?= htmlspecialchars($heading) ?></h1>

    <p>
        Login to manage your events, payments, rating, and tournament activity.
    </p>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <strong>Login failed:</strong>
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" action="index.php?page=login-submit">
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

        <div class="form-group">
            <label for="password">Password</label>
            <input
                type="password"
                id="password"
                name="password"
                required
            >
        </div>

        <button class="btn" type="submit">Login</button>
    </form>
</section>