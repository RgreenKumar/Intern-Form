<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

require_candidate_login();
$candidateId = $_SESSION['user_id'];

$stmt = $pdo->prepare(
    "SELECT * FROM candidate_documents
     WHERE candidate_id = ? AND document_status = 'AVAILABLE'
     ORDER BY uploaded_at DESC"
);
$stmt->execute([$candidateId]);
$documents = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Documents - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="../../public/assets/style.css">
</head>
<body>
    <?php include __DIR__ . '/../../includes/candidate-header.php'; ?>
    <div class="admin-page">
        <div class="page-header">
            <h1>My Documents</h1>
        </div>

        <?php if (empty($documents)): ?>
            <div class="card empty-state">No documents shared with you yet.</div>
        <?php else: ?>
            <?php foreach ($documents as $d): ?>
                <div class="card" style="display:flex;justify-content:space-between;align-items:center;">
                    <div>
                        <strong><?= sanitize($d['document_title']) ?></strong>
                        <div style="font-size:12.5px;color:var(--text-muted);margin-top:3px;">
                            <?= sanitize($d['document_type']) ?> · <?= date('d M Y', strtotime($d['uploaded_at'])) ?>
                            <?php if ($d['message_note']): ?><br><?= sanitize($d['message_note']) ?><?php endif; ?>
                        </div>
                    </div>
                    <a href="../../<?= sanitize($d['file_path']) ?>" target="_blank" class="btn btn-sm">Download</a>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>
