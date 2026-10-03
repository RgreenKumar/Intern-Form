<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

require_admin_login();
$adminId = $_SESSION['user_id'];
$fieldId = (int)($_GET['id'] ?? 0);
$formId  = (int)($_GET['form_id'] ?? 0);
$direction = $_GET['dir'] ?? '';

// Verify ownership
$stmt = $pdo->prepare(
    'SELECT ff.id, ff.display_order FROM form_fields ff
     JOIN forms f ON f.id = ff.form_id
     WHERE ff.id = ? AND f.id = ? AND f.admin_id = ?'
);
$stmt->execute([$fieldId, $formId, $adminId]);
$field = $stmt->fetch();

if (!$field) {
    set_flash('error', 'Field not found.');
    redirect('admin/forms/index.php');
}

// Find the neighboring field to swap display_order with
if ($direction === 'up') {
    $stmt = $pdo->prepare(
        'SELECT id, display_order FROM form_fields WHERE form_id = ? AND display_order < ? ORDER BY display_order DESC LIMIT 1'
    );
} else {
    $stmt = $pdo->prepare(
        'SELECT id, display_order FROM form_fields WHERE form_id = ? AND display_order > ? ORDER BY display_order ASC LIMIT 1'
    );
}
$stmt->execute([$formId, $field['display_order']]);
$neighbor = $stmt->fetch();

if ($neighbor) {
    $pdo->beginTransaction();
    try {
        $pdo->prepare('UPDATE form_fields SET display_order = ? WHERE id = ?')->execute([$neighbor['display_order'], $fieldId]);
        $pdo->prepare('UPDATE form_fields SET display_order = ? WHERE id = ?')->execute([$field['display_order'], $neighbor['id']]);
        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
    }
}

redirect('admin/forms/fields.php?form_id=' . $formId);
