<?php
$events = $events ?? [];
?>

<section class="dashboard-header">
    <div>
        <div class="hero-kicker">Completed Events</div>
        <h1><?= htmlspecialchars($heading ?? 'Tournament Archive') ?></h1>
        <p>Completed tournaments, final standings, pairings, and results.</p>
    </div>
</section>

<?php if (empty($events)): ?>
    <section class="card">
        <h2>No archived tournaments yet</h2>
        <p>Completed tournaments will appear here.</p>
    </section>
<?php else: ?>
    <section class="event-grid">
        <?php foreach ($events as $event): ?>
            <article class="card event-card">
                <div>
                    <div class="hero-kicker"><?= htmlspecialchars(date('d M Y', strtotime($event['event_date']))) ?></div>
                    <h2><?= htmlspecialchars($event['title']) ?></h2>
                    <p><?= htmlspecialchars($event['venue_name']) ?><?= !empty($event['venue_city']) ? ' - ' . htmlspecialchars($event['venue_city']) : '' ?></p>
                </div>

                <div class="hero-actions">
                    <a class="btn" href="index.php?page=live-tournament&event=<?= (int) $event['id'] ?>">View Results</a>
                </div>
            </article>
        <?php endforeach; ?>
    </section>
<?php endif; ?>
