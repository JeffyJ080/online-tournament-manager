<?php
$player = $player ?? [];
$stats = $stats ?? [];
$name = $player['display_name'] ?: ($player['real_name'] ?? 'Player');
?>

<section class="dashboard-header">
    <div>
        <div class="hero-kicker">Player Profile</div>
        <h1><?= htmlspecialchars($name) ?></h1>
        <p>Public Mate Tournaments player record.</p>
    </div>
</section>

<section class="dashboard-grid">
    <div class="card stat-card">
        <strong><?= (int) ($player['current_rating'] ?? 800) ?></strong>
        <span>Mate Elo</span>
    </div>
    <div class="card stat-card">
        <strong><?= (int) ($stats['matches_played'] ?? 0) ?></strong>
        <span>Matches</span>
    </div>
    <div class="card stat-card">
        <strong><?= (int) ($stats['wins'] ?? 0) ?>-<?= (int) ($stats['draws'] ?? 0) ?>-<?= (int) ($stats['losses'] ?? 0) ?></strong>
        <span>Win/Draw/Loss</span>
    </div>
</section>
