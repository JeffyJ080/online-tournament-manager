<?php

class Controller
{
    protected function view(string $viewPath, array $data = []): void
    {
        extract($data);

        require __DIR__ . '/../Views/layouts/header.php';
        require __DIR__ . '/../Views/' . $viewPath . '.php';
        require __DIR__ . '/../Views/layouts/footer.php';
    }
}