<?php
$config = require __DIR__ . '/../mate_config/app.php';

if (!empty($config['production'])) {
    ini_set('display_errors', '0');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
}

$sessionPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'mate_tournaments_sessions';

if (!is_dir($sessionPath)) {
    mkdir($sessionPath, 0777, true);
}

if (is_writable($sessionPath)) {
    session_save_path($sessionPath);
}

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
    'httponly' => true,
    'samesite' => 'Lax',
]);

session_start();

header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');

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
require_once __DIR__ . '/../mate_app/Controllers/TournamentManagerController.php';
require_once __DIR__ . '/../mate_app/Controllers/AdminUserController.php';
require_once __DIR__ . '/../mate_app/Controllers/AdminLeaderboardController.php';
require_once __DIR__ . '/../mate_app/Controllers/ProfileController.php';
require_once __DIR__ . '/../mate_app/Helpers/Auth.php';
require_once __DIR__ . '/../mate_app/Helpers/url.php';
require_once __DIR__ . '/../mate_app/Helpers/Csrf.php';
require_once __DIR__ . '/../mate_app/Middleware/RequireAuth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !csrf_verify($_POST['csrf_token'] ?? null)) {
    http_response_code(419);
    echo '<h1>419 - Security check failed</h1>';
    echo '<p>Please go back, refresh the page, and try again.</p>';
    exit;
}

ob_start('csrf_inject_forms');

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
$tournamentManagerController = new TournamentManagerController();
$adminUserController = new AdminUserController();
$adminLeaderboardController = new AdminLeaderboardController();
$profileController = new ProfileController();

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

$router->get('my-profile', function () use ($profileController) {
    $profileController->show();
});

$router->get('my-profile-update', function () use ($profileController) {
    $profileController->update();
});

$router->get('my-profile-password', function () use ($profileController) {
    $profileController->changePassword();
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

$router->get('admin-users', function () use ($adminUserController) {
    $adminUserController->index();
});

$router->get('admin-users-store', function () use ($adminUserController) {
    $adminUserController->store();
});

$router->get('admin-users-update', function () use ($adminUserController) {
    $adminUserController->update();
});

$router->get('admin-leaderboards', function () use ($adminLeaderboardController) {
    $adminLeaderboardController->index();
});

$router->get('admin-leaderboards-store', function () use ($adminLeaderboardController) {
    $adminLeaderboardController->store();
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

$router->get('leaderboard', function () use ($publicController) {
    $publicController->leaderboard();
});

$router->get('live-tournament', function () use ($publicController) {
    $publicController->liveTournament();
});

$router->get('live-display', function () use ($publicController) {
    $publicController->liveDisplay();
});

$router->get('live-display-data', function () use ($publicController) {
    $publicController->liveDisplayData();
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

$router->get('host-walk-in', function () use ($hostEventController) {
    $hostEventController->addWalkIn();
});

$router->get('host-event-report', function () use ($hostEventController) {
    $hostEventController->report();
});

$router->get('host-event-report-store', function () use ($hostEventController) {
    $hostEventController->storeReport();
});

$router->get('tournament-manager', function () use ($tournamentManagerController) {
    $tournamentManagerController->index();
});

$router->get('tournament-create', function () use ($tournamentManagerController) {
    $tournamentManagerController->create();
});

$router->get('tournament-import-participants', function () use ($tournamentManagerController) {
    $tournamentManagerController->importParticipants();
});

$router->get('tournament-generate-round-one', function () use ($tournamentManagerController) {
    $tournamentManagerController->generateRoundOne();
});

$router->get('tournament-submit-result', function () use ($tournamentManagerController) {
    $tournamentManagerController->submitResult();
});

$router->get('tournament-submit-round-results', function () use ($tournamentManagerController) {
    $tournamentManagerController->submitRoundResults();
});

$router->get('tournament-generate-next-round', function () use ($tournamentManagerController) {
    $tournamentManagerController->generateNextRound();
});

$router->get('tournament-complete', function () use ($tournamentManagerController) {
    $tournamentManagerController->completeTournament();
});

$router->dispatch();
