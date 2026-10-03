<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

require_admin_login();
$adminId = $_SESSION['user_id'];
$formId  = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare('SELECT id FROM forms WHERE id = ? AND admin_id = ?');
$stmt->execute([$formId, $adminId]);
if (!$stmt->fetch()) {
    set_flash('error', 'Form not found.');
    redirect('admin/forms/index.php');
}

// form_fields, form_submissions, submission_values all cascade-delete via foreign keys
$stmt = $pdo->prepare('DELETE FROM forms WHERE id = ?');
$stmt->execute([$formId]);

set_flash('success', 'Form and all its submissions have been permanently deleted.');
redirect('admin/forms/index.php');
