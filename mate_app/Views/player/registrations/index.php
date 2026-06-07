<?php
$heading = $heading ?? 'My Registrations';
$registrations = $registrations ?? [];

function playerRegDate(?string $date): string
{
    return $date ? date('d M Y', strtotime($date)) : '-';
}

function playerRegTime(?string $time): string
{
    return $time ? date('H:i', strtotime($time)) : '-';
}

function playerRegLabel(?string $value): string
{
    return $value ? ucwords(str_replace('_', ' ', $value)) : '-';
}

function playerRegMoney($amount): string
{
    return 'R' . number_format((float) $amount, 0);
}
?>

<section class="dashboard-header">
    <div>
        <div class="hero-kicker">Player Area</div>
        <h1><?= htmlspecialchars($heading) ?></h1>
        <p>
            View your event bookings, payment status, and upload proof of payment when needed.
        </p>
    </div>
</section>

<section class="card">
    <div class="section-header">
        <div>
            <h2>Your Event Registrations</h2>
            <p>Use this page to track your bookings and payment progress.</p>
        </div>

        <a class="btn" href="index.php?page=events">Find Events</a>
    </div>

    <?php if (empty($registrations)): ?>
        <p>You have not registered for any events yet.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Event</th>
                        <th>Registration</th>
                        <th>Payment</th>
                        <th>Proof</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($registrations as $registration): ?>
                        <?php
                        $paymentStatus = $registration['payment_record_status'] ?? $registration['payment_status'];
                        $paymentMethod = $registration['payment_record_method'] ?? $registration['payment_method'];
                        $needsProof = $paymentMethod === 'eft' && !in_array($paymentStatus, ['verified', 'paid_eft', 'comped'], true);
                        ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($registration['event_title']) ?></strong>
                                <br>
                                <span class="muted">
                                    <?= htmlspecialchars($registration['venue_name']) ?>
                                    ·
                                    <?= htmlspecialchars(playerRegDate($registration['event_date'])) ?>
                                    <?= htmlspecialchars(playerRegTime($registration['start_time'])) ?>
                                </span>
                            </td>

                            <td>
                                <span class="status-pill status-<?= htmlspecialchars($registration['registration_status']) ?>">
                                    <?= htmlspecialchars(playerRegLabel($registration['registration_status'])) ?>
                                </span>
                            </td>

                            <td>
                                <span class="status-pill status-<?= htmlspecialchars($paymentStatus) ?>">
                                    <?= htmlspecialchars(playerRegLabel($paymentStatus)) ?>
                                </span>
                                <br>
                                <span class="muted">
                                    <?= htmlspecialchars(playerRegLabel($paymentMethod)) ?>
                                    ·
                                    <?= htmlspecialchars(playerRegMoney($registration['amount'] ?? 0)) ?>
                                </span>
                            </td>

                            <td>
                                <?php if (!empty($registration['proof_id'])): ?>
                                    <span class="status-pill status-<?= htmlspecialchars($registration['proof_status']) ?>">
                                        <?= htmlspecialchars(playerRegLabel($registration['proof_status'])) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="muted">No proof uploaded</span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <div class="table-actions">
                                    <a class="btn btn-outline btn-sm" href="index.php?page=event&slug=<?= urlencode($registration['event_slug']) ?>">
                                        Event
                                    </a>

                                    <?php if ($needsProof): ?>
                                        <a class="btn btn-sm" href="index.php?page=upload-proof&registration=<?= (int) $registration['id'] ?>">
                                            Upload POP
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>