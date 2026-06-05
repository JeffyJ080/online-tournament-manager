<?php
$heading = $heading ?? 'Register';
$errors = $errors ?? [];
$old = $old ?? [];

function oldValue(array $old, string $key): string
{
    return htmlspecialchars($old[$key] ?? '');
}

function selectedValue(array $old, string $key, string $value): string
{
    return (($old[$key] ?? '') === $value) ? 'selected' : '';
}
?>

<section class="card auth-card">
    <h1><?= htmlspecialchars($heading) ?></h1>

    <p>
        Create a player account to register for events, track your rating,
        view your match history, and manage your tournament payments.
    </p>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <strong>Hold up, check this:</strong>
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" action="index.php?page=register-submit">
        <div class="form-group">
            <label for="real_name">Full name</label>
            <input
                type="text"
                id="real_name"
                name="real_name"
                value="<?= oldValue($old, 'real_name') ?>"
                required
            >
        </div>

        <div class="form-group">
            <label for="display_name">Display name optional</label>
            <input
                type="text"
                id="display_name"
                name="display_name"
                value="<?= oldValue($old, 'display_name') ?>"
            >
        </div>

        <div class="form-group">
            <label for="phone">Phone number</label>
            <input
                type="text"
                id="phone"
                name="phone"
                value="<?= oldValue($old, 'phone') ?>"
            >
        </div>

        <div class="form-group">
            <label for="email">Email address</label>
            <input
                type="email"
                id="email"
                name="email"
                value="<?= oldValue($old, 'email') ?>"
                required
            >
        </div>

        <div class="form-group">
            <label for="rating_category">Starting rating category</label>
            <select id="rating_category" name="rating_category">
                <option value="beginner" <?= selectedValue($old, 'rating_category', 'beginner') ?>>Beginner - 800</option>
                <option value="casual" <?= selectedValue($old, 'rating_category', 'casual') ?>>Casual - 1000</option>
                <option value="standard" <?= selectedValue($old, 'rating_category', 'standard') ?>>Standard / Club - 1200</option>
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