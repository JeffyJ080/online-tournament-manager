<?php

require_once __DIR__ . '/../mate_app/Core/Database.php';
require_once __DIR__ . '/../mate_app/Core/Router.php';
require_once __DIR__ . '/../mate_app/Controllers/PublicController.php';

$config = require __DIR__ . '/../mate_config/app.php';

$router = new Router();

$publicController = new PublicController();

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

$router->dispatch();