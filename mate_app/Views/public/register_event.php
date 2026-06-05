<?php
$heading = $heading ?? 'Register for Event';
$event = $event ?? [];
$errors = $errors ?? [];
$old = $old ?? [];
$spotsLeft = $spots_left ?? 0;
$activeRegistrations = $active_registrations ?? 0;

function regOld(array $old, string $key): string
{
    return htmlspecialchars($old[$key] ?? '');
}

function regSelected(array $old, string $key, string $value): string
{
    return (($old[$key] ?? '') === $value) ? 'selected' : '';
}

function regDate(string $date): string
{
    return date('D, d M Y', strtotime($date));
}

function regTime(string $time): string
{
    return date('H:i', strtotime($time));
}

function regMoney($amount): string
{
    return 'R' . number_format((float) $amount, 0);
}
?>

<section class="event-detail-hero">
    <div>
        <div class="hero-kicker">Seat Booking</div>

        <h1><?= htmlspecialchars($heading) ?></h1>

        <p>
            You are registering for
            <strong><?= htmlspecialchars($event['title'] ?? 'this event') ?></strong>.
            Fill in your details below to reserve your spot.
        </p>

        <?php if ($spotsLeft <= 0): ?>
            <div class="alert alert-danger">
                <strong>This event is full.</strong>
                <p>You may still be added to the waitlist later once waitlist handling is enabled.</p>
            </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <strong>Registration could not be completed:</strong>
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <section class="card auth-card no-margin">
            <form method="POST" action="index.php?page=register-event-submit">
                <input type="hidden" name="event_id" value="<?= (int) ($event['id'] ?? 0) ?>">

                <div class="form-group">
                    <label for="full_name">Full name</label>
                    <input
                        type="text"
                        id="full_name"
                        name="full_name"
                        value="<?= regOld($old, 'full_name') ?>"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="display_name">Display name optional</label>
                    <input
                        type="text"
                        id="display_name"
                        name="display_name"
                        value="<?= regOld($old, 'display_name') ?>"
                    >
                </div>

                <div class="form-group">
                    <label for="email">Email address</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="<?= regOld($old, 'email') ?>"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="phone">Phone number</label>
                    <input
                        type="text"
                        id="phone"
                        name="phone"
                        value="<?= regOld($old, 'phone') ?>"
                    >
                </div>

                <div class="form-group">
                    <label for="rating_category">Rating category</label>
                    <select id="rating_category" name="rating_category">
                        <option value="beginner" <?= regSelected($old, 'rating_category', 'beginner') ?>>Beginner - 800</option>
                        <option value="casual" <?= regSelected($old, 'rating_category', 'casual') ?>>Casual - 1000</option>
                        <option value="standard" <?= regSelected($old, 'rating_category', 'standard') ?>>Standard / Club - 1200</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="payment_method">Payment method</label>
                    <select id="payment_method" name="payment_method">
                        <option value="cash" <?= regSelected($old, 'payment_method', 'cash') ?>>Cash at event</option>
                        <option value="eft" <?= regSelected($old, 'payment_method', 'eft') ?>>EFT</option>
                    </select>
                </div>

                <div class="hero-actions">
                    <button class="btn" type="submit" <?= $spotsLeft <= 0 ? 'disabled' : '' ?>>
                        Reserve Spot
                    </button>

                    <a class="btn btn-outline" href="index.php?page=event&slug=<?= urlencode($event['slug'] ?? '') ?>">
                        Back to Event
                    </a>
                </div>
            </form>
        </section>
    </div>

    <aside class="card event-summary-card">
        <h2>Event Summary</h2>

        <div class="summary-list">
            <div>
                <span>Date</span>
                <strong><?= htmlspecialchars(regDate($event['event_date'])) ?></strong>
            </div>

            <div>
                <span>Time</span>
                <strong><?= htmlspecialchars(regTime($event['start_time'])) ?></strong>
            </div>

            <div>
                <span>Venue</span>
                <strong><?= htmlspecialchars($event['venue_name'] ?? '-') ?></strong>
            </div>

            <div>
                <span>Entry</span>
                <strong><?= htmlspecialchars(regMoney($event['entry_fee'] ?? 0)) ?></strong>
            </div>

            <div>
                <span>Spots Left</span>
                <strong><?= (int) $spotsLeft ?> / <?= (int) ($event['max_players'] ?? 0) ?></strong>
            </div>

            <div>
                <span>Already Registered</span>
                <strong><?= (int) $activeRegistrations ?></strong>
            </div>
        </div>
    </aside>
</section>