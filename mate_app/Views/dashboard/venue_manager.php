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

    <div class="shortcut-grid">
        <a class="shortcut-card is-disabled" href="#">
            <strong>Upcoming Events</strong>
            <span>Coming soon: view events scheduled at your venue.</span>
        </a>

        <a class="shortcut-card is-disabled" href="#">
            <strong>Attendance Stats</strong>
            <span>Coming soon: track event turnout over time.</span>
        </a>

        <a class="shortcut-card is-disabled" href="#">
            <strong>Past Events</strong>
            <span>Coming soon: see previous Mate events hosted here.</span>
        </a>

        <a class="shortcut-card is-disabled" href="#">
            <strong>Venue Profile</strong>
            <span>Coming soon: review venue contact and event details.</span>
        </a>
    </div>
</section>