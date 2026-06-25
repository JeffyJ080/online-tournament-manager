<?php

require_once __DIR__ . '/../Core/Controller.php';
require_once __DIR__ . '/../Core/Mailer.php';
require_once __DIR__ . '/../Helpers/Auth.php';
require_once __DIR__ . '/../Middleware/RequireAuth.php';
require_once __DIR__ . '/../Models/User.php';
require_once __DIR__ . '/../Models/Player.php';
require_once __DIR__ . '/../Models/EventRegistration.php';
require_once __DIR__ . '/../Models/AuditLog.php';
require_once __DIR__ . '/../Models/PasswordReset.php';

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

        $userModel = new User();
        $user = $userModel->findById(Auth::id());

        if ($user && ($user['status'] ?? '') === 'active') {
            $this->sendResetEmail((int) $user['id'], $user['email']);
        }

        $_SESSION['profile_success'] = 'Password reset link sent to your email.';

        header('Location: index.php?page=my-profile');
        exit;
    }

    private function sendResetEmail(int $userId, string $email): bool
    {
        $token = bin2hex(random_bytes(32));
        $expiresAt = new DateTime('+1 hour');

        $resetModel = new PasswordReset();
        $resetModel->create($userId, $token, $expiresAt);

        $appConfig = require __DIR__ . '/../../mate_config/app.php';
        $resetUrl = rtrim($appConfig['app_url'], '/') . '/index.php?page=reset-password&token=' . urlencode($token);

        $body = '
            <p>A password reset was requested for your Mate Tournaments account.</p>
            <p><a href="' . htmlspecialchars($resetUrl) . '">Reset your password</a></p>
            <p>This link expires in 1 hour. If you did not request this, you can ignore this email.</p>
        ';

        $sent = Mailer::send($email, 'Reset your Mate Tournaments password', $body);

        $auditLog = new AuditLog();
        $auditLog->create(
            $userId,
            'password_reset_requested',
            'user',
            $userId,
            'Password reset email requested from profile.'
        );

        return $sent;
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
