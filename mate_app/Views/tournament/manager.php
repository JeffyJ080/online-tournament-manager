<?php
$event = $event ?? [];
$tournament = $tournament ?? null;
$checkedInPlayers = $checkedInPlayers ?? [];
$participants = $participants ?? [];
$lateEntries = $lateEntries ?? [];
$rounds = $rounds ?? [];
$matches = $matches ?? [];
$standings = $standings ?? [];
$latestRound = $latestRound ?? null;
$eventClosed = in_array(($event['event_status'] ?? ''), ['completed', 'cancelled'], true);
$tournamentCompleted = ($tournament['status'] ?? '') === 'completed';
$readOnly = $eventClosed || $tournamentCompleted;
$canAdminCorrect = $readOnly && Auth::hasAnyRole(['admin', 'super_admin']);
$isKnockout = ($tournament['format'] ?? '') === 'knockout';

function tmLabel(?string $value): string
{
    return $value ? ucwords(str_replace('_', ' ', $value)) : '-';
}

function tmPlayerName(array $row, string $side): string
{
    $display = $row[$side . '_display_name'] ?? null;
    $full = $row[$side . '_full_name'] ?? null;

    return $display ?: ($full ?: '-');
}

$latestRoundPendingMatches = 0;
$latestRoundCompletedMatches = 0;

if ($latestRound) {
    foreach ($matches as $match) {
        if ((int) ($match['round_id'] ?? 0) !== (int) ($latestRound['id'] ?? 0)) {
            continue;
        }

        if (($match['status'] ?? '') === 'completed') {
            $latestRoundCompletedMatches++;
        } else {
            $latestRoundPendingMatches++;
        }
    }
}

$latestRoundComplete = $latestRound ? $latestRoundPendingMatches === 0 : false;
$visibleMatches = $latestRound
    ? array_values(array_filter(
        $matches,
        fn ($match) => (int) ($match['round_id'] ?? 0) === (int) ($latestRound['id'] ?? 0)
    ))
    : $matches;

$canGenerateNextRound = $tournament
    && !$readOnly
    && !empty($participants);

if ($latestRound && !$latestRoundComplete) {
    $canGenerateNextRound = false;
}
?>

<section class="dashboard-header">
    <div>
        <div class="hero-kicker">Tournament Manager</div>

        <h1><?= htmlspecialchars($event['title'] ?? 'Tournament') ?></h1>

        <p>
            <?= $readOnly ? 'Tournament record and results review.' : 'Tournament control panel for event-night operations.' ?>
        </p>

        <div class="hero-actions">
            <a class="btn btn-outline" href="index.php?page=live-display&event=<?= (int) ($event['id'] ?? 0) ?>" target="_blank">
                Open Second Screen
            </a>
            <a class="btn btn-outline" href="index.php?page=timer-control&event=<?= (int) ($event['id'] ?? 0) ?>">
                Timer Remote
            </a>
            <a class="btn btn-outline" href="index.php?page=live-tournament&event=<?= (int) ($event['id'] ?? 0) ?>" target="_blank">
                Open Live View
            </a>
            <a class="btn btn-outline" href="index.php?page=export-tournament-results&id=<?= (int) ($event['id'] ?? 0) ?>">
                Export Results
            </a>
            <a class="btn btn-outline" href="index.php?page=export-pairings&id=<?= (int) ($event['id'] ?? 0) ?>">
                Export Pairings
            </a>
            <button class="btn btn-outline" type="button" onclick="window.print()">Print Pairings</button>
        </div>
    </div>
</section>

<?php if ($readOnly): ?>
    <section class="card">
        <h2>Review Mode</h2>
        <p>This event or tournament is completed, so pairings, participants, and match results are locked.</p>
        <?php if ($canAdminCorrect): ?>
            <p>Admin correction mode is available below for fixing recorded results. Corrections are audit logged.</p>
        <?php endif; ?>
    </section>

    <br/>
<?php endif; ?>

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

