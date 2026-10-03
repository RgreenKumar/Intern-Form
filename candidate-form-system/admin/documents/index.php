<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

require_admin_login();
$adminId = $_SESSION['user_id'];

$stmt = $pdo->prepare(
    'SELECT d.*, c.name AS candidate_name
     FROM candidate_documents d
     JOIN candidate_profiles c ON c.id = d.candidate_id
     WHERE d.admin_id = ?
     ORDER BY d.uploaded_at DESC'
);
$stmt->execute([$adminId]);
$documents = $stmt->fetchAll();

$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Documents - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="../../public/assets/style.css">
</head>
<body>
    <?php include __DIR__ . '/../../includes/admin-header.php'; ?>
    <div class="admin-page">
        <div class="page-header">
            <h1>Documents</h1>
            <div>
                <a href="signature.php" class="btn btn-secondary">✍️ My Signature</a>
                <a href="generate-offer-letter.php" class="btn btn-secondary">Generate Offer Letter</a>
                <a href="generate-certificate.php" class="btn btn-secondary">Generate Certificate</a>
                <a href="upload.php" class="btn">+ Upload File</a>
            </div>
        </div>

        <?php if ($flash): ?>
            <div class="flash <?= $flash['type'] ?>"><?= sanitize($flash['message']) ?></div>
        <?php endif; ?>

        <?php if (empty($documents)): ?>
            <div class="card empty-state">No documents uploaded yet.</div>
        <?php else: ?>
            <div class="table-wrap"><table class="data-table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Type</th>
                        <th>Candidate</th>
                        <th>Status</th>
                        <th>Uploaded</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($documents as $d): ?>
                    <tr>
                        <td><?= sanitize($d['document_title']) ?></td>
                        <td><?= sanitize($d['document_type']) ?></td>
                        <td><?= sanitize($d['candidate_name']) ?></td>
                        <td>
                            <span class="badge <?= $d['document_status'] === 'AVAILABLE' ? 'badge-active' : 'badge-archived' ?>">
                                <?= sanitize($d['document_status']) ?>
                            </span>
                        </td>
                        <td><?= date('d M Y', strtotime($d['uploaded_at'])) ?></td>
                        <td>
                            <a href="../../<?= sanitize($d['file_path']) ?>" target="_blank" class="btn btn-sm btn-secondary">Download</a>
                            <a href="archive.php?id=<?= $d['id'] ?>" class="btn btn-sm <?= $d['document_status'] === 'AVAILABLE' ? 'btn-danger' : '' ?>"
                               onclick="return confirm('<?= $d['document_status'] === 'AVAILABLE' ? 'Archive this document? It will be hidden from the candidate.' : 'Restore this document for the candidate?' ?>');">
                                <?= $d['document_status'] === 'AVAILABLE' ? 'Archive' : 'Restore' ?>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table></div>
        <?php endif; ?>
    </div>
</body>
</html>
