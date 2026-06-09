<?php
$heading = $heading ?? 'Submit Event Report';
$event = $event ?? [];
$errors = $errors ?? [];
$old = $old ?? [];

function reportOld(array $old, string $key): string
{
    return htmlspecialchars($old[$key] ?? '');
}
?>

<section class="dashboard-header">
    <div>
        <div class="hero-kicker">Host Report</div>
        <h1><?= htmlspecialchars($heading) ?></h1>
        <p>
            Submit the end-of-event summary for
            <strong><?= htmlspecialchars($event['title'] ?? 'this event') ?></strong>.
        </p>
    </div>
</section>

<section class="card auth-card">
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <strong>Report could not be submitted:</strong>
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" action="index.php?page=host-event-report-store">
        <input type="hidden" name="event_id" value="<?= (int) ($event['id'] ?? 0) ?>">

        <div class="form-group">
            <label for="attendance_count">Attendance count</label>
            <input
                type="number"
                min="0"
                id="attendance_count"
                name="attendance_count"
                value="<?= reportOld($old, 'attendance_count') ?: '0' ?>"
            >
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="cash_collected">Cash collected</label>
                <input
                    type="number"
                    min="0"
                    step="0.01"
                    id="cash_collected"
                    name="cash_collected"
                    value="<?= reportOld($old, 'cash_collected') ?: '0' ?>"
                >
            </div>

            <div class="form-group">
                <label for="cash_handed_over">Cash handed over</label>
                <input
                    type="number"
                    min="0"
                    step="0.01"
                    id="cash_handed_over"
                    name="cash_handed_over"
                    value="<?= reportOld($old, 'cash_handed_over') ?: '0' ?>"
                >
            </div>
        </div>

        <div class="form-group">
            <label for="winner_notes">Winner / result notes</label>
            <textarea id="winner_notes" name="winner_notes" rows="3"><?= reportOld($old, 'winner_notes') ?></textarea>
        </div>

        <div class="form-group">
            <label for="issue_notes">Issues / incidents</label>
            <textarea id="issue_notes" name="issue_notes" rows="3"><?= reportOld($old, 'issue_notes') ?></textarea>
        </div>

        <div class="form-group">
            <label for="general_notes">General notes</label>
            <textarea id="general_notes" name="general_notes" rows="4"><?= reportOld($old, 'general_notes') ?></textarea>
        </div>

        <div class="hero-actions">
            <button class="btn" type="submit">Submit Report</button>
            <a class="btn btn-outline" href="index.php?page=host-event-registrations&id=<?= (int) ($event['id'] ?? 0) ?>">
                Cancel
            </a>
        </div>
    </form>
</section>