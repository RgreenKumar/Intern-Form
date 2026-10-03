<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

require_admin_login();
$adminId = $_SESSION['user_id'];
$fieldId = (int)($_GET['id'] ?? 0);
$formId  = (int)($_GET['form_id'] ?? 0);

// Verify the field belongs to a form owned by this admin
$stmt = $pdo->prepare(
    'SELECT ff.id FROM form_fields ff
     JOIN forms f ON f.id = ff.form_id
     WHERE ff.id = ? AND f.id = ? AND f.admin_id = ?'
);
$stmt->execute([$fieldId, $formId, $adminId]);

if ($stmt->fetch()) {
    $stmt = $pdo->prepare('DELETE FROM form_fields WHERE id = ?');
    $stmt->execute([$fieldId]);
    set_flash('success', 'Field deleted.');
} else {
    set_flash('error', 'Field not found.');
}

redirect('admin/forms/fields.php?form_id=' . $formId);
