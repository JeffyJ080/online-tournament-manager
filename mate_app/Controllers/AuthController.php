<?php

require_once __DIR__ . '/../Core/Controller.php';
require_once __DIR__ . '/../Core/Database.php';
require_once __DIR__ . '/../Core/Mailer.php';
require_once __DIR__ . '/../Models/User.php';
require_once __DIR__ . '/../Models/Player.php';
require_once __DIR__ . '/../Models/AuditLog.php';
require_once __DIR__ . '/../Models/PasswordReset.php';

class AuthController extends Controller
{
    public function showRegister(): void
    {
        if (isset($_SESSION['user'])) {
            header('Location: index.php?page=dashboard');
            exit;
        }

        $this->view('auth/register', [
            'title' => 'Register',
            'heading' => 'Create your Mate Tournaments account',
            'errors' => [],
            'old' => [],
        ]);
    }

    public function showLogin(): void
    {
        if (isset($_SESSION['user'])) {
            header('Location: index.php?page=dashboard');
            exit;
        }

        $this->view('auth/login', [
            'title' => 'Login',
            'heading' => 'Login to Mate Tournaments',
            'errors' => [],
            'old' => [],
        ]);
    }

    public function showForgotPassword(): void
    {
        if (isset($_SESSION['user'])) {
            header('Location: index.php?page=dashboard');
            exit;
        }

        $this->view('auth/forgot_password', [
            'title' => 'Forgot Password',
            'heading' => 'Reset your password',
            'errors' => [],
            'old' => [],
            'sent' => false,
        ]);
    }

    public function sendPasswordReset(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=forgot-password');
            exit;
        }

