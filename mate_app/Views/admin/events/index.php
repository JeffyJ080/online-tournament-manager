<?php
$heading = $heading ?? 'Manage Events';
$events = $events ?? [];

function adminEventDate(?string $date): string
{
    return $date ? date('d M Y', strtotime($date)) : '-';
}

function adminEventTime(?string $time): string
{
    return $time ? date('H:i', strtotime($time)) : '-';
}

function adminEventMoney($amount): string
{
    return 'R' . number_format((float) $amount, 0);
}

function adminEventLabel(?string $value): string
{
    return $value ? ucwords(str_replace('_', ' ', $value)) : '-';
}
?>

<section class="dashboard-header">
    <div>
        <div class="hero-kicker">Admin</div>
        <h1><?= htmlspecialchars($heading) ?></h1>
        <p>
            Create, review, and manage Mate Tournaments events.
        </p>
    </div>
</section>

<section class="card">
    <div class="section-header">
        <div>
            <h2>Events</h2>
            <p>Each row is one actual tournament date players can register for.</p>
        </div>

        <a class="btn" href="index.php?page=admin-events-create">Create Event</a>
    </div>

    <?php if (empty($events)): ?>
        <p>No events found yet.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Event</th>
                        <th>Venue</th>
                        <th>Date</th>
                        <th>Entry</th>
                        <th>Format</th>
                        <th>Registrations</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($events as $event): ?>
                        <?php
                        $activeCount = (int) ($event['active_registrations'] ?? 0);
                        $maxPlayers = (int) ($event['max_players'] ?? 0);
                        ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($event['title']) ?></strong>
                                <br>
                                <span class="muted">
                                    <?= htmlspecialchars($event['series_title'] ?? 'Once-off event') ?>
                                </span>
                            </td>

                            <td>
                                <?= htmlspecialchars($event['venue_name']) ?>
                                <?php if (!empty($event['venue_city'])): ?>
                                    <br>
                                    <span class="muted"><?= htmlspecialchars($event['venue_city']) ?></span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(adminEventDate($event['event_date'])) ?>
                                <br>
                                <span class="muted"><?= htmlspecialchars(adminEventTime($event['start_time'])) ?></span>
                            </td>

                            <td><?= htmlspecialchars(adminEventMoney($event['entry_fee'])) ?></td>

                            <td><?= htmlspecialchars(adminEventLabel($event['format'])) ?></td>

                            <td><?= $activeCount ?> / <?= $maxPlayers ?></td>

                            <td>
                                <span class="status-pill status-<?= htmlspecialchars($event['event_status']) ?>">
                                    <?= htmlspecialchars(adminEventLabel($event['event_status'])) ?>
                                </span>
                                <br>
                                <span class="muted">
                                    Reg: <?= htmlspecialchars(adminEventLabel($event['registration_status'])) ?>
                                </span>
                            </td>

                            <td>
                                <div class="table-actions">
                                    <a class="btn btn-outline btn-sm" href="index.php?page=event&slug=<?= urlencode($event['slug']) ?>">
                                        Public
                                    </a>

                                    <a class="btn btn-outline btn-sm" href="index.php?page=admin-events-edit&id=<?= (int) $event['id'] ?>">
                                        Edit
                                    </a>

                                    <a class="btn btn-outline btn-sm" href="index.php?page=admin-registrations">
                                        Registrations
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>