<?php if (!$tournament && !$eventClosed): ?>
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
                <button class="btn" type="submit" <?= (!empty($matches) || $readOnly) ? 'disabled' : '' ?>>
                    Import Checked-in Players
                </button>
            </form>
        </div>

        <?php if (!empty($matches)): ?>
            <p>Initial participant import is locked because pairings have already been generated.</p>
        <?php endif; ?>

        <?php if (
            !$readOnly
            && !empty($matches)
            && in_array(($tournament['format'] ?? ''), ['swiss', 'weekly_points'], true)
        ): ?>
            <div class="alert">
                <div class="section-header">
                    <div>
                        <strong>Late Entries</strong>
                        <p>
                            <?= count($lateEntries) ?> checked-in player<?= count($lateEntries) === 1 ? '' : 's' ?> not yet in the tournament.
                            Late entries join from the next generated round on 0 points.
                        </p>
                    </div>

                    <form method="POST" action="index.php?page=tournament-add-late-entries">
                        <input type="hidden" name="event_id" value="<?= (int) ($event['id'] ?? 0) ?>">
                        <button class="btn" type="submit" <?= empty($lateEntries) ? 'disabled' : '' ?>>
                            Add Late Entries
                        </button>
                    </form>
                </div>

                <?php if (!empty($lateEntries)): ?>
                    <div class="compact-list">
                        <?php foreach ($lateEntries as $lateEntry): ?>
                            <span class="status-pill status-scheduled">
                                <?= htmlspecialchars($lateEntry['display_name'] ?: $lateEntry['full_name']) ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php elseif (
            !$readOnly
            && !empty($matches)
            && !in_array(($tournament['format'] ?? ''), ['swiss', 'weekly_points'], true)
        ): ?>
            <div class="alert">
                Late entries are blocked once pairings exist for this format.
            </div>
        <?php endif; ?>

        <?php if (empty($participants)): ?>
            <p>No participants imported yet.</p>
        <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Seed</th>
                            <th>Player</th>
                            <th>Rating at Entry</th>
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

<?php if ($tournament): ?>
    <section class="card">
        <div class="section-header">
            <div>
                <h2>Pairings & Results</h2>
                <?php if ($latestRound && !$readOnly): ?>
                    <p>
                        <?= htmlspecialchars($latestRound['name'] ?? 'Current round') ?>:
                        <?= (int) $latestRoundCompletedMatches ?> completed,
                        <?= (int) $latestRoundPendingMatches ?> pending.
                    </p>
                <?php else: ?>
                    <p>Generate rounds, enter match results, and track the event live.</p>
                <?php endif; ?>
            </div>

            <?php if (!$readOnly && empty($matches) && in_array(($tournament['format'] ?? ''), ['swiss', 'weekly_points'], true)): ?>
                <form method="POST" action="index.php?page=tournament-generate-round-one">
                    <input type="hidden" name="event_id" value="<?= (int) ($event['id'] ?? 0) ?>">
                    <button class="btn" type="submit" <?= count($participants) < 2 ? 'disabled' : '' ?>>
                        Generate Round 1
                    </button>
                </form>
            <?php elseif (!$readOnly && $canGenerateNextRound): ?>
                <form method="POST" action="index.php?page=tournament-generate-next-round">
                    <input type="hidden" name="event_id" value="<?= (int) ($event['id'] ?? 0) ?>">
                    <button class="btn" type="submit">
                        <?= empty($matches) ? 'Generate First Round' : 'Generate Next Round' ?>
                    </button>
                </form>
            <?php endif; ?>
        </div>

        <?php if (!$readOnly && $latestRound && !$latestRoundComplete): ?>
            <div class="alert">
                Finish all results in <?= htmlspecialchars($latestRound['name'] ?? 'the current round') ?> before generating the next round or completing the tournament.
            </div>
        <?php endif; ?>

        <?php if (count($participants) < 2): ?>
            <p>Import at least two checked-in players before generating Round 1.</p>
        <?php elseif (empty($visibleMatches)): ?>
            <p>No pairings generated yet.</p>
        <?php else: ?>
            <form method="POST" action="index.php?page=<?= $canAdminCorrect ? 'admin-correct-round-results' : 'tournament-submit-round-results' ?>" class="compact-form round-results-form">
                <input type="hidden" name="event_id" value="<?= (int) ($event['id'] ?? 0) ?>">

                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Board</th>
                                <th>White</th>
                                <th>Black</th>
                                <th>Current</th>
                                <th>Status</th>
                                <th>Result</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($visibleMatches as $match): ?>
                                <tr>
                                    <td><?= (int) ($match['board_number'] ?? 0) ?></td>
                                    <td><?= htmlspecialchars(tmPlayerName($match, 'white')) ?></td>
                                    <td><?= htmlspecialchars($match['black_registration_id'] ? tmPlayerName($match, 'black') : 'Bye') ?></td>
                                    <td><?= htmlspecialchars(tmLabel($match['result'] ?? 'pending')) ?></td>
                                    <td>
                                        <span class="status-pill status-<?= htmlspecialchars($match['status']) ?>">
                                            <?= htmlspecialchars(tmLabel($match['status'])) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ((!$readOnly || $canAdminCorrect) && !empty($match['black_registration_id'])): ?>
                                            <select name="results[<?= (int) ($match['id'] ?? 0) ?>]">
                                                <option value="">No change</option>
                                                <option value="white_win" <?= ($match['result'] ?? '') === 'white_win' ? 'selected' : '' ?>>White win</option>
                                                <option value="black_win" <?= ($match['result'] ?? '') === 'black_win' ? 'selected' : '' ?>>Black win</option>
                                                <?php if (!$isKnockout): ?>
                                                    <option value="draw" <?= ($match['result'] ?? '') === 'draw' ? 'selected' : '' ?>>Draw</option>
                                                    <option value="forfeit" <?= ($match['result'] ?? '') === 'forfeit' ? 'selected' : '' ?>>Forfeit</option>
                                                <?php endif; ?>
                                            </select>
                                        <?php else: ?>
                                            <span class="muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php if (!$readOnly || $canAdminCorrect): ?>
                    <div class="hero-actions">
                        <button class="btn" type="submit">
                            <?= $canAdminCorrect ? 'Save Admin Corrections' : 'Save Visible Results' ?>
                        </button>
                    </div>
                <?php endif; ?>
            </form>
        <?php endif; ?>

        <?php if (!$readOnly && !empty($matches) && (!$latestRound || $latestRoundComplete)): ?>
            <form method="POST" action="index.php?page=tournament-complete" class="compact-form">
                <input type="hidden" name="event_id" value="<?= (int) ($event['id'] ?? 0) ?>">
                <button class="btn btn-outline" type="submit">Complete Tournament</button>
            </form>
        <?php endif; ?>

        <?php if (count($matches) > count($visibleMatches)): ?>
            <details class="compact-details">
                <summary>Round history</summary>

                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Round</th>
                                <th>Board</th>
                                <th>White</th>
                                <th>Black</th>
                                <th>Result</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($matches as $match): ?>
                                <?php if ($latestRound && (int) ($match['round_id'] ?? 0) === (int) ($latestRound['id'] ?? 0)) continue; ?>
                                <tr>
                                    <td><?= htmlspecialchars($match['round_name'] ?? 'Round ' . ($match['round_number'] ?? '-')) ?></td>
                                    <td><?= (int) ($match['board_number'] ?? 0) ?></td>
                                    <td><?= htmlspecialchars(tmPlayerName($match, 'white')) ?></td>
                                    <td><?= htmlspecialchars($match['black_registration_id'] ? tmPlayerName($match, 'black') : 'Bye') ?></td>
                                    <td><?= htmlspecialchars(tmLabel($match['result'] ?? 'pending')) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </details>
        <?php endif; ?>
    </section>

    <br/>
