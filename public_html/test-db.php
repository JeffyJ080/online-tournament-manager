<?php
    require_once __DIR__ . '/../mate_app/Core/Database.php';

    $db = Database::connect();

    echo 'Database connection works.';
?>