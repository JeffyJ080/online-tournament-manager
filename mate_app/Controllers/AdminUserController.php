<?php

require_once __DIR__ . '/../Core/Controller.php';
require_once __DIR__ . '/../Middleware/RequireAuth.php';
require_once __DIR__ . '/../Models/User.php';
require_once __DIR__ . '/../Models/Player.php';
require_once __DIR__ . '/../Models/AuditLog.php';
require_once __DIR__ . '/../Helpers/Auth.php';

class AdminUserController extends Controller
{
    public function index(): void
    {
        RequireAuth::anyRole(['admin', 'super_admin']);

        $userModel = new User();

        $this->view('admin/users/index', [
            'title' => 'Users',
            'heading' => 'Manage Users',
            'users' => $userModel->allWithRoles(),
            'roles' => $userModel->roles(),
            'errors' => [],
            'old' => [],
        ]);
    }

    public function store(): void
    {
        RequireAuth::anyRole(['admin', 'super_admin']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=admin-users');
            exit;
        }

        $email = trim($_POST['email'] ?? '');
        $role = trim($_POST['role'] ?? 'player');
        $password = $_POST['password'] ?? '';
        $realName = trim($_POST['real_name'] ?? '');
        $displayName = trim($_POST['display_name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');

        $errors = [];
        $validRoles = ['player', 'host', 'event_manager', 'venue_manager', 'admin', 'super_admin'];

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'A valid email address is required.';
        }

        if (!in_array($role, $validRoles, true)) {
            $errors[] = 'Invalid role selected.';
        }

        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters.';
        }

        if ($role === 'player' && $realName === '') {
            $errors[] = 'Player accounts require a real name.';
        }

        $userModel = new User();

        if ($email !== '' && $userModel->findByEmail($email)) {
            $errors[] = 'A user with this email already exists.';
        }

        $old = [
            'email' => $email,
            'role' => $role,
            'real_name' => $realName,
            'display_name' => $displayName,
            'phone' => $phone,
        ];

        if (!empty($errors)) {
            $this->view('admin/users/index', [
                'title' => 'Users',
                'heading' => 'Manage Users',
                'users' => $userModel->allWithRoles(),
                'roles' => $userModel->roles(),
                'errors' => $errors,
                'old' => $old,
            ]);
            return;
        }

        $userId = $userModel->create($email, $password, $role);

        if ($role === 'player') {
            $playerModel = new Player();
            $playerModel->createForUser(
                $userId,
                $realName,
                $displayName !== '' ? $displayName : null,
                $phone !== '' ? $phone : null,
                $email,
                'beginner'
            );
        }

        $auditLog = new AuditLog();
        $auditLog->create(
            Auth::id(),
            'user_created_by_admin',
            'user',
            $userId,
            'User created with role: ' . $role
        );

        header('Location: index.php?page=admin-users');
        exit;
    }

    public function update(): void
    {
        RequireAuth::anyRole(['admin', 'super_admin']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=admin-users');
            exit;
        }

        $id = (int) ($_POST['id'] ?? 0);
        $role = trim($_POST['role'] ?? '');
        $status = trim($_POST['status'] ?? '');

        if ($id <= 0 || !in_array($role, ['player', 'host', 'event_manager', 'venue_manager', 'admin', 'super_admin'], true) || !in_array($status, ['active', 'inactive', 'banned'], true)) {
            header('Location: index.php?page=admin-users');
            exit;
        }

        $userModel = new User();
        $userModel->updateRoleAndStatus($id, $role, $status);

        $auditLog = new AuditLog();
        $auditLog->create(
            Auth::id(),
            'user_role_status_updated',
            'user',
            $id,
            'User role/status updated.'
        );

        header('Location: index.php?page=admin-users');
        exit;
    }
}
