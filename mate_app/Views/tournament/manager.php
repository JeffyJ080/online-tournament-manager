<?php
$event = $event ?? [];
$tournament = $tournament ?? null;
$checkedInPlayers = $checkedInPlayers ?? [];
$participants = $participants ?? [];
$rounds = $rounds ?? [];

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

    <div class="card stat-card">
        <strong><?= (int) ($tournament['total_rounds'] ?? 5) ?></strong>
        <span>Total Rounds</span>
    </div>

    <div class="card stat-card">
        <strong><?= (int) ($tournament['current_round'] ?? 0) ?></strong>
        <span>Current Round</span>
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

<?php if ($tournament): ?>
    <section class="card">
        <div class="section-header">
            <div>
                <h2>Tournament Participants</h2>
                <p>
                    Import checked-in players into the tournament player pool.
                </p>
            </div>

            <form method="POST" action="index.php?page=tournament-import-participants">
                <input type="hidden" name="event_id" value="<?= (int) ($event['id'] ?? 0) ?>">
                <button class="btn" type="submit">Import Checked-in Players</button>
            </form>
        </div>

        <?php if (empty($participants)): ?>
            <p>No participants imported yet.</p>
        <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Seed</th>
                            <th>Player</th>
                            <th>Rating</th>
                            <th>Score</th>
                            <th>Status</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($participants as $participant): ?>
                            <tr>
                                <td><?= (int) ($participant['seed_number'] ?? 0) ?></td>

                                <td>
                                    <strong>
                                        <?= htmlspecialchars($participant['display_name'] ?: $participant['full_name']) ?>
                                    </strong>
                                    <br>
                                    <span class="muted"><?= htmlspecialchars($participant['email']) ?></span>
                                </td>

                                <td><?= (int) ($participant['starting_rating'] ?? 0) ?></td>

                                <td><?= htmlspecialchars((string) ($participant['current_score'] ?? '0.0')) ?></td>

                                <td>
                                    <span class="status-pill status-<?= htmlspecialchars($participant['status']) ?>">
                                        <?= htmlspecialchars(tmLabel($participant['status'])) ?>
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

<br/>

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

<br/>

<section class="card">
    <div class="section-header">
        <div>
            <h2>Rounds</h2>
            <p>Swiss rounds will appear here once generated.</p>
        </div>
    </div>

    <?php if (empty($rounds)): ?>
        <p>No rounds generated yet.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Round</th>
                        <th>Status</th>
                        <th>Started</th>
                        <th>Completed</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($rounds as $round): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($round['name'] ?? 'Round ' . $round['round_number']) ?></strong>
                            </td>

                            <td>
                                <span class="status-pill status-<?= htmlspecialchars($round['status']) ?>">
                                    <?= htmlspecialchars(tmLabel($round['status'])) ?>
                                </span>
                            </td>

                            <td><?= htmlspecialchars($round['started_at'] ?? '-') ?></td>

                            <td><?= htmlspecialchars($round['completed_at'] ?? '-') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>