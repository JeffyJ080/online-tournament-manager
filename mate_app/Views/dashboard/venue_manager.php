<?php
$heading = $heading ?? 'Venue Manager Dashboard';
$user = $user ?? [];
$upcomingEvents = $upcomingEvents ?? 0;
$totalEventsHosted = $totalEventsHosted ?? 0;
$averageAttendance = $averageAttendance ?? 0;
?>

<section class="dashboard-header">
    <div>
        <div class="hero-kicker">Venue Stats</div>
        <h1><?= htmlspecialchars($heading) ?></h1>
        <p>
            Welcome, <?= htmlspecialchars($user['email'] ?? 'venue manager') ?>.
            Review venue activity, public event listings, and event performance.
        </p>
    </div>
</section>

<section class="dashboard-grid">
    <div class="card stat-card">
        <strong><?= (int) $upcomingEvents ?></strong>
        <span>Upcoming Events</span>
    </div>

    <div class="card stat-card">
        <strong><?= (int) $totalEventsHosted ?></strong>
        <span>Total Events Hosted</span>
    </div>

    <div class="card stat-card">
        <strong><?= (int) $averageAttendance ?></strong>
        <span>Average Attendance</span>
    </div>
</section>

<section class="card">
    <h2>Venue overview</h2>

    <div class="shortcut-grid">
        <a class="shortcut-card" href="index.php?page=events">
            <strong>Upcoming Events</strong>
            <span>View active public events and player registration options.</span>
        </a>

        <a class="shortcut-card" href="index.php?page=leaderboard">
            <strong>Attendance Stats</strong>
            <span>Use tournament standings and leaderboards to review activity.</span>
        </a>

        <a class="shortcut-card" href="index.php?page=events">
            <strong>Past Events</strong>
            <span>Review event status and live tournament pages from event links.</span>
        </a>

        <a class="shortcut-card" href="index.php?page=venues">
            <strong>Venue Profile</strong>
            <span>Review public venue contact details and food specials.</span>
        </a>
    </div>
</section>
