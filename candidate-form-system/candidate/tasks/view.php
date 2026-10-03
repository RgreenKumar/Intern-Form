<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

require_candidate_login();
$candidateId = $_SESSION['user_id'];
$taskId = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare(
    'SELECT t.*, a.name AS admin_name
     FROM tasks t
     JOIN admin_profiles a ON a.id = t.admin_id
     WHERE t.id = ? AND t.candidate_id = ?'
);
$stmt->execute([$taskId, $candidateId]);
$task = $stmt->fetch();

if (!$task) {
    set_flash('error', 'Task not found.');
    redirect('candidate/tasks/index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_status'])) {
        $newStatus = $_POST['status'];
        if (in_array($newStatus, ['PENDING','IN_PROGRESS','COMPLETED','CANCELLED'], true) && $newStatus !== $task['status']) {
            $pdo->prepare('UPDATE tasks SET status = ? WHERE id = ?')->execute([$newStatus, $taskId]);
            $pdo->prepare('INSERT INTO task_status_history (task_id, status) VALUES (?, ?)')->execute([$taskId, $newStatus]);
            set_flash('success', 'Status updated.');
        }
    } elseif (isset($_POST['add_comment'])) {
        $message = sanitize($_POST['message']);
        if ($message !== '') {
            $pdo->prepare('INSERT INTO task_comments (task_id, user_id, message) VALUES (?, ?, ?)')
                ->execute([$taskId, $_SESSION['auth_user_id'], $message]);
        }
    }
    redirect('candidate/tasks/view.php?id=' . $taskId);
}

$stmt = $pdo->prepare(
    'SELECT tc.*, COALESCE(a.name, c.name) AS author_name, u.role
     FROM task_comments tc
     JOIN users u ON u.id = tc.user_id
     LEFT JOIN admin_profiles a ON a.user_id = u.id
     LEFT JOIN candidate_profiles c ON c.user_id = u.id
     WHERE tc.task_id = ?
     ORDER BY tc.created_at ASC'
);
$stmt->execute([$taskId]);
$comments = $stmt->fetchAll();

$stmt = $pdo->prepare('SELECT * FROM task_attachments WHERE task_id = ? ORDER BY created_at');
$stmt->execute([$taskId]);
$attachments = $stmt->fetchAll();

$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= sanitize($task['title']) ?> - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="../../public/assets/style.css">
</head>
<body>
    <?php include __DIR__ . '/../../includes/candidate-header.php'; ?>
    <div class="admin-page">
        <div class="page-header">
            <h1><?= sanitize($task['title']) ?></h1>
            <a href="index.php" class="btn btn-secondary">Back to My Tasks</a>
        </div>

        <?php if ($flash): ?>
            <div class="flash <?= $flash['type'] ?>"><?= sanitize($flash['message']) ?></div>
        <?php endif; ?>

        <div class="card">
            <p style="font-size:13.5px;color:var(--text);">
                <strong>Assigned by:</strong> <?= sanitize($task['admin_name']) ?><br>
                <strong>Priority:</strong> <?= sanitize($task['priority']) ?><br>
                <strong>Due:</strong> <?= $task['due_date'] ? date('d M Y', strtotime($task['due_date'])) : '—' ?><br>
                <?php if ($task['description']): ?><strong>Description:</strong> <?= sanitize($task['description']) ?><?php endif; ?>
            </p>

            <?php if (!empty($attachments)): ?>
                <p style="font-size:13px;"><strong>Resources:</strong></p>
                <ul style="font-size:13px;margin-top:0;">
                <?php foreach ($attachments as $a): ?>
                    <li>
                        <?= $a['attachment_type'] === 'FILE' ? '📎' : '🔗' ?>
                        <a href="<?= $a['attachment_type'] === 'FILE' ? '../../' . sanitize($a['file_path']) : sanitize($a['url']) ?>" target="_blank">
                            <?= sanitize($a['original_filename'] ?? $a['url']) ?>
                        </a>
                    </li>
                <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <form method="POST" style="display:flex;gap:10px;align-items:center;">
                <select name="status">
                    <?php foreach (['PENDING','IN_PROGRESS','COMPLETED','CANCELLED'] as $s): ?>
                        <option value="<?= $s ?>" <?= $task['status'] === $s ? 'selected' : '' ?>><?= $s ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" name="update_status" value="1" class="btn btn-sm">Update Status</button>
            </form>
        </div>

        <div class="card">
            <h2 style="font-size:15px;margin-top:0;">Comments</h2>
            <?php if (empty($comments)): ?>
                <p class="empty-state">No comments yet.</p>
            <?php else: ?>
                <?php foreach ($comments as $c): ?>
                    <div style="border-bottom:1px solid #eee;padding:10px 0;">
                        <div style="font-size:13px;font-weight:600;">
                            <?= sanitize($c['author_name']) ?>
                            <span style="font-weight:400;color:var(--text-muted);">(<?= $c['role'] ?>) · <?= date('d M Y, h:i A', strtotime($c['created_at'])) ?></span>
                        </div>
                        <div style="font-size:13.5px;margin-top:3px;"><?= nl2br(sanitize($c['message'])) ?></div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <form method="POST" style="margin-top:14px;">
                <textarea name="message" rows="3" placeholder="Write a comment..." required></textarea>
                <button type="submit" name="add_comment" value="1" class="btn btn-sm" style="margin-top:8px;">Post Comment</button>
            </form>
        </div>
    </div>
</body>
</html>
