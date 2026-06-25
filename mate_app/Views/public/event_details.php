<?php
$heading = $heading ?? 'Event Details';
$event = $event ?? [];
$activeCount = (int) ($event['active_registrations'] ?? 0);
$spotsLeft = max(0, (int) ($event['max_players'] ?? 0) - $activeCount);

function eventDetailDate(string $date): string
{
    return date('D, d M Y', strtotime($date));
}

function eventDetailTime(?string $time): string
{
    return $time ? date('H:i', strtotime($time)) : '-';
}

function eventDetailMoney($amount): string
{
    return 'R' . number_format((float) $amount, 0);
}

function eventDetailLabel(string $value): string
{
    return ucwords(str_replace('_', ' ', $value));
}
?>

<section class="event-detail-hero">
    <div>
        <div class="hero-kicker">
            <?= htmlspecialchars(eventDetailLabel($event['format'] ?? 'event')) ?>
        </div>

        <h1><?= htmlspecialchars($heading) ?></h1>

        <p>
            <?= htmlspecialchars($event['description'] ?? 'Chess tournament hosted by Mate Tournaments.') ?>
        </p>

        <div class="hero-actions">
            <a class="btn" href="index.php?page=register-event&event=<?= (int) $event['id'] ?>">
                Register for Event
            </a>

            <a class="btn btn-outline" href="index.php?page=live-tournament&event=<?= (int) $event['id'] ?>">
                Live View
            </a>

            <a class="btn btn-outline" href="index.php?page=events">
                Back to Events
            </a>
        </div>
    </div>

    <aside class="card event-summary-card">
        <h2>Event Summary</h2>

        <div class="summary-list">
            <div>
                <span>Date</span>
                <strong><?= htmlspecialchars(eventDetailDate($event['event_date'])) ?></strong>
            </div>

            <div>
                <span>Start Time</span>
                <strong><?= htmlspecialchars(eventDetailTime($event['start_time'])) ?></strong>
            </div>

            <div>
                <span>End Time</span>
                <strong><?= htmlspecialchars(eventDetailTime($event['end_time'] ?? null)) ?></strong>
            </div>

            <div>
                <span>Venue</span>
                <strong>
                    <?= htmlspecialchars($event['venue_name'] ?? '-') ?>
                    <?php if (!empty($event['venue_city'])): ?>
                        — <?= htmlspecialchars($event['venue_city']) ?>
                    <?php endif; ?>
                </strong>
            </div>

            <div>
                <span>Entry Fee</span>
                <strong><?= htmlspecialchars(eventDetailMoney($event['entry_fee'] ?? 0)) ?></strong>
            </div>

            <div>
                <span>Prize Info</span>
                <strong><?= htmlspecialchars($event['prize_info'] ?? '-') ?></strong>
            </div>

            <div>
                <span>Registered</span>
                <strong><?= $activeCount ?> / <?= (int) ($event['max_players'] ?? 0) ?></strong>
            </div>

            <div>
                <span>Spots Left</span>
                <strong><?= $spotsLeft ?></strong>
            </div>

            <div>
                <span>Registration</span>
                <strong><?= htmlspecialchars(eventDetailLabel($event['registration_status'] ?? 'closed')) ?></strong>
            </div>
        </div>
    </aside>
</section>

<section class="card">
    <h2>Rules & Notes</h2>

    <?php if (!empty($event['notes'])): ?>
        <p><?= nl2br(htmlspecialchars($event['notes'])) ?></p>
    <?php else: ?>
        <p>
            Final rules and tournament details will be confirmed by the event host.
            Please arrive before the start time so check-in can run smoothly.
        </p>
    <?php endif; ?>
</section>
