<?php
$heading = $heading ?? 'Host Dashboard';
$user = $user ?? [];
$assignedEvents = $assignedEvents ?? 0;
$checkedInPlayers = $checkedInPlayers ?? 0;
$cashToHandOver = $cashToHandOver ?? 0;
?>

<section class="dashboard-header">
    <div>
        <div class="hero-kicker">Event Control</div>
        <h1><?= htmlspecialchars($heading) ?></h1>
        <p>
            Welcome, <?= htmlspecialchars($user['email'] ?? 'host') ?>.
            Manage assigned events, check-ins, walk-ins, reports, and tournament results.
        </p>
    </div>
</section>

<section class="dashboard-grid">
    <div class="card stat-card">
        <strong><?= (int) $assignedEvents ?></strong>
        <span>Assigned Events</span>
    </div>

    <div class="card stat-card">
        <strong><?= (int) $checkedInPlayers ?></strong>
        <span>Players Checked In</span>
    </div>

    <div class="card stat-card">
        <strong>R<?= number_format((float) $cashToHandOver, 0) ?></strong>
        <span>Cash to Hand Over</span>
    </div>
</section>

<section class="card">
    <h2>Host tools</h2>

    <div class="shortcut-grid">
        <a class="shortcut-card" href="index.php?page=host-events">
            <strong>Assigned Events</strong>
            <span>View and manage events assigned to your account.</span>
        </a>

        <a class="shortcut-card" href="index.php?page=host-events">
            <strong>Check-in</strong>
            <span>Open an assigned event and check players in on event night.</span>
        </a>

        <a class="shortcut-card" href="index.php?page=host-events">
            <strong>Walk-ins</strong>
            <span>Open Assigned Events, choose Manage Event, then use Add Walk-in Player.</span>
        </a>

        <a class="shortcut-card" href="index.php?page=host-events">
            <strong>Enter Results</strong>
            <span>Open Tournament Manager from an assigned event to submit results.</span>
        </a>
    </div>
</section>
