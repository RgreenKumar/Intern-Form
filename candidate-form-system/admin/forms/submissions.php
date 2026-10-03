<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

require_admin_login();
$adminId = $_SESSION['user_id'];
$formId  = (int)($_GET['form_id'] ?? 0);

$stmt = $pdo->prepare('SELECT * FROM forms WHERE id = ? AND admin_id = ?');
$stmt->execute([$formId, $adminId]);
$form = $stmt->fetch();

if (!$form) {
    set_flash('error', 'Form not found.');
    redirect('admin/forms/index.php');
}

// Find the field used to capture "name" and "email" for display in the list (best-effort)
$stmt = $pdo->prepare('SELECT id, field_label, field_type FROM form_fields WHERE form_id = ? ORDER BY display_order');
$stmt->execute([$formId]);
$formFields = $stmt->fetchAll();

$nameFieldId  = null;
$emailFieldId = null;
foreach ($formFields as $ff) {
    if ($emailFieldId === null && $ff['field_type'] === 'EMAIL') $emailFieldId = $ff['id'];
    if ($nameFieldId === null && stripos($ff['field_label'], 'name') !== false && $ff['field_type'] !== 'EMAIL') $nameFieldId = $ff['id'];
}

$stmt = $pdo->prepare(
    'SELECT s.*, cp.name AS candidate_name
     FROM form_submissions s
     LEFT JOIN candidate_profiles cp ON cp.id = s.candidate_id
     WHERE s.form_id = ?
     ORDER BY s.submitted_at DESC'
);
$stmt->execute([$formId]);
$submissions = $stmt->fetchAll();

// Pull display name/email per submission from submission_values if not linked to an account
foreach ($submissions as &$s) {
    $s['display_email'] = null;
    if ($emailFieldId) {
        $stmt2 = $pdo->prepare('SELECT field_value FROM submission_values WHERE submission_id = ? AND form_field_id = ?');
        $stmt2->execute([$s['id'], $emailFieldId]);
        $s['display_email'] = $stmt2->fetchColumn();
    }
    $s['display_name'] = $s['candidate_name'];
    if (!$s['display_name'] && $nameFieldId) {
        $stmt2 = $pdo->prepare('SELECT field_value FROM submission_values WHERE submission_id = ? AND form_field_id = ?');
        $stmt2->execute([$s['id'], $nameFieldId]);
        $s['display_name'] = $stmt2->fetchColumn();
    }
}
unset($s);

$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Submissions - <?= sanitize($form['title']) ?> - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="../../public/assets/style.css">
</head>
<body>
    <?php include __DIR__ . '/../../includes/admin-header.php'; ?>
    <div class="admin-page">
        <div class="page-header">
            <h1><?= sanitize($form['title']) ?> — Submissions</h1>
            <a href="index.php" class="btn btn-secondary">Back to Forms</a>
        </div>

        <?php if ($flash): ?>
            <div class="flash <?= $flash['type'] ?>"><?= sanitize($flash['message']) ?></div>
        <?php endif; ?>

        <?php if (empty($submissions)): ?>
            <div class="card empty-state">No submissions yet for this form.</div>
        <?php else: ?>
            <div class="table-wrap"><table class="data-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Account</th>
                        <th>Status</th>
                        <th>Submitted</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($submissions as $s): ?>
                    <tr>
                        <td><?= sanitize($s['display_name'] ?? '—') ?></td>
                        <td><?= sanitize($s['display_email'] ?? '—') ?></td>
                        <td>
                            <?= $s['candidate_id']
                                ? '<span class="badge badge-active">Linked</span>'
                                : '<span class="badge badge-draft">Anonymous</span>' ?>
                        </td>
                        <td>
                            <span class="badge badge-<?= strtolower($s['application_status']) === 'accepted' ? 'active' : (strtolower($s['application_status']) === 'rejected' ? 'closed' : 'draft') ?>">
                                <?= sanitize($s['application_status']) ?>
                            </span>
                        </td>
                        <td><?= date('d M Y, h:i A', strtotime($s['submitted_at'])) ?></td>
                        <td>
                            <a href="submission-view.php?id=<?= $s['id'] ?>" class="btn btn-sm btn-secondary">View</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table></div>
        <?php endif; ?>
    </div>
</body>
</html>
