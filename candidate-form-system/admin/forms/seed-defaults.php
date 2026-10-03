<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

require_admin_login();

$created = seed_default_forms($pdo, $_SESSION['user_id']);

if ($created > 0) {
    set_flash('success', $created . ' default form(s) added and published. You can edit them anytime.');
} else {
    set_flash('success', 'You already have the default forms. Nothing new to add.');
}

redirect('admin/forms/index.php');
