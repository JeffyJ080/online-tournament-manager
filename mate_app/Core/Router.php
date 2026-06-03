<?php

class Router
{
    private array $routes = [];

    public function get(string $page, callable $callback): void
    {
        $this->routes[$page] = $callback;
    }

    public function dispatch(): void
    {
        $page = $_GET['page'] ?? 'home';

        if (array_key_exists($page, $this->routes)) {
            call_user_func($this->routes[$page]);
            return;
        }

        http_response_code(404);
        echo '<h1>404 - Page not found</h1>';
        echo '<p>The page you are looking for does not exist.</p>';
    }
}