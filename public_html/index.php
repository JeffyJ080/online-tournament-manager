<?php

require_once __DIR__ . '/../mate_app/Core/Database.php';
require_once __DIR__ . '/../mate_app/Core/Router.php';
require_once __DIR__ . '/../mate_app/Controllers/PublicController.php';
require_once __DIR__ . '/../mate_app/Controllers/AuthController.php';

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

$router->dispatch();