<?php
$heading = $heading ?? 'Venue Partner Requests';
$requests = $requests ?? [];

function adminVenueRequestLabel(?string $value): string
{
    return $value ? ucwords(str_replace('_', ' ', $value)) : '-';
}

function adminVenueRequestFieldLabel(string $field): string
{
    return ucwords(str_replace('_', ' ', $field));
}
?>

<section class="dashboard-header">
    <div>
        <div class="hero-kicker">Admin</div>
        <h1><?= htmlspecialchars($heading) ?></h1>
        <p>Review public venue-detail updates submitted by external Venue Partners.</p>
    </div>
</section>

<section class="card">
    <?php if (empty($requests)): ?>
        <p>No pending Venue Partner update requests.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Venue</th>
                        <th>Requested by</th>
                        <th>Requested changes</th>
                        <th>Review</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($requests as $request): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($request['venue_name'] ?? '-') ?></strong>
                                <br>
                                <span class="muted"><?= htmlspecialchars($request['created_at'] ?? '') ?></span>
                            </td>
                            <td><?= htmlspecialchars($request['requester_email'] ?? '-') ?></td>
                            <td>
                                <?php foreach (($request['requested_changes'] ?? []) as $field => $value): ?>
                                    <strong><?= htmlspecialchars(adminVenueRequestFieldLabel($field)) ?>:</strong>
                                    <?= nl2br(htmlspecialchars($value ?? '')) ?>
                                    <br>
                                <?php endforeach; ?>
                            </td>
                            <td>
                                <form method="POST" action="index.php?page=admin-venue-update-approve" class="inline-form">
                                    <input type="hidden" name="id" value="<?= (int) $request['id'] ?>">
                                    <input type="text" name="review_notes" placeholder="Optional notes">
                                    <button class="btn btn-sm" type="submit">Approve</button>
                                </form>

                                <form method="POST" action="index.php?page=admin-venue-update-reject" class="inline-form">
                                    <input type="hidden" name="id" value="<?= (int) $request['id'] ?>">
                                    <input type="text" name="review_notes" placeholder="Reason optional">
                                    <button class="btn btn-outline btn-sm" type="submit">Reject</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
