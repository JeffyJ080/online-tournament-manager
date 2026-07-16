<?php
$heading = $heading ?? 'Admin Dashboard';
$user = $user ?? [];
$upcomingEvents = $upcomingEvents ?? 0;
$openRegistrations = $openRegistrations ?? 0;
$pendingProofs = $pendingProofs ?? 0;
$activeVenues = $activeVenues ?? 0;
?>

<section class="dashboard-header">
    <div>
        <div class="hero-kicker">Command Center</div>
        <h1><?= htmlspecialchars($heading) ?></h1>
        <p>
            Welcome, <?= htmlspecialchars($user['email'] ?? 'admin') ?>.
            Manage events, payments, registrations, venues, leaderboards, and tournament tools.
        </p>
    </div>
</section>

<section class="dashboard-grid">
    <div class="card stat-card">
        <strong><?= (int) $upcomingEvents ?></strong>
        <span>Upcoming Events</span>
    </div>

    <div class="card stat-card">
        <strong><?= (int) $openRegistrations ?></strong>
        <span>Open Registrations</span>
    </div>

    <div class="card stat-card">
        <strong><?= (int) $pendingProofs ?></strong>
        <span>Pending Proof Reviews</span>
    </div>

    <div class="card stat-card">
        <strong><?= (int) $activeVenues ?></strong>
        <span>Active Venues</span>
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

        <a class="shortcut-card" href="index.php?page=finance-stats">
            <strong>Finance Stats</strong>
            <span>Review private revenue, outstanding payments, and the R5 player fund.</span>
        </a>

        <a class="shortcut-card" href="index.php?page=admin-venues">
            <strong>Venues</strong>
            <span>Manage restaurants, campuses, and host locations.</span>
        </a>

        <a class="shortcut-card" href="index.php?page=admin-users">
            <strong>Users</strong>
            <span>Create hosts, event managers, Venue Partners, admins, and players.</span>
        </a>

        <a class="shortcut-card" href="index.php?page=admin-players">
            <strong>Player Identity</strong>
            <span>Link accounts and merge duplicate imported or walk-in player records.</span>
        </a>

        <a class="shortcut-card" href="index.php?page=events">
            <strong>Public Events</strong>
            <span>View the public event listing as players see it.</span>
        </a>

        <a class="shortcut-card" href="index.php?page=admin-events">
            <strong>Events Admin</strong>
            <span>Create, edit, and manage tournament events.</span>
        </a>

        <a class="shortcut-card" href="index.php?page=admin-events">
            <strong>Tournament Manager</strong>
            <span>Open an event, then launch Tournament Manager for pairings and results.</span>
        </a>

        <a class="shortcut-card" href="index.php?page=leaderboard">
            <strong>Leaderboards</strong>
            <span>View public season standings across tournament results.</span>
        </a>

        <a class="shortcut-card" href="index.php?page=admin-leaderboards">
            <strong>Leaderboard Admin</strong>
            <span>Create restaurant seasons and custom placement point rules.</span>
        </a>

        <a class="shortcut-card" href="index.php?page=admin-audit">
            <strong>Audit Log</strong>
            <span>Review recent admin, event, payment, tournament, and rating actions.</span>
        </a>

        <a class="shortcut-card" href="index.php?page=host-events">
            <strong>Reports</strong>
            <span>Review assigned event operations and submit event reports.</span>
        </a>
    </div>
</section>
