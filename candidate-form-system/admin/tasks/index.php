<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

require_admin_login();
$adminId = $_SESSION['user_id'];

$stmt = $pdo->prepare(
    'SELECT t.*, c.name AS candidate_name
     FROM tasks t
     JOIN candidate_profiles c ON c.id = t.candidate_id
     WHERE t.admin_id = ?
     ORDER BY t.due_date IS NULL, t.due_date ASC, t.created_at DESC'
);
$stmt->execute([$adminId]);
$tasks = $stmt->fetchAll();

$flash = get_flash();

function priority_class($p) {
    return $p === 'HIGH' ? 'badge-closed' : ($p === 'MEDIUM' ? 'badge-draft' : 'badge-archived');
}
function status_class($s) {
    return $s === 'COMPLETED' ? 'badge-active' : ($s === 'CANCELLED' ? 'badge-closed' : 'badge-draft');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Tasks - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="../../public/assets/style.css">
</head>
<body>
    <?php include __DIR__ . '/../../includes/admin-header.php'; ?>
    <div class="admin-page">
        <div class="page-header">
            <h1>Tasks</h1>
            <a href="create.php" class="btn">+ Assign New Task</a>
        </div>

        <?php if ($flash): ?>
            <div class="flash <?= $flash['type'] ?>"><?= sanitize($flash['message']) ?></div>
        <?php endif; ?>

        <?php if (empty($tasks)): ?>
            <div class="card empty-state">No tasks assigned yet.</div>
        <?php else: ?>
            <div class="table-wrap"><table class="data-table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Candidate</th>
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
                        <td><?= sanitize($t['candidate_name']) ?></td>
                        <td><span class="badge <?= priority_class($t['priority']) ?>"><?= sanitize($t['priority']) ?></span></td>
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
