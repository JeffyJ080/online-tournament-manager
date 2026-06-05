<?php
session_start();

require_once __DIR__ . '/../mate_app/Core/Database.php';
require_once __DIR__ . '/../mate_app/Core/Router.php';
require_once __DIR__ . '/../mate_app/Controllers/PublicController.php';
require_once __DIR__ . '/../mate_app/Controllers/AuthController.php';
require_once __DIR__ . '/../mate_app/Helpers/Auth.php';

$config = require __DIR__ . '/../mate_config/app.php';

$router = new Router();

$publicController = new PublicController();
$authController = new AuthController();

$router->get('home', function () use ($publicController) {
    $publicController->home();
});

$router->get('events', function () use ($publicController) {
    $publicController->events();
});

$router->get('about', function () use ($publicController) {
    $publicController->about();
});

$router->get('contact', function () use ($publicController) {
    $publicController->contact();
});

$router->get('register', function () use ($authController) {
    $authController->showRegister();
});

$router->get('login', function () use ($authController) {
    $authController->showLogin();
});

$router->get('register-submit', function () use ($authController) {
    $authController->register();
});

$router->get('login-submit', function () use ($authController) {
    $authController->login();
});

$router->get('dashboard', function () {
    if (!Auth::check()) {
        header('Location: index.php?page=login');
        exit;
    }

    $user = Auth::user();

    echo '<h1>Dashboard</h1>';
    echo '<p>Logged in as: ' . htmlspecialchars($user['email']) . '</p>';
    echo '<p>Role: ' . htmlspecialchars($user['role']) . '</p>';
    echo '<p><a href="index.php?page=logout">Logout</a></p>';

    echo '<p>This will redirect to role-specific dashboards next.</p>';
});

$router->get('logout', function () use ($authController) {
    $authController->logout();
});

$router->dispatch();