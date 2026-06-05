<?php
$heading = $heading ?? 'Edit Registration';
$registration = $registration ?? [];
$errors = $errors ?? [];
$old = $old ?? [];

function oldRegEditValue(array $old, string $key): string
{
    return htmlspecialchars($old[$key] ?? '');
}

function selectedRegEditValue(array $old, string $key, string $value): string
{
    return (($old[$key] ?? '') === $value) ? 'selected' : '';
}

function regEditLabel(string $value): string
{
    return ucwords(str_replace('_', ' ', $value));
}
?>

<section class="dashboard-header">
    <div>
        <div class="hero-kicker">Admin</div>
        <h1><?= htmlspecialchars($heading) ?></h1>
        <p>
            Update registration and payment status for this player.
        </p>
    </div>
</section>

<section class="event-detail-hero">
    <div class="card auth-card no-margin">
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <strong>Registration could not be updated:</strong>
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" action="index.php?page=admin-registrations-update">
            <input type="hidden" name="id" value="<?= oldRegEditValue($old, 'id') ?>">

            <div class="form-group">
                <label>Player</label>
                <input type="text" value="<?= oldRegEditValue($old, 'full_name') ?>" disabled>
            </div>

            <div class="form-group">
                <label>Email</label>
                <input type="text" value="<?= oldRegEditValue($old, 'email') ?>" disabled>
            </div>

            <div class="form-group">
                <label for="registration_status">Registration status</label>
                <select id="registration_status" name="registration_status">
                    <?php foreach (['pending', 'confirmed', 'cancelled', 'waitlisted', 'checked_in', 'no_show'] as $status): ?>
                        <option value="<?= htmlspecialchars($status) ?>" <?= selectedRegEditValue($old, 'registration_status', $status) ?>>
                            <?= htmlspecialchars(regEditLabel($status)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="payment_status">Payment status</label>
                <select id="payment_status" name="payment_status">
                    <?php foreach (['unpaid', 'paid_cash', 'paid_eft', 'proof_uploaded', 'verified', 'refunded', 'comped'] as $status): ?>
                        <option value="<?= htmlspecialchars($status) ?>" <?= selectedRegEditValue($old, 'payment_status', $status) ?>>
                            <?= htmlspecialchars(regEditLabel($status)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="payment_method">Payment method</label>
                <select id="payment_method" name="payment_method">
                    <?php foreach (['cash', 'eft', 'comped', 'other'] as $method): ?>
                        <option value="<?= htmlspecialchars($method) ?>" <?= selectedRegEditValue($old, 'payment_method', $method) ?>>
                            <?= htmlspecialchars(regEditLabel($method)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="notes">Internal notes</label>
                <textarea id="notes" name="notes" rows="4"><?= oldRegEditValue($old, 'notes') ?></textarea>
            </div>

            <div class="hero-actions">
                <button class="btn" type="submit">Update Registration</button>
                <a class="btn btn-outline" href="index.php?page=admin-registrations">Cancel</a>
            </div>
        </form>
    </div>

    <aside class="card event-summary-card">
        <h2>Event</h2>

        <div class="summary-list">
            <div>
                <span>Event</span>
                <strong><?= htmlspecialchars($registration['event_title'] ?? '-') ?></strong>
            </div>

            <div>
                <span>Venue</span>
                <strong><?= htmlspecialchars($registration['venue_name'] ?? '-') ?></strong>
            </div>

            <div>
                <span>Current registration</span>
                <strong><?= htmlspecialchars(regEditLabel($registration['registration_status'] ?? 'pending')) ?></strong>
            </div>

            <div>
                <span>Current payment</span>
                <strong><?= htmlspecialchars(regEditLabel($registration['payment_status'] ?? 'unpaid')) ?></strong>
            </div>
        </div>
    </aside>
</section>