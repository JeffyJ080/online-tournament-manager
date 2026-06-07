<?php
$heading = $heading ?? 'Player Dashboard';
$user = $user ?? [];
$player = $player ?? [];
$upcomingRegistrations = $upcomingRegistrations ?? 0;
$matchesPlayed = $matchesPlayed ?? 0;
?>

<section class="dashboard-header">
    <div>
        <div class="hero-kicker">Player Area</div>
        <h1><?= htmlspecialchars($heading) ?></h1>
        <p>
            Welcome back, <?= htmlspecialchars($user['email'] ?? 'player') ?>.
            This is where your events, rating, payments, and match history will live.
        </p>
    </div>
</section>

<section class="dashboard-grid">
    <div class="card stat-card">
        <strong><?= (int) ($player['current_rating'] ?? 800) ?></strong>
        <span>Current Mate Elo</span>
    </div>

    <div class="card stat-card">
        <strong><?= (int) $upcomingRegistrations ?></strong>
        <span>Upcoming Events</span>
    </div>

    <div class="card stat-card">
        <strong><?= (int) $matchesPlayed ?></strong>
        <span>Matches Played</span>
    </div>
</section>

<section class="card">
    <h2>Next actions</h2>

    <div class="shortcut-grid">
        <a class="shortcut-card" href="index.php?page=my-registrations">
            <strong>My Registrations</strong>
            <span>View bookings, payment status, and upload proof of payment.</span>
        </a>

        <a class="shortcut-card" href="index.php?page=events">
            <strong>Find Events</strong>
            <span>Browse upcoming Mate Tournaments events.</span>
        </a>

        <a class="shortcut-card is-disabled" href="#">
            <strong>My Rating</strong>
            <span>Coming soon: view your Mate Elo and rating history.</span>
        </a>

        <a class="shortcut-card is-disabled" href="#">
            <strong>Match History</strong>
            <span>Coming soon: view your played matches and results.</span>
        </a>
    </div>
</section>