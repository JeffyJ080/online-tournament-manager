<?php
$heading = $heading ?? 'Player Dashboard';
$user = $user ?? [];
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
        <strong>800</strong>
        <span>Current Mate Elo</span>
    </div>

    <div class="card stat-card">
        <strong>0</strong>
        <span>Upcoming Events</span>
    </div>

    <div class="card stat-card">
        <strong>0</strong>
        <span>Matches Played</span>
    </div>
</section>

<section class="card">
    <h2>Next actions</h2>
    <div class="hero-actions">
        <a class="btn" href="index.php?page=events">View Events</a>
        <a class="btn btn-outline" href="index.php?page=logout">Logout</a>
    </div>
</section>