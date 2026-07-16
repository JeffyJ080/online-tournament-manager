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
            Track your registrations, payments, rating, and leaderboard position.
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

        <a class="shortcut-card" href="index.php?page=my-profile">
            <strong>My Profile</strong>
            <span>Update your player details and change your password.</span>
        </a>

        <a class="shortcut-card" href="index.php?page=events">
            <strong>Find Events</strong>
            <span>Browse upcoming Mate Tournaments events.</span>
        </a>

        <a class="shortcut-card" href="index.php?page=leaderboard">
            <strong>My Rating</strong>
            <span>Compare your tournament score against the public leaderboard.</span>
        </a>

        <a class="shortcut-card" href="index.php?page=my-match-history">
            <strong>Match History</strong>
            <span>Review your completed games, record, and rating activity.</span>
        </a>
    </div>
</section>
