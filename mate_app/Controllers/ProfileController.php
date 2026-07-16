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
require_once __DIR__ . '/../Models/WalkInInvitation.php';
require_once __DIR__ . '/../Helpers/app_url.php';

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
        $unlinkedRegistrations = ($user && Auth::role() === 'player')
            ? $registrationModel->unlinkedForEmail($user['email'])
            : [];
        $matches = Auth::role() === 'player' ? $registrationModel->matchHistoryForUser(Auth::id()) : [];

        $this->view('profile/show', [
            'title' => 'My Profile',
            'heading' => 'My Profile',
            'user' => $user,
            'player' => $player,
            'registrations' => array_slice($registrations, 0, 5),
            'unlinkedRegistrations' => $unlinkedRegistrations,
            'matches' => array_slice($matches, 0, 8),
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
        $profileVisibility = trim($_POST['profile_visibility'] ?? 'private');

        $errors = [];

        if ($realName === '') {
            $errors[] = 'Full name is required.';
        }

        if (!in_array($ratingCategory, ['beginner', 'casual', 'standard'], true)) {
            $errors[] = 'Invalid rating category selected.';
        }

        if (!in_array($profileVisibility, ['public', 'private'], true)) {
            $errors[] = 'Invalid profile visibility selected.';
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
            $ratingCategory,
            $profileVisibility
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

    public function claimHistory(): void
    {
        RequireAuth::anyRole(['player']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=my-profile');
            exit;
        }

        $userModel = new User();
        $playerModel = new Player();
        $registrationModel = new EventRegistration();

        $user = $userModel->findById(Auth::id());
        $player = $playerModel->findByUserId(Auth::id());

        if (!$user || !$player) {
            header('Location: index.php?page=my-profile');
            exit;
        }

        $claimed = $registrationModel->claimMatchingEmail($user['email'], Auth::id(), (int) $player['id']);

        $auditLog = new AuditLog();
        $auditLog->create(
            Auth::id(),
            'walkin_history_claimed',
            'player',
            (int) $player['id'],
            'Claimed ' . $claimed . ' previous unlinked registrations by email.'
        );

        $_SESSION['profile_success'] = $claimed . ' previous registration(s) linked to your account.';

        header('Location: index.php?page=my-profile');
        exit;
    }

    public function matchHistory(): void
    {
        RequireAuth::anyRole(['player']);

        $registrationModel = new EventRegistration();
        $playerModel = new Player();
        $matches = $registrationModel->matchHistoryForUser(Auth::id());
        $player = $playerModel->findByUserId(Auth::id());
        $stats = $player ? $playerModel->publicProfileStats((int) $player['id']) : [];
        $ratingHistory = $player ? $playerModel->ratingHistoryForUser(Auth::id()) : [];

        $this->view('profile/match_history', [
            'title' => 'Match History',
            'heading' => 'Match History',
            'matches' => $matches,
            'player' => $player,
            'stats' => $stats,
            'ratingHistory' => $ratingHistory,
        ]);
    }

    public function acceptWalkInInvite(): void
    {
        $token = trim($_GET['token'] ?? $_POST['token'] ?? '');
        $invitationModel = new WalkInInvitation();
        $invitation = $token !== '' ? $invitationModel->findValidByToken($token) : null;

        if (!$invitation) {
            $this->view('profile/accept_walkin_invite', [
                'title' => 'Walk-in Invitation',
                'heading' => 'Walk-in invitation',
                'token' => '',
                'invitation' => null,
                'message' => 'This invitation link is invalid or has expired.',
                'success' => false,
            ]);
            return;
        }

        if (!Auth::check()) {
            $this->view('profile/accept_walkin_invite', [
                'title' => 'Walk-in Invitation',
                'heading' => 'Link your walk-in result',
                'token' => $token,
                'invitation' => $invitation,
                'message' => 'Log in or create an account with this email, then open this link again to claim the result.',
                'success' => false,
            ]);
            return;
        }

        $userModel = new User();
        $playerModel = new Player();
        $registrationModel = new EventRegistration();
        $user = $userModel->findById(Auth::id());
        $player = $playerModel->findByUserId(Auth::id());

        if (!$user || !$player || strtolower($user['email']) !== strtolower($invitation['email'])) {
            $this->view('profile/accept_walkin_invite', [
                'title' => 'Walk-in Invitation',
                'heading' => 'Link your walk-in result',
                'token' => $token,
                'invitation' => $invitation,
                'message' => 'This invitation must be accepted by an account using ' . $invitation['email'] . '.',
                'success' => false,
            ]);
            return;
        }

        $registrationModel->linkToPlayer((int) $invitation['event_registration_id'], Auth::id(), (int) $player['id']);
        $invitationModel->markAccepted((int) $invitation['id'], Auth::id());

        $auditLog = new AuditLog();
        $auditLog->create(
            Auth::id(),
            'walkin_invitation_accepted',
            'event_registration',
            (int) $invitation['event_registration_id'],
            'Player accepted walk-in account invitation.'
        );

        $this->view('profile/accept_walkin_invite', [
            'title' => 'Walk-in Invitation',
            'heading' => 'Walk-in linked',
            'token' => '',
            'invitation' => $invitation,
            'message' => 'This walk-in result is now linked to your account.',
            'success' => true,
        ]);
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

        $resetUrl = absolute_url('index.php?page=reset-password&token=' . urlencode($token));

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
        $user = $userModel->findById(Auth::id());
        $matches = Auth::role() === 'player' ? $registrationModel->matchHistoryForUser(Auth::id()) : [];

        $this->view('profile/show', [
            'title' => 'My Profile',
            'heading' => 'My Profile',
            'user' => $user,
            'player' => $playerModel->findByUserId(Auth::id()),
            'registrations' => array_slice($registrations, 0, 5),
            'unlinkedRegistrations' => ($user && Auth::role() === 'player') ? $registrationModel->unlinkedForEmail($user['email']) : [],
            'matches' => array_slice($matches, 0, 8),
            'profileErrors' => $profileErrors,
            'passwordErrors' => $passwordErrors,
            'success' => null,
        ]);
    }
}
