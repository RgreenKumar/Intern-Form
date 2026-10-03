<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

$dashboardUrl = null;
if (!empty($_SESSION['role'])) {
    $dashboardUrl = $_SESSION['role'] === 'ADMIN' ? '../admin/dashboard.php' : '../candidate/dashboard.php';
}
$activePage = 'how';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>How it Works - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="assets/style.css">
    <link rel="stylesheet" href="assets/landing.css">
    <style>
        .detail-steps { display: flex; flex-direction: column; gap: 18px; margin-top: 10px; }
        .detail-step {
            display: flex; gap: 20px; background: var(--panel); border: 1px solid var(--border);
            border-radius: 14px; padding: 26px; box-shadow: 0 4px 16px rgba(28,29,31,0.05);
        }
        .detail-step .step-num { flex-shrink: 0; width: 44px; height: 44px; font-size: 18px; }
        .detail-step h3 { font-size: 17px; margin: 0 0 6px; }
        .detail-step p { color: var(--text-muted); font-size: 14px; line-height: 1.65; margin: 0; }
    </style>
</head>
<body>
<div class="page">
    <?php include __DIR__ . '/partials/site-nav.php'; ?>

    <section class="page-hero">
        <div class="eyebrow">How it works</div>
        <h1>From application to certificate</h1>
        <p>Everything you do — applying, tracking status, completing tasks, collecting documents — happens in one place on <?= APP_NAME ?>.</p>
    </section>

    <section class="section">
        <div class="detail-steps">
            <div class="detail-step">
                <div class="step-num">1</div>
                <div><h3>Apply to an internship or workshop</h3>
                <p>Browse open <a href="internships.php" style="color:var(--accent);font-weight:700;text-decoration:none;">Internships</a> or <a href="workshops.php" style="color:var(--accent);font-weight:700;text-decoration:none;">Workshops</a> and fill in that program's own registration form. If the program requires it, create a free account so your application is saved to it — new or returning candidates are handled automatically.</p></div>
            </div>
            <div class="detail-step">
                <div class="step-num">2</div>
                <div><h3>Track your application status live</h3>
                <p>Once submitted, your application moves through Submitted → Under Review → Accepted/Rejected. Check "My Applications" in your dashboard anytime to see exactly where you stand.</p></div>
            </div>
            <div class="detail-step">
                <div class="step-num">3</div>
                <div><h3>Complete assigned tasks</h3>
                <p>If you're accepted, the admin team may assign you tasks with due dates and priority. Update your progress and chat with the admin directly on each task.</p></div>
            </div>
            <div class="detail-step">
                <div class="step-num">4</div>
                <div><h3>Get your offer letter &amp; certificate</h3>
                <p>Offer letters and completion certificates are issued straight to your account — view and download them anytime from "My Documents".</p></div>
            </div>
        </div>

        <?php if (!$dashboardUrl): ?>
            <div style="text-align:center;margin-top:36px;">
                <a href="candidate-register.php" class="btn-lg btn-primary-lg" style="text-decoration:none;">Create Your Account</a>
            </div>
        <?php endif; ?>
    </section>

    <?php include __DIR__ . '/partials/site-footer.php'; ?>
</div>
</body>
</html>
