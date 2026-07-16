<?php
$heading = $heading ?? 'Manage Venues';
$venues = $venues ?? [];
$pendingUpdateRequests = $pendingUpdateRequests ?? 0;
?>

<section class="dashboard-header">
    <div>
        <div class="hero-kicker">Admin</div>
        <h1><?= htmlspecialchars($heading) ?></h1>
        <p>
            View and manage the venues where Mate Tournaments hosts events.
        </p>
    </div>
</section>

<section class="card">
    <div class="section-header">
        <div>
            <h2>Venues</h2>
            <p>These venues are stored in the database.</p>
        </div>

        <div class="hero-actions">
            <a class="btn btn-outline" href="index.php?page=admin-venue-update-requests">
                Venue Partner Requests<?= $pendingUpdateRequests > 0 ? ' (' . (int) $pendingUpdateRequests . ')' : '' ?>
            </a>
            <a class="btn" href="index.php?page=admin-venues-create">Add Venue</a>
        </div>
    </div>

    <?php if (empty($venues)): ?>
        <p>No venues found yet.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>City</th>
                        <th>Contact</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($venues as $venue): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($venue['name']) ?></strong>
                                <br>
                                <span class="muted"><?= htmlspecialchars($venue['slug']) ?></span>
                            </td>
                            <td><?= htmlspecialchars($venue['city'] ?? '-') ?></td>
                            <td>
                                <?= htmlspecialchars($venue['contact_person'] ?? '-') ?>
                                <?php if (!empty($venue['contact_phone'])): ?>
                                    <br>
                                    <span class="muted"><?= htmlspecialchars($venue['contact_phone']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="status-pill status-<?= htmlspecialchars($venue['status']) ?>">
                                    <?= htmlspecialchars($venue['status']) ?>
                                </span>
                            </td>
                            <td>
                                <a class="btn btn-outline btn-sm" href="index.php?page=admin-venues-edit&id=<?= (int) $venue['id'] ?>">
                                    Edit
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
