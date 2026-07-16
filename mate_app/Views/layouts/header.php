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
        <a class="brand-link" href="index.php?page=home">Mate Tournaments</a>

        <ul class="desktop-nav">
            <li><a href="<?= url('home') ?>">Home</a></li>
            <li><a href="<?= url('events') ?>">Events</a></li>
            <li><a href="<?= url('venues') ?>">Venues</a></li>
            <li><a href="<?= url('leaderboard') ?>">Leaderboard</a></li>
            <li><a href="index.php?page=tournament-archive">Archive</a></li>
            <li><a href="<?= url('about') ?>">About</a></li>
            <li><a href="<?= url('contact') ?>">Contact</a></li>
            <?php if (Auth::check()): ?>
                <li><a href="<?= url('dashboard') ?>">Dashboard</a></li>
                <?php if (Auth::hasAnyRole(['admin', 'super_admin'])): ?>
                    <li><a href="<?= url('admin-players') ?>">Players</a></li>
                <?php endif; ?>
                <li><a href="<?= url('my-profile') ?>">Profile</a></li>
                <li><a href="<?= url('logout') ?>">Logout</a></li>
            <?php else: ?>
                <li><a href="<?= url('login') ?>">Login</a></li>
                <li><a href="<?= url('register') ?>">Register</a></li>
            <?php endif; ?>
        </ul>

        <details class="mobile-nav">
            <summary>Menu</summary>

            <ul>
                <li><a href="<?= url('home') ?>">Home</a></li>
                <li><a href="<?= url('events') ?>">Events</a></li>
                <li><a href="<?= url('venues') ?>">Venues</a></li>
                <li><a href="<?= url('leaderboard') ?>">Leaderboard</a></li>
                <li><a href="index.php?page=tournament-archive">Archive</a></li>
                <li><a href="<?= url('about') ?>">About</a></li>
                <li><a href="<?= url('contact') ?>">Contact</a></li>
                <?php if (Auth::check()): ?>
                    <li><a href="<?= url('dashboard') ?>">Dashboard</a></li>
                    <?php if (Auth::hasAnyRole(['admin', 'super_admin'])): ?>
                        <li><a href="<?= url('admin-players') ?>">Players</a></li>
                    <?php endif; ?>
                    <li><a href="<?= url('my-profile') ?>">Profile</a></li>
                    <li><a href="<?= url('logout') ?>">Logout</a></li>
                <?php else: ?>
                    <li><a href="<?= url('login') ?>">Login</a></li>
                    <li><a href="<?= url('register') ?>">Register</a></li>
                <?php endif; ?>
            </ul>
        </details>
    </nav>
</header>

<main>
