<?php
$players = $players ?? [];
$query = $query ?? '';
$errors = $errors ?? [];
$success = $success ?? null;
$unlinkedRegistrationCount = (int) ($unlinkedRegistrationCount ?? 0);

function adminPlayerValue($value): string
{
    return htmlspecialchars((string) ($value ?? ''));
}

function adminPlayerName(array $player): string
{
    $name = $player['display_name'] ?: $player['real_name'];

    return $name . ' (#' . (int) $player['id'] . ')';
}
?>

<section class="dashboard-header">
    <div>
        <div class="hero-kicker">Admin</div>
        <h1><?= htmlspecialchars($heading ?? 'Player Identity') ?></h1>
        <p>Link player accounts and merge duplicate imported or walk-in player records.</p>
    </div>
</section>

<?php if ($success): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <strong>Player identity action failed:</strong>
        <ul>
            <?php foreach ($errors as $error): ?>
                <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<section class="card">
    <h2>Find Players</h2>

    <form method="GET" action="index.php">
        <input type="hidden" name="page" value="admin-players">
        <div class="form-row">
            <div class="form-group">
                <label for="q">Name or email</label>
                <input type="search" id="q" name="q" value="<?= adminPlayerValue($query) ?>" placeholder="Peet, James, admin@example.com">
            </div>
            <div class="form-group form-actions-end">
                <button class="btn" type="submit">Search</button>
            </div>
        </div>
    </form>
</section>

<?php if ($unlinkedRegistrationCount > 0): ?>
    <section class="card">
        <h2>Missing Player Records</h2>
        <p><?= $unlinkedRegistrationCount ?> registrations are not attached to a player record yet.</p>

        <form method="POST" action="index.php?page=admin-players-materialize">
            <button class="btn" type="submit">Create Player Records</button>
        </form>
    </section>
<?php endif; ?>

<section class="card">
    <h2>Link Player To Account</h2>
    <p>Use this when a historical/imported player already has a player account.</p>

    <form method="POST" action="index.php?page=admin-players-link">
        <div class="form-row">
            <div class="form-group">
                <label for="player_id">Player ID</label>
                <input type="number" min="1" id="player_id" name="player_id" required>
            </div>
            <div class="form-group">
                <label for="account_email">Account email</label>
                <input type="email" id="account_email" name="account_email" required>
            </div>
        </div>
        <button class="btn" type="submit">Link Account</button>
    </form>
</section>

<section class="card">
    <h2>Merge Duplicate Players</h2>
    <p>Source is deleted after its registrations, payments, and rating rows are moved to target. Use the correct account-linked player as target.</p>

    <form method="POST" action="index.php?page=admin-players-merge">
        <div class="form-row">
            <div class="form-group">
                <label for="source_player_id">Source player ID</label>
                <input type="number" min="1" id="source_player_id" name="source_player_id" required>
            </div>
            <div class="form-group">
                <label for="target_player_id">Target player ID</label>
                <input type="number" min="1" id="target_player_id" name="target_player_id" required>
            </div>
        </div>
        <button class="btn btn-outline" type="submit">Merge Into Target</button>
    </form>
</section>

<section class="card">
    <h2>Player Records</h2>

    <?php if (empty($players)): ?>
        <p>No player records found.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Player</th>
                        <th>Email</th>
                        <th>Account</th>
                        <th>Rating</th>
                        <th>Registrations</th>
                        <th>Last Event</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($players as $player): ?>
                        <tr>
                            <td><?= (int) $player['id'] ?></td>
                            <td>
                                <strong><?= htmlspecialchars(adminPlayerName($player)) ?></strong><br>
                                <span><?= htmlspecialchars($player['real_name']) ?></span>
                            </td>
                            <td><?= adminPlayerValue($player['email']) ?></td>
                            <td>
                                <?php if (!empty($player['account_email'])): ?>
                                    <?= htmlspecialchars($player['account_email']) ?><br>
                                    <span><?= htmlspecialchars(ucwords((string) $player['account_status'])) ?></span>
                                <?php else: ?>
                                    <span>Unlinked</span>
                                <?php endif; ?>
                            </td>
                            <td><?= (int) ($player['current_rating'] ?? 800) ?></td>
                            <td><?= (int) ($player['registration_count'] ?? 0) ?></td>
                            <td>
                                <?= !empty($player['last_event_date'])
                                    ? htmlspecialchars(date('d M Y', strtotime($player['last_event_date'])))
                                    : '-' ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
