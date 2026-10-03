<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_candidate_login();
$candidateId = $_SESSION['user_id'];

$stmt = $pdo->prepare('SELECT COUNT(*) FROM form_submissions WHERE candidate_id = ?');
$stmt->execute([$candidateId]);
$applicationCount = $stmt->fetchColumn();

$stmt = $pdo->prepare(
    "SELECT COUNT(*) FROM form_submissions WHERE candidate_id = ? AND application_status = 'ACCEPTED'"
);
$stmt->execute([$candidateId]);
$acceptedCount = $stmt->fetchColumn();

$stmt = $pdo->prepare(
    "SELECT COUNT(*) FROM tasks WHERE candidate_id = ? AND status NOT IN ('COMPLETED', 'CANCELLED')"
);
$stmt->execute([$candidateId]);
$openTaskCount = $stmt->fetchColumn();

$stmt = $pdo->prepare(
    "SELECT COUNT(*) FROM candidate_documents WHERE candidate_id = ? AND document_status = 'AVAILABLE'"
);
$stmt->execute([$candidateId]);
$documentCount = $stmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Candidate Dashboard - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="../public/assets/style.css">
</head>
<body>
    <?php include __DIR__ . '/../includes/candidate-header.php'; ?>
    <div class="admin-page">
        <div class="welcome-banner">
            <h1>Welcome, <?= sanitize($_SESSION['name']) ?> 👋</h1>
            <p>Track your applications, tasks and documents all in one place.</p>
        </div>

        <div class="stat-grid">
            <div class="stat-card"><span class="stat-icon">📥</span><div class="stat-num"><?= (int)$applicationCount ?></div><div class="stat-label">Applications Submitted</div></div>
            <div class="stat-card"><span class="stat-icon">🎉</span><div class="stat-num"><?= (int)$acceptedCount ?></div><div class="stat-label">Accepted</div></div>
            <div class="stat-card"><span class="stat-icon">✅</span><div class="stat-num"><?= (int)$openTaskCount ?></div><div class="stat-label">Open Tasks</div></div>
            <div class="stat-card"><span class="stat-icon">📄</span><div class="stat-num"><?= (int)$documentCount ?></div><div class="stat-label">Documents Available</div></div>
        </div>
    </div>
</body>
</html>
