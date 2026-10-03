<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

$slug = sanitize($_GET['slug'] ?? '');

$stmt = $pdo->prepare('SELECT * FROM forms WHERE slug = ?');
$stmt->execute([$slug]);
$form = $stmt->fetch();

if (!$form || $form['status'] !== 'ACTIVE') {
    http_response_code(404);
    echo 'This form is not available.';
    exit;
}

if (!empty($form['external_url'])) {
    header('Location: ' . $form['external_url']);
    exit;
}

if ($form['login_requirement'] && empty($_SESSION['user_id'])) {
    set_flash('error', 'Please login as a candidate to submit this form.');
    redirect('public/login.php');
}

$stmt = $pdo->prepare('SELECT * FROM form_fields WHERE form_id = ? ORDER BY display_order ASC');
$stmt->execute([$form['id']]);
$fields = $stmt->fetchAll();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $values = [];

    foreach ($fields as $field) {
        $key = 'field_' . $field['id'];

        if ($field['field_type'] === 'FILE') {
            if ($field['is_required'] && (empty($_FILES[$key]) || $_FILES[$key]['error'] !== UPLOAD_ERR_OK)) {
                $errors[] = $field['field_label'] . ' is required.';
            }
            continue;
        }

        $val = sanitize($_POST[$key] ?? '');
        if ($field['is_required'] && $val === '') {
            $errors[] = $field['field_label'] . ' is required.';
        }
        $values[$field['id']] = $val;
    }

    if (empty($errors)) {
        $candidateId = null;
        if (!empty($_SESSION['user_id']) && $_SESSION['role'] === 'CANDIDATE') {
            $candidateId = $_SESSION['user_id'];
        }

        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO form_submissions (form_id, candidate_id, application_status) VALUES (?, ?, "SUBMITTED")'
            );
            $stmt->execute([$form['id'], $candidateId]);
            $submissionId = $pdo->lastInsertId();

            foreach ($fields as $field) {
                $key = 'field_' . $field['id'];

                if ($field['field_type'] === 'FILE') {
                    $filePath = null;
                    if (!empty($_FILES[$key]) && $_FILES[$key]['error'] === UPLOAD_ERR_OK) {
                        $ext = pathinfo($_FILES[$key]['name'], PATHINFO_EXTENSION);
                        $newName = 'sub_' . $submissionId . '_' . $field['id'] . '_' . time() . '.' . $ext;
                        $dest = UPLOAD_PATH_SUBMISSIONS . $newName;
                        if (move_uploaded_file($_FILES[$key]['tmp_name'], $dest)) {
                            $filePath = 'uploads/submissions/' . $newName;
                        }
                    }
                    $stmt = $pdo->prepare(
                        'INSERT INTO submission_values (submission_id, form_field_id, file_path) VALUES (?, ?, ?)'
                    );
                    $stmt->execute([$submissionId, $field['id'], $filePath]);
                } else {
                    $stmt = $pdo->prepare(
                        'INSERT INTO submission_values (submission_id, form_field_id, field_value) VALUES (?, ?, ?)'
                    );
                    $stmt->execute([$submissionId, $field['id'], $values[$field['id']] ?? '']);
                }
            }

            $pdo->commit();

            if ($candidateId) {
                // Already logged in — submission is already linked
                redirect('public/form-success.php?slug=' . urlencode($slug));
            }

            // Only trigger the "save your application" flow if this form is configured for it
            if ($form['require_website_registration']) {
                $submittedEmail = null;
                foreach ($fields as $field) {
                    if ($field['field_type'] === 'EMAIL' && !empty($values[$field['id']])) {
                        $submittedEmail = $values[$field['id']];
                        break;
                    }
                }

                if ($submittedEmail) {
                    $_SESSION['pending_link_submission_id'] = $submissionId;
                    $_SESSION['pending_link_slug'] = $slug;
                    $_SESSION['pending_link_message'] = $form['registration_message'];
                    $_SESSION['pending_link_button_text'] = $form['registration_button_text'];
                    redirect('public/link-submission.php?email=' . urlencode($submittedEmail));
                }
            }

            redirect('public/form-success.php?slug=' . urlencode($slug));
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = 'Something went wrong while submitting. Please try again.';
        }
    }
}
$requiredCount = count(array_filter($fields, fn($f) => $f['is_required']));
$estMinutes = max(1, (int)ceil(count($fields) * 0.6));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= sanitize($form['title']) ?> - <?= APP_NAME ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:wght@500;600&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/form-style.css">
</head>
<body class="form-page">
    <div class="form-shell">
        <aside class="form-brand">
            <div class="form-brand-top">
                <div class="brand-mark"><?= APP_NAME ?></div>
                <h1><?= sanitize($form['title']) ?></h1>
                <?php if ($form['description']): ?>
                    <p><?= sanitize($form['description']) ?></p>
                <?php endif; ?>
                <div class="form-meta">
                    <div><strong><?= count($fields) ?></strong>fields</div>
                    <div><strong><?= $requiredCount ?></strong>required</div>
                    <div><strong>~<?= $estMinutes ?>m</strong>to complete</div>
                </div>
            </div>
            <div class="form-brand-bottom">
                Your responses are only visible to the <?= APP_NAME ?> admin team.
            </div>
        </aside>

        <main class="form-fieldset">
            <?php if (!empty($errors)): ?>
                <div class="form-error-box"><?= sanitize(implode(' ', $errors)) ?></div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <?php foreach ($fields as $field):
                    $key = 'field_' . $field['id'];
                    $options = $field['options'] ? json_decode($field['options'], true) : [];
                ?>
                <div class="field-group">
                    <label>
                        <?= sanitize($field['field_label']) ?><?php if ($field['is_required']): ?> <span class="required-mark">*</span><?php endif; ?>
                    </label>
                    <?php switch ($field['field_type']):
                        case 'TEXTAREA': ?>
                            <textarea name="<?= $key ?>" placeholder="<?= sanitize($field['placeholder']) ?>" <?= $field['is_required'] ? 'required' : '' ?>></textarea>
                        <?php break;
                        case 'SELECT': ?>
                            <select name="<?= $key ?>" <?= $field['is_required'] ? 'required' : '' ?>>
                                <option value="">Select an option</option>
                                <?php foreach ($options as $opt): ?>
                                    <option value="<?= sanitize($opt) ?>"><?= sanitize($opt) ?></option>
                                <?php endforeach; ?>
                            </select>
                        <?php break;
                        case 'RADIO': ?>
                            <div class="radio-options">
                            <?php foreach ($options as $i => $opt): ?>
                                <label class="radio-pill">
                                    <input type="radio" name="<?= $key ?>" value="<?= sanitize($opt) ?>" <?= $field['is_required'] && $i === 0 ? 'required' : '' ?>>
                                    <?= sanitize($opt) ?>
                                </label>
                            <?php endforeach; ?>
                            </div>
                        <?php break;
                        case 'CHECKBOX': ?>
                            <label class="check-line">
                                <input type="checkbox" name="<?= $key ?>" value="Yes">
                                Yes
                            </label>
                        <?php break;
                        case 'FILE': ?>
                            <div class="file-drop">
                                Upload a file
                                <br>
                                <input type="file" name="<?= $key ?>" <?= $field['is_required'] ? 'required' : '' ?>>
                            </div>
                        <?php break;
                        case 'DATE': ?>
                            <input type="date" name="<?= $key ?>" <?= $field['is_required'] ? 'required' : '' ?>>
                        <?php break;
                        case 'NUMBER': ?>
                            <input type="number" name="<?= $key ?>" placeholder="<?= sanitize($field['placeholder']) ?>" <?= $field['is_required'] ? 'required' : '' ?>>
                        <?php break;
                        case 'EMAIL': ?>
                            <input type="email" name="<?= $key ?>" placeholder="<?= sanitize($field['placeholder']) ?>" <?= $field['is_required'] ? 'required' : '' ?>>
                        <?php break;
                        case 'PHONE': ?>
                            <input type="tel" name="<?= $key ?>" placeholder="<?= sanitize($field['placeholder']) ?>" <?= $field['is_required'] ? 'required' : '' ?>>
                        <?php break;
                        default: ?>
                            <input type="text" name="<?= $key ?>" placeholder="<?= sanitize($field['placeholder']) ?>" <?= $field['is_required'] ? 'required' : '' ?>>
                    <?php endswitch; ?>
                </div>
                <?php endforeach; ?>

                <button type="submit" class="form-submit-btn">Submit Application</button>
            </form>
        </main>
    </div>
</body>
</html>
