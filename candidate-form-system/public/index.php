<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

[$grouped, $openCount] = get_open_forms_grouped($pdo);
$hasHeroPhoto = file_exists(__DIR__ . '/assets/hero.jpg');
$activePage = 'home';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= APP_NAME ?> — Internships & Workshops</title>
    <link rel="stylesheet" href="assets/style.css">
    <link rel="stylesheet" href="assets/landing.css">
</head>
<body>
<div class="page">
    <?php include __DIR__ . '/partials/site-nav.php'; ?>

    <!-- ===== Hero ===== -->
    <section class="hero">
        <div>
            <div class="hero-chip"><i></i> <?= $openCount ?> opportunit<?= $openCount === 1 ? 'y' : 'ies' ?> open right now</div>
            <h1>Start your career with <em>real internships</em> &amp; workshops.</h1>
            <p class="lead">Apply in minutes, track your application live, complete tasks and receive your offer letters and certificates — all in one place.</p>
            <div class="hero-cta">
                <a href="internships.php" class="btn-lg btn-primary-lg">Explore Internships</a>
                <a href="workshops.php" class="btn-lg btn-ghost-lg">Explore Workshops</a>
            </div>
        </div>
        <div class="hero-visual">
            <?php if ($hasHeroPhoto): ?>
                <img src="assets/hero.jpg" class="hero-photo" alt="">
            <?php else: ?>
                <div class="mock-card mc-1"><b>Internship Application</b><small>Reviewed by the admin team</small><br><span class="mc-pill">ACCEPTED 🎉</span></div>
                <div class="mock-card mc-2"><b>📄 Offer Letter</b><small>Ready to download</small></div>
                <div class="mock-card mc-3"><b>✅ Submit project report</b><small>Task · Due soon · High priority</small></div>
            <?php endif; ?>
        </div>
    </section>

    <!-- ===== Trust strip ===== -->
    <section class="trust-strip">
        <div>🔒 <span>Secure Applications<small>Your data stays protected</small></span></div>
        <div>✅ <span>Verified Opportunities<small>Published by the admin team</small></span></div>
        <div>⚡ <span>Live Status Tracking<small>Know where you stand</small></span></div>
        <div>📄 <span>Official Documents<small>Offer letters &amp; certificates</small></span></div>
    </section>

    <!-- ===== Internships preview ===== -->
    <section class="section">
        <div class="eyebrow">Internships</div>
        <h2>Open internship registrations</h2>
        <p class="sub">Pick an internship and register with its own application form.</p>
        <?php render_program_cards(array_slice($grouped['internship'], 0, 3), '🎯', 'Internship', 'No internships are open right now — please check back soon.'); ?>
        <?php if (count($grouped['internship']) > 0): ?>
            <p style="margin-top:18px;"><a href="internships.php" style="color:var(--accent);font-weight:700;font-size:13.5px;text-decoration:none;">See all internships →</a></p>
        <?php endif; ?>
    </section>

    <!-- ===== Workshops preview ===== -->
    <section class="section">
        <div class="eyebrow">Workshops</div>
        <h2>Upcoming workshop registrations</h2>
        <p class="sub">Hands-on sessions with limited seats. Register with the workshop's own form.</p>
        <?php render_program_cards(array_slice($grouped['workshop'], 0, 3), '🛠️', 'Workshop', 'No workshops are open right now — please check back soon.'); ?>
        <?php if (count($grouped['workshop']) > 0): ?>
            <p style="margin-top:18px;"><a href="workshops.php" style="color:var(--accent);font-weight:700;font-size:13.5px;text-decoration:none;">See all workshops →</a></p>
        <?php endif; ?>
    </section>

    <!-- ===== How it works ===== -->
    <section class="section" id="how">
        <div class="eyebrow">How it works</div>
        <h2>From application to certificate</h2>
        <p class="sub">Three simple steps, everything tracked in your dashboard. <a href="how-it-works.php" style="color:var(--accent);font-weight:700;text-decoration:none;">Learn more →</a></p>
        <div class="steps">
            <div class="step"><div class="step-num">1</div><h3>Apply</h3><p>Choose an internship or workshop and fill its registration form. Create an account to save your application.</p></div>
            <div class="step"><div class="step-num">2</div><h3>Get reviewed</h3><p>The admin team reviews your application. Follow the status live: Under Review, Accepted or Rejected.</p></div>
            <div class="step"><div class="step-num">3</div><h3>Learn &amp; get certified</h3><p>Complete assigned tasks, chat on each task, and download your offer letter and certificate.</p></div>
        </div>
    </section>

    <?php include __DIR__ . '/partials/site-footer.php'; ?>
</div>
</body>
</html>
