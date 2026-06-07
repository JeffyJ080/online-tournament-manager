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

    <div class="shortcut-grid">
        <a class="shortcut-card" href="index.php?page=admin-registrations">
            <strong>Registrations</strong>
            <span>View player bookings, payment methods, and statuses.</span>
        </a>

        <a class="shortcut-card" href="index.php?page=admin-payment-proofs">
            <strong>Payment Proofs</strong>
            <span>Review EFT proof uploads and verify payments.</span>
        </a>

        <a class="shortcut-card" href="index.php?page=admin-venues">
            <strong>Venues</strong>
            <span>Manage restaurants, campuses, and host locations.</span>
        </a>

        <a class="shortcut-card" href="index.php?page=events">
            <strong>Public Events</strong>
            <span>View the public event listing as players see it.</span>
        </a>

        <a class="shortcut-card is-disabled" href="#">
            <strong>Events Admin</strong>
            <span>Coming soon: create and edit events.</span>
        </a>

        <a class="shortcut-card is-disabled" href="#">
            <strong>Tournament Manager</strong>
            <span>Coming soon: run pairings, rounds, and results.</span>
        </a>

        <a class="shortcut-card is-disabled" href="#">
            <strong>Leaderboards</strong>
            <span>Coming soon: Elo, venue, weekly, and seasonal rankings.</span>
        </a>

        <a class="shortcut-card is-disabled" href="#">
            <strong>Reports</strong>
            <span>Coming soon: event summaries and venue stats.</span>
        </a>
    </div>
</section>