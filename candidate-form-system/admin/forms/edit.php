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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = sanitize($_POST['title']);
    $description = sanitize($_POST['description']);
    $allowMultiple = isset($_POST['allow_multiple_submissions']) ? 1 : 0;
    $loginRequired = isset($_POST['login_requirement']) ? 1 : 0;
    $visibility  = $_POST['public_visibility'] === 'PRIVATE' ? 'PRIVATE' : 'PUBLIC';
    $requireRegistration = isset($_POST['require_website_registration']) ? 1 : 0;
    $registrationMessage = sanitize($_POST['registration_message'] ?? '');
    $registrationButtonText = sanitize($_POST['registration_button_text'] ?? '');
    $externalUrl = trim($_POST['external_url'] ?? '');
    $externalUrl = $externalUrl === '' ? null : $externalUrl;

    if ($title === '') {
        set_flash('error', 'Form title is required.');
    } else {
        $stmt = $pdo->prepare(
            'UPDATE forms SET title = ?, description = ?, allow_multiple_submissions = ?, login_requirement = ?, public_visibility = ?,
             require_website_registration = ?, registration_message = ?, registration_button_text = ?, external_url = ?
             WHERE id = ?'
        );
        $stmt->execute([$title, $description, $allowMultiple, $loginRequired, $visibility, $requireRegistration, $registrationMessage, $registrationButtonText, $externalUrl, $formId]);
        set_flash('success', 'Form updated.');
        redirect('admin/forms/index.php');
    }
}

$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Form - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="../../public/assets/style.css">
</head>
<body>
    <?php include __DIR__ . '/../../includes/admin-header.php'; ?>
    <div class="admin-page">
        <div class="page-header">
            <h1>Edit Form</h1>
            <a href="index.php" class="btn btn-secondary">Back to Forms</a>
        </div>

        <?php if ($flash): ?>
            <div class="flash <?= $flash['type'] ?>"><?= sanitize($flash['message']) ?></div>
        <?php endif; ?>

        <div class="card">
            <form method="POST">
                <label class="form-label">Form Title</label>
                <input type="text" name="title" value="<?= sanitize($form['title']) ?>" required>

                <label class="form-label">Description</label>
                <input type="text" name="description" value="<?= sanitize($form['description']) ?>">

                <label class="form-label">External Form Link (optional)</label>
                <input type="url" name="external_url" value="<?= sanitize($form['external_url'] ?? '') ?>" placeholder="e.g. your Google Form link — https://forms.gle/...">
                <p style="font-size:12.5px;color:#777;margin:-6px 0 14px;">If set, "Apply Now" sends candidates straight to this link instead of the built-in form below. Leave blank to keep using the built-in form.</p>

                <label class="form-label">Visibility</label>
                <select name="public_visibility">
                    <option value="PUBLIC" <?= $form['public_visibility'] === 'PUBLIC' ? 'selected' : '' ?>>Public</option>
                    <option value="PRIVATE" <?= $form['public_visibility'] === 'PRIVATE' ? 'selected' : '' ?>>Private</option>
                </select>

                <div class="checkbox-row">
                    <input type="checkbox" name="allow_multiple_submissions" id="allow_multiple" <?= $form['allow_multiple_submissions'] ? 'checked' : '' ?>>
                    <label for="allow_multiple">Allow multiple submissions per candidate</label>
                </div>

                <div class="checkbox-row">
                    <input type="checkbox" name="login_requirement" id="login_req" <?= $form['login_requirement'] ? 'checked' : '' ?>>
                    <label for="login_req">Require candidate login to submit</label>
                </div>

                <div class="checkbox-row" style="margin-top:10px;">
                    <input type="checkbox" name="require_website_registration" id="require_reg" <?= $form['require_website_registration'] ? 'checked' : '' ?>
                           onchange="document.getElementById('reg_extra_fields').style.display=this.checked?'block':'none';">
                    <label for="require_reg">Require Website Registration After Submission</label>
                </div>

                <div id="reg_extra_fields" style="margin-top:10px;<?= $form['require_website_registration'] ? '' : 'display:none;' ?>">
                    <label class="form-label">Registration Message (shown after submit)</label>
                    <input type="text" name="registration_message" value="<?= sanitize($form['registration_message']) ?>" placeholder="e.g. To receive your Acceptance Letter, please create an account.">

                    <label class="form-label">Registration Button Text</label>
                    <input type="text" name="registration_button_text" value="<?= sanitize($form['registration_button_text']) ?>" placeholder="e.g. Register on Website">
                </div>

                <button type="submit" class="btn" style="margin-top:10px;">Save Changes</button>
            </form>
        </div>
    </div>
</body>
</html>
