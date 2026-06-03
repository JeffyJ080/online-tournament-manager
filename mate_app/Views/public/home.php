<?php
$heading = $heading ?? 'Mate Tournaments';
?>

<section class="hero">
    <div>
        <div class="hero-kicker">Restaurant chess. Real tournaments. Better nights.</div>

        <h1><?= htmlspecialchars($heading) ?></h1>

        <p>
            Mate Tournaments hosts polished chess events at restaurants, venues,
            campuses, and online — built for players, hosts, and growing communities.
        </p>

        <div class="hero-actions">
            <a class="btn" href="index.php?page=events">View Events</a>
            <a class="btn btn-outline" href="index.php?page=contact">Host an Event</a>
        </div>
    </div>

    <div class="card">
        <h2>Next move</h2>
        <p>
            Upcoming event cards will appear here once the database event system is connected.
        </p>
    </div>
</section>

<section class="stat-grid">
    <div class="card stat-card">
        <strong>3</strong>
        <span>Core formats planned</span>
    </div>

    <div class="card stat-card">
        <strong>5+</strong>
        <span>Leaderboard types</span>
    </div>

    <div class="card stat-card">
        <strong>∞</strong>
        <span>Potential chess chaos</span>
    </div>
</section>