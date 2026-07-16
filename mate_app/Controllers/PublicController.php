<?php

require_once __DIR__ . '/../Core/Controller.php';
require_once __DIR__ . '/../Core/Database.php';
require_once __DIR__ . '/../Core/Mailer.php';
require_once __DIR__ . '/../Models/Event.php';
require_once __DIR__ . '/../Models/EventRegistration.php';
require_once __DIR__ . '/../Models/Player.php';
require_once __DIR__ . '/../Models/AuditLog.php';
require_once __DIR__ . '/../Helpers/Auth.php';
require_once __DIR__ . '/../Models/Payment.php';
require_once __DIR__ . '/../Models/Venue.php';
require_once __DIR__ . '/../Models/Tournament.php';
require_once __DIR__ . '/../Models/TournamentParticipant.php';
require_once __DIR__ . '/../Models/LeaderboardSeason.php';
require_once __DIR__ . '/../Models/Match.php';
require_once __DIR__ . '/../Models/Round.php';
require_once __DIR__ . '/../Helpers/LiveTimer.php';

class PublicController extends Controller
{
    public function home(): void
    {
        $this->view('public/home', [
            'title' => 'Home',
            'heading' => 'Mate Tournaments',
        ]);
    }

    public function events(): void
    {
        $eventModel = new Event();
        $events = $eventModel->publishedUpcoming();

        $this->view('public/events', [
            'title' => 'Events',
            'heading' => 'Upcoming Events',
            'events' => $events,
        ]);
    }

    public function about(): void
    {
        $this->view('public/about', [
            'title' => 'About',
            'heading' => 'About Mate Tournaments',
        ]);
    }

    public function contact(): void
    {
        $this->view('public/contact', [
            'title' => 'Contact',
            'heading' => 'Contact Mate Tournaments',
        ]);
    }

    public function eventDetails(): void
    {
        $slug = trim($_GET['slug'] ?? '');

        if ($slug === '') {
            header('Location: index.php?page=events');
            exit;
        }

        $eventModel = new Event();
        $event = $eventModel->findBySlug($slug);

        if (!$event || $event['event_status'] !== 'published') {
            http_response_code(404);

            $this->view('public/404', [
                'title' => 'Event Not Found',
                'heading' => 'Event not found',
            ]);
            return;
        }

        $this->view('public/event_details', [
            'title' => $event['title'],
            'heading' => $event['title'],
            'event' => $event,
        ]);
    }

    public function registerEvent(): void
    {
        $eventId = (int) ($_GET['event'] ?? 0);

        if ($eventId <= 0) {
            header('Location: index.php?page=events');
            exit;
        }

        $eventModel = new Event();
        $registrationModel = new EventRegistration();

        $event = $eventModel->findById($eventId);

        if (!$event || $event['event_status'] !== 'published') {
            http_response_code(404);

            $this->view('public/404', [
                'title' => 'Event Not Found',
                'heading' => 'Event not found',
            ]);
            return;
        }

        $activeRegistrations = $registrationModel->countActiveForEvent($eventId);
        $spotsLeft = max(0, (int) $event['max_players'] - $activeRegistrations);

        $old = [];
        $player = null;

        if (Auth::check()) {
            $playerModel = new Player();
            $player = $playerModel->findByUserId(Auth::id());

            if ($player) {
                $old = [
                    'full_name' => $player['real_name'] ?? '',
                    'display_name' => $player['display_name'] ?? '',
                    'email' => $player['email'] ?? Auth::user()['email'],
                    'phone' => $player['phone'] ?? '',
                    'rating_category' => $player['rating_category'] ?? 'beginner',
                ];
            }
        }

        $this->view('public/register_event', [
            'title' => 'Register for Event',
            'heading' => 'Register for Event',
            'event' => $event,
            'spots_left' => $spotsLeft,
            'active_registrations' => $activeRegistrations,
            'errors' => [],
            'old' => $old,
            'player' => $player,
        ]);
    }

    public function storeEventRegistration(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=events');
            exit;
        }