<?php endif; ?>

<?php if ($tournament): ?>
    <section class="card">
        <div class="section-header">
            <div>
                <h2>Standings</h2>
                <p>Rankings are ordered by score, then seed.</p>
            </div>
        </div>

        <?php if (empty($standings)): ?>
            <p>No standings yet.</p>
        <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Rank</th>
                            <th>Player</th>
                            <th>Score</th>
                            <th>Buchholz</th>
                            <th>SB</th>
                            <th>H2H</th>
                            <th>Seed</th>
                            <th>Status</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($standings as $rank => $standing): ?>
                            <tr>
                                <td><?= $rank + 1 ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($standing['display_name'] ?: $standing['full_name']) ?></strong>
                                    <br>
                                    <span class="muted"><?= htmlspecialchars($standing['email']) ?></span>
                                </td>
                                <td><?= htmlspecialchars((string) ($standing['current_score'] ?? '0.0')) ?></td>
                                <td><?= htmlspecialchars((string) ($standing['buchholz'] ?? '0')) ?></td>
                                <td><?= htmlspecialchars((string) ($standing['sonneborn_berger'] ?? '0')) ?></td>
                                <td><?= htmlspecialchars((string) ($standing['head_to_head'] ?? '0')) ?></td>
                                <td><?= (int) ($standing['seed_number'] ?? 0) ?></td>
                                <td>
                                    <span class="status-pill status-<?= htmlspecialchars($standing['status']) ?>">
                                        <?= htmlspecialchars(tmLabel($standing['status'])) ?>
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

<br/>

<section class="card">
    <div class="section-header">
        <div>
            <h2>Rounds</h2>
            <p>Generated rounds, status, and timing.</p>
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
