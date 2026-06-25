<?php

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrf_verify(?string $token): bool
{
    return is_string($token)
        && isset($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

function csrf_inject_forms(string $html): string
{
    $token = htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8');
    $field = '<input type="hidden" name="csrf_token" value="' . $token . '">';

    return preg_replace_callback(
        '/<form\b([^>]*)>/i',
        function (array $matches) use ($field): string {
            $attributes = $matches[1];

            if (!preg_match('/method\s*=\s*["\']?post["\']?/i', $attributes)) {
                return $matches[0];
            }

            return '<form' . $attributes . '>' . $field;
        },
        $html
    );
}
