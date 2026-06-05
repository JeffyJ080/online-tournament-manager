<?php

require_once __DIR__ . '/../Helpers/Auth.php';

class RequireAuth
{
    public static function check(): void
    {
        if (!Auth::check()) {
            header('Location: index.php?page=login');
            exit;
        }
    }

    public static function role(string $role): void
    {
        self::check();

        if (!Auth::is($role)) {
            http_response_code(403);
            echo '<h1>403 - Access denied</h1>';
            echo '<p>You do not have permission to access this page.</p>';
            exit;
        }
    }

    public static function anyRole(array $roles): void
    {
        self::check();

        if (!Auth::hasAnyRole($roles)) {
            http_response_code(403);
            echo '<h1>403 - Access denied</h1>';
            echo '<p>You do not have permission to access this page.</p>';
            exit;
        }
    }
}