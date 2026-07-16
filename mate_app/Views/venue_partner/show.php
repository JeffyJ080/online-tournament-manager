<?php
$heading = $heading ?? 'Venue Partner';
$venue = $venue ?? [];
$events = $events ?? [];
$pendingRequests = $pendingRequests ?? [];
$requestHistory = $requestHistory ?? [];
$errors = $errors ?? [];
$old = $old ?? $venue;
$success = $success ?? false;

function venuePartnerShowValue(array $old, string $key): string
{
    return htmlspecialchars($old[$key] ?? '');
}

function venuePartnerShowDate(?string $date): string
{
    return $date ? date('d M Y', strtotime($date)) : '-';
}

function venuePartnerShowLabel(?string $value): string
{
    return $value ? ucwords(str_replace('_', ' ', $value)) : '-';
}
?>

<section class="dashboard-header">
    <div>
        <div class="hero-kicker">Venue Partner</div>
        <h1><?= htmlspecialchars($heading) ?></h1>
        <p>Partner-safe venue details, event summaries, and public detail update requests.</p>
    </div>
</section>

<?php if ($success): ?>
    <div class="notice success">Your venue detail update request was sent for Mate approval.</div>
<?php endif; ?>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <strong>Request could not be submitted:</strong>
        <ul>
            <?php foreach ($errors as $error): ?>
                <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<section class="dashboard-grid">
    <div class="card stat-card">
        <strong><?= count(array_filter($events, static fn ($event): bool => strtotime($event['event_date'] ?? '') >= strtotime(date('Y-m-d')))) ?></strong>
        <span>Upcoming Events</span>
    </div>

    <div class="card stat-card">
        <strong><?= count($events) ?></strong>
        <span>Total Events Listed</span>
    </div>

    <div class="card stat-card">
        <strong><?= array_sum(array_map(static fn ($event): int => (int) ($event['active_registrations'] ?? 0), $events)) ?></strong>
        <span>Registrations Counted</span>
    </div>

    <div class="card stat-card">
        <strong><?= count($pendingRequests) ?></strong>
        <span>Pending Requests</span>
    </div>
</section>

<section class="card">
    <div class="section-header">
        <div>
            <h2>Current public details</h2>
            <p>These are the venue fields visible or used publicly by Mate.</p>
        </div>
    </div>

    <div class="event-meta">
        <div>
            <span>Contact person</span>
            <strong><?= htmlspecialchars($venue['contact_person'] ?? '-') ?></strong>
        </div>
        <div>
            <span>Email</span>
            <strong><?= htmlspecialchars($venue['contact_email'] ?? '-') ?></strong>
        </div>
        <div>
            <span>Phone</span>
            <strong><?= htmlspecialchars($venue['contact_phone'] ?? '-') ?></strong>
        </div>
        <div>
            <span>City</span>
            <strong><?= htmlspecialchars($venue['city'] ?? '-') ?></strong>
        </div>
    </div>

    <p><strong>Address:</strong> <?= htmlspecialchars($venue['address'] ?? '-') ?></p>
    <p><strong>Food deal / special:</strong> <?= nl2br(htmlspecialchars($venue['food_deal_description'] ?? '-')) ?></p>
</section>

<section class="card auth-card">
    <h2>Request public detail changes</h2>
    <p>Requests are reviewed by Mate before they are published.</p>

    <form method="POST" action="index.php?page=venue-partner-request-update">
        <input type="hidden" name="venue_id" value="<?= (int) ($venue['id'] ?? 0) ?>">

        <div class="form-row">
            <div class="form-group">
                <label for="contact_person">Contact person</label>
                <input type="text" id="contact_person" name="contact_person" value="<?= venuePartnerShowValue($old, 'contact_person') ?>">
            </div>

            <div class="form-group">
                <label for="contact_email">Contact email</label>
                <input type="email" id="contact_email" name="contact_email" value="<?= venuePartnerShowValue($old, 'contact_email') ?>">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="contact_phone">Contact phone</label>
                <input type="text" id="contact_phone" name="contact_phone" value="<?= venuePartnerShowValue($old, 'contact_phone') ?>">
            </div>

            <div class="form-group">
                <label for="city">City</label>
                <input type="text" id="city" name="city" value="<?= venuePartnerShowValue($old, 'city') ?>">
            </div>
        </div>

        <div class="form-group">
            <label for="address">Address</label>
            <input type="text" id="address" name="address" value="<?= venuePartnerShowValue($old, 'address') ?>">
        </div>

        <div class="form-group">
            <label for="food_deal_description">Food deal / special</label>
            <textarea id="food_deal_description" name="food_deal_description" rows="4"><?= venuePartnerShowValue($old, 'food_deal_description') ?></textarea>
        </div>

        <button class="btn" type="submit">Submit for Approval</button>
    </form>
</section>

<section class="card">
    <div class="section-header">
        <div>
            <h2>Venue event summaries</h2>
            <p>Counts and totals only. Player identities and payment details are not shown.</p>
        </div>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Event</th>
                    <th>Registrations</th>
                    <th>Attendance</th>
                    <th>Status</th>
                    <th>Revenue</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($events)): ?>
                    <tr>
                        <td colspan="6">No events found for this venue yet.</td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($events as $event): ?>
                    <tr>
                        <td><?= htmlspecialchars(venuePartnerShowDate($event['event_date'] ?? null)) ?></td>
                        <td><?= htmlspecialchars($event['title'] ?? '') ?></td>
                        <td><?= (int) ($event['active_registrations'] ?? 0) ?> / <?= (int) ($event['max_players'] ?? 0) ?></td>
                        <td><?= (int) max((int) ($event['attendance_count'] ?? 0), (int) ($event['checked_in_count'] ?? 0)) ?></td>
                        <td><?= htmlspecialchars(venuePartnerShowLabel($event['event_status'] ?? null)) ?></td>
                        <td>R<?= number_format((float) ($event['revenue_received'] ?? 0), 2) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="card">
    <h2>Request history</h2>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Venue</th>
                    <th>Status</th>
                    <th>Submitted</th>
                    <th>Review notes</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($requestHistory)): ?>
                    <tr>
                        <td colspan="4">No update requests submitted yet.</td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($requestHistory as $request): ?>
                    <tr>
                        <td><?= htmlspecialchars($request['venue_name'] ?? '-') ?></td>
                        <td><?= htmlspecialchars(venuePartnerShowLabel($request['status'] ?? null)) ?></td>
                        <td><?= htmlspecialchars($request['created_at'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($request['review_notes'] ?? '-') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
