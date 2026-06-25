<?php
$seasons = $seasons ?? [];
$venues = $venues ?? [];
$seriesList = $seriesList ?? [];
$errors = $errors ?? [];
$old = $old ?? [];

function oldLeaderboardValue(array $old, string $key): string
{
    return htmlspecialchars((string) ($old[$key] ?? ''));
}

function selectedLeaderboardValue(array $old, string $key, string $value): string
{
    return ((string) ($old[$key] ?? '') === (string) $value) ? 'selected' : '';
}
?>

<section class="dashboard-header">
    <div>
        <div class="hero-kicker">Admin</div>
        <h1><?= htmlspecialchars($heading ?? 'Leaderboard Seasons') ?></h1>
        <p>Create reusable venue or series leaderboards with custom placement points.</p>
    </div>
</section>

<section class="card auth-card">
    <h2>Create season</h2>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <strong>Leaderboard season could not be created:</strong>
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" action="index.php?page=admin-leaderboards-store">
        <div class="form-group">
            <label for="venue_id">Venue</label>
            <select id="venue_id" name="venue_id" required>
                <option value="">Select venue</option>
                <?php foreach ($venues as $venue): ?>
                    <option value="<?= (int) $venue['id'] ?>" <?= selectedLeaderboardValue($old, 'venue_id', (string) $venue['id']) ?>>
                        <?= htmlspecialchars($venue['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="series_id">Event series optional</label>
            <select id="series_id" name="series_id">
                <option value="">All events at selected venue</option>
                <?php foreach ($seriesList as $series): ?>
                    <option value="<?= (int) $series['id'] ?>" <?= selectedLeaderboardValue($old, 'series_id', (string) $series['id']) ?>>
                        <?= htmlspecialchars($series['title']) ?> - <?= htmlspecialchars($series['venue_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="name">Season name</label>
            <input type="text" id="name" name="name" value="<?= oldLeaderboardValue($old, 'name') ?>" placeholder="Sinkhuis 2026 Season" required>
        </div>

        <div class="form-group">
            <label for="starts_on">Starts on</label>
            <input type="date" id="starts_on" name="starts_on" value="<?= oldLeaderboardValue($old, 'starts_on') ?>" required>
        </div>

        <div class="form-group">
            <label for="ends_on">Ends on</label>
            <input type="date" id="ends_on" name="ends_on" value="<?= oldLeaderboardValue($old, 'ends_on') ?>" required>
        </div>

        <div class="form-group">
            <label for="status">Status</label>
            <select id="status" name="status">
                <?php foreach (['active', 'inactive', 'archived'] as $status): ?>
                    <option value="<?= htmlspecialchars($status) ?>" <?= selectedLeaderboardValue($old, 'status', $status) ?>>
                        <?= htmlspecialchars(ucwords($status)) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="rules_text">Placement points</label>
            <textarea id="rules_text" name="rules_text" rows="7" required><?= oldLeaderboardValue($old, 'rules_text') ?></textarea>
        </div>

        <div class="form-group">
            <label for="notes">Notes</label>
            <textarea id="notes" name="notes" rows="3"><?= oldLeaderboardValue($old, 'notes') ?></textarea>
        </div>

        <div class="hero-actions">
            <button class="btn" type="submit">Create Season</button>
            <a class="btn btn-outline" href="index.php?page=leaderboard">View Public Leaderboard</a>
        </div>
    </form>
</section>

<section class="card">
    <h2>Existing seasons</h2>

    <?php if (empty($seasons)): ?>
        <p>No leaderboard seasons have been created yet.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Season</th>
                        <th>Venue</th>
                        <th>Series</th>
                        <th>Dates</th>
                        <th>Status</th>
                        <th>Public</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($seasons as $season): ?>
                        <tr>
                            <td><?= htmlspecialchars($season['name']) ?></td>
                            <td><?= htmlspecialchars($season['venue_name']) ?></td>
                            <td><?= htmlspecialchars($season['series_title'] ?: 'All venue events') ?></td>
                            <td>
                                <?= htmlspecialchars(date('d M Y', strtotime($season['starts_on']))) ?>
                                to
                                <?= htmlspecialchars(date('d M Y', strtotime($season['ends_on']))) ?>
                            </td>
                            <td><?= htmlspecialchars(ucwords($season['status'])) ?></td>
                            <td>
                                <a href="index.php?page=leaderboard&season=<?= urlencode($season['slug']) ?>">View</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
