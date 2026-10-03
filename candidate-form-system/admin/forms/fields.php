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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $label       = sanitize($_POST['field_label']);
    $name        = strtolower(trim(preg_replace('/[^A-Za-z0-9]+/', '_', $label), '_'));
    $type        = $_POST['field_type'];
    $required    = isset($_POST['is_required']) ? 1 : 0;
    $placeholder = sanitize($_POST['placeholder']);
    $optionsRaw  = trim($_POST['options'] ?? '');
    $options     = null;

    $validTypes = ['TEXT','EMAIL','PHONE','NUMBER','DATE','TEXTAREA','SELECT','RADIO','CHECKBOX','FILE'];

    if ($label === '' || !in_array($type, $validTypes, true)) {
        set_flash('error', 'Please fill in the field label and choose a valid type.');
    } else {
        if (in_array($type, ['SELECT', 'RADIO'], true) && $optionsRaw !== '') {
            $opts = array_filter(array_map('trim', explode(',', $optionsRaw)));
            $options = json_encode(array_values($opts));
        }

        $stmt = $pdo->prepare('SELECT COALESCE(MAX(display_order), 0) + 1 FROM form_fields WHERE form_id = ?');
        $stmt->execute([$formId]);
        $order = $stmt->fetchColumn();

        $stmt = $pdo->prepare(
            'INSERT INTO form_fields (form_id, field_label, field_name, field_type, is_required, placeholder, display_order, options)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$formId, $label, $name, $type, $required, $placeholder, $order, $options]);

        set_flash('success', 'Field added.');
    }
    redirect('admin/forms/fields.php?form_id=' . $formId);
}

$stmt = $pdo->prepare('SELECT * FROM form_fields WHERE form_id = ? ORDER BY display_order ASC');
$stmt->execute([$formId]);
$fields = $stmt->fetchAll();

$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Fields - <?= sanitize($form['title']) ?> - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="../../public/assets/style.css">
</head>
<body>
    <?php include __DIR__ . '/../../includes/admin-header.php'; ?>
    <div class="admin-page">
        <div class="page-header">
            <h1><?= sanitize($form['title']) ?> — Fields</h1>
            <a href="index.php" class="btn btn-secondary">Back to Forms</a>
        </div>

        <?php if ($flash): ?>
            <div class="flash <?= $flash['type'] ?>"><?= sanitize($flash['message']) ?></div>
        <?php endif; ?>

        <div class="card">
            <h2 style="font-size:15px;margin-top:0;">Existing Fields</h2>
            <?php if (empty($fields)): ?>
                <p class="empty-state">No fields added yet.</p>
            <?php else: ?>
                <?php foreach ($fields as $i => $f): ?>
                    <div class="field-row">
                        <div style="display:flex;flex-direction:column;gap:2px;">
                            <a href="field-reorder.php?id=<?= $f['id'] ?>&form_id=<?= $formId ?>&dir=up"
                               class="btn btn-sm btn-secondary" style="padding:2px 8px;<?= $i === 0 ? 'visibility:hidden;' : '' ?>">▲</a>
                            <a href="field-reorder.php?id=<?= $f['id'] ?>&form_id=<?= $formId ?>&dir=down"
                               class="btn btn-sm btn-secondary" style="padding:2px 8px;<?= $i === count($fields) - 1 ? 'visibility:hidden;' : '' ?>">▼</a>
                        </div>
                        <div class="field-main">
                            <strong><?= sanitize($f['field_label']) ?></strong>
                            (<?= sanitize($f['field_type']) ?><?= $f['is_required'] ? ', required' : '' ?>)
                            <?php if ($f['options']): ?>
                                <div style="font-size:12px;color:var(--text-muted);">
                                    Options: <?= sanitize(implode(', ', json_decode($f['options'], true))) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <a href="field-edit.php?id=<?= $f['id'] ?>" class="btn btn-sm btn-secondary">Edit</a>
                        <a href="field-delete.php?id=<?= $f['id'] ?>&form_id=<?= $formId ?>"
                           class="btn btn-sm btn-danger"
                           onclick="return confirm('Delete this field?');">Delete</a>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="card">
            <h2 style="font-size:15px;margin-top:0;">Add New Field</h2>
            <form method="POST">
                <label class="form-label">Field Label</label>
                <input type="text" name="field_label" placeholder="e.g. Full Name" required>

                <label class="form-label">Field Type</label>
                <select name="field_type" id="field_type">
                    <option value="TEXT">Text</option>
                    <option value="EMAIL">Email</option>
                    <option value="PHONE">Phone</option>
                    <option value="NUMBER">Number</option>
                    <option value="DATE">Date</option>
                    <option value="TEXTAREA">Textarea</option>
                    <option value="SELECT">Dropdown (Select)</option>
                    <option value="RADIO">Radio buttons</option>
                    <option value="CHECKBOX">Checkbox</option>
                    <option value="FILE">File Upload</option>
                </select>

                <label class="form-label">Placeholder (optional)</label>
                <input type="text" name="placeholder" placeholder="Hint text shown inside the field">

                <label class="form-label">Options (comma-separated — only for Dropdown / Radio)</label>
                <input type="text" name="options" placeholder="e.g. CSE, ECE, MECH">

                <div class="checkbox-row">
                    <input type="checkbox" name="is_required" id="is_required" checked>
                    <label for="is_required">Required field</label>
                </div>

                <button type="submit" class="btn" style="margin-top:10px;">+ Add Field</button>
            </form>
        </div>
    </div>
</body>
</html>
