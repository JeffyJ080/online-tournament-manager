<?php

require_once __DIR__ . '/../Core/Controller.php';
require_once __DIR__ . '/../Middleware/RequireAuth.php';
require_once __DIR__ . '/../Helpers/Auth.php';
require_once __DIR__ . '/../Models/Venue.php';
require_once __DIR__ . '/../Models/VenueUpdateRequest.php';
require_once __DIR__ . '/../Models/AuditLog.php';

class VenuePartnerController extends Controller
{
    public function showVenue(): void
    {
        RequireAuth::anyRole(['venue_manager', 'super_admin']);

        $venueId = (int) ($_GET['id'] ?? 0);
        $venueModel = new Venue();
        $venue = Auth::is('super_admin')
            ? $venueModel->findById($venueId)
            : $venueModel->findManagedById($venueId, Auth::id());

        if (!$venue) {
            http_response_code(403);
            echo '<h1>403 - Access denied</h1>';
            return;
        }

        $requestModel = new VenueUpdateRequest();

        $this->view('venue_partner/show', [
            'title' => 'Venue Partner',
            'heading' => $venue['name'] . ' Partner View',
            'venue' => $venue,
            'events' => Auth::is('super_admin')
                ? $venueModel->eventSummariesForVenue($venueId)
                : $venueModel->eventSummariesForManager(Auth::id(), $venueId),
            'pendingRequests' => $requestModel->pendingForVenue($venueId),
            'requestHistory' => $requestModel->allForRequester(Auth::id()),
            'success' => isset($_GET['requested']),
            'errors' => [],
            'old' => $venue,
        ]);
    }

    public function requestUpdate(): void
    {
        RequireAuth::anyRole(['venue_manager', 'super_admin']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=dashboard');
            exit;
        }

        $venueId = (int) ($_POST['venue_id'] ?? 0);
        $venueModel = new Venue();
        $venue = Auth::is('super_admin')
            ? $venueModel->findById($venueId)
            : $venueModel->findManagedById($venueId, Auth::id());

        if (!$venue) {
            http_response_code(403);
            echo '<h1>403 - Access denied</h1>';
            return;
        }

        $requestModel = new VenueUpdateRequest();
        $changes = $requestModel->filterAllowed($_POST);
        $errors = $this->validate($changes);

        if (!$this->hasChangedPublicDetails($venue, $changes)) {
            $errors[] = 'Change at least one public venue detail before submitting.';
        }

        if (!empty($errors)) {
            $this->view('venue_partner/show', [
                'title' => 'Venue Partner',
                'heading' => $venue['name'] . ' Partner View',
                'venue' => $venue,
                'events' => Auth::is('super_admin')
                    ? $venueModel->eventSummariesForVenue($venueId)
                    : $venueModel->eventSummariesForManager(Auth::id(), $venueId),
                'pendingRequests' => $requestModel->pendingForVenue($venueId),
                'requestHistory' => $requestModel->allForRequester(Auth::id()),
                'success' => false,
                'errors' => $errors,
                'old' => array_merge($venue, $changes),
            ]);
            return;
        }

        $requestId = $requestModel->create($venueId, Auth::id(), $changes);

        $auditLog = new AuditLog();
        $auditLog->create(
            Auth::id(),
            'venue_partner_update_requested',
            'venue_update_request',
            $requestId,
            'Venue Partner requested public detail updates for venue: ' . $venue['name']
        );

        header('Location: index.php?page=venue-partner-venue&id=' . $venueId . '&requested=1');
        exit;
    }

    private function validate(array $changes): array
    {
        $errors = [];

        if (!empty($changes['contact_email']) && !filter_var($changes['contact_email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Contact email must be valid.';
        }

        return $errors;
    }

    private function hasChangedPublicDetails(array $venue, array $changes): bool
    {
        foreach ($changes as $field => $value) {
            $current = trim((string) ($venue[$field] ?? ''));
            $next = trim((string) ($value ?? ''));

            if ($current !== $next) {
                return true;
            }
        }

        return false;
    }
}
