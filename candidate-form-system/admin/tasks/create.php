<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

require_admin_login();
$adminId = $_SESSION['user_id'];

$stmt = $pdo->query('SELECT id, name FROM candidate_profiles ORDER BY name');
$candidates = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $candidateId = (int)$_POST['candidate_id'];
    $title       = sanitize($_POST['title']);
    $description = sanitize($_POST['description']);
    $dueDate     = $_POST['due_date'] !== '' ? $_POST['due_date'] : null;
    $priority    = in_array($_POST['priority'], ['LOW','MEDIUM','HIGH'], true) ? $_POST['priority'] : 'MEDIUM';
    $linkUrl     = sanitize($_POST['link_url'] ?? '');

    if ($title === '' || $candidateId === 0) {
        set_flash('error', 'Please select a candidate and enter a title.');
    } else {
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO tasks (admin_id, candidate_id, title, description, due_date, priority, status)
                 VALUES (?, ?, ?, ?, ?, ?, "PENDING")'
            );
            $stmt->execute([$adminId, $candidateId, $title, $description, $dueDate, $priority]);
            $taskId = $pdo->lastInsertId();

            $stmt = $pdo->prepare('INSERT INTO task_status_history (task_id, status) VALUES (?, "PENDING")');
            $stmt->execute([$taskId]);

            if ($linkUrl !== '') {
                $stmt = $pdo->prepare('INSERT INTO task_attachments (task_id, attachment_type, url) VALUES (?, "LINK", ?)');
                $stmt->execute([$taskId, $linkUrl]);
            }

            if (!empty($_FILES['attachment_file']) && $_FILES['attachment_file']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['attachment_file'];
                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $newName = 'task_' . $taskId . '_' . time() . '_' . uniqid() . '.' . $ext;
                if (move_uploaded_file($file['tmp_name'], UPLOAD_PATH_TASKS . $newName)) {
                    $stmt = $pdo->prepare(
                        'INSERT INTO task_attachments (task_id, attachment_type, file_path, original_filename, mime_type, file_size)
                         VALUES (?, "FILE", ?, ?, ?, ?)'
                    );
                    $stmt->execute([$taskId, 'uploads/tasks/' . $newName, $file['name'], $file['type'], $file['size']]);
                }
            }

            $pdo->commit();
            set_flash('success', 'Task assigned.');
            redirect('admin/tasks/view.php?id=' . $taskId);
        } catch (Exception $e) {
            $pdo->rollBack();
            set_flash('error', 'Something went wrong. Please try again.');
        }
    }
}

$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Assign Task - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="../../public/assets/style.css">
</head>
<body>
    <?php include __DIR__ . '/../../includes/admin-header.php'; ?>
    <div class="admin-page">
        <div class="page-header">
            <h1>Assign New Task</h1>
            <a href="index.php" class="btn btn-secondary">Back to Tasks</a>
        </div>

        <?php if ($flash): ?>
            <div class="flash <?= $flash['type'] ?>"><?= sanitize($flash['message']) ?></div>
        <?php endif; ?>

        <?php if (empty($candidates)): ?>
            <div class="card empty-state">No candidates registered yet. Tasks can only be assigned once a candidate has an account.</div>
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

                    <label class="form-label">Task Title</label>
                    <input type="text" name="title" placeholder="e.g. Submit signed offer letter" required>

                    <label class="form-label">Description</label>
                    <input type="text" name="description" placeholder="Details about this task">

                    <label class="form-label">Due Date</label>
                    <input type="date" name="due_date">

                    <label class="form-label">Priority</label>
                    <select name="priority">
                        <option value="LOW">Low</option>
                        <option value="MEDIUM" selected>Medium</option>
                        <option value="HIGH">High</option>
                    </select>

                    <label class="form-label">Attach a Link (optional)</label>
                    <input type="text" name="link_url" placeholder="https://...">

                    <label class="form-label">Attach a File/PDF (optional)</label>
                    <input type="file" name="attachment_file">

                    <button type="submit" class="btn" style="margin-top:10px;">Assign Task</button>
                </form>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
