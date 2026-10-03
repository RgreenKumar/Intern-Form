<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

require_candidate_login();
$candidateId = $_SESSION['user_id'];

$stmt = $pdo->prepare(
    'SELECT t.*, a.name AS admin_name
     FROM tasks t
     JOIN admin_profiles a ON a.id = t.admin_id
     WHERE t.candidate_id = ?
     ORDER BY t.due_date IS NULL, t.due_date ASC, t.created_at DESC'
);
$stmt->execute([$candidateId]);
$tasks = $stmt->fetchAll();

function status_class($s) {
    return $s === 'COMPLETED' ? 'badge-active' : ($s === 'CANCELLED' ? 'badge-closed' : 'badge-draft');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Tasks - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="../../public/assets/style.css">
</head>
<body>
    <?php include __DIR__ . '/../../includes/candidate-header.php'; ?>
    <div class="admin-page">
        <div class="page-header">
            <h1>My Tasks</h1>
        </div>

        <?php if (empty($tasks)): ?>
            <div class="card empty-state">No tasks assigned to you yet.</div>
        <?php else: ?>
            <div class="table-wrap"><table class="data-table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Due Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($tasks as $t): ?>
                    <tr>
                        <td><?= sanitize($t['title']) ?></td>
                        <td><?= sanitize($t['priority']) ?></td>
                        <td><span class="badge <?= status_class($t['status']) ?>"><?= sanitize($t['status']) ?></span></td>
                        <td><?= $t['due_date'] ? date('d M Y', strtotime($t['due_date'])) : '—' ?></td>
                        <td><a href="view.php?id=<?= $t['id'] ?>" class="btn btn-sm btn-secondary">View</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table></div>
        <?php endif; ?>
    </div>
</body>
</html>
