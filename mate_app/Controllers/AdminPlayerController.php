<?php

require_once __DIR__ . '/../Core/Controller.php';
require_once __DIR__ . '/../Middleware/RequireAuth.php';
require_once __DIR__ . '/../Helpers/Auth.php';
require_once __DIR__ . '/../Models/Player.php';
require_once __DIR__ . '/../Models/User.php';
require_once __DIR__ . '/../Models/AuditLog.php';

class AdminPlayerController extends Controller
{
    public function index(): void
    {
        RequireAuth::anyRole(['admin', 'super_admin']);

        $query = trim($_GET['q'] ?? '');
        $playerModel = new Player();

        $this->view('admin/players/index', [
            'title' => 'Player Identity',
            'heading' => 'Player Identity',
            'players' => $playerModel->searchWithAccountDetails($query),
            'unlinkedRegistrationCount' => $playerModel->countUnlinkedRegistrations(),
            'query' => $query,
            'errors' => $_SESSION['admin_player_errors'] ?? [],
            'success' => $_SESSION['admin_player_success'] ?? null,
        ]);

        unset($_SESSION['admin_player_errors'], $_SESSION['admin_player_success']);
    }

    public function linkAccount(): void
    {
        RequireAuth::anyRole(['admin', 'super_admin']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=admin-players');
            exit;
        }

        $playerId = (int) ($_POST['player_id'] ?? 0);
        $email = trim($_POST['account_email'] ?? '');
        $errors = [];

        if ($playerId <= 0) {
            $errors[] = 'Choose a valid player record.';
        }

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Enter a valid account email.';
        }

        $userModel = new User();
        $playerModel = new Player();
        $user = $email !== '' ? $userModel->findByEmail($email) : null;

        if (!$user) {
            $errors[] = 'No account exists for that email.';
        } elseif (($user['role_name'] ?? '') !== 'player') {
            $errors[] = 'Only player accounts can be linked to player records.';
        }

        if (empty($errors) && !$playerModel->linkToUser($playerId, (int) $user['id'])) {
            $errors[] = 'Could not link this player. The account may already be linked to another player record.';
        }

        if (!empty($errors)) {
            $_SESSION['admin_player_errors'] = $errors;
            header('Location: index.php?page=admin-players');
            exit;
        }

        $auditLog = new AuditLog();
        $auditLog->create(
            Auth::id(),
            'player_linked_to_account',
            'player',
            $playerId,
            'Linked player #' . $playerId . ' to account ' . $user['email']
        );

        $_SESSION['admin_player_success'] = 'Player linked to account.';

        header('Location: index.php?page=admin-players');
        exit;
    }

    public function materializeRegistrations(): void
    {
        RequireAuth::anyRole(['admin', 'super_admin']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=admin-players');
            exit;
        }

        $playerModel = new Player();
        $created = $playerModel->materializeUnlinkedRegistrations();

        if ($created <= 0) {
            $_SESSION['admin_player_errors'] = ['No missing player records were created. There may be no unlinked registrations left.'];
            header('Location: index.php?page=admin-players');
            exit;
        }

        $auditLog = new AuditLog();
        $auditLog->create(
            Auth::id(),
            'player_records_created_from_registrations',
            'player',
            null,
            'Created ' . $created . ' player records from unlinked registrations'
        );

        $_SESSION['admin_player_success'] = 'Created ' . $created . ' player records from registrations.';

        header('Location: index.php?page=admin-players');
        exit;
    }

    public function merge(): void
    {
        RequireAuth::anyRole(['admin', 'super_admin']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=admin-players');
            exit;
        }

        $sourcePlayerId = (int) ($_POST['source_player_id'] ?? 0);
        $targetPlayerId = (int) ($_POST['target_player_id'] ?? 0);
        $errors = [];

        if ($sourcePlayerId <= 0 || $targetPlayerId <= 0) {
            $errors[] = 'Both source and target player IDs are required.';
        }

        if ($sourcePlayerId === $targetPlayerId) {
            $errors[] = 'Source and target cannot be the same player.';
        }

        $playerModel = new Player();

        if (empty($errors) && !$playerModel->mergeInto($sourcePlayerId, $targetPlayerId)) {
            $errors[] = 'Merge failed. Check that both players exist and are not linked to different user accounts.';
        }

        if (!empty($errors)) {
            $_SESSION['admin_player_errors'] = $errors;
            header('Location: index.php?page=admin-players');
            exit;
        }

        $auditLog = new AuditLog();
        $auditLog->create(
            Auth::id(),
            'player_records_merged',
            'player',
            $targetPlayerId,
            'Merged source player #' . $sourcePlayerId . ' into target player #' . $targetPlayerId
        );

        $_SESSION['admin_player_success'] = 'Player records merged.';

        header('Location: index.php?page=admin-players');
        exit;
    }
}
