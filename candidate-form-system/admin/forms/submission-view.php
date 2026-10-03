<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

require_admin_login();
$adminId = $_SESSION['user_id'];
$submissionId = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare(
    'SELECT s.*, f.title AS form_title, f.id AS form_id, cp.name AS candidate_name
     FROM form_submissions s
     JOIN forms f ON f.id = s.form_id
     LEFT JOIN candidate_profiles cp ON cp.id = s.candidate_id
     WHERE s.id = ? AND f.admin_id = ?'
);
$stmt->execute([$submissionId, $adminId]);
$submission = $stmt->fetch();

if (!$submission) {
    set_flash('error', 'Submission not found.');
    redirect('admin/forms/index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newStatus = $_POST['application_status'];
    $validStatuses = ['SUBMITTED', 'UNDER_REVIEW', 'ACCEPTED', 'REJECTED', 'ARCHIVED'];
    if (in_array($newStatus, $validStatuses, true)) {
        $stmt = $pdo->prepare('UPDATE form_submissions SET application_status = ? WHERE id = ?');
        $stmt->execute([$newStatus, $submissionId]);
        set_flash('success', 'Status updated to ' . $newStatus . '.');
        redirect('admin/forms/submission-view.php?id=' . $submissionId);
    }
}

$stmt = $pdo->prepare(
    'SELECT ff.field_label, ff.field_type, sv.field_value, sv.file_path
     FROM submission_values sv
     JOIN form_fields ff ON ff.id = sv.form_field_id
     WHERE sv.submission_id = ?
     ORDER BY ff.display_order'
);
$stmt->execute([$submissionId]);
$values = $stmt->fetchAll();

$flash = get_flash();

$statusOptions = ['SUBMITTED', 'UNDER_REVIEW', 'ACCEPTED', 'REJECTED', 'ARCHIVED'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Submission #<?= $submissionId ?> - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="../../public/assets/style.css">
</head>
<body>
    <?php include __DIR__ . '/../../includes/admin-header.php'; ?>
    <div class="admin-page">
        <div class="page-header">
            <h1><?= sanitize($submission['form_title']) ?> — Submission #<?= $submissionId ?></h1>
            <a href="submissions.php?form_id=<?= $submission['form_id'] ?>" class="btn btn-secondary">Back to Submissions</a>
        </div>

        <?php if ($flash): ?>
            <div class="flash <?= $flash['type'] ?>"><?= sanitize($flash['message']) ?></div>
        <?php endif; ?>

        <div class="card">
            <h2 style="font-size:15px;margin-top:0;">Status</h2>
            <p style="font-size:13px;color:var(--text-muted);">
                Account: <?= $submission['candidate_id'] ? 'Linked — ' . sanitize($submission['candidate_name']) : 'Anonymous (not linked)' ?><br>
                Submitted: <?= date('d M Y, h:i A', strtotime($submission['submitted_at'])) ?>
            </p>
            <form method="POST" style="display:flex;gap:10px;align-items:center;">
                <select name="application_status">
                    <?php foreach ($statusOptions as $opt): ?>
                        <option value="<?= $opt ?>" <?= $submission['application_status'] === $opt ? 'selected' : '' ?>><?= $opt ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-sm">Update Status</button>
            </form>
        </div>

        <div class="card">
            <h2 style="font-size:15px;margin-top:0;">Submitted Data</h2>
            <table class="data-table">
                <?php foreach ($values as $v): ?>
                    <tr>
                        <th style="width:220px;"><?= sanitize($v['field_label']) ?></th>
                        <td>
                            <?php if ($v['field_type'] === 'FILE'): ?>
                                <?php if ($v['file_path']): ?>
                                    <a href="../../<?= sanitize($v['file_path']) ?>" target="_blank">Download file</a>
                                <?php else: ?>
                                    <em>No file uploaded</em>
                                <?php endif; ?>
                            <?php else: ?>
                                <?= nl2br(sanitize($v['field_value'])) ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>
</body>
</html>
