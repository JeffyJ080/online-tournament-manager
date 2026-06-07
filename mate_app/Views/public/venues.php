<?php
$heading = $heading ?? 'Our Venues';
$venues = $venues ?? [];
?>

<section class="dashboard-header">
    <div>
        <div class="hero-kicker">Mate Locations</div>
        <h1><?= htmlspecialchars($heading) ?></h1>
        <p>
            These are the restaurants, campuses, and venues where Mate Tournaments hosts chess events.
        </p>
    </div>
</section>

<?php if (empty($venues)): ?>
    <section class="card">
        <h2>No venues listed yet</h2>
        <p>Venues will appear here once they are active.</p>
    </section>
<?php else: ?>
    <section class="event-grid">
        <?php foreach ($venues as $venue): ?>
            <article class="card event-card">
                <div>
                    <div class="hero-kicker"><?= htmlspecialchars($venue['city'] ?? 'Venue') ?></div>
                    <h2><?= htmlspecialchars($venue['name']) ?></h2>
                </div>

                <?php if (!empty($venue['address'])): ?>
                    <p><?= htmlspecialchars($venue['address']) ?></p>
                <?php endif; ?>

                <?php if (!empty($venue['food_deal_description'])): ?>
                    <div class="event-meta">
                        <div>
                            <span>Venue Deal</span>
                            <strong><?= htmlspecialchars($venue['food_deal_description']) ?></strong>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="hero-actions">
                    <a class="btn btn-outline" href="index.php?page=events">View Events</a>
                </div>
            </article>
        <?php endforeach; ?>
    </section>
<?php endif; ?>