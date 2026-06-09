<?php
$event = $event ?? [];
$tournament = $tournament ?? null;
$checkedInPlayers = $checkedInPlayers ?? [];

function tmLabel(?string $value): string
{
    return $value ? ucwords(str_replace('_', ' ', $value)) : '-';
}
?>

<section class="dashboard-header">
    <div>
        <div class="hero-kicker">Tournament Manager</div>

        <h1><?= htmlspecialchars($event['title'] ?? 'Tournament') ?></h1>

        <p>
            Tournament control panel for event-night operations.
        </p>
    </div>
</section>

<section class="dashboard-grid">
    <div class="card stat-card">
        <strong><?= count($checkedInPlayers) ?></strong>
        <span>Checked-in Players</span>
    </div>

    <div class="card stat-card">
        <strong><?= htmlspecialchars(tmLabel($event['format'] ?? '-')) ?></strong>
        <span>Format</span>
    </div>

    <div class="card stat-card">
        <strong>
            <?= htmlspecialchars(tmLabel($tournament['status'] ?? 'not_created')) ?>
        </strong>
        <span>Tournament Status</span>
    </div>
</section>

<?php if (!$tournament): ?>
    <section class="card">
        <h2>Create Tournament</h2>

        <p>
            Create the tournament instance for this event.
        </p>

        <form method="POST" action="index.php?page=tournament-create">
            <input type="hidden" name="event_id" value="<?= (int) ($event['id'] ?? 0) ?>">

            <button class="btn" type="submit">
                Create Tournament
            </button>
        </form>
    </section>
<?php endif; ?>

<section class="card">
    <div class="section-header">
        <div>
            <h2>Checked-in Players</h2>
            <p>Only checked-in players are eligible for tournament import.</p>
        </div>
    </div>

    <?php if (empty($checkedInPlayers)): ?>
        <p>No checked-in players yet.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Player</th>
                        <th>Rating Category</th>
                        <th>Status</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($checkedInPlayers as $player): ?>
                        <tr>
                            <td>
                                <strong>
                                    <?= htmlspecialchars($player['display_name'] ?: $player['full_name']) ?>
                                </strong>

                                <br>

                                <span class="muted">
                                    <?= htmlspecialchars($player['email']) ?>
                                </span>
                            </td>

                            <td>
                                <?= htmlspecialchars(tmLabel($player['rating_category'])) ?>
                            </td>

                            <td>
                                <span class="status-pill status-approved">
                                    Checked In
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>