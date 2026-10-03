<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

require_admin_login();
$adminId = $_SESSION['user_id'];

$stmt = $pdo->query('SELECT id, name FROM candidate_profiles ORDER BY name');
$candidates = $stmt->fetchAll();

$documentTypes = [
    'Internship Acceptance Letter',
    'Internship Completion Certificate',
    'Offer Letter',
    'Participation Certificate',
    'Payment Receipt',
    'ID Card',
    'Training Certificate',
    'Other',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $candidateId   = (int)$_POST['candidate_id'];
    $documentType  = $_POST['document_type'];
    $documentTitle = sanitize($_POST['document_title']);
    $note          = sanitize($_POST['message_note'] ?? '');

    if ($candidateId === 0 || $documentTitle === '' || !in_array($documentType, $documentTypes, true)) {
        set_flash('error', 'Please fill in all required fields.');
    } elseif (empty($_FILES['document_file']) || $_FILES['document_file']['error'] !== UPLOAD_ERR_OK) {
        set_flash('error', 'Please choose a file to upload.');
    } else {
        $file = $_FILES['document_file'];
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $newName = 'doc_' . $candidateId . '_' . time() . '_' . uniqid() . '.' . $ext;
        $dest = UPLOAD_PATH_DOCUMENTS . $newName;

        if (move_uploaded_file($file['tmp_name'], $dest)) {
            $stmt = $pdo->prepare(
                'INSERT INTO candidate_documents
                 (candidate_id, admin_id, document_type, document_title, file_path, original_filename, mime_type, file_size, message_note, document_status)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, "AVAILABLE")'
            );
            $stmt->execute([
                $candidateId, $adminId, $documentType, $documentTitle,
                'uploads/documents/' . $newName, $file['name'], $file['type'], $file['size'], $note,
            ]);

            // Notify the candidate
            $stmt = $pdo->prepare('SELECT u.email FROM candidate_profiles c JOIN users u ON u.id = c.user_id WHERE c.id = ?');
            $stmt->execute([$candidateId]);
            $candidateEmail = $stmt->fetchColumn();
            if ($candidateEmail) {
                send_email(
                    $candidateEmail,
                    'New Document Available - ' . APP_NAME,
                    "A new document \"{$documentTitle}\" ({$documentType}) has been shared with you. Log in to your " . APP_NAME . " account to view it."
                );
            }

            set_flash('success', 'Document uploaded and candidate notified.');
            redirect('admin/documents/index.php');
        } else {
            set_flash('error', 'File upload failed. Please try again.');
        }
    }
}

$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Upload Document - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="../../public/assets/style.css">
</head>
<body>
    <?php include __DIR__ . '/../../includes/admin-header.php'; ?>
    <div class="admin-page">
        <div class="page-header">
            <h1>Upload Document</h1>
            <a href="index.php" class="btn btn-secondary">Back to Documents</a>
        </div>

        <?php if ($flash): ?>
            <div class="flash <?= $flash['type'] ?>"><?= sanitize($flash['message']) ?></div>
        <?php endif; ?>

        <?php if (empty($candidates)): ?>
            <div class="card empty-state">No candidates registered yet.</div>
        <?php else: ?>
            <div class="card">
                <form method="POST" enctype="multipart/form-data">
                    <label class="form-label">Candidate</label>
                    <select name="candidate_id" required>
                        <option value="">-- Select Candidate --</option>
                        <?php foreach ($candidates as $c): ?>
                            <option value="<?= $c['id'] ?>"><?= sanitize($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>

                    <label class="form-label">Document Type</label>
                    <select name="document_type" required>
                        <?php foreach ($documentTypes as $t): ?>
                            <option value="<?= sanitize($t) ?>"><?= sanitize($t) ?></option>
                        <?php endforeach; ?>
                    </select>

                    <label class="form-label">Document Title</label>
                    <input type="text" name="document_title" placeholder="e.g. Internship Offer Letter - John" required>

                    <label class="form-label">Note (optional)</label>
                    <input type="text" name="message_note" placeholder="A short note for the candidate">

                    <label class="form-label">File</label>
                    <input type="file" name="document_file" required>

                    <button type="submit" class="btn" style="margin-top:10px;">Upload & Notify Candidate</button>
                </form>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
