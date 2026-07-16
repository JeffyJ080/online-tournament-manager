<?php

require_once __DIR__ . '/../Core/Controller.php';
require_once __DIR__ . '/../Middleware/RequireAuth.php';
require_once __DIR__ . '/../Models/Venue.php';
require_once __DIR__ . '/../Helpers/slug.php';
require_once __DIR__ . '/../Models/AuditLog.php';
require_once __DIR__ . '/../Models/User.php';
require_once __DIR__ . '/../Models/VenueUpdateRequest.php';

class AdminVenueController extends Controller
{
    public function index(): void
    {
        RequireAuth::anyRole(['admin', 'super_admin']);

        $venueModel = new Venue();
        $venues = $venueModel->all();
        $requestModel = new VenueUpdateRequest();

        $this->view('admin/venues/index', [
            'title' => 'Venues',
            'heading' => 'Manage Venues',
            'venues' => $venues,
            'pendingUpdateRequests' => $requestModel->countPending(),
        ]);
    }

    public function create(): void
    {
        RequireAuth::anyRole(['admin', 'super_admin']);
        $userModel = new User();

        $this->view('admin/venues/create', [
            'title' => 'Add Venue',
            'heading' => 'Add Venue',
            'errors' => [],
            'old' => [],
            'venueManagers' => $userModel->usersByRole(['venue_manager']),
        ]);
    }

