<?php
$heading = $heading ?? 'Assigned Events';
$events = $events ?? [];

function hostEventDate(?string $date): string
{
    return $date ? date('d M Y', strtotime($date)) : '-';
}

function hostEventTime(?string $time): string
{
    return $time ? date('H:i', strtotime($time)) : '-';
}

function hostEventLabel(?string $value): string
{
    return $value ? ucwords(str_replace('_', ' ', $value)) : '-';
}
?>

<section class="dashboard-header">
    <div>
        <div class="hero-kicker">Host Operations</div>
        <h1><?= htmlspecialchars($heading) ?></h1>
        <p>
            These are the events currently assigned to you.
        </p>
    </div>
</section>

<?php if (empty($events)): ?>
    <section class="card">
        <h2>No assigned events</h2>
        <p>You currently have no events assigned to your account.</p>
    </section>
<?php else: ?>
    <section class="event-grid">
        <?php foreach ($events as $event): ?>
            <?php
            $eventClosed = in_array(($event['event_status'] ?? ''), ['completed', 'cancelled'], true);
            ?>
            <article class="card event-card">
                <div>
                    <div class="hero-kicker">
                        <?= htmlspecialchars(hostEventDate($event['event_date'])) ?>
                        ·
                        <?= htmlspecialchars(hostEventTime($event['start_time'])) ?>
                    </div>

                    <h2><?= htmlspecialchars($event['title']) ?></h2>

                    <p>
                        <?= htmlspecialchars($event['venue_name']) ?>
                        <?php if (!empty($event['venue_city'])): ?>
                            · <?= htmlspecialchars($event['venue_city']) ?>
                        <?php endif; ?>
                    </p>
                </div>

                <div class="event-meta">
                    <div>
                        <span>Format</span>
                        <strong><?= htmlspecialchars(hostEventLabel($event['format'])) ?></strong>
                    </div>

                    <div>
                        <span>Registrations</span>
                        <strong><?= (int) ($event['active_registrations'] ?? 0) ?> / <?= (int) ($event['max_players'] ?? 0) ?></strong>
                    </div>

                    <div>
                        <span>Status</span>
                        <strong><?= htmlspecialchars(hostEventLabel($event['event_status'] ?? '-')) ?></strong>
                    </div>
                </div>

                <div class="hero-actions">
                    <a class="btn btn-outline" href="index.php?page=host-event-registrations&id=<?= (int) $event['id'] ?>">
                        <?= $eventClosed ? 'Review Event' : 'Manage Event' ?>
                    </a>
                    <a class="btn btn-outline" href="index.php?page=live-tournament&event=<?= (int) $event['id'] ?>">
                        Live View
                    </a>
                    <a class="btn btn-outline" href="index.php?page=live-display&event=<?= (int) $event['id'] ?>" target="_blank">
                        Second Screen
                    </a>
                </div>
            </article>
        <?php endforeach; ?>
    </section>
<?php endif; ?>
