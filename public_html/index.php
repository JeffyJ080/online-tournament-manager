<?php
session_start();

require_once __DIR__ . '/../mate_app/Core/Database.php';
require_once __DIR__ . '/../mate_app/Core/Router.php';
require_once __DIR__ . '/../mate_app/Controllers/PublicController.php';
require_once __DIR__ . '/../mate_app/Controllers/AuthController.php';
require_once __DIR__ . '/../mate_app/Controllers/DashboardController.php';
require_once __DIR__ . '/../mate_app/Controllers/AdminVenueController.php';
require_once __DIR__ . '/../mate_app/Helpers/Auth.php';
require_once __DIR__ . '/../mate_app/Helpers/url.php';
require_once __DIR__ . '/../mate_app/Middleware/RequireAuth.php';

$config = require __DIR__ . '/../mate_config/app.php';

$router = new Router();

$publicController = new PublicController();
$authController = new AuthController();
$dashboardController = new DashboardController();
$adminVenueController = new AdminVenueController();

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

$router->get('dashboard', function () use ($dashboardController) {
    $dashboardController->index();
});

$router->get('logout', function () use ($authController) {
    $authController->logout();
});

$router->get('admin-venues', function () use ($adminVenueController) {
    $adminVenueController->index();
});

$router->dispatch();