<?php
$user = $user ?? [];
$player = $player ?? null;
$registrations = $registrations ?? [];
$profileErrors = $profileErrors ?? [];
$passwordErrors = $passwordErrors ?? [];
$success = $success ?? null;

function profileValue(?array $player, string $key): string
{
    return htmlspecialchars((string) ($player[$key] ?? ''));
}

function profileSelected(?array $player, string $value): string
{
    return (($player['rating_category'] ?? '') === $value) ? 'selected' : '';
}
?>

<section class="dashboard-header">
    <div>
        <div class="hero-kicker">Account</div>
        <h1><?= htmlspecialchars($heading ?? 'My Profile') ?></h1>
        <p>Manage the details used for registrations, event lists, and player-facing records.</p>
    </div>
</section>

<?php if ($success): ?>
    <div class="alert alert-success">
        <?= htmlspecialchars($success) ?>
    </div>
<?php endif; ?>

<section class="profile-layout">
    <div class="card">
        <h2>Account</h2>

        <div class="profile-summary">
            <div>
                <span>Email</span>
                <strong><?= htmlspecialchars($user['email'] ?? '-') ?></strong>
            </div>
            <div>
                <span>Role</span>
                <strong><?= htmlspecialchars(ucwords(str_replace('_', ' ', $user['role_name'] ?? 'user'))) ?></strong>
            </div>
            <div>
                <span>Status</span>
                <strong><?= htmlspecialchars(ucwords($user['status'] ?? 'active')) ?></strong>
            </div>
        </div>
    </div>

    <?php if ($player): ?>
        <div class="card">
            <h2>Player Details</h2>

            <?php if (!empty($profileErrors)): ?>
                <div class="alert alert-danger">
                    <strong>Profile could not be updated:</strong>
                    <ul>
                        <?php foreach ($profileErrors as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" action="index.php?page=my-profile-update">
                <div class="form-group">
                    <label for="real_name">Full name</label>
                    <input type="text" id="real_name" name="real_name" value="<?= profileValue($player, 'real_name') ?>" required>
                </div>

                <div class="form-group">
                    <label for="display_name">Display name optional</label>
                    <input type="text" id="display_name" name="display_name" value="<?= profileValue($player, 'display_name') ?>">
                </div>

                <div class="form-group">
                    <label for="phone">Phone number</label>
                    <input type="text" id="phone" name="phone" value="<?= profileValue($player, 'phone') ?>">
                </div>

                <div class="form-group">
                    <label for="rating_category">Playing level</label>
                    <select id="rating_category" name="rating_category">
                        <option value="beginner" <?= profileSelected($player, 'beginner') ?>>Beginner</option>
                        <option value="casual" <?= profileSelected($player, 'casual') ?>>Casual</option>
                        <option value="standard" <?= profileSelected($player, 'standard') ?>>Standard</option>
                    </select>
                </div>

                <div class="hero-actions">
                    <button class="btn" type="submit">Save Profile</button>
                </div>
            </form>
        </div>
    <?php endif; ?>

    <div class="card">
        <h2>Password</h2>

        <?php if (!empty($passwordErrors)): ?>
            <div class="alert alert-danger">
                <strong>Password could not be changed:</strong>
                <ul>
                    <?php foreach ($passwordErrors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" action="index.php?page=my-profile-password">
            <div class="form-group">
                <label for="current_password">Current password</label>
                <input type="password" id="current_password" name="current_password" required>
            </div>

            <div class="form-group">
                <label for="new_password">New password</label>
                <input type="password" id="new_password" name="new_password" minlength="8" required>
            </div>

            <div class="form-group">
                <label for="new_password_confirm">Confirm new password</label>
                <input type="password" id="new_password_confirm" name="new_password_confirm" minlength="8" required>
            </div>

            <div class="hero-actions">
                <button class="btn" type="submit">Change Password</button>
            </div>
        </form>
    </div>
</section>

<?php if ($player): ?>
    <section class="card">
        <div class="section-header">
            <div>
                <h2>Recent Registrations</h2>
                <p>Your latest event activity linked to this account.</p>
            </div>
            <a class="btn btn-outline btn-sm" href="index.php?page=my-registrations">View All</a>
        </div>

        <?php if (empty($registrations)): ?>
            <p>No registrations yet.</p>
        <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Event</th>
                            <th>Date</th>
                            <th>Venue</th>
                            <th>Status</th>
                            <th>Payment</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($registrations as $registration): ?>
                            <tr>
                                <td><?= htmlspecialchars($registration['event_title']) ?></td>
                                <td><?= htmlspecialchars(date('d M Y', strtotime($registration['event_date']))) ?></td>
                                <td><?= htmlspecialchars($registration['venue_name']) ?></td>
                                <td>
                                    <span class="status-pill status-<?= htmlspecialchars($registration['registration_status']) ?>">
                                        <?= htmlspecialchars(str_replace('_', ' ', $registration['registration_status'])) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status-pill status-<?= htmlspecialchars($registration['payment_status']) ?>">
                                        <?= htmlspecialchars(str_replace('_', ' ', $registration['payment_status'])) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
<?php endif; ?>
