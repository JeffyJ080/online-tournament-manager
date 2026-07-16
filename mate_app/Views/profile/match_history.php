<?php
$matches = $matches ?? [];
$stats = $stats ?? [];
$player = $player ?? null;
$ratingHistory = $ratingHistory ?? [];

function mhName(array $match, string $side): string
{
    return $match[$side . '_display_name'] ?: ($match[$side . '_name'] ?? 'Bye');
}
?>

<section class="dashboard-header">
    <div>
        <div class="hero-kicker">Player Records</div>
        <h1><?= htmlspecialchars($heading ?? 'Match History') ?></h1>
        <p>Completed matches linked to your player account.</p>
    </div>
</section>

<br>

<section class="card">
    <div class="section-header">
        <div>
            <h2>Rating History</h2>
            <p>Mate Elo changes from completed rated matches.</p>
        </div>
    </div>

    <?php if (empty($ratingHistory)): ?>
        <p>No rating adjustments recorded yet.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Event</th>
                        <th>Opponent</th>
                        <th>Old</th>
                        <th>New</th>
                        <th>Change</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ratingHistory as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars(date('d M Y', strtotime($row['event_date']))) ?></td>
                            <td><?= htmlspecialchars($row['event_title']) ?></td>
                            <td><?= htmlspecialchars($row['opponent_display_name'] ?: ($row['opponent_real_name'] ?? 'Walk-in')) ?></td>
                            <td><?= (int) $row['old_rating'] ?></td>
                            <td><?= (int) $row['new_rating'] ?></td>
                            <td><?= (int) $row['change_amount'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<section class="dashboard-grid">
    <div class="card stat-card">
        <strong><?= (int) ($player['current_rating'] ?? 800) ?></strong>
        <span>Current Mate Elo</span>
    </div>
    <div class="card stat-card">
        <strong><?= (int) ($stats['matches_played'] ?? 0) ?></strong>
        <span>Matches Played</span>
    </div>
    <div class="card stat-card">
        <strong><?= (int) ($stats['wins'] ?? 0) ?>-<?= (int) ($stats['draws'] ?? 0) ?>-<?= (int) ($stats['losses'] ?? 0) ?></strong>
        <span>Win/Draw/Loss</span>
    </div>
</section>

<section class="card">
    <?php if (empty($matches)): ?>
        <p>No completed matches linked to your account yet.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Event</th>
                        <th>Board</th>
                        <th>White</th>
                        <th>Black</th>
                        <th>Result</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($matches as $match): ?>
                        <tr>
                            <td><?= htmlspecialchars(date('d M Y', strtotime($match['event_date']))) ?></td>
                            <td><?= htmlspecialchars($match['event_title']) ?></td>
                            <td><?= (int) ($match['board_number'] ?? 0) ?></td>
                            <td><?= htmlspecialchars(mhName($match, 'white')) ?></td>
                            <td><?= htmlspecialchars(mhName($match, 'black')) ?></td>
                            <td><?= htmlspecialchars(ucwords(str_replace('_', ' ', $match['result']))) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
