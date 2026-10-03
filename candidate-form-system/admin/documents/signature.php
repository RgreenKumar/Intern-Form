<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

require_admin_login();
$adminId = $_SESSION['user_id'];

// Signature is always stored as a transparent PNG after background removal
$sigPath = UPLOAD_PATH_PROFILES . 'signature_' . $adminId . '.png';
$hasSignature = file_exists($sigPath);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_FILES['signature']) && $_FILES['signature']['error'] === UPLOAD_ERR_OK) {
        $threshold = (int)($_POST['threshold'] ?? 200);
        $threshold = max(120, min(250, $threshold));

        if (remove_signature_background($_FILES['signature']['tmp_name'], $sigPath, $threshold)) {
            set_flash('success', 'Signature saved with background removed. It will now appear on generated documents.');
        } else {
            set_flash('error', 'Please upload a valid PNG or JPG image.');
        }
    } else {
        set_flash('error', 'Please choose an image file.');
    }
    redirect('admin/documents/signature.php');
}

$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Signature - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="../../public/assets/style.css">
</head>
<body>
    <?php include __DIR__ . '/../../includes/admin-header.php'; ?>
    <div class="admin-page">
        <div class="page-header">
            <h1>My Signature</h1>
            <a href="index.php" class="btn btn-secondary">Back to Documents</a>
        </div>

        <?php if ($flash): ?>
            <div class="flash <?= $flash['type'] ?>"><?= sanitize($flash['message']) ?></div>
        <?php endif; ?>

        <div class="card">
            <p style="font-size:13px;color:var(--text-muted);">
                Upload a photo/scan of your signature on a plain white/light paper. The background is
                removed automatically — only the ink strokes are kept — and added to every Offer Letter
                and Certificate you generate.
            </p>

            <?php if ($hasSignature): ?>
                <p style="font-size:13px;font-weight:600;margin-top:14px;">Current signature (background removed):</p>
                <div style="background:repeating-conic-gradient(#e2e8f0 0% 25%, #fff 0% 50%) 0 0/16px 16px; display:inline-block; border-radius:6px; padding:8px; border:1px solid #e2e8f0;">
                    <img src="../../uploads/profiles/signature_<?= $adminId ?>.png?t=<?= time() ?>" style="max-width:220px;display:block;">
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" style="margin-top:16px;">
                <label class="form-label">Signature Image (PNG/JPG)</label>
                <input type="file" name="signature" accept="image/png,image/jpeg" required>

                <label class="form-label">Background Sensitivity</label>
                <select name="threshold">
                    <option value="230">Very light background (bright white paper)</option>
                    <option value="200" selected>Normal (default)</option>
                    <option value="170">Shadowy / off-white background</option>
                </select>

                <button type="submit" class="btn" style="margin-top:10px;">
                    <?= $hasSignature ? 'Replace Signature' : 'Save Signature' ?>
                </button>
            </form>
        </div>
    </div>
</body>
</html>
