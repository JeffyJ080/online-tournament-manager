<?php

require_once __DIR__ . '/../Core/Controller.php';
require_once __DIR__ . '/../Core/Database.php';
require_once __DIR__ . '/../Models/User.php';
require_once __DIR__ . '/../Models/Player.php';
require_once __DIR__ . '/../Models/AuditLog.php';

class AuthController extends Controller
{
    public function showRegister(): void
    {
        $this->view('auth/register', [
            'title' => 'Register',
            'heading' => 'Create your Mate Tournaments account',
            'errors' => [],
            'old' => [],
        ]);
    }

    public function showLogin(): void
    {
        $this->view('auth/login', [
            'title' => 'Login',
            'heading' => 'Login to Mate Tournaments',
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
}