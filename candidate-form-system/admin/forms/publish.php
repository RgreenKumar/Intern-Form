<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

require_admin_login();
$adminId = $_SESSION['user_id'];
$formId  = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare('SELECT * FROM forms WHERE id = ? AND admin_id = ?');
$stmt->execute([$formId, $adminId]);
$form = $stmt->fetch();

if (!$form) {
    set_flash('error', 'Form not found.');
    redirect('admin/forms/index.php');
}

if ($form['status'] === 'ACTIVE') {
    $newStatus = 'CLOSED';
    $message = 'Form closed.';
} else {
    // Require at least one field before publishing
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM form_fields WHERE form_id = ?');
    $stmt->execute([$formId]);
    if ($stmt->fetchColumn() == 0) {
        set_flash('error', 'Add at least one field before publishing this form.');
        redirect('admin/forms/fields.php?form_id=' . $formId);
    }
    $newStatus = 'ACTIVE';
    $message = 'Form published! It is now live at: ' . BASE_URL . '/public/form.php?slug=' . $form['slug'];
}

$stmt = $pdo->prepare('UPDATE forms SET status = ? WHERE id = ?');
$stmt->execute([$newStatus, $formId]);

set_flash('success', $message);
redirect('admin/forms/index.php');
