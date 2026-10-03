<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

require_admin_login();
$adminId = $_SESSION['user_id'];
$fieldId = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare(
    'SELECT ff.*, f.id AS form_id, f.title AS form_title
     FROM form_fields ff
     JOIN forms f ON f.id = ff.form_id
     WHERE ff.id = ? AND f.admin_id = ?'
);
$stmt->execute([$fieldId, $adminId]);
$field = $stmt->fetch();

if (!$field) {
    set_flash('error', 'Field not found.');
    redirect('admin/forms/index.php');
}

$validTypes = ['TEXT','EMAIL','PHONE','NUMBER','DATE','TEXTAREA','SELECT','RADIO','CHECKBOX','FILE'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $label       = sanitize($_POST['field_label']);
    $type        = $_POST['field_type'];
    $required    = isset($_POST['is_required']) ? 1 : 0;
    $placeholder = sanitize($_POST['placeholder']);
    $optionsRaw  = trim($_POST['options'] ?? '');
    $options     = null;

    if ($label === '' || !in_array($type, $validTypes, true)) {
        set_flash('error', 'Please fill in the field label and choose a valid type.');
    } else {
        if (in_array($type, ['SELECT', 'RADIO'], true) && $optionsRaw !== '') {
            $opts = array_filter(array_map('trim', explode(',', $optionsRaw)));
            $options = json_encode(array_values($opts));
        }

        $stmt = $pdo->prepare(
            'UPDATE form_fields SET field_label = ?, field_type = ?, is_required = ?, placeholder = ?, options = ?
             WHERE id = ?'
        );
        $stmt->execute([$label, $type, $required, $placeholder, $options, $fieldId]);

        set_flash('success', 'Field updated.');
        redirect('admin/forms/fields.php?form_id=' . $field['form_id']);
    }
}

$flash = get_flash();
$currentOptions = $field['options'] ? implode(', ', json_decode($field['options'], true)) : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Field - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="../../public/assets/style.css">
</head>
<body>
    <?php include __DIR__ . '/../../includes/admin-header.php'; ?>
    <div class="admin-page">
        <div class="page-header">
            <h1>Edit Field — <?= sanitize($field['form_title']) ?></h1>
            <a href="fields.php?form_id=<?= $field['form_id'] ?>" class="btn btn-secondary">Back to Fields</a>
        </div>

        <?php if ($flash): ?>
            <div class="flash <?= $flash['type'] ?>"><?= sanitize($flash['message']) ?></div>
        <?php endif; ?>

        <div class="card">
            <form method="POST">
                <label class="form-label">Field Label</label>
                <input type="text" name="field_label" value="<?= sanitize($field['field_label']) ?>" required>

                <label class="form-label">Field Type</label>
                <select name="field_type">
                    <?php foreach ($validTypes as $t): ?>
                        <option value="<?= $t ?>" <?= $field['field_type'] === $t ? 'selected' : '' ?>><?= ucfirst(strtolower($t)) ?></option>
                    <?php endforeach; ?>
                </select>

                <label class="form-label">Placeholder (optional)</label>
                <input type="text" name="placeholder" value="<?= sanitize($field['placeholder']) ?>">

                <label class="form-label">Options (comma-separated — only for Dropdown / Radio)</label>
                <input type="text" name="options" value="<?= sanitize($currentOptions) ?>">

                <div class="checkbox-row">
                    <input type="checkbox" name="is_required" id="is_required" <?= $field['is_required'] ? 'checked' : '' ?>>
                    <label for="is_required">Required field</label>
                </div>

                <button type="submit" class="btn" style="margin-top:10px;">Save Changes</button>
            </form>
        </div>
    </div>
</body>
</html>
