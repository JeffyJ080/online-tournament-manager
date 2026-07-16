<?php
$heading = $heading ?? 'Audit Log';
$logs = $logs ?? [];

function auditDate(?string $date): string
{
    return $date ? date('d M Y H:i', strtotime($date)) : '-';
}

function auditLabel(?string $value): string
{
    return $value ? ucwords(str_replace('_', ' ', $value)) : '-';
}
?>

<section class="dashboard-header">
    <div>
        <div class="hero-kicker">Admin</div>
        <h1><?= htmlspecialchars($heading) ?></h1>
        <p>Recent operational actions across events, users, payments, registrations, tournaments, and ratings.</p>
    </div>
</section>

<section class="card">
    <div class="section-header">
        <div>
            <h2>Recent Activity</h2>
            <p>Newest actions appear first.</p>
        </div>
    </div>

    <?php if (empty($logs)): ?>
        <p>No audit activity has been recorded yet.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>When</th>
                        <th>User</th>
                        <th>Action</th>
                        <th>Entity</th>
                        <th>Description</th>
                        <th>IP</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><?= htmlspecialchars(auditDate($log['created_at'] ?? null)) ?></td>
                            <td>
                                <?= htmlspecialchars($log['user_email'] ?? 'System') ?>
                                <?php if (!empty($log['user_role'])): ?>
                                    <br>
                                    <span class="muted"><?= htmlspecialchars(auditLabel($log['user_role'])) ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars(auditLabel($log['action'] ?? '')) ?></td>
                            <td>
                                <?= htmlspecialchars(auditLabel($log['entity_type'] ?? '')) ?>
                                <?php if (!empty($log['entity_id'])): ?>
                                    <br>
                                    <span class="muted">#<?= (int) $log['entity_id'] ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($log['description'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($log['ip_address'] ?? '-') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
