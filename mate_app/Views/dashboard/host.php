<?php
$heading = $heading ?? 'Host Dashboard';
$user = $user ?? [];
?>

<section class="dashboard-header">
    <div>
        <div class="hero-kicker">Event Control</div>
        <h1><?= htmlspecialchars($heading) ?></h1>
        <p>
            Welcome, <?= htmlspecialchars($user['email'] ?? 'host') ?>.
            Assigned events, check-ins, walk-ins, and results will appear here.
        </p>
    </div>
</section>

<section class="dashboard-grid">
    <div class="card stat-card">
        <strong>0</strong>
        <span>Assigned Events</span>
    </div>

    <div class="card stat-card">
        <strong>0</strong>
        <span>Players Checked In</span>
    </div>

    <div class="card stat-card">
        <strong>R0</strong>
        <span>Cash to Hand Over</span>
    </div>
</section>

<section class="card">
    <h2>Host tools</h2>

    <div class="shortcut-grid">
        <a class="shortcut-card is-disabled" href="#">
            <strong>Assigned Events</strong>
            <span>Coming soon: view events assigned to you.</span>
        </a>

        <a class="shortcut-card is-disabled" href="#">
            <strong>Check-in</strong>
            <span>Coming soon: check players in on event night.</span>
        </a>

        <a class="shortcut-card is-disabled" href="#">
            <strong>Walk-ins</strong>
            <span>Coming soon: add players at the venue.</span>
        </a>

        <a class="shortcut-card is-disabled" href="#">
            <strong>Enter Results</strong>
            <span>Coming soon: submit match and round results.</span>
        </a>
    </div>
</section>