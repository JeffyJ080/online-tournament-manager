<?php
$seasons = $seasons ?? [];
$selectedSeason = $selectedSeason ?? null;
$leaders = $leaders ?? [];
$recentResults = $recentResults ?? [];
?>

<section class="dashboard-header">
    <div>
        <div class="hero-kicker">Season Rankings</div>
        <h1>Leaderboard</h1>
        <?php if ($selectedSeason): ?>
            <p>
                <?= htmlspecialchars($selectedSeason['name']) ?>
                at <?= htmlspecialchars($selectedSeason['venue_name']) ?>.
                Points are awarded from final tournament placement.
            </p>
        <?php else: ?>
            <p>Season leaderboards will appear here once a season has been created.</p>
        <?php endif; ?>
    </div>
</section>

<?php if (!empty($seasons)): ?>
    <section class="card">
        <form method="GET" action="index.php">
            <input type="hidden" name="page" value="leaderboard">

            <div class="form-group">
                <label for="season">Season</label>
                <select id="season" name="season" onchange="this.form.submit()">
                    <?php foreach ($seasons as $season): ?>
                        <option value="<?= htmlspecialchars($season['slug']) ?>" <?= $selectedSeason && (int) $selectedSeason['id'] === (int) $season['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($season['name']) ?> - <?= htmlspecialchars($season['venue_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>
        <?php if ($selectedSeason): ?>
            <a class="btn btn-outline" href="index.php?page=export-leaderboard&season=<?= htmlspecialchars($selectedSeason['slug']) ?>">
                Export CSV
            </a>
        <?php endif; ?>
    </section>
<?php endif; ?>

<section class="card">
    <?php if (!$selectedSeason): ?>
        <p>No leaderboard seasons have been created yet.</p>
    <?php elseif (empty($leaders)): ?>
        <p>No season results have been recorded yet. Complete a tournament in this season, or ask an admin to recalculate the season from completed tournaments.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Rank</th>
                        <th>Player</th>
                        <th>Season Points</th>
                        <th>Events</th>
                        <th>Best Finish</th>
                        <th>Avg Points</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($leaders as $rank => $leader): ?>
                        <tr>
                            <td><?= $rank + 1 ?></td>
                            <td><?= htmlspecialchars($leader['player_name']) ?></td>
                            <td><?= (int) ($leader['total_points'] ?? 0) ?></td>
                            <td><?= (int) ($leader['events_played'] ?? 0) ?></td>
                            <td><?= (int) ($leader['best_finish'] ?? 0) ?></td>
                            <td><?= htmlspecialchars((string) ($leader['average_points'] ?? '0')) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<?php if (!empty($recentResults)): ?>
    <section class="card">
        <h2>Recent event points</h2>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Event</th>
                        <th>Player</th>
                        <th>Place</th>
                        <th>Score</th>
                        <th>Points</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentResults as $result): ?>
                        <tr>
                            <td><?= htmlspecialchars(date('d M Y', strtotime($result['event_date']))) ?></td>
                            <td><?= htmlspecialchars($result['event_title']) ?></td>
                            <td><?= htmlspecialchars($result['player_name']) ?></td>
                            <td><?= (int) $result['placement'] ?></td>
                            <td><?= htmlspecialchars((string) $result['tournament_score']) ?></td>
                            <td><?= (int) $result['points'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
<?php endif; ?>
