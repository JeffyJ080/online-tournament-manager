<?php

require_once __DIR__ . '/../Core/Controller.php';
require_once __DIR__ . '/../Middleware/RequireAuth.php';
require_once __DIR__ . '/../Models/AuditLog.php';

class AdminAuditController extends Controller
{
    public function index(): void
    {
        RequireAuth::anyRole(['admin', 'super_admin']);

        $auditLog = new AuditLog();

        $this->view('admin/audit/index', [
            'title' => 'Audit Log',
            'heading' => 'Audit Log',
            'logs' => $auditLog->latest(250),
        ]);
    }
}
