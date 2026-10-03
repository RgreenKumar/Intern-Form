<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_admin_login();
$adminId = $_SESSION['user_id'];

$stmt = $pdo->prepare('SELECT COUNT(*) FROM forms WHERE admin_id = ?');
$stmt->execute([$adminId]);
$formCount = $stmt->fetchColumn();

$stmt = $pdo->prepare(
    'SELECT COUNT(*) FROM form_submissions s JOIN forms f ON f.id = s.form_id WHERE f.admin_id = ?'
);
$stmt->execute([$adminId]);
$submissionCount = $stmt->fetchColumn();

$stmt = $pdo->prepare(
    "SELECT COUNT(*) FROM form_submissions s JOIN forms f ON f.id = s.form_id
     WHERE f.admin_id = ? AND s.application_status = 'UNDER_REVIEW'"
);
$stmt->execute([$adminId]);
$underReviewCount = $stmt->fetchColumn();

$stmt = $pdo->prepare(
    "SELECT COUNT(*) FROM tasks WHERE admin_id = ? AND status NOT IN ('COMPLETED', 'CANCELLED')"
);
$stmt->execute([$adminId]);
$openTaskCount = $stmt->fetchColumn();

$stmt = $pdo->prepare('SELECT COUNT(*) FROM candidate_documents WHERE admin_id = ?');
$stmt->execute([$adminId]);
$documentCount = $stmt->fetchColumn();

$stmt = $pdo->query('SELECT COUNT(*) FROM candidate_profiles');
$candidateCount = $stmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="../public/assets/style.css">
</head>
<body>
    <?php include __DIR__ . '/../includes/admin-header.php'; ?>
    <div class="admin-page">
        <div class="welcome-banner">
            <h1>Welcome back, <?= sanitize($_SESSION['name']) ?> 👋</h1>
            <p>Here's what's happening across your forms, tasks and candidates today.</p>
        </div>

        <div class="stat-grid">
            <div class="stat-card"><span class="stat-icon">📋</span><div class="stat-num"><?= (int)$formCount ?></div><div class="stat-label">Forms Created</div></div>
            <div class="stat-card"><span class="stat-icon">📥</span><div class="stat-num"><?= (int)$submissionCount ?></div><div class="stat-label">Total Submissions</div></div>
            <div class="stat-card"><span class="stat-icon">🔎</span><div class="stat-num"><?= (int)$underReviewCount ?></div><div class="stat-label">Under Review</div></div>
            <div class="stat-card"><span class="stat-icon">✅</span><div class="stat-num"><?= (int)$openTaskCount ?></div><div class="stat-label">Open Tasks</div></div>
            <div class="stat-card"><span class="stat-icon">📄</span><div class="stat-num"><?= (int)$documentCount ?></div><div class="stat-label">Documents Issued</div></div>
            <div class="stat-card"><span class="stat-icon">👥</span><div class="stat-num"><?= (int)$candidateCount ?></div><div class="stat-label">Registered Candidates</div></div>
        </div>
    </div>
</body>
</html>
