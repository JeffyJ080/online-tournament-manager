<?php
$heading = $heading ?? 'Login';
?>

<section class="card auth-card">
    <h1><?= htmlspecialchars($heading) ?></h1>

    <p>
        Login to manage your events, payments, rating, and tournament activity.
    </p>

    <form method="POST" action="index.php?page=login-submit">
        <div class="form-group">
            <label for="email">Email address</label>
            <input type="email" id="email" name="email" required>
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>
        </div>

        <button class="btn" type="submit">Login</button>
    </form>
</section>