    public function store(): void
    {
        RequireAuth::anyRole(['admin', 'super_admin']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=admin-venues-create');
            exit;
        }

        $name = trim($_POST['name'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $contactPerson = trim($_POST['contact_person'] ?? '');
        $contactEmail = trim($_POST['contact_email'] ?? '');
        $contactPhone = trim($_POST['contact_phone'] ?? '');
        $venueManagerUserId = ($_POST['venue_manager_user_id'] ?? '') !== '' ? (int) $_POST['venue_manager_user_id'] : null;
        $foodDealDescription = trim($_POST['food_deal_description'] ?? '');
        $notes = trim($_POST['notes'] ?? '');
        $status = trim($_POST['status'] ?? 'active');

        $errors = [];

        if ($name === '') {
            $errors[] = 'Venue name is required.';
        }

        if ($contactEmail !== '' && !filter_var($contactEmail, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Contact email must be valid.';
        }

        if (!in_array($status, ['active', 'inactive'], true)) {
            $errors[] = 'Invalid venue status.';
        }

        $venueModel = new Venue();

        $slug = makeSlug($name);
        $baseSlug = $slug;
        $counter = 2;

        while ($venueModel->slugExists($slug)) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        $old = [
            'name' => $name,
            'address' => $address,
            'city' => $city,
            'contact_person' => $contactPerson,
            'contact_email' => $contactEmail,
            'contact_phone' => $contactPhone,
            'venue_manager_user_id' => $venueManagerUserId,
            'food_deal_description' => $foodDealDescription,
            'notes' => $notes,
            'status' => $status,
        ];

        if (!empty($errors)) {
            $userModel = new User();
            $this->view('admin/venues/create', [
                'title' => 'Add Venue',
                'heading' => 'Add Venue',
                'errors' => $errors,
                'old' => $old,
                'venueManagers' => $userModel->usersByRole(['venue_manager']),
            ]);
            return;
        }

        $venueId = $venueModel->create([
            'name' => $name,
            'slug' => $slug,
            'address' => $address !== '' ? $address : null,
            'city' => $city !== '' ? $city : null,
            'contact_person' => $contactPerson !== '' ? $contactPerson : null,
            'contact_email' => $contactEmail !== '' ? $contactEmail : null,
            'contact_phone' => $contactPhone !== '' ? $contactPhone : null,
            'venue_manager_user_id' => $venueManagerUserId,
            'food_deal_description' => $foodDealDescription !== '' ? $foodDealDescription : null,
            'notes' => $notes !== '' ? $notes : null,
            'status' => $status,
        ]);

        $auditLog = new AuditLog();
        $auditLog->create(
            Auth::id(),
            'venue_created',
            'venue',
            $venueId,
            'Venue created: ' . $name
        );

        header('Location: index.php?page=admin-venues');
        exit;
    }

    public function edit(): void
    {
        RequireAuth::anyRole(['admin', 'super_admin']);

        $id = (int) ($_GET['id'] ?? 0);

        if ($id <= 0) {
            header('Location: index.php?page=admin-venues');
            exit;
        }

        $venueModel = new Venue();
        $venue = $venueModel->findById($id);
        $userModel = new User();

        if (!$venue) {
            http_response_code(404);
            echo '<h1>404 - Venue not found</h1>';
            return;
        }

        $this->view('admin/venues/edit', [
            'title' => 'Edit Venue',
            'heading' => 'Edit Venue',
            'venue' => $venue,
            'errors' => [],
            'old' => $venue,
            'venueManagers' => $userModel->usersByRole(['venue_manager']),
        ]);
    }

    public function update(): void
    {
        RequireAuth::anyRole(['admin', 'super_admin']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=admin-venues');
            exit;
        }

        $id = (int) ($_POST['id'] ?? 0);

        if ($id <= 0) {
            header('Location: index.php?page=admin-venues');
            exit;
        }

        $name = trim($_POST['name'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $contactPerson = trim($_POST['contact_person'] ?? '');
        $contactEmail = trim($_POST['contact_email'] ?? '');
        $contactPhone = trim($_POST['contact_phone'] ?? '');
        $venueManagerUserId = ($_POST['venue_manager_user_id'] ?? '') !== '' ? (int) $_POST['venue_manager_user_id'] : null;
        $foodDealDescription = trim($_POST['food_deal_description'] ?? '');
        $notes = trim($_POST['notes'] ?? '');
        $status = trim($_POST['status'] ?? 'active');

        $errors = [];

        if ($name === '') {
            $errors[] = 'Venue name is required.';
        }

        if ($contactEmail !== '' && !filter_var($contactEmail, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Contact email must be valid.';
        }

        if (!in_array($status, ['active', 'inactive'], true)) {
            $errors[] = 'Invalid venue status.';
        }

        $venueModel = new Venue();
        $venue = $venueModel->findById($id);

        if (!$venue) {
            http_response_code(404);
            echo '<h1>404 - Venue not found</h1>';
            return;
        }

        $old = [
            'id' => $id,
            'name' => $name,
            'slug' => $venue['slug'],
            'address' => $address,
            'city' => $city,
            'contact_person' => $contactPerson,
            'contact_email' => $contactEmail,
            'contact_phone' => $contactPhone,
            'venue_manager_user_id' => $venueManagerUserId,
            'food_deal_description' => $foodDealDescription,
            'notes' => $notes,
            'status' => $status,
        ];

        if (!empty($errors)) {
            $userModel = new User();
            $this->view('admin/venues/edit', [
                'title' => 'Edit Venue',
                'heading' => 'Edit Venue',
                'venue' => $venue,
                'errors' => $errors,
                'old' => $old,
                'venueManagers' => $userModel->usersByRole(['venue_manager']),
            ]);
            return;
        }

        $venueModel->update($id, [
            'name' => $name,
            'address' => $address !== '' ? $address : null,
            'city' => $city !== '' ? $city : null,
            'contact_person' => $contactPerson !== '' ? $contactPerson : null,
            'contact_email' => $contactEmail !== '' ? $contactEmail : null,
            'contact_phone' => $contactPhone !== '' ? $contactPhone : null,
            'venue_manager_user_id' => $venueManagerUserId,
            'food_deal_description' => $foodDealDescription !== '' ? $foodDealDescription : null,
            'notes' => $notes !== '' ? $notes : null,
            'status' => $status,
        ]);

        $auditLog = new AuditLog();
        $auditLog->create(
            Auth::id(),
            'venue_updated',
            'venue',
            $id,
            'Venue updated: ' . $name
        );

        header('Location: index.php?page=admin-venues');
        exit;
    }

    public function updateRequests(): void
    {
        RequireAuth::anyRole(['admin', 'super_admin']);

        $requestModel = new VenueUpdateRequest();

        $this->view('admin/venues/update_requests', [
            'title' => 'Venue Partner Requests',
            'heading' => 'Venue Partner Requests',
            'requests' => $requestModel->pendingForAdmin(),
        ]);
    }

    public function approveUpdateRequest(): void
    {
        $this->reviewUpdateRequest('approve');
    }

    public function rejectUpdateRequest(): void
    {
        $this->reviewUpdateRequest('reject');
    }

    private function reviewUpdateRequest(string $action): void
    {
        RequireAuth::anyRole(['admin', 'super_admin']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=admin-venue-update-requests');
            exit;
        }

        $requestId = (int) ($_POST['id'] ?? 0);
        $reviewNotes = trim($_POST['review_notes'] ?? '');
        $requestModel = new VenueUpdateRequest();
        $request = $requestModel->findById($requestId);

        if (!$request) {
            http_response_code(404);
            echo '<h1>404 - Request not found</h1>';
            return;
        }

        $ok = $action === 'approve'
            ? $requestModel->approve($requestId, Auth::id(), $reviewNotes !== '' ? $reviewNotes : null)
            : $requestModel->reject($requestId, Auth::id(), $reviewNotes !== '' ? $reviewNotes : null);

        if ($ok) {
            $auditLog = new AuditLog();
            $auditLog->create(
                Auth::id(),
                $action === 'approve' ? 'venue_partner_update_approved' : 'venue_partner_update_rejected',
                'venue_update_request',
                $requestId,
                ucfirst($action) . 'd Venue Partner update request for venue: ' . ($request['venue_name'] ?? $request['venue_id'])
            );
        }

        header('Location: index.php?page=admin-venue-update-requests');
        exit;
    }
}
