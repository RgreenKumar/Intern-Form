<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

[$grouped, $openCount] = get_open_forms_grouped($pdo);
$activePage = 'internships';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Internships - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="assets/style.css">
    <link rel="stylesheet" href="assets/landing.css">
</head>
<body>
<div class="page">
    <?php include __DIR__ . '/partials/site-nav.php'; ?>

    <section class="page-hero">
        <div class="eyebrow">Internships</div>
        <h1>🎯 Open Internship Registrations</h1>
        <p>Browse every internship currently open for applications. Each one has its own registration form — pick yours and apply directly.</p>
    </section>

    <section class="section">
        <?php render_program_cards($grouped['internship'], '🎯', 'Internship', 'No internships are open right now — please check back soon.'); ?>
    </section>

    <?php include __DIR__ . '/partials/site-footer.php'; ?>
</div>
</body>
</html>
