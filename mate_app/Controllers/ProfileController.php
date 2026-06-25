<?php

require_once __DIR__ . '/../Core/Controller.php';
require_once __DIR__ . '/../Helpers/Auth.php';
require_once __DIR__ . '/../Middleware/RequireAuth.php';
require_once __DIR__ . '/../Models/User.php';
require_once __DIR__ . '/../Models/Player.php';
require_once __DIR__ . '/../Models/EventRegistration.php';
require_once __DIR__ . '/../Models/AuditLog.php';

class ProfileController extends Controller
{
    public function show(): void
    {
        RequireAuth::check();

        $userModel = new User();
        $playerModel = new Player();
        $registrationModel = new EventRegistration();

        $user = $userModel->findById(Auth::id());
        $player = $playerModel->findByUserId(Auth::id());
        $registrations = Auth::role() === 'player' ? $registrationModel->forUser(Auth::id()) : [];

        $this->view('profile/show', [
            'title' => 'My Profile',
            'heading' => 'My Profile',
            'user' => $user,
            'player' => $player,
            'registrations' => array_slice($registrations, 0, 5),
            'profileErrors' => [],
            'passwordErrors' => [],
            'success' => $_SESSION['profile_success'] ?? null,
        ]);

        unset($_SESSION['profile_success']);
    }

    public function update(): void
    {
        RequireAuth::check();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=my-profile');
            exit;
        }

        $playerModel = new Player();
        $player = $playerModel->findByUserId(Auth::id());

        if (!$player) {
            header('Location: index.php?page=my-profile');
            exit;
        }

        $realName = trim($_POST['real_name'] ?? '');
        $displayName = trim($_POST['display_name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $ratingCategory = trim($_POST['rating_category'] ?? 'beginner');

        $errors = [];

        if ($realName === '') {
            $errors[] = 'Full name is required.';
        }

        if (!in_array($ratingCategory, ['beginner', 'casual', 'standard'], true)) {
            $errors[] = 'Invalid rating category selected.';
        }

        if (!empty($errors)) {
            $this->renderWithErrors($errors, []);
            return;
        }

        $playerModel->updateForUser(
            Auth::id(),
            $realName,
            $displayName !== '' ? $displayName : null,
            $phone !== '' ? $phone : null,
            $ratingCategory
        );

        $auditLog = new AuditLog();
        $auditLog->create(
            Auth::id(),
            'profile_updated',
            'user',
            Auth::id(),
            'User updated their profile details.'
        );

        $_SESSION['profile_success'] = 'Profile updated.';

        header('Location: index.php?page=my-profile');
        exit;
    }

    public function changePassword(): void
    {
        RequireAuth::check();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=my-profile');
            exit;
        }

        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $newPasswordConfirm = $_POST['new_password_confirm'] ?? '';

        $errors = [];
        $userModel = new User();
        $user = $userModel->findById(Auth::id());

        if (!$user || !password_verify($currentPassword, $user['password_hash'])) {
            $errors[] = 'Current password is incorrect.';
        }

        if (strlen($newPassword) < 8) {
            $errors[] = 'New password must be at least 8 characters.';
        }

        if ($newPassword !== $newPasswordConfirm) {
            $errors[] = 'New passwords do not match.';
        }

        if (!empty($errors)) {
            $this->renderWithErrors([], $errors);
            return;
        }

        $userModel->updatePassword(Auth::id(), $newPassword);

        $auditLog = new AuditLog();
        $auditLog->create(
            Auth::id(),
            'password_changed',
            'user',
            Auth::id(),
            'User changed their password.'
        );

        $_SESSION['profile_success'] = 'Password changed.';

        header('Location: index.php?page=my-profile');
        exit;
    }

    private function renderWithErrors(array $profileErrors, array $passwordErrors): void
    {
        $userModel = new User();
        $playerModel = new Player();
        $registrationModel = new EventRegistration();

        $registrations = Auth::role() === 'player' ? $registrationModel->forUser(Auth::id()) : [];

        $this->view('profile/show', [
            'title' => 'My Profile',
            'heading' => 'My Profile',
            'user' => $userModel->findById(Auth::id()),
            'player' => $playerModel->findByUserId(Auth::id()),
            'registrations' => array_slice($registrations, 0, 5),
            'profileErrors' => $profileErrors,
            'passwordErrors' => $passwordErrors,
            'success' => null,
        ]);
    }
}
