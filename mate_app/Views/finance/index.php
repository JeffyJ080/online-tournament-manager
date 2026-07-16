<?php
$heading = $heading ?? 'Finance Stats';
$filters = $filters ?? ['year' => (int) date('Y'), 'month' => null];
$summary = $summary ?? [];
$monthlyBreakdown = $monthlyBreakdown ?? [];
$eventBreakdown = $eventBreakdown ?? [];
$emailSent = $emailSent ?? false;
$emailFailed = $emailFailed ?? false;

$monthNames = [
    1 => 'January',
    2 => 'February',
    3 => 'March',
    4 => 'April',
    5 => 'May',
    6 => 'June',
    7 => 'July',
    8 => 'August',
    9 => 'September',
    10 => 'October',
    11 => 'November',
    12 => 'December',
];

$money = static fn ($value): string => 'R' . number_format((float) $value, 2);
$query = 'year=' . (int) $filters['year'] . ($filters['month'] ? '&month=' . (int) $filters['month'] : '');
?>

<section class="dashboard-header">
    <div>
        <div class="hero-kicker">Private Finance</div>
        <h1><?= htmlspecialchars($heading) ?></h1>
        <p>Venue-scoped revenue, outstanding totals, counted players, and the R5 per-player fund for your permitted event scope.</p>
    </div>
</section>

<?php if ($emailSent): ?>
    <div class="notice success">Finance summary emailed to your account.</div>
<?php endif; ?>

<?php if ($emailFailed): ?>
    <div class="notice error">Finance summary could not be emailed. Check mail settings or your account email.</div>
<?php endif; ?>

<section class="card finance-toolbar finance-section">
    <form method="get" action="index.php" class="inline-form finance-filter">
        <input type="hidden" name="page" value="finance-stats">

        <label>
            Year
            <input type="number" name="year" min="2020" max="2100" value="<?= (int) $filters['year'] ?>">
        </label>

        <label>
            Month
            <select name="month">
                <option value="">Full year</option>
                <?php foreach ($monthNames as $number => $name): ?>
                    <option value="<?= (int) $number ?>" <?= (int) ($filters['month'] ?? 0) === $number ? 'selected' : '' ?>>
                        <?= htmlspecialchars($name) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>

        <button class="btn" type="submit">Apply</button>
    </form>

    <div class="table-actions finance-actions">
        <a class="btn btn-sm" href="index.php?page=export-finance-stats&<?= htmlspecialchars($query) ?>">Export CSV</a>
        <a class="btn btn-sm secondary" href="index.php?page=finance-stats-email&<?= htmlspecialchars($query) ?>">Email to me</a>
        <button class="btn btn-sm secondary" type="button" onclick="window.print()">Print / PDF</button>
    </div>
</section>

<section class="dashboard-grid finance-stat-grid finance-section">
    <div class="card stat-card">
        <strong><?= $money($summary['revenue_received'] ?? 0) ?></strong>
        <span>Revenue Received</span>
    </div>

    <div class="card stat-card">
        <strong><?= $money($summary['outstanding_amount'] ?? 0) ?></strong>
        <span>Outstanding</span>
    </div>

    <div class="card stat-card">
        <strong><?= (int) ($summary['active_players'] ?? 0) ?></strong>
        <span>Players Counted</span>
    </div>

    <div class="card stat-card">
        <strong><?= $money($summary['player_fund_generated'] ?? 0) ?></strong>
        <span>R5 Fund This Period</span>
    </div>

    <div class="card stat-card">
        <strong><?= $money($summary['year_end_fund_generated'] ?? 0) ?></strong>
        <span>Year-end Fund Generated</span>
    </div>

    <div class="card stat-card">
        <strong><?= (int) ($summary['total_events'] ?? 0) ?></strong>
        <span>Events Included</span>
    </div>
</section>

<section class="card finance-section">
    <div class="section-header">
        <div>
            <h2>Monthly finance</h2>
            <p class="muted">Full-year view for <?= (int) $filters['year'] ?>.</p>
        </div>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Month</th>
                    <th>Events</th>
                    <th>Players</th>
                    <th>Revenue</th>
                    <th>Fund</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($monthlyBreakdown)): ?>
                    <tr>
                        <td colspan="5">No finance records found for this year.</td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($monthlyBreakdown as $row): ?>
                    <?php $players = (int) ($row['active_players'] ?? 0); ?>
                    <tr>
                        <td><?= htmlspecialchars($monthNames[(int) $row['month_number']] ?? 'Month') ?></td>
                        <td><?= (int) ($row['total_events'] ?? 0) ?></td>
                        <td><?= $players ?></td>
                        <td><?= $money($row['revenue_received'] ?? 0) ?></td>
                        <td><?= $money($players * 5) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="card finance-section">
    <div class="section-header">
        <div>
            <h2>Event finance</h2>
            <p class="muted">Event-level rows for the selected period.</p>
        </div>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Event</th>
                    <th>Venue</th>
                    <th>Entry Fee</th>
                    <th>Players</th>
                    <th>Revenue</th>
                    <th>Fund</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($eventBreakdown)): ?>
                    <tr>
                        <td colspan="7">No events found for this period.</td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($eventBreakdown as $event): ?>
                    <?php $players = (int) ($event['active_players'] ?? 0); ?>
                    <tr>
                        <td><?= htmlspecialchars($event['event_date'] ?? '') ?></td>
                        <td><?= htmlspecialchars($event['title'] ?? '') ?></td>
                        <td><?= htmlspecialchars($event['venue_name'] ?? '') ?></td>
                        <td><?= $money($event['entry_fee'] ?? 0) ?></td>
                        <td><?= $players ?></td>
                        <td><?= $money($event['revenue_received'] ?? 0) ?></td>
                        <td><?= $money($players * 5) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
