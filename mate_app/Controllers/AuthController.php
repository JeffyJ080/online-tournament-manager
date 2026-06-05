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
}