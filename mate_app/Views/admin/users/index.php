<?php
$heading = $heading ?? 'Manage Users';
$users = $users ?? [];
$roles = $roles ?? [];
$errors = $errors ?? [];
$old = $old ?? [];

function adminUserOld(array $old, string $key): string
{
    return htmlspecialchars($old[$key] ?? '');
}

function adminUserSelected($current, string $value): string
{
    return ((string) $current === $value) ? 'selected' : '';
}

function adminUserLabel(?string $value): string
{
    return $value ? ucwords(str_replace('_', ' ', $value)) : '-';
}
?>

<section class="dashboard-header">
    <div>
        <div class="hero-kicker">Admin</div>
        <h1><?= htmlspecialchars($heading) ?></h1>
        <p>Create staff accounts, assign roles, and manage account status.</p>
    </div>
</section>

<section class="card auth-card">
    <h2>Create User</h2>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <strong>User could not be created:</strong>
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" action="index.php?page=admin-users-store">
        <div class="form-row">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="<?= adminUserOld($old, 'email') ?>" required>
            </div>

            <div class="form-group">
                <label for="password">Temporary password</label>
                <input type="password" id="password" name="password" minlength="8" required>
            </div>
        </div>

        <div class="form-group">
            <label for="role">Role</label>
            <select id="role" name="role">
                <?php foreach ($roles as $role): ?>
                    <option value="<?= htmlspecialchars($role['name']) ?>" <?= adminUserSelected($old['role'] ?? 'player', $role['name']) ?>>
                        <?= htmlspecialchars(adminUserLabel($role['name'])) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="real_name">Player real name</label>
                <input type="text" id="real_name" name="real_name" value="<?= adminUserOld($old, 'real_name') ?>">
            </div>

            <div class="form-group">
                <label for="display_name">Player display name</label>
                <input type="text" id="display_name" name="display_name" value="<?= adminUserOld($old, 'display_name') ?>">
            </div>
        </div>

        <div class="form-group">
            <label for="phone">Player phone</label>
            <input type="text" id="phone" name="phone" value="<?= adminUserOld($old, 'phone') ?>">
        </div>

        <button class="btn" type="submit">Create User</button>
    </form>
</section>

<br>

<section class="card">
    <div class="section-header">
        <div>
            <h2>Accounts</h2>
            <p>Update role and account status for existing users.</p>
        </div>
    </div>

    <?php if (empty($users)): ?>
        <p>No users found.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Update</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?= htmlspecialchars($user['email']) ?></td>
                            <td><?= htmlspecialchars(adminUserLabel($user['role_name'])) ?></td>
                            <td>
                                <span class="status-pill status-<?= htmlspecialchars($user['status']) ?>">
                                    <?= htmlspecialchars(adminUserLabel($user['status'])) ?>
                                </span>
                            </td>
                            <td>
                                <form method="POST" action="index.php?page=admin-users-update" class="inline-form">
                                    <input type="hidden" name="id" value="<?= (int) $user['id'] ?>">
                                    <select name="role">
                                        <?php foreach ($roles as $role): ?>
                                            <option value="<?= htmlspecialchars($role['name']) ?>" <?= adminUserSelected($user['role_name'], $role['name']) ?>>
                                                <?= htmlspecialchars(adminUserLabel($role['name'])) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <select name="status">
                                        <?php foreach (['active', 'inactive', 'banned'] as $status): ?>
                                            <option value="<?= $status ?>" <?= adminUserSelected($user['status'], $status) ?>>
                                                <?= htmlspecialchars(adminUserLabel($status)) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button class="btn btn-sm" type="submit">Save</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
