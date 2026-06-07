<?php
$heading = $heading ?? 'Event Registrations';
$event = $event ?? [];
$registrations = $registrations ?? [];

function hostRegLabel(?string $value): string
{
    return $value ? ucwords(str_replace('_', ' ', $value)) : '-';
}
?>

<section class="dashboard-header">
    <div>
        <div class="hero-kicker">Host Operations</div>
        <h1><?= htmlspecialchars($event['title'] ?? $heading) ?></h1>
        <p>
            Check players in and monitor registrations for this event.
        </p>
    </div>
</section>

<section class="card">
    <div class="section-header">
        <div>
            <h2>Registrations</h2>
            <p>
                <?= count($registrations) ?> total registrations
            </p>
        </div>

        <a class="btn btn-outline" href="index.php?page=host-events">
            Back
        </a>
    </div>

    <?php if (empty($registrations)): ?>
        <p>No registrations found.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Player</th>
                        <th>Rating</th>
                        <th>Registration</th>
                        <th>Payment</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($registrations as $registration): ?>
                        <?php
                        $paymentStatus = $registration['payment_record_status'] ?? $registration['payment_status'];
                        ?>
                        <tr>
                            <td>
                                <strong>
                                    <?= htmlspecialchars($registration['display_name'] ?: $registration['full_name']) ?>
                                </strong>

                                <br>

                                <span class="muted">
                                    <?= htmlspecialchars($registration['email']) ?>
                                </span>
                            </td>

                            <td>
                                <?= htmlspecialchars(hostRegLabel($registration['rating_category'])) ?>
                            </td>

                            <td>
                                <span class="status-pill status-<?= htmlspecialchars($registration['registration_status']) ?>">
                                    <?= htmlspecialchars(hostRegLabel($registration['registration_status'])) ?>
                                </span>
                            </td>

                            <td>
                                <span class="status-pill status-<?= htmlspecialchars($paymentStatus) ?>">
                                    <?= htmlspecialchars(hostRegLabel($paymentStatus)) ?>
                                </span>

                                <br>

                                <span class="muted">
                                    <?= htmlspecialchars(hostRegLabel($registration['payment_record_method'] ?? $registration['payment_method'])) ?>
                                </span>
                            </td>

                            <td>
                                <?php if (($registration['registration_status'] ?? '') !== 'checked_in'): ?>
                                    <form method="POST" action="index.php?page=host-check-in">
                                        <input type="hidden" name="registration_id" value="<?= (int) $registration['id'] ?>">
                                        <input type="hidden" name="event_id" value="<?= (int) $event['id'] ?>">

                                        <button class="btn btn-sm" type="submit">
                                            Check In
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span class="status-pill status-approved">
                                        Checked In
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>