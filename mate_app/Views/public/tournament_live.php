<?php
$event = $event ?? [];
$tournament = $tournament ?? null;
$standings = $standings ?? [];
$matches = $matches ?? [];

function liveLabel(?string $value): string
{
    return $value ? ucwords(str_replace('_', ' ', $value)) : '-';
}

function livePlayerName(array $row, string $side): string
{
    $display = $row[$side . '_display_name'] ?? null;
    $full = $row[$side . '_full_name'] ?? null;

    return $display ?: ($full ?: '-');
}
?>

<section class="dashboard-header">
    <div>
        <div class="hero-kicker">Live Tournament</div>
        <h1><?= htmlspecialchars($event['title'] ?? 'Tournament') ?></h1>
        <p>Public pairings, standings, and results for players and spectators.</p>
        <div class="hero-actions">
            <a class="btn btn-outline" href="index.php?page=live-display&event=<?= (int) ($event['id'] ?? 0) ?>" target="_blank">
                Second Screen
            </a>
        </div>
    </div>
</section>

<section class="dashboard-grid">
    <div class="card stat-card">
        <strong><?= htmlspecialchars(liveLabel($event['format'] ?? '-')) ?></strong>
        <span>Format</span>
    </div>
    <div class="card stat-card">
        <strong><?= htmlspecialchars(liveLabel($tournament['status'] ?? 'not_created')) ?></strong>
        <span>Tournament Status</span>
    </div>
    <div class="card stat-card">
        <strong><?= count($standings) ?></strong>
        <span>Players</span>
    </div>
</section>

<?php if (!$tournament): ?>
    <section class="card">
        <p>No tournament has been created for this event yet.</p>
    </section>
<?php else: ?>
    <section class="card">
        <div class="section-header">
            <div>
                <h2>Standings</h2>
                <p>Updated as results are submitted.</p>
            </div>
        </div>

        <?php if (empty($standings)): ?>
            <p>No standings available yet.</p>
        <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Rank</th>
                            <th>Player</th>
                            <th>Score</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($standings as $rank => $standing): ?>
                            <tr>
                                <td><?= $rank + 1 ?></td>
                                <td><?= htmlspecialchars($standing['display_name'] ?: $standing['full_name']) ?></td>
                                <td><?= htmlspecialchars((string) ($standing['current_score'] ?? '0.0')) ?></td>
                                <td><?= htmlspecialchars(liveLabel($standing['status'] ?? '-')) ?></td>
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
                <h2>Pairings & Results</h2>
                <p>Boards are grouped by generated round.</p>
            </div>
        </div>

        <?php if (empty($matches)): ?>
            <p>No pairings generated yet.</p>
        <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Round</th>
                            <th>Board</th>
                            <th>White</th>
                            <th>Black</th>
                            <th>Result</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($matches as $match): ?>
                            <tr>
                                <td><?= htmlspecialchars($match['round_name'] ?? 'Round ' . ($match['round_number'] ?? '-')) ?></td>
                                <td><?= (int) ($match['board_number'] ?? 0) ?></td>
                                <td><?= htmlspecialchars(livePlayerName($match, 'white')) ?></td>
                                <td><?= htmlspecialchars($match['black_registration_id'] ? livePlayerName($match, 'black') : 'Bye') ?></td>
                                <td><?= htmlspecialchars(liveLabel($match['result'] ?? 'pending')) ?></td>
                                <td><?= htmlspecialchars(liveLabel($match['status'] ?? '-')) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
<?php endif; ?>
