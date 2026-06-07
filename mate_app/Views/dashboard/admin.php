<?php
$heading = $heading ?? 'Admin Dashboard';
$user = $user ?? [];
?>

<section class="dashboard-header">
    <div>
        <div class="hero-kicker">Command Center</div>
        <h1><?= htmlspecialchars($heading) ?></h1>
        <p>
            Welcome, <?= htmlspecialchars($user['email'] ?? 'admin') ?>.
            Upcoming events, payments, registrations, leaderboards, and tournament tools will live here.
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
        <span>Outstanding Payments</span>
    </div>

    <div class="card stat-card">
        <strong>0</strong>
        <span>Registered Players</span>
    </div>
</section>

<section class="card">
    <h2>Admin shortcuts</h2>

    <div class="hero-actions">
        <a class="btn" href="index.php?page=admin-events">Create Event</a>
        <a class="btn btn-outline" href="index.php?page=leaderboards">View Leaderboards</a>
        <a class="btn btn-outline" href="index.php?page=admin-registrations">View Registrations</a>
        <a class="btn btn-outline" href="index.php?page=admin-payment-proofs">Review Payment Proofs</a>
        <a class="btn btn-outline" href="index.php?page=logout">Logout</a>
    </div>
</section>