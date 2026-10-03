<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

require_admin_login();
$adminId = $_SESSION['user_id'];

$stmt = $pdo->prepare(
    'SELECT f.*, (SELECT COUNT(*) FROM form_submissions s WHERE s.form_id = f.id) AS submission_count
     FROM forms f
     WHERE f.admin_id = ?
     ORDER BY f.created_at DESC'
);
$stmt->execute([$adminId]);
$forms = $stmt->fetchAll();

$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Forms - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="../../public/assets/style.css">
</head>
<body>
    <?php include __DIR__ . '/../../includes/admin-header.php'; ?>
    <div class="admin-page">
        <div class="page-header">
            <h1>My Forms</h1>
            <div>
                <a href="seed-defaults.php" class="btn btn-secondary">⚙️ Add Default Forms</a>
                <a href="create.php" class="btn">+ Create New Form</a>
            </div>
        </div>

        <?php if ($flash): ?>
            <div class="flash <?= $flash['type'] ?>"><?= sanitize($flash['message']) ?></div>
        <?php endif; ?>

        <?php if (empty($forms)): ?>
            <div class="card empty-state">
                No forms yet. Click "Create New Form" to build your first one.
            </div>
        <?php else: ?>
            <div class="table-wrap"><table class="data-table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Status</th>
                        <th>Submissions</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($forms as $form): ?>
                    <tr>
                        <td><?= sanitize($form['title']) ?></td>
                        <td>
                            <span class="badge badge-<?= strtolower($form['status']) ?>">
                                <?= sanitize($form['status']) ?>
                            </span>
                        </td>
                        <td><?= (int)$form['submission_count'] ?></td>
                        <td><?= date('d M Y', strtotime($form['created_at'])) ?></td>
                        <td>
                            <a href="submissions.php?form_id=<?= $form['id'] ?>" class="btn btn-sm btn-secondary">Submissions (<?= (int)$form['submission_count'] ?>)</a>
                            <a href="fields.php?form_id=<?= $form['id'] ?>" class="btn btn-sm btn-secondary">Fields</a>
                            <a href="edit.php?id=<?= $form['id'] ?>" class="btn btn-sm btn-secondary">Edit</a>
                            <?php if ($form['status'] !== 'ARCHIVED'): ?>
                                <a href="publish.php?id=<?= $form['id'] ?>" class="btn btn-sm">
                                    <?= $form['status'] === 'ACTIVE' ? 'Close' : 'Publish' ?>
                                </a>
                                <a href="archive.php?id=<?= $form['id'] ?>" class="btn btn-sm btn-secondary"
                                   onclick="return confirm('Archive this form? It will no longer be publicly visible.');">Archive</a>
                            <?php endif; ?>
                            <a href="delete.php?id=<?= $form['id'] ?>" class="btn btn-sm btn-danger"
                               onclick="return confirm('Permanently delete this form and ALL its submissions? This cannot be undone.');">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table></div>
        <?php endif; ?>
    </div>
</body>
</html>