        $email = trim($_POST['email'] ?? '');
        $errors = [];

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        }

        if (!empty($errors)) {
            $this->view('auth/forgot_password', [
                'title' => 'Forgot Password',
                'heading' => 'Reset your password',
                'errors' => $errors,
                'old' => ['email' => $email],
                'sent' => false,
            ]);
            return;
        }

        $userModel = new User();
        $user = $userModel->findByEmail($email);

        if ($user && ($user['status'] ?? '') === 'active') {
            $this->sendResetEmail((int) $user['id'], $user['email']);
        }

        $this->view('auth/forgot_password', [
            'title' => 'Forgot Password',
            'heading' => 'Reset your password',
            'errors' => [],
            'old' => ['email' => $email],
            'sent' => true,
        ]);
    }

    public function showResetPassword(): void
    {
        if (isset($_SESSION['user'])) {
            header('Location: index.php?page=dashboard');
            exit;
        }

        $token = trim($_GET['token'] ?? '');
        $resetModel = new PasswordReset();
        $reset = $token !== '' ? $resetModel->findValidByToken($token) : null;

        if (!$reset) {
            $this->view('auth/reset_password', [
                'title' => 'Reset Password',
                'heading' => 'Reset your password',
                'token' => '',
                'errors' => ['This password reset link is invalid or has expired.'],
                'success' => false,
            ]);
            return;
        }

        $this->view('auth/reset_password', [
            'title' => 'Reset Password',
            'heading' => 'Reset your password',
            'token' => $token,
            'errors' => [],
            'success' => false,
        ]);
    }

    public function resetPassword(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=login');
            exit;
        }

        $token = trim($_POST['token'] ?? '');
        $password = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';
        $errors = [];

        $resetModel = new PasswordReset();
        $reset = $token !== '' ? $resetModel->findValidByToken($token) : null;

        if (!$reset) {
            $errors[] = 'This password reset link is invalid or has expired.';
        }

        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters.';
        }

        if ($password !== $passwordConfirm) {
            $errors[] = 'Passwords do not match.';
        }

        if (!empty($errors)) {
            $this->view('auth/reset_password', [
                'title' => 'Reset Password',
                'heading' => 'Reset your password',
                'token' => $token,
                'errors' => $errors,
                'success' => false,
            ]);
            return;
        }

        $userModel = new User();
        $userModel->updatePassword((int) $reset['user_id'], $password);
        $resetModel->markUsed((int) $reset['id']);

        $auditLog = new AuditLog();
        $auditLog->create(
            (int) $reset['user_id'],
            'password_reset_completed',
            'user',
            (int) $reset['user_id'],
            'User reset their password using an email link.'
        );

        $this->view('auth/reset_password', [
            'title' => 'Reset Password',
            'heading' => 'Reset your password',
            'token' => '',
            'errors' => [],
            'success' => true,
        ]);
    }

    public function register(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=register');
            exit;
        }

        $realName = trim($_POST['real_name'] ?? '');
        $displayName = trim($_POST['display_name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $ratingCategory = trim($_POST['rating_category'] ?? 'beginner');
        $password = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';

        $errors = [];

        if ($realName === '') {
            $errors[] = 'Full name is required.';
        }

        if ($email === '') {
            $errors[] = 'Email address is required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        }

        if (!in_array($ratingCategory, ['beginner', 'casual', 'standard'], true)) {
            $errors[] = 'Invalid rating category selected.';
        }

        if ($password === '') {
            $errors[] = 'Password is required.';
        } elseif (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters.';
        }

        if ($password !== $passwordConfirm) {
            $errors[] = 'Passwords do not match.';
        }

        $userModel = new User();

        if ($email !== '' && $userModel->findByEmail($email)) {
            $errors[] = 'An account with this email already exists.';
        }

        $old = [
            'real_name' => $realName,
            'display_name' => $displayName,
            'phone' => $phone,
            'email' => $email,
            'rating_category' => $ratingCategory,
        ];

        if (!empty($errors)) {
            $this->view('auth/register', [
                'title' => 'Register',
                'heading' => 'Create your Mate Tournaments account',
                'errors' => $errors,
                'old' => $old,
            ]);
            return;
        }

        $db = Database::connect();

        try {
            $db->beginTransaction();

            $userId = $userModel->create($email, $password, 'player');

            $playerModel = new Player();
            $playerModel->createForUser(
                $userId,
                $realName,
                $displayName !== '' ? $displayName : null,
                $phone !== '' ? $phone : null,
                $email,
                $ratingCategory
            );

            $auditLog = new AuditLog();
            $auditLog->create(
                $userId,
                'user_registered',
                'user',
                $userId,
                'New player account created.'
            );

            $db->commit();

            $this->view('auth/register_success', [
                'title' => 'Registration Successful',
                'heading' => 'Account created successfully',
                'email' => $email,
            ]);
        } catch (Exception $e) {
            $db->rollBack();

            $this->view('auth/register', [
                'title' => 'Register',
                'heading' => 'Create your Mate Tournaments account',
                'errors' => ['Something went wrong while creating your account. Please try again.'],
                'old' => $old,
            ]);
        }
    }

    public function login(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=login');
            exit;
        }

        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        $errors = [];

        if ($email === '') {
            $errors[] = 'Email address is required.';
        }

        if ($password === '') {
            $errors[] = 'Password is required.';
        }

        $userModel = new User();
        $user = null;

        if (empty($errors)) {
            $user = $userModel->findByEmail($email);

            if (!$user || !password_verify($password, $user['password_hash'])) {
                $errors[] = 'Invalid email or password.';
            } elseif ($user['status'] !== 'active') {
                $errors[] = 'This account is not active.';
            }
        }

        if (!empty($errors)) {
            $auditLog = new AuditLog();
            $auditLog->create(
                $user['id'] ?? null,
                'failed_login',
                'user',
                $user['id'] ?? null,
                'Failed login attempt for email: ' . $email
            );

            $this->view('auth/login', [
                'title' => 'Login',
                'heading' => 'Login to Mate Tournaments',
                'errors' => $errors,
                'old' => [
                    'email' => $email,
                ],
            ]);
            return;
        }

        session_regenerate_id(true);

        $_SESSION['user'] = [
            'id' => (int) $user['id'],
            'email' => $user['email'],
            'role' => $user['role_name'],
        ];

        $auditLog = new AuditLog();
        $auditLog->create(
            (int) $user['id'],
            'user_logged_in',
            'user',
            (int) $user['id'],
            'User logged in successfully.'
        );

        header('Location: index.php?page=dashboard');
        exit;
    }

    public function logout(): void
    {
        $userId = $_SESSION['user']['id'] ?? null;

        if ($userId !== null) {
            $auditLog = new AuditLog();
            $auditLog->create(
                (int) $userId,
                'user_logged_out',
                'user',
                (int) $userId,
                'User logged out.'
            );
        }

        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();

            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();

        header('Location: index.php?page=login');
        exit;
    }

    public function sendResetEmail(int $userId, string $email): bool
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
            'Password reset email requested.'
        );

        return $sent;
    }
}
