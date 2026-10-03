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

$stmt = $pdo->prepare('UPDATE forms SET status = "ARCHIVED" WHERE id = ?');
$stmt->execute([$formId]);

set_flash('success', 'Form archived. It is no longer publicly visible.');
redirect('admin/forms/index.php');