        $eventId = (int) ($_POST['event_id'] ?? 0);
        $fullName = trim($_POST['full_name'] ?? '');
        $displayName = trim($_POST['display_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $ratingCategory = trim($_POST['rating_category'] ?? 'beginner');
        $paymentMethod = trim($_POST['payment_method'] ?? 'cash');
        $useProfile = Auth::check() && ($_POST['use_profile'] ?? '') === '1';

        $errors = [];

        $eventModel = new Event();
        $registrationModel = new EventRegistration();
        $player = null;

        if (Auth::check()) {
            $playerModel = new Player();
            $player = $playerModel->findByUserId(Auth::id());

            if ($useProfile && $player) {
                $fullName = $player['real_name'] ?? '';
                $displayName = $player['display_name'] ?? '';
                $email = $player['email'] ?? Auth::user()['email'];
                $phone = $player['phone'] ?? '';
                $ratingCategory = $player['rating_category'] ?? 'beginner';
            }
        }

        $event = $eventModel->findById($eventId);

        if (!$event || $event['event_status'] !== 'published') {
            $errors[] = 'This event is not available for registration.';
        }

        if ($fullName === '') {
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

        if (!in_array($paymentMethod, ['cash', 'eft'], true)) {
            $errors[] = 'Invalid payment method selected.';
        }

        $activeRegistrations = 0;
        $spotsLeft = 0;

        if ($event) {
            $activeRegistrations = $registrationModel->countActiveForEvent($eventId);
            $spotsLeft = max(0, (int) $event['max_players'] - $activeRegistrations);

            if ($event['registration_status'] !== 'open') {
                $errors[] = 'Registration is not open for this event.';
            }

            if ($spotsLeft <= 0) {
                $errors[] = 'This event is full.';
            }

            if (Auth::check() && $registrationModel->userAlreadyRegistered($eventId, Auth::id())) {
                $errors[] = 'You are already registered for this event.';
            }

            if ($email !== '' && $registrationModel->emailAlreadyRegistered($eventId, $email)) {
                $errors[] = 'This email is already registered for this event.';
            }
        }

        $old = [
            'full_name' => $fullName,
            'display_name' => $displayName,
            'email' => $email,
            'phone' => $phone,
            'rating_category' => $ratingCategory,
            'payment_method' => $paymentMethod,
        ];

        if (!empty($errors)) {
            $this->view('public/register_event', [
                'title' => 'Register for Event',
                'heading' => 'Register for Event',
                'event' => $event ?? [],
                'spots_left' => $spotsLeft,
                'active_registrations' => $activeRegistrations,
                'errors' => $errors,
                'old' => $old,
                'player' => $player,
            ]);
            return;
        }

        $playerId = null;
        $userId = null;

        if (Auth::check()) {
            $userId = Auth::id();

            if (!$player) {
                $playerModel = new Player();
                $player = $playerModel->findByUserId($userId);
            }

            if ($player) {
                $playerId = (int) $player['id'];
            }
        }

        $registrationStatus = 'pending';
        $paymentStatus = 'unpaid';

        $db = Database::connect();

        try {
            $db->beginTransaction();

            $registrationId = $registrationModel->create([
                'event_id' => $eventId,
                'player_id' => $playerId,
                'user_id' => $userId,
                'full_name' => $fullName,
                'display_name' => $displayName !== '' ? $displayName : null,
                'email' => $email,
                'phone' => $phone !== '' ? $phone : null,
                'rating_category' => $ratingCategory,
                'registration_status' => $registrationStatus,
                'payment_status' => $paymentStatus,
                'payment_method' => $paymentMethod,
                'notes' => null,
            ]);

            $paymentModel = new Payment();

            $paymentId = $paymentModel->createForRegistration([
                'registration_id' => $registrationId,
                'event_id' => $eventId,
                'user_id' => $userId,
                'player_id' => $playerId,
                'amount' => $event['entry_fee'],
                'payment_method' => $paymentMethod,
                'payment_status' => $paymentStatus,
                'notes' => 'Payment record created during event registration.',
            ]);

            $auditLog = new AuditLog();
            $auditLog->create(
                $userId,
                'event_registration_created',
                'event_registration',
                $registrationId,
                'Registration created for event: ' . $event['title']
            );

            $auditLog->create(
                $userId,
                'payment_record_created',
                'payment',
                $paymentId,
                'Payment record created for registration #' . $registrationId
            );

            $playerEmailBody = '
                <p>Hi ' . htmlspecialchars($fullName) . ',</p>

                <p>Your registration for <strong>' . htmlspecialchars($event['title']) . '</strong> has been received.</p>

                <p>
                    <strong>Venue:</strong> ' . htmlspecialchars($event['venue_name']) . '<br>
                    <strong>Date:</strong> ' . htmlspecialchars(date('D, d M Y', strtotime($event['event_date']))) . '<br>
                    <strong>Time:</strong> ' . htmlspecialchars(date('H:i', strtotime($event['start_time']))) . '<br>
                    <strong>Payment method:</strong> ' . htmlspecialchars(ucwords($paymentMethod)) . '
                </p>

                <p>Your registration reference is <strong>#' . (int) $registrationId . '</strong>.</p>
            ';

            if ($paymentMethod === 'eft') {
                $playerEmailBody .= '
                    <p>Please upload your proof of payment so admin can verify your spot.</p>
                ';
            } else {
                $playerEmailBody .= '
                    <p>Please pay at the event during check-in.</p>
                ';
            }

            Mailer::send(
                $email,
                'Registration received - ' . $event['title'],
                $playerEmailBody,
                $fullName
            );

            $adminEmailBody = '
                <p>A new event registration was created.</p>

                <p>
                    <strong>Event:</strong> ' . htmlspecialchars($event['title']) . '<br>
                    <strong>Venue:</strong> ' . htmlspecialchars($event['venue_name']) . '<br>
                    <strong>Player:</strong> ' . htmlspecialchars($fullName) . '<br>
                    <strong>Email:</strong> ' . htmlspecialchars($email) . '<br>
                    <strong>Phone:</strong> ' . htmlspecialchars($phone !== '' ? $phone : '-') . '<br>
                    <strong>Payment method:</strong> ' . htmlspecialchars(ucwords($paymentMethod)) . '<br>
                    <strong>Registration reference:</strong> #' . (int) $registrationId . '
                </p>
            ';

            Mailer::sendToAdmin(
                'New registration - ' . $event['title'],
                $adminEmailBody
            );

            $db->commit();
        } catch (Exception $e) {
            $db->rollBack();

            $this->view('public/register_event', [
                'title' => 'Register for Event',
                'heading' => 'Register for Event',
                'event' => $event,
                'spots_left' => $spotsLeft,
                'active_registrations' => $activeRegistrations,
                'errors' => ['Something went wrong while saving your registration. Please try again.'],
                'old' => $old,
            ]);
            return;
        }

        $this->view('public/register_event_success', [
            'title' => 'Registration Submitted',
            'heading' => 'Registration submitted',
            'event' => $event,
            'registration_id' => $registrationId,
            'payment_method' => $paymentMethod,
        ]);
    }
    
    public function venues(): void
    {
        $venueModel = new Venue();
        $venues = $venueModel->active();

        $this->view('public/venues', [
            'title' => 'Venues',
            'heading' => 'Our Venues',
            'venues' => $venues,
        ]);
    }

    public function liveTournament(): void
    {
        $eventId = (int) ($_GET['event'] ?? 0);

        if ($eventId <= 0) {
            header('Location: index.php?page=events');
            exit;
        }

        $eventModel = new Event();
        $event = $eventModel->findById($eventId);

        if (!$event || !in_array($event['event_status'], ['published', 'running', 'completed'], true)) {
            http_response_code(404);

            $this->view('public/404', [
                'title' => 'Tournament Not Found',
                'heading' => 'Tournament not found',
            ]);
            return;
        }

        $tournamentModel = new Tournament();
        $tournament = $tournamentModel->findByEventId($eventId);

        $standings = [];
        $matches = [];
        $rounds = [];

        if ($tournament) {
            $participantModel = new TournamentParticipant();
            $standings = $participantModel->standings((int) $tournament['id']);

            $matchModel = new MatchModel();
            $matches = $matchModel->forTournament((int) $tournament['id']);

            $roundModel = new Round();
            $rounds = $roundModel->forTournament((int) $tournament['id']);
        }

        $this->view('public/tournament_live', [
            'title' => 'Live Tournament',
            'heading' => 'Live Tournament',
            'event' => $event,
            'tournament' => $tournament,
            'standings' => $standings,
            'matches' => $matches,
            'rounds' => $rounds,
        ]);
    }

    public function tournamentArchive(): void
    {
        $eventModel = new Event();

        $this->view('public/tournament_archive', [
            'title' => 'Tournament Archive',
            'heading' => 'Tournament Archive',
            'events' => $eventModel->completedPublic(),
        ]);
    }

    public function playerProfile(): void
    {
        $slug = trim($_GET['slug'] ?? '');
        $playerModel = new Player();
        $player = $slug !== '' ? $playerModel->findPublicBySlug($slug) : null;

        if (!$player) {
            http_response_code(404);
            $this->view('public/404', [
                'title' => 'Player Not Found',
                'heading' => 'Player profile not found',
            ]);
            return;
        }

        $this->view('public/player_profile', [
            'title' => $player['display_name'] ?: $player['real_name'],
            'heading' => $player['display_name'] ?: $player['real_name'],
            'player' => $player,
            'stats' => $playerModel->publicProfileStats((int) $player['id']),
        ]);
    }

    public function liveDisplay(): void
    {
        $eventId = (int) ($_GET['event'] ?? 0);

        if ($eventId <= 0) {
            header('Location: index.php?page=events');
            exit;
        }

        $payload = $this->liveTournamentPayload($eventId);

        if (!$payload) {
            http_response_code(404);

            $this->view('public/404', [
                'title' => 'Live Display Not Found',
                'heading' => 'Live display not found',
            ]);
            return;
        }

        $this->view('public/live_display', [
            'title' => 'Live Display',
            'heading' => 'Live Display',
            'eventId' => $eventId,
            'payload' => $payload,
        ]);
    }

    public function liveDisplayData(): void
    {
        $eventId = (int) ($_GET['event'] ?? 0);
        $payload = $eventId > 0 ? $this->liveTournamentPayload($eventId) : null;

        header('Content-Type: application/json');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

        if (!$payload) {
            http_response_code(404);
            echo json_encode([
                'ok' => false,
                'message' => 'Live display not found.',
            ]);
            return;
        }

        echo json_encode([
            'ok' => true,
            'data' => $payload,
        ]);
    }

    private function liveTournamentPayload(int $eventId): ?array
    {
        $eventModel = new Event();
        $event = $eventModel->findById($eventId);

        if (!$event || !in_array($event['event_status'], ['published', 'running', 'completed'], true)) {
            return null;
        }

        $tournamentModel = new Tournament();
        $tournament = $tournamentModel->findByEventId($eventId);

        $standings = [];
        $pairings = [];
        $overallLeaders = [];
        $overallLeaderboardName = 'Overall leaderboard';
        $recentResults = [];
        $roundStats = [
            'total' => 0,
            'completed' => 0,
            'in_play' => 0,
            'remaining' => 0,
        ];
        $currentRoundName = 'Not started';
        $currentRoundStatus = 'pending';
        $latestRound = null;

        if ($tournament) {
            $participantModel = new TournamentParticipant();
            $rawStandings = $participantModel->standings((int) $tournament['id']);
            $standings = array_map(
                fn (array $standing, int $index): array => [
                    'rank' => $index + 1,
                    'name' => $standing['display_name'] ?: $standing['full_name'],
                    'score' => (string) ($standing['current_score'] ?? '0.0'),
                    'status' => $this->displayLabel($standing['status'] ?? ''),
                ],
                $rawStandings,
                array_keys($rawStandings)
            );

            $roundModel = new Round();
            $latestRound = $roundModel->latestForTournament((int) $tournament['id']);

            $matchModel = new MatchModel();
            $matches = $latestRound
                ? $matchModel->forRound((int) $latestRound['id'])
                : $matchModel->forTournament((int) $tournament['id']);

            $allMatches = $matchModel->forTournament((int) $tournament['id']);
            $matchNames = [];

            foreach ($allMatches as $match) {
                $matchNames[(int) $match['id']] = $match;
            }

            if ($latestRound) {
                $currentRoundName = $latestRound['name'] ?: 'Round ' . $latestRound['round_number'];
                $currentRoundStatus = $latestRound['status'] ?? 'pending';
            }

            $pairings = array_map(function (array $match) use ($matchNames): array {
                $named = $matchNames[(int) $match['id']] ?? $match;

                return [
                    'board' => (int) ($match['board_number'] ?? 0),
                    'white' => $named['white_display_name'] ?: ($named['white_full_name'] ?? '-'),
                    'black' => !empty($match['black_registration_id'])
                        ? ($named['black_display_name'] ?: ($named['black_full_name'] ?? '-'))
                        : 'Bye',
                    'result' => $this->displayLabel($match['result'] ?? 'pending'),
                    'status' => $this->displayLabel($match['status'] ?? 'scheduled'),
                ];
            }, $matches);

            foreach ($matches as $match) {
                $roundStats['total']++;

                if (($match['status'] ?? '') === 'completed') {
                    $roundStats['completed']++;
                } elseif (($match['status'] ?? '') === 'running') {
                    $roundStats['in_play']++;
                } else {
                    $roundStats['remaining']++;
                }
            }

            $completedMatches = array_values(array_filter(
                $allMatches,
                fn (array $match): bool => ($match['status'] ?? '') === 'completed'
            ));
            $completedMatches = array_slice(array_reverse($completedMatches), 0, 5);
            $recentResults = array_map(function (array $match): array {
                return [
                    'round' => $match['round_name'] ?: 'Round ' . ($match['round_number'] ?? '-'),
                    'board' => (int) ($match['board_number'] ?? 0),
                    'white' => $match['white_display_name'] ?: ($match['white_full_name'] ?? '-'),
                    'black' => !empty($match['black_registration_id'])
                        ? ($match['black_display_name'] ?: ($match['black_full_name'] ?? '-'))
                        : 'Bye',
                    'result' => $this->displayLabel($match['result'] ?? 'pending'),
                ];
            }, $completedMatches);

            $seasonModel = new LeaderboardSeason();
            $season = $seasonModel->seasonForEvent($eventId);

            if ($season) {
                $overallLeaderboardName = $season['name'] ?? $overallLeaderboardName;
                $seasonStandings = array_slice($seasonModel->standings((int) $season['id']), 0, 5);
                $overallLeaders = array_map(
                    fn (array $leader, int $index): array => [
                        'rank' => $index + 1,
                        'name' => $leader['player_name'] ?? '-',
                        'points' => (int) ($leader['total_points'] ?? 0),
                        'events' => (int) ($leader['events_played'] ?? 0),
                    ],
                    $seasonStandings,
                    array_keys($seasonStandings)
                );
            }
        }

        $roundNumber = (int) ($tournament['current_round'] ?? 0);
        $timer = new LiveTimer();
        $timerState = $latestRound && ($latestRound['status'] ?? '') === 'completed'
            ? $timer->completeRound($eventId, $roundNumber)
            : $timer->state($eventId, $roundNumber);

        return [
            'event' => [
                'title' => $event['title'],
                'venue' => $event['venue_name'] ?? '',
                'date' => date('d M Y', strtotime($event['event_date'])),
                'time' => date('H:i', strtotime($event['start_time'])),
                'format' => $this->displayLabel($event['format'] ?? ''),
                'status' => $this->displayLabel($event['event_status'] ?? ''),
                'description' => trim((string) ($event['description'] ?? '')),
                'prize' => trim((string) ($event['prize_info'] ?? '')),
                'poster' => trim((string) ($event['poster_path'] ?? '')),
                'city' => trim((string) ($event['venue_city'] ?? '')),
                'players' => (int) ($event['active_registrations'] ?? 0),
                'capacity' => (int) ($event['max_players'] ?? 0),
            ],
            'tournament' => [
                'status' => $this->displayLabel($tournament['status'] ?? 'not_created'),
                'current_round' => (int) ($tournament['current_round'] ?? 0),
                'total_rounds' => (int) ($tournament['total_rounds'] ?? 0),
                'round_name' => $currentRoundName,
                'round_status' => $currentRoundStatus,
            ],
            'standings' => $standings,
            'pairings' => $pairings,
            'overall_leaderboard' => [
                'name' => $overallLeaderboardName,
                'leaders' => $overallLeaders,
            ],
            'recent_results' => $recentResults,
            'round_stats' => $roundStats,
            'timer' => $timerState,
            'updated_at' => date('H:i:s'),
        ];
    }

    private function displayLabel(?string $value): string
    {
        return $value ? ucwords(str_replace('_', ' ', $value)) : '-';
    }

    public function leaderboard(): void
    {
        $seasonModel = new LeaderboardSeason();
        $seasons = $seasonModel->active();
        $selectedSeason = null;
        $leaders = [];
        $recentResults = [];

        $seasonSlug = trim($_GET['season'] ?? '');

        if ($seasonSlug !== '') {
            $selectedSeason = $seasonModel->findBySlug($seasonSlug);
        }

        if (!$selectedSeason && !empty($seasons)) {
            $selectedSeason = $seasons[0];
        }

        if ($selectedSeason) {
            $leaders = $seasonModel->standings((int) $selectedSeason['id']);
            $recentResults = $seasonModel->resultsForSeason((int) $selectedSeason['id']);
        }

        $this->view('public/leaderboard', [
            'title' => 'Leaderboard',
            'heading' => 'Leaderboard',
            'seasons' => $seasons,
            'selectedSeason' => $selectedSeason,
            'leaders' => $leaders,
            'recentResults' => $recentResults,
        ]);
    }
}
