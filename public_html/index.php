<?php
session_start();

require_once __DIR__ . '/../mate_app/Core/Database.php';
require_once __DIR__ . '/../mate_app/Core/Router.php';
require_once __DIR__ . '/../mate_app/Controllers/PublicController.php';
require_once __DIR__ . '/../mate_app/Controllers/AuthController.php';
require_once __DIR__ . '/../mate_app/Controllers/DashboardController.php';
require_once __DIR__ . '/../mate_app/Controllers/AdminVenueController.php';
require_once __DIR__ . '/../mate_app/Controllers/AdminRegistrationController.php';
require_once __DIR__ . '/../mate_app/Controllers/PaymentProofController.php';
require_once __DIR__ . '/../mate_app/Controllers/AdminPaymentProofController.php';
require_once __DIR__ . '/../mate_app/Controllers/PlayerRegistrationController.php';
require_once __DIR__ . '/../mate_app/Controllers/AdminEventController.php';
require_once __DIR__ . '/../mate_app/Controllers/HostEventController.php';
require_once __DIR__ . '/../mate_app/Helpers/Auth.php';
require_once __DIR__ . '/../mate_app/Helpers/url.php';
require_once __DIR__ . '/../mate_app/Middleware/RequireAuth.php';

$config = require __DIR__ . '/../mate_config/app.php';

$router = new Router();

$publicController = new PublicController();
$authController = new AuthController();
$dashboardController = new DashboardController();
$adminVenueController = new AdminVenueController();
$adminRegistrationController = new AdminRegistrationController();
$paymentProofController = new PaymentProofController();
$adminPaymentProofController = new AdminPaymentProofController();
$playerRegistrationController = new PlayerRegistrationController();
$adminEventController = new AdminEventController();
$hostEventController = new HostEventController();

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

$router->get('admin-venues-create', function () use ($adminVenueController) {
    $adminVenueController->create();
});

$router->get('admin-venues-store', function () use ($adminVenueController) {
    $adminVenueController->store();
});

$router->get('admin-venues-edit', function () use ($adminVenueController) {
    $adminVenueController->edit();
});

$router->get('admin-venues-update', function () use ($adminVenueController) {
    $adminVenueController->update();
});

$router->get('event', function () use ($publicController) {
    $publicController->eventDetails();
});

$router->get('register-event', function () use ($publicController) {
    $publicController->registerEvent();
});

$router->get('register-event-submit', function () use ($publicController) {
    $publicController->storeEventRegistration();
});

$router->get('admin-registrations', function () use ($adminRegistrationController) {
    $adminRegistrationController->index();
});

$router->get('admin-registrations-edit', function () use ($adminRegistrationController) {
    $adminRegistrationController->edit();
});

$router->get('admin-registrations-update', function () use ($adminRegistrationController) {
    $adminRegistrationController->update();
});

$router->get('upload-proof', function () use ($paymentProofController) {
    $paymentProofController->create();
});

$router->get('upload-proof-submit', function () use ($paymentProofController) {
    $paymentProofController->store();
});

$router->get('admin-payment-proofs', function () use ($adminPaymentProofController) {
    $adminPaymentProofController->index();
});

$router->get('admin-payment-proofs-review', function () use ($adminPaymentProofController) {
    $adminPaymentProofController->review();
});

$router->get('admin-payment-proofs-update', function () use ($adminPaymentProofController) {
    $adminPaymentProofController->update();
});

$router->get('my-registrations', function () use ($playerRegistrationController) {
    $playerRegistrationController->index();
});

$router->get('admin-events', function () use ($adminEventController) {
    $adminEventController->index();
});

$router->get('admin-events-create', function () use ($adminEventController) {
    $adminEventController->create();
});

$router->get('admin-events-store', function () use ($adminEventController) {
    $adminEventController->store();
});

$router->get('admin-events-edit', function () use ($adminEventController) {
    $adminEventController->edit();
});

$router->get('admin-events-update', function () use ($adminEventController) {
    $adminEventController->update();
});

$router->get('venues', function () use ($publicController) {
    $publicController->venues();
});

$router->get('host-events', function () use ($hostEventController) {
    $hostEventController->assigned();
});

$router->get('host-event-registrations', function () use ($hostEventController) {
    $hostEventController->registrations();
});

$router->get('host-check-in', function () use ($hostEventController) {
    $hostEventController->checkIn();
});

$router->dispatch();