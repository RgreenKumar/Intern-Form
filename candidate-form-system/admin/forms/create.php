<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

require_admin_login();
$adminId = $_SESSION['user_id'];

function generate_unique_slug($pdo, $title) {
    $base = strtolower(trim(preg_replace('/[^A-Za-z0-9]+/', '-', $title), '-'));
    if ($base === '') $base = 'form';
    $slug = $base;
    $i = 1;
    $stmt = $pdo->prepare('SELECT id FROM forms WHERE slug = ?');
    while (true) {
        $stmt->execute([$slug]);
        if (!$stmt->fetch()) break;
        $i++;
        $slug = $base . '-' . $i;
    }
    return $slug;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = sanitize($_POST['title']);
    $description = sanitize($_POST['description']);
    $allowMultiple = isset($_POST['allow_multiple_submissions']) ? 1 : 0;
    $loginRequired = isset($_POST['login_requirement']) ? 1 : 0;
    $visibility  = $_POST['public_visibility'] === 'PRIVATE' ? 'PRIVATE' : 'PUBLIC';
    $requireRegistration = isset($_POST['require_website_registration']) ? 1 : 0;
    $registrationMessage = sanitize($_POST['registration_message'] ?? '');
    $registrationButtonText = sanitize($_POST['registration_button_text'] ?? '');

    if ($title === '') {
        set_flash('error', 'Form title is required.');
    } else {
        $slug = generate_unique_slug($pdo, $title);
        $stmt = $pdo->prepare(
            'INSERT INTO forms (admin_id, title, description, slug, status, allow_multiple_submissions, login_requirement, public_visibility, require_website_registration, registration_message, registration_button_text)
             VALUES (?, ?, ?, ?, "DRAFT", ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$adminId, $title, $description, $slug, $allowMultiple, $loginRequired, $visibility, $requireRegistration, $registrationMessage, $registrationButtonText]);
        $formId = $pdo->lastInsertId();

        set_flash('success', 'Form created. Now add fields to it.');
        redirect('admin/forms/fields.php?form_id=' . $formId);
    }
}

$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create Form - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="../../public/assets/style.css">
</head>
<body>
    <?php include __DIR__ . '/../../includes/admin-header.php'; ?>
    <div class="admin-page">
        <div class="page-header">
            <h1>Create New Form</h1>
            <a href="index.php" class="btn btn-secondary">Back to Forms</a>
        </div>

        <?php if ($flash): ?>
            <div class="flash <?= $flash['type'] ?>"><?= sanitize($flash['message']) ?></div>
        <?php endif; ?>

        <div class="card">
            <form method="POST">
                <label class="form-label">Form Title</label>
                <input type="text" name="title" placeholder="e.g. Internship Registration" required>

                <label class="form-label">Description</label>
                <input type="text" name="description" placeholder="Short description shown to applicants">

                <label class="form-label">Visibility</label>
                <select name="public_visibility">
                    <option value="PUBLIC">Public</option>
                    <option value="PRIVATE">Private</option>
                </select>

                <div class="checkbox-row">
                    <input type="checkbox" name="allow_multiple_submissions" id="allow_multiple">
                    <label for="allow_multiple">Allow multiple submissions per candidate</label>
                </div>

                <div class="checkbox-row">
                    <input type="checkbox" name="login_requirement" id="login_req">
                    <label for="login_req">Require candidate login to submit</label>
                </div>

                <div class="checkbox-row" style="margin-top:10px;">
                    <input type="checkbox" name="require_website_registration" id="require_reg" onchange="document.getElementById('reg_extra_fields').style.display=this.checked?'block':'none';">
                    <label for="require_reg">Require Website Registration After Submission</label>
                </div>

                <div id="reg_extra_fields" style="display:none;margin-top:10px;">
                    <label class="form-label">Registration Message (shown after submit)</label>
                    <input type="text" name="registration_message" placeholder="e.g. To receive your Acceptance Letter, please create an account.">

                    <label class="form-label">Registration Button Text</label>
                    <input type="text" name="registration_button_text" placeholder="e.g. Register on Website">
                </div>

                <button type="submit" class="btn" style="margin-top:10px;">Create & Add Fields</button>
            </form>
        </div>
    </div>
</body>
</html>
