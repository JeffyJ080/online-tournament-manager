<?php
$heading = $heading ?? 'Event Registrations';
$registrations = $registrations ?? [];

function adminRegDate(?string $date): string
{
    return $date ? date('d M Y', strtotime($date)) : '-';
}

function adminRegTime(?string $time): string
{
    return $time ? date('H:i', strtotime($time)) : '-';
}

function adminRegLabel(?string $value): string
{
    return $value ? ucwords(str_replace('_', ' ', $value)) : '-';
}
?>

<section class="dashboard-header">
    <div>
        <div class="hero-kicker">Admin</div>
        <h1><?= htmlspecialchars($heading) ?></h1>
        <p>
            View all event registrations, payment statuses, and booking details.
        </p>
    </div>
</section>

<section class="card">
    <div class="section-header">
        <div>
            <h2>All Registrations</h2>
            <p>Player lists are private and only visible to admin/event staff.</p>
        </div>
    </div>

    <?php if (empty($registrations)): ?>
        <p>No registrations found yet.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Event</th>
                        <th>Player</th>
                        <th>Contact</th>
                        <th>Registration</th>
                        <th>Payment</th>
                        <th>Registered</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($registrations as $registration): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($registration['event_title']) ?></strong>
                                <br>
                                <span class="muted">
                                    <?= htmlspecialchars($registration['venue_name']) ?>
                                    ·
                                    <?= htmlspecialchars(adminRegDate($registration['event_date'])) ?>
                                    <?= htmlspecialchars(adminRegTime($registration['start_time'])) ?>
                                </span>
                            </td>

                            <td>
                                <strong><?= htmlspecialchars($registration['full_name']) ?></strong>

                                <?php if (!empty($registration['display_name'])): ?>
                                    <br>
                                    <span class="muted">
                                        <?= htmlspecialchars($registration['display_name']) ?>
                                    </span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($registration['email']) ?>

                                <?php if (!empty($registration['phone'])): ?>
                                    <br>
                                    <span class="muted"><?= htmlspecialchars($registration['phone']) ?></span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <span class="status-pill status-<?= htmlspecialchars($registration['registration_status']) ?>">
                                    <?= htmlspecialchars(adminRegLabel($registration['registration_status'])) ?>
                                </span>
                            </td>

                            <td>
                                <span class="status-pill status-<?= htmlspecialchars($registration['payment_status']) ?>">
                                    <?= htmlspecialchars(adminRegLabel($registration['payment_status'])) ?>
                                </span>
                                <br>
                                <span class="muted">
                                    <?= htmlspecialchars(adminRegLabel($registration['payment_method'])) ?>
                                </span>
                            </td>

                            <td>
                                <?= htmlspecialchars(adminRegDate($registration['registered_at'])) ?>
                            </td>
                            
                            <td>
                                <a class="btn btn-outline btn-sm" href="index.php?page=admin-registrations-edit&id=<?= (int) $registration['id'] ?>">
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