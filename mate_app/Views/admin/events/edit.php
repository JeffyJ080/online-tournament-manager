<?php
$heading = $heading ?? 'Edit Event';
$venues = $venues ?? [];
$seriesList = $seriesList ?? [];
$errors = $errors ?? [];
$old = $old ?? [];

function oldEventValue(array $old, string $key): string
{
    return htmlspecialchars($old[$key] ?? '');
}

function selectedEventValue(array $old, string $key, string $value): string
{
    return ((string) ($old[$key] ?? '') === (string) $value) ? 'selected' : '';
}

function eventOptionLabel(string $value): string
{
    return ucwords(str_replace('_', ' ', $value));
}
?>

<section class="dashboard-header">
    <div>
        <div class="hero-kicker">Admin</div>
        <h1><?= htmlspecialchars($heading) ?></h1>
        <p>Create a once-off event or link it to a recurring event series.</p>
    </div>
</section>

<section class="card auth-card">
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <strong>Event could not be created:</strong>
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" action="index.php?page=admin-events-update">
        <input type="hidden" name="id" value="<?= (int) ($old['id'] ?? 0) ?>">

        <div class="form-group">
            <label for="series_id">Event series optional</label>
            <select id="series_id" name="series_id">
                <option value="">None / once-off event</option>
                <?php foreach ($seriesList as $series): ?>
                    <option value="<?= (int) $series['id'] ?>" <?= selectedEventValue($old, 'series_id', (string) $series['id']) ?>>
                        <?= htmlspecialchars($series['title']) ?> — <?= htmlspecialchars($series['venue_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="venue_id">Venue</label>
            <select id="venue_id" name="venue_id" required>
                <option value="">Select venue</option>
                <?php foreach ($venues as $venue): ?>
                    <option value="<?= (int) $venue['id'] ?>" <?= selectedEventValue($old, 'venue_id', (string) $venue['id']) ?>>
                        <?= htmlspecialchars($venue['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="title">Event title</label>
            <input type="text" id="title" name="title" value="<?= oldEventValue($old, 'title') ?>" required>
        </div>

        <div class="form-group">
            <label for="description">Description</label>
            <textarea id="description" name="description" rows="4"><?= oldEventValue($old, 'description') ?></textarea>
        </div>

        <div class="form-group">
            <label for="event_date">Event date</label>
            <input type="date" id="event_date" name="event_date" value="<?= oldEventValue($old, 'event_date') ?>" required>
        </div>

        <div class="form-group">
            <label for="start_time">Start time</label>
            <input type="time" id="start_time" name="start_time" value="<?= oldEventValue($old, 'start_time') ?>" required>
        </div>

        <div class="form-group">
            <label for="end_time">End time optional</label>
            <input type="time" id="end_time" name="end_time" value="<?= oldEventValue($old, 'end_time') ?>">
        </div>

        <div class="form-group">
            <label for="entry_fee">Entry fee</label>
            <input type="number" step="0.01" min="0" id="entry_fee" name="entry_fee" value="<?= oldEventValue($old, 'entry_fee') ?: '0' ?>">
        </div>

        <div class="form-group">
            <label for="prize_info">Prize info</label>
            <input type="text" id="prize_info" name="prize_info" value="<?= oldEventValue($old, 'prize_info') ?>">
        </div>

        <div class="form-group">
            <label for="format">Format</label>
            <select id="format" name="format">
                <?php foreach (['knockout', 'swiss', 'round_robin', 'weekly_points', 'team', 'doubles'] as $format): ?>
                    <option value="<?= htmlspecialchars($format) ?>" <?= selectedEventValue($old, 'format', $format) ?>>
                        <?= htmlspecialchars(eventOptionLabel($format)) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="max_players">Max players</label>
            <input type="number" min="1" id="max_players" name="max_players" value="<?= oldEventValue($old, 'max_players') ?: '32' ?>">
        </div>

        <div class="form-group">
            <label for="registration_status">Registration status</label>
            <select id="registration_status" name="registration_status">
                <?php foreach (['open', 'closed', 'full', 'cancelled'] as $status): ?>
                    <option value="<?= htmlspecialchars($status) ?>" <?= selectedEventValue($old, 'registration_status', $status) ?>>
                        <?= htmlspecialchars(eventOptionLabel($status)) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="event_status">Event status</label>
            <select id="event_status" name="event_status">
                <?php foreach (['draft', 'published', 'running', 'completed', 'cancelled'] as $status): ?>
                    <option value="<?= htmlspecialchars($status) ?>" <?= selectedEventValue($old, 'event_status', $status) ?>>
                        <?= htmlspecialchars(eventOptionLabel($status)) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="notes">Internal notes</label>
            <textarea id="notes" name="notes" rows="4"><?= oldEventValue($old, 'notes') ?></textarea>
        </div>

        <div class="hero-actions">
            <button class="btn" type="submit">Update Event</button>
            <a class="btn btn-outline" href="index.php?page=admin-events">Cancel</a>
        </div>
    </form>
</section>