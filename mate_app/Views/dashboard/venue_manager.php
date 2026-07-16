<?php
$heading = $heading ?? 'Venue Partner Dashboard';
$user = $user ?? [];
$venues = $venues ?? [];
$events = $events ?? [];
$requestHistory = $requestHistory ?? [];
$upcomingEvents = $upcomingEvents ?? 0;
$totalEventsHosted = $totalEventsHosted ?? 0;
$averageAttendance = $averageAttendance ?? 0;

function venuePartnerDate(?string $date): string
{
    return $date ? date('d M Y', strtotime($date)) : '-';
}

function venuePartnerLabel(?string $value): string
{
    return $value ? ucwords(str_replace('_', ' ', $value)) : '-';
}

$upcoming = array_values(array_filter($events, static fn ($event): bool => strtotime($event['event_date'] ?? '') >= strtotime(date('Y-m-d'))));
$past = array_values(array_filter($events, static fn ($event): bool => strtotime($event['event_date'] ?? '') < strtotime(date('Y-m-d'))));
?>

<section class="dashboard-header">
    <div>
        <div class="hero-kicker">Venue Partner</div>
        <h1><?= htmlspecialchars($heading) ?></h1>
        <p>
            Welcome, <?= htmlspecialchars($user['email'] ?? 'venue partner') ?>.
            Review your assigned venue activity and submit public detail updates for Mate approval.
        </p>
    </div>
</section>

<section class="dashboard-grid">
    <div class="card stat-card">
        <strong><?= count($venues) ?></strong>
        <span>Assigned Venues</span>
    </div>

    <div class="card stat-card">
        <strong><?= (int) $upcomingEvents ?></strong>
        <span>Upcoming Events</span>
    </div>

    <div class="card stat-card">
        <strong><?= (int) $totalEventsHosted ?></strong>
        <span>Completed Events</span>
    </div>

    <div class="card stat-card">
        <strong><?= (int) $averageAttendance ?></strong>
        <span>Average Attendance</span>
    </div>
</section>

<section class="card">
    <div class="section-header">
        <div>
            <h2>Your venues</h2>
            <p>Only venues assigned to your partner account are shown here.</p>
        </div>
    </div>

    <?php if (empty($venues)): ?>
        <p>No venues are assigned to this account yet.</p>
    <?php else: ?>
        <div class="event-grid">
            <?php foreach ($venues as $venue): ?>
                <article class="event-card">
                    <div>
                        <div class="hero-kicker"><?= htmlspecialchars($venue['city'] ?? 'Venue') ?></div>
                        <h2><?= htmlspecialchars($venue['name']) ?></h2>
                        <p><?= htmlspecialchars($venue['address'] ?? 'No address listed') ?></p>
                    </div>

                    <div class="hero-actions">
                        <a class="btn btn-outline" href="index.php?page=venue-partner-venue&id=<?= (int) $venue['id'] ?>">
                            View Venue
                        </a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<section class="card">
    <div class="section-header">
        <div>
            <h2>Upcoming venue events</h2>
            <p>Summary counts only. Player identities and payment rows stay private to Mate operations.</p>
        </div>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Event</th>
                    <th>Venue</th>
                    <th>Registrations</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($upcoming)): ?>
                    <tr>
                        <td colspan="5">No upcoming events found for your assigned venues.</td>
                    </tr>
                <?php endif; ?>

                <?php foreach (array_slice($upcoming, 0, 8) as $event): ?>
                    <tr>
                        <td><?= htmlspecialchars(venuePartnerDate($event['event_date'] ?? null)) ?></td>
                        <td><?= htmlspecialchars($event['title'] ?? '') ?></td>
                        <td><?= htmlspecialchars($event['venue_name'] ?? '') ?></td>
                        <td><?= (int) ($event['active_registrations'] ?? 0) ?> / <?= (int) ($event['max_players'] ?? 0) ?></td>
                        <td><?= htmlspecialchars(venuePartnerLabel($event['event_status'] ?? null)) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="card">
    <div class="section-header">
        <div>
            <h2>Past event summaries</h2>
            <p>Attendance and venue-level totals for completed or past events.</p>
        </div>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Event</th>
                    <th>Venue</th>
                    <th>Attendance</th>
                    <th>Revenue</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($past)): ?>
                    <tr>
                        <td colspan="5">No past event summaries found yet.</td>
                    </tr>
                <?php endif; ?>

                <?php foreach (array_slice($past, 0, 8) as $event): ?>
                    <tr>
                        <td><?= htmlspecialchars(venuePartnerDate($event['event_date'] ?? null)) ?></td>
                        <td><?= htmlspecialchars($event['title'] ?? '') ?></td>
                        <td><?= htmlspecialchars($event['venue_name'] ?? '') ?></td>
                        <td><?= (int) max((int) ($event['attendance_count'] ?? 0), (int) ($event['checked_in_count'] ?? 0)) ?></td>
                        <td>R<?= number_format((float) ($event['revenue_received'] ?? 0), 2) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="card">
    <h2>Partner tools</h2>

    <div class="shortcut-grid">
        <a class="shortcut-card" href="index.php?page=finance-stats">
            <strong>Finance Stats</strong>
            <span>Review venue-scoped summary totals and export partner-safe reports.</span>
        </a>

        <a class="shortcut-card" href="index.php?page=venues">
            <strong>Public Venue Listing</strong>
            <span>See how venue details appear publicly after Mate approval.</span>
        </a>
    </div>
</section>
