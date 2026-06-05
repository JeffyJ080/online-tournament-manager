<?php

require_once __DIR__ . '/../Core/Controller.php';
require_once __DIR__ . '/../Middleware/RequireAuth.php';
require_once __DIR__ . '/../Models/Venue.php';
require_once __DIR__ . '/../Helpers/slug.php';
require_once __DIR__ . '/../Models/AuditLog.php';

class AdminVenueController extends Controller
{
    public function index(): void
    {
        RequireAuth::anyRole(['admin', 'super_admin']);

        $venueModel = new Venue();
        $venues = $venueModel->all();

        $this->view('admin/venues/index', [
            'title' => 'Venues',
            'heading' => 'Manage Venues',
            'venues' => $venues,
        ]);
    }

    public function create(): void
    {
        RequireAuth::anyRole(['admin', 'super_admin']);

        $this->view('admin/venues/create', [
            'title' => 'Add Venue',
            'heading' => 'Add Venue',
            'errors' => [],
            'old' => [],
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
            'food_deal_description' => $foodDealDescription,
            'notes' => $notes,
            'status' => $status,
        ];

        if (!empty($errors)) {
            $this->view('admin/venues/create', [
                'title' => 'Add Venue',
                'heading' => 'Add Venue',
                'errors' => $errors,
                'old' => $old,
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
}