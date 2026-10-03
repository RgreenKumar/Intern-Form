<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

[$grouped, $openCount] = get_open_forms_grouped($pdo);
$activePage = 'workshops';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Workshops - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="assets/style.css">
    <link rel="stylesheet" href="assets/landing.css">
</head>
<body>
<div class="page">
    <?php include __DIR__ . '/partials/site-nav.php'; ?>

    <section class="page-hero">
        <div class="eyebrow">Workshops</div>
        <h1>🛠️ Upcoming Workshop Registrations</h1>
        <p>Hands-on sessions with limited seats. Each workshop has its own registration form — pick yours and register directly.</p>
    </section>

    <section class="section">
        <?php render_program_cards($grouped['workshop'], '🛠️', 'Workshop', 'No workshops are open right now — please check back soon.'); ?>
    </section>

    <?php include __DIR__ . '/partials/site-footer.php'; ?>
</div>
</body>
</html>
