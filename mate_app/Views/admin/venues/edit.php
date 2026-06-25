<?php
$heading = $heading ?? 'Edit Venue';
$errors = $errors ?? [];
$old = $old ?? [];
$venueManagers = $venueManagers ?? [];

function oldVenueEditValue(array $old, string $key): string
{
    return htmlspecialchars($old[$key] ?? '');
}

function selectedVenueEditValue(array $old, string $key, string $value): string
{
    return ((string) ($old[$key] ?? 'active') === $value) ? 'selected' : '';
}
?>

<section class="dashboard-header">
    <div>
        <div class="hero-kicker">Admin</div>
        <h1><?= htmlspecialchars($heading) ?></h1>
        <p>
            Update venue details. The venue slug stays unchanged so future public links remain stable.
        </p>
    </div>
</section>

<section class="card auth-card">
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <strong>Venue could not be updated:</strong>
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" action="index.php?page=admin-venues-update">
        <input type="hidden" name="id" value="<?= oldVenueEditValue($old, 'id') ?>">

        <div class="form-group">
            <label for="name">Venue name</label>
            <input
                type="text"
                id="name"
                name="name"
                value="<?= oldVenueEditValue($old, 'name') ?>"
                required
            >
        </div>

        <div class="form-group">
            <label for="slug">Slug</label>
            <input
                type="text"
                id="slug"
                value="<?= oldVenueEditValue($old, 'slug') ?>"
                disabled
            >
        </div>

        <div class="form-group">
            <label for="address">Address</label>
            <input
                type="text"
                id="address"
                name="address"
                value="<?= oldVenueEditValue($old, 'address') ?>"
            >
        </div>

        <div class="form-group">
            <label for="city">City / Area</label>
            <input
                type="text"
                id="city"
                name="city"
                value="<?= oldVenueEditValue($old, 'city') ?>"
            >
        </div>

        <div class="form-group">
            <label for="contact_person">Contact person</label>
            <input
                type="text"
                id="contact_person"
                name="contact_person"
                value="<?= oldVenueEditValue($old, 'contact_person') ?>"
            >
        </div>

        <div class="form-group">
            <label for="contact_email">Contact email</label>
            <input
                type="email"
                id="contact_email"
                name="contact_email"
                value="<?= oldVenueEditValue($old, 'contact_email') ?>"
            >
        </div>

        <div class="form-group">
            <label for="contact_phone">Contact phone</label>
            <input
                type="text"
                id="contact_phone"
                name="contact_phone"
                value="<?= oldVenueEditValue($old, 'contact_phone') ?>"
            >
        </div>

        <div class="form-group">
            <label for="venue_manager_user_id">Venue manager account</label>
            <select id="venue_manager_user_id" name="venue_manager_user_id">
                <option value="">No manager assigned</option>
                <?php foreach ($venueManagers as $manager): ?>
                    <option value="<?= (int) $manager['id'] ?>" <?= selectedVenueEditValue($old, 'venue_manager_user_id', (string) $manager['id']) ?>>
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
            ><?= oldVenueEditValue($old, 'food_deal_description') ?></textarea>
        </div>

        <div class="form-group">
            <label for="notes">Internal notes</label>
            <textarea
                id="notes"
                name="notes"
                rows="4"
            ><?= oldVenueEditValue($old, 'notes') ?></textarea>
        </div>

        <div class="form-group">
            <label for="status">Status</label>
            <select id="status" name="status">
                <option value="active" <?= selectedVenueEditValue($old, 'status', 'active') ?>>Active</option>
                <option value="inactive" <?= selectedVenueEditValue($old, 'status', 'inactive') ?>>Inactive</option>
            </select>
        </div>

        <div class="hero-actions">
            <button class="btn" type="submit">Update Venue</button>
            <a class="btn btn-outline" href="index.php?page=admin-venues">Cancel</a>
        </div>
    </form>
</section>
