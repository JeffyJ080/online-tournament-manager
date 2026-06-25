<?php
$heading = $heading ?? 'Add Venue';
$errors = $errors ?? [];
$old = $old ?? [];
$venueManagers = $venueManagers ?? [];

function oldVenueValue(array $old, string $key): string
{
    return htmlspecialchars($old[$key] ?? '');
}

function selectedVenueValue(array $old, string $key, string $value): string
{
    return ((string) ($old[$key] ?? 'active') === $value) ? 'selected' : '';
}
?>

<section class="dashboard-header">
    <div>
        <div class="hero-kicker">Admin</div>
        <h1><?= htmlspecialchars($heading) ?></h1>
        <p>Add a restaurant, campus, or venue where Mate Tournaments can host events.</p>
    </div>
</section>

<section class="card auth-card">
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <strong>Venue could not be saved:</strong>
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" action="index.php?page=admin-venues-store">
        <div class="form-group">
            <label for="name">Venue name</label>
            <input
                type="text"
                id="name"
                name="name"
                value="<?= oldVenueValue($old, 'name') ?>"
                required
            >
        </div>

        <div class="form-group">
            <label for="address">Address</label>
            <input
                type="text"
                id="address"
                name="address"
                value="<?= oldVenueValue($old, 'address') ?>"
            >
        </div>

        <div class="form-group">
            <label for="city">City / Area</label>
            <input
                type="text"
                id="city"
                name="city"
                value="<?= oldVenueValue($old, 'city') ?>"
            >
        </div>

        <div class="form-group">
            <label for="contact_person">Contact person</label>
            <input
                type="text"
                id="contact_person"
                name="contact_person"
                value="<?= oldVenueValue($old, 'contact_person') ?>"
            >
        </div>

        <div class="form-group">
            <label for="contact_email">Contact email</label>
            <input
                type="email"
                id="contact_email"
                name="contact_email"
                value="<?= oldVenueValue($old, 'contact_email') ?>"
            >
        </div>

        <div class="form-group">
            <label for="contact_phone">Contact phone</label>
            <input
                type="text"
                id="contact_phone"
                name="contact_phone"
                value="<?= oldVenueValue($old, 'contact_phone') ?>"
            >
        </div>

        <div class="form-group">
            <label for="venue_manager_user_id">Venue manager account</label>
            <select id="venue_manager_user_id" name="venue_manager_user_id">
                <option value="">No manager assigned</option>
                <?php foreach ($venueManagers as $manager): ?>
                    <option value="<?= (int) $manager['id'] ?>" <?= selectedVenueValue($old, 'venue_manager_user_id', (string) $manager['id']) ?>>
                        <?= htmlspecialchars($manager['email']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="food_deal_description">Food/drink deal</label>
            <textarea
                id="food_deal_description"
                name="food_deal_description"
                rows="3"
            ><?= oldVenueValue($old, 'food_deal_description') ?></textarea>
        </div>

        <div class="form-group">
            <label for="notes">Internal notes</label>
            <textarea
                id="notes"
                name="notes"
                rows="4"
            ><?= oldVenueValue($old, 'notes') ?></textarea>
        </div>

        <div class="form-group">
            <label for="status">Status</label>
            <select id="status" name="status">
                <option value="active" <?= selectedVenueValue($old, 'status', 'active') ?>>Active</option>
                <option value="inactive" <?= selectedVenueValue($old, 'status', 'inactive') ?>>Inactive</option>
            </select>
        </div>

        <div class="hero-actions">
            <button class="btn" type="submit">Save Venue</button>
            <a class="btn btn-outline" href="index.php?page=admin-venues">Cancel</a>
        </div>
    </form>
</section>
