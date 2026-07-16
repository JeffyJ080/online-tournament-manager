<?php
$heading = $heading ?? 'Payment Proofs';
$proofs = $proofs ?? [];

function proofDate(?string $date): string
{
    return $date ? date('d M Y H:i', strtotime($date)) : '-';
}

function proofLabel(?string $value): string
{
    return $value ? ucwords(str_replace('_', ' ', $value)) : '-';
}

function proofMoney($amount): string
{
    return 'R' . number_format((float) $amount, 0);
}
?>

<section class="dashboard-header">
    <div>
        <div class="hero-kicker">Admin</div>
        <h1><?= htmlspecialchars($heading) ?></h1>
        <p>
            Review EFT proof uploads and verify or reject payments.
        </p>
    </div>
</section>

<section class="card">
    <div class="section-header">
        <div>
            <h2>Uploaded Proofs</h2>
            <p>Newest uploads appear first.</p>
        </div>
        <a class="btn btn-outline" href="index.php?page=export-payment-proofs">Export CSV</a>
    </div>

    <?php if (empty($proofs)): ?>
        <p>No payment proofs uploaded yet.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Event</th>
                        <th>Player</th>
                        <th>Amount</th>
                        <th>Proof</th>
                        <th>Status</th>
                        <th>Uploaded</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($proofs as $proof): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($proof['event_title']) ?></strong>
                                <br>
                                <span class="muted"><?= htmlspecialchars($proof['venue_name']) ?></span>
                            </td>

                            <td>
                                <strong><?= htmlspecialchars($proof['full_name']) ?></strong>
                                <br>
                                <span class="muted"><?= htmlspecialchars($proof['email']) ?></span>
                            </td>

                            <td>
                                <?= htmlspecialchars(proofMoney($proof['amount'])) ?>
                                <br>
                                <span class="muted"><?= htmlspecialchars(proofLabel($proof['payment_method'])) ?></span>
                            </td>

                            <td>
                                <a class="btn btn-outline btn-sm" href="<?= htmlspecialchars($proof['file_path']) ?>" target="_blank">
                                    Open File
                                </a>
                            </td>

                            <td>
                                <span class="status-pill status-<?= htmlspecialchars($proof['status']) ?>">
                                    <?= htmlspecialchars(proofLabel($proof['status'])) ?>
                                </span>
                                <br>
                                <span class="muted">
                                    Payment: <?= htmlspecialchars(proofLabel($proof['payment_status'])) ?>
                                </span>
                            </td>

                            <td>
                                <?= htmlspecialchars(proofDate($proof['created_at'])) ?>
                            </td>

                            <td>
                                <a class="btn btn-outline btn-sm" href="index.php?page=admin-payment-proofs-review&id=<?= (int) $proof['id'] ?>">
                                    Review
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
