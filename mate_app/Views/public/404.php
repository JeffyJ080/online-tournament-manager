<?php
$heading = $heading ?? 'Page not found';
?>

<section class="card">
    <h1><?= htmlspecialchars($heading) ?></h1>

    <p>
        The page or event you are looking for does not exist, or it is no longer available.
    </p>

    <div class="hero-actions">
        <a class="btn" href="index.php?page=events">View Events</a>
        <a class="btn btn-outline" href="index.php?page=home">Go Home</a>
    </div>
</section>