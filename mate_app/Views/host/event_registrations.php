<?php
$heading = $heading ?? 'Event Registrations';
$event = $event ?? [];
$registrations = $registrations ?? [];
$errors = $errors ?? [];
$walkInOld = $walkInOld ?? [];
$eventClosed = in_array(($event['event_status'] ?? ''), ['completed', 'cancelled'], true);

function walkOld(array $old, string $key): string
{
    return htmlspecialchars($old[$key] ?? '');
}

function walkSelected(array $old, string $key, string $value): string
{
    return (($old[$key] ?? '') === $value) ? 'selected' : '';
}

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
            <?= $eventClosed ? 'Review registrations for a completed or closed event.' : 'Check players in and monitor registrations for this event.' ?>
        </p>
    </div>
</section>

<?php if ($eventClosed): ?>
    <section class="card">
        <h2>Review Mode</h2>
        <p>This event is completed or closed, so walk-ins and check-ins are locked.</p>
    </section>

    <br/>
<?php else: ?>
<section class="card">
    <div class="section-header">
        <div>
            <h2>Add Walk-in Player</h2>
            <p>Add a player who joins at the venue on event night.</p>
        </div>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <strong>Walk-in could not be added:</strong>
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" action="index.php?page=host-walk-in">
        <input type="hidden" name="event_id" value="<?= (int) ($event['id'] ?? 0) ?>">

        <div class="form-row">
            <div class="form-group">
                <label for="full_name">Full name</label>
                <input
                    type="text"
                    id="full_name"
                    name="full_name"
                    value="<?= walkOld($walkInOld, 'full_name') ?>"
                    required
                >
            </div>

            <div class="form-group">
                <label for="display_name">Display name optional</label>
                <input
                    type="text"
                    id="display_name"
                    name="display_name"
                    value="<?= walkOld($walkInOld, 'display_name') ?>"
                >
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="email">Email optional</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    value="<?= walkOld($walkInOld, 'email') ?>"
                >
            </div>

            <div class="form-group">
                <label for="phone">Phone optional</label>
                <input
                    type="text"
                    id="phone"
                    name="phone"
                    value="<?= walkOld($walkInOld, 'phone') ?>"
                >
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="rating_category">Rating category</label>
                <select id="rating_category" name="rating_category">
                    <option value="beginner" <?= walkSelected($walkInOld, 'rating_category', 'beginner') ?>>Beginner - 800</option>
                    <option value="casual" <?= walkSelected($walkInOld, 'rating_category', 'casual') ?>>Casual - 1000</option>
                    <option value="standard" <?= walkSelected($walkInOld, 'rating_category', 'standard') ?>>Standard / Club - 1200</option>
                </select>
            </div>

            <div class="form-group">
                <label for="payment_method">Payment method</label>
                <select id="payment_method" name="payment_method">
                    <option value="cash" <?= walkSelected($walkInOld, 'payment_method', 'cash') ?>>Cash</option>
                    <option value="eft" <?= walkSelected($walkInOld, 'payment_method', 'eft') ?>>EFT</option>
                    <option value="comped" <?= walkSelected($walkInOld, 'payment_method', 'comped') ?>>Comped</option>
                    <option value="other" <?= walkSelected($walkInOld, 'payment_method', 'other') ?>>Other</option>
                </select>
            </div>

            <div class="form-group">
                <label for="payment_status">Payment status</label>
                <select id="payment_status" name="payment_status">
                    <option value="unpaid" <?= walkSelected($walkInOld, 'payment_status', 'unpaid') ?>>Unpaid</option>
                    <option value="paid_cash" <?= walkSelected($walkInOld, 'payment_status', 'paid_cash') ?>>Paid Cash</option>
                    <option value="paid_eft" <?= walkSelected($walkInOld, 'payment_status', 'paid_eft') ?>>Paid EFT</option>
                    <option value="verified" <?= walkSelected($walkInOld, 'payment_status', 'verified') ?>>Verified</option>
                    <option value="comped" <?= walkSelected($walkInOld, 'payment_status', 'comped') ?>>Comped</option>
                </select>
            </div>
        </div>

        <div class="hero-actions">
            <button class="btn" type="submit">Add Walk-in</button>
        </div>
    </form>
</section>

<br/>
<?php endif; ?>

<section class="card">
    <div class="section-header">
        <div>
            <h2>Registrations</h2>
            <p>
                <?= count($registrations) ?> total registrations
            </p>
        </div>

        <a class="btn" href="index.php?page=tournament-manager&id=<?= (int) ($event['id'] ?? 0) ?>">
            <?= $eventClosed ? 'View Tournament' : 'Tournament Manager' ?>
        </a>

        <a class="btn btn-outline" href="index.php?page=live-display&event=<?= (int) ($event['id'] ?? 0) ?>" target="_blank">
            Second Screen
        </a>

        <a class="btn" href="index.php?page=host-event-report&id=<?= (int) ($event['id'] ?? 0) ?>">
            Submit Report
        </a>

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
                                <?php if (!$eventClosed && ($registration['registration_status'] ?? '') !== 'checked_in'): ?>
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
