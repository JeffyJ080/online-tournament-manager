<?php
$heading = $heading ?? 'Upcoming Events';
$events = $events ?? [];

function formatEventDate(string $date): string
{
    return date('D, d M Y', strtotime($date));
}

function formatEventTime(string $time): string
{
    return date('H:i', strtotime($time));
}

function formatEventMoney($amount): string
{
    return 'R' . number_format((float) $amount, 0);
}

function formatEventLabel(string $value): string
{
    return ucwords(str_replace('_', ' ', $value));
}
?>

<section class="dashboard-header">
    <div>
        <div class="hero-kicker">Play Over The Board</div>
        <h1><?= htmlspecialchars($heading) ?></h1>
        <p>
            Browse upcoming Mate Tournaments events, check formats, entry fees,
            venues, and available spots.
        </p>
    </div>
</section>

<?php if (empty($events)): ?>
    <section class="card">
        <h2>No upcoming events yet</h2>
        <p>
            New events will appear here once they are published.
        </p>
    </section>
<?php else: ?>
    <section class="event-grid">
        <?php foreach ($events as $event): ?>
            <article class="card event-card">
                <div class="event-card-top">
                    <div>
                        <div class="hero-kicker">
                            <?= htmlspecialchars(formatEventLabel($event['format'])) ?>
                        </div>

                        <h2><?= htmlspecialchars($event['title']) ?></h2>
                    </div>

                    <span class="status-pill status-<?= htmlspecialchars($event['registration_status']) ?>">
                        <?= htmlspecialchars($event['registration_status']) ?>
                    </span>
                </div>

                <p>
                    <?= htmlspecialchars($event['description'] ?? 'Chess tournament hosted by Mate Tournaments.') ?>
                </p>

                <div class="event-meta">
                    <div>
                        <span>Date</span>
                        <strong><?= htmlspecialchars(formatEventDate($event['event_date'])) ?></strong>
                    </div>

                    <div>
                        <span>Time</span>
                        <strong><?= htmlspecialchars(formatEventTime($event['start_time'])) ?></strong>
                    </div>

                    <div>
                        <span>Venue</span>
                        <strong>
                            <?= htmlspecialchars($event['venue_name']) ?>
                            <?php if (!empty($event['venue_city'])): ?>
                                — <?= htmlspecialchars($event['venue_city']) ?>
                            <?php endif; ?>
                        </strong>
                    </div>

                    <div>
                        <span>Entry</span>
                        <strong><?= htmlspecialchars(formatEventMoney($event['entry_fee'])) ?></strong>
                    </div>

                    <div>
                        <span>Prize</span>
                        <strong><?= htmlspecialchars($event['prize_info'] ?? '-') ?></strong>
                    </div>

                    <?php
                    $activeCount = (int) ($event['active_registrations'] ?? 0);
                    $spotsLeft = max(0, (int) $event['max_players'] - $activeCount);
                    ?>

                    <div>
                        <span>Registered</span>
                        <strong><?= $activeCount ?> / <?= (int) $event['max_players'] ?></strong>
                    </div>

                    <div>
                        <span>Spots Left</span>
                        <strong><?= $spotsLeft ?></strong>
                    </div>
                </div>

                <div class="hero-actions">
                    <a class="btn" href="index.php?page=event&slug=<?= urlencode($event['slug']) ?>">
                        View Details
                    </a>
                    <a class="btn btn-outline" href="index.php?page=register-event&event=<?= (int) $event['id'] ?>">
                        Register
                    </a>
                </div>
            </article>
        <?php endforeach; ?>
    </section>
<?php endif; ?>