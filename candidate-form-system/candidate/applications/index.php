<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

require_candidate_login();
$candidateId = $_SESSION['user_id'];

$stmt = $pdo->prepare(
    'SELECT s.*, f.title AS form_title
     FROM form_submissions s
     JOIN forms f ON f.id = s.form_id
     WHERE s.candidate_id = ?
     ORDER BY s.submitted_at DESC'
);
$stmt->execute([$candidateId]);
$applications = $stmt->fetchAll();

function app_status_class($s) {
    if ($s === 'ACCEPTED') return 'badge-active';
    if ($s === 'REJECTED') return 'badge-closed';
    if ($s === 'ARCHIVED') return 'badge-archived';
    return 'badge-draft'; // SUBMITTED, UNDER_REVIEW
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Applications - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="../../public/assets/style.css">
</head>
<body>
    <?php include __DIR__ . '/../../includes/candidate-header.php'; ?>
    <div class="admin-page">
        <div class="page-header">
            <h1>My Applications</h1>
        </div>

        <?php if (empty($applications)): ?>
            <div class="card empty-state">You haven't submitted any applications yet.</div>
        <?php else: ?>
            <div class="table-wrap"><table class="data-table">
                <thead>
                    <tr>
                        <th>Form</th>
                        <th>Status</th>
                        <th>Submitted</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($applications as $a): ?>
                    <tr>
                        <td><?= sanitize($a['form_title']) ?></td>
                        <td><span class="badge <?= app_status_class($a['application_status']) ?>"><?= sanitize($a['application_status']) ?></span></td>
                        <td><?= date('d M Y, h:i A', strtotime($a['submitted_at'])) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table></div>
        <?php endif; ?>
    </div>
</body>
</html>
