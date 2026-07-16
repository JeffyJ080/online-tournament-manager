<?php

function absolute_url(string $path): string
{
    $config = require __DIR__ . '/../../mate_config/app.php';
    $baseUrl = rtrim((string) ($config['app_url'] ?? ''), '/');

    if (
        $baseUrl === '' ||
        str_contains($baseUrl, 'localhost') ||
        str_contains($baseUrl, '127.0.0.1')
    ) {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? '';

        if ($host !== '') {
            $scriptDir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
            $baseUrl = $scheme . '://' . $host . ($scriptDir === '/' ? '' : $scriptDir);
        }
    }

    return rtrim($baseUrl, '/') . '/' . ltrim($path, '/');
}
