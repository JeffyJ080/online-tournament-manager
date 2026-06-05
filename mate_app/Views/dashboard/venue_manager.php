<?php
$heading = $heading ?? 'Venue Manager Dashboard';
$user = $user ?? [];
?>

<section class="dashboard-header">
    <div>
        <div class="hero-kicker">Venue Stats</div>
        <h1><?= htmlspecialchars($heading) ?></h1>
        <p>
            Welcome, <?= htmlspecialchars($user['email'] ?? 'venue manager') ?>.
            Your venue’s upcoming events, attendance stats, and past events will appear here.
        </p>
    </div>
</section>

<section class="dashboard-grid">
    <div class="card stat-card">
        <strong>0</strong>
        <span>Upcoming Events</span>
    </div>

    <div class="card stat-card">
        <strong>0</strong>
        <span>Total Events Hosted</span>
    </div>

    <div class="card stat-card">
        <strong>0</strong>
        <span>Average Attendance</span>
    </div>
</section>

<section class="card">
    <h2>Venue overview</h2>
    <p>
        Restaurant-facing stats will go here without showing internal Mate profit.
    </p>
</section>