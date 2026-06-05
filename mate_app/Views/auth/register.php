<?php
$heading = $heading ?? 'Register';
?>

<section class="card auth-card">
    <h1><?= htmlspecialchars($heading) ?></h1>

    <p>
        Create a player account to register for events, track your rating,
        view your match history, and manage your tournament payments.
    </p>

    <form method="POST" action="index.php?page=register-submit">
        <div class="form-group">
            <label for="real_name">Full name</label>
            <input type="text" id="real_name" name="real_name" required>
        </div>

        <div class="form-group">
            <label for="display_name">Display name optional</label>
            <input type="text" id="display_name" name="display_name">
        </div>

        <div class="form-group">
            <label for="phone">Phone number</label>
            <input type="text" id="phone" name="phone">
        </div>

        <div class="form-group">
            <label for="email">Email address</label>
            <input type="email" id="email" name="email" required>
        </div>

        <div class="form-group">
            <label for="rating_category">Starting rating category</label>
            <select id="rating_category" name="rating_category">
                <option value="beginner">Beginner - 800</option>
                <option value="casual">Casual - 1000</option>
                <option value="standard">Standard / Club - 1200</option>
            </select>
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>
        </div>

        <div class="form-group">
            <label for="password_confirm">Confirm password</label>
            <input type="password" id="password_confirm" name="password_confirm" required>
        </div>

        <button class="btn" type="submit">Create Account</button>
    </form>
</section>