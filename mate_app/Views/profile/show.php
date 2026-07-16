<?php
$user = $user ?? [];
$player = $player ?? null;
$registrations = $registrations ?? [];
$unlinkedRegistrations = $unlinkedRegistrations ?? [];
$matches = $matches ?? [];
$profileErrors = $profileErrors ?? [];
$success = $success ?? null;

function profileValue(?array $player, string $key): string
{
    return htmlspecialchars((string) ($player[$key] ?? ''));
}

function profileSelected(?array $player, string $value): string
{
    return (($player['rating_category'] ?? '') === $value) ? 'selected' : '';
}

function visibilitySelected(?array $player, string $value): string
{
    return (($player['profile_visibility'] ?? 'private') === $value) ? 'selected' : '';
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
                <strong><?= htmlspecialchars(($user['role_name'] ?? '') === 'venue_manager' ? 'Venue Partner' : ucwords(str_replace('_', ' ', $user['role_name'] ?? 'user'))) ?></strong>
            </div>
            <div>
                <span>Status</span>
                <strong><?= htmlspecialchars(ucwords($user['status'] ?? 'active')) ?></strong>
            </div>
            <div>
                <span>Email Verified</span>
                <strong><?= !empty($user['email_verified_at']) ? 'Yes' : 'Pending' ?></strong>
            </div>
        </div>

        <?php if (empty($user['email_verified_at'])): ?>
            <div class="alert">
                Email verification is still pending. Some account-link and history features work best once this email is confirmed.
            </div>
        <?php endif; ?>
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

                <div class="form-group">
                    <label for="profile_visibility">Public profile</label>
                    <select id="profile_visibility" name="profile_visibility">
                        <option value="private" <?= visibilitySelected($player, 'private') ?>>Private</option>
                        <option value="public" <?= visibilitySelected($player, 'public') ?>>Public</option>
                    </select>
                    <p class="field-help">Public profiles show your rating, recent activity, and tournament records. Private profiles stay hidden from public player pages.</p>
                </div>

                <div class="hero-actions">
                    <button class="btn" type="submit">Save Profile</button>
                    <?php if (($player['profile_visibility'] ?? 'private') === 'public'): ?>
                        <a class="btn btn-outline" href="index.php?page=player&slug=<?= urlencode($player['public_slug'] ?? '') ?>">View Public Profile</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    <?php endif; ?>

    <div class="card">
        <h2>Password</h2>

        <p>For security, password changes are completed through an email reset link.</p>

        <form method="POST" action="index.php?page=my-profile-password">
            <div class="hero-actions">
                <button class="btn" type="submit">Email Password Reset Link</button>
            </div>
        </form>
    </div>
</section>

<?php if ($player): ?>
    <?php if (!empty($unlinkedRegistrations)): ?>
        <section class="card">
            <div class="section-header">
                <div>
                    <h2>Claim Previous Walk-ins</h2>
                    <p>These unlinked registrations use your account email.</p>
                </div>
                <form method="POST" action="index.php?page=my-profile-claim-history">
                    <button class="btn" type="submit">Claim Matching History</button>
                </form>
            </div>
        </section>

        <br>
    <?php else: ?>
        <section class="card profile-register-card">
            <div class="section-header">
                <div>
                    <h2>Previous Walk-ins</h2>
                    <p>If a walk-in was registered with this email, it will appear here for claiming. Hosts can also send an invite or link it manually.</p>
                </div>
            </div>
        </section>

        <br>
    <?php endif; ?>

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

    <br>

    <section class="card">
        <div class="section-header">
            <div>
                <h2>Recent Matches</h2>
                <p>Your latest completed match results.</p>
            </div>
            <a class="btn btn-outline btn-sm" href="index.php?page=my-match-history">View All</a>
        </div>

        <?php if (empty($matches)): ?>
            <p>No completed matches linked to this account yet.</p>
        <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Event</th>
                            <th>White</th>
                            <th>Black</th>
                            <th>Result</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($matches as $match): ?>
                            <tr>
                                <td><?= htmlspecialchars($match['event_title']) ?></td>
                                <td><?= htmlspecialchars($match['white_display_name'] ?: $match['white_name']) ?></td>
                                <td><?= htmlspecialchars($match['black_display_name'] ?: ($match['black_name'] ?? 'Bye')) ?></td>
                                <td><?= htmlspecialchars(ucwords(str_replace('_', ' ', $match['result']))) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
<?php endif; ?>
