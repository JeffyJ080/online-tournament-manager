<?php
$appConfig = require __DIR__ . '/../../../mate_config/app.php';
require_once __DIR__ . '/../../Helpers/Auth.php';
$pageTitle = $title ?? $appConfig['app_name'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/css/style.css">
    <title><?= htmlspecialchars($pageTitle) ?> | <?= htmlspecialchars($appConfig['app_name']) ?></title>
</head>
<body>

<header>
    <nav>
        <a href="index.php?page=home">Mate Tournaments</a>

        <ul>
            <li><a href="index.php?page=home">Home</a></li>
            <li><a href="index.php?page=events">Events</a></li>
            <li><a href="index.php?page=leaderboards">Leaderboards</a></li>
            <li><a href="index.php?page=venues">Venues</a></li>
            <li><a href="index.php?page=about">About</a></li>
            <li><a href="index.php?page=contact">Contact</a></li>
            <?php if (Auth::check()): ?>
                <li><a href="index.php?page=dashboard">Dashboard</a></li>
                <li><a href="index.php?page=logout">Logout</a></li>
            <?php else: ?>
                <li><a href="index.php?page=login">Login</a></li>
                <li><a href="index.php?page=register">Register</a></li>
            <?php endif; ?>
        </ul>
    </nav>
</header>

<main>