<?php $__cur = basename($_SERVER['PHP_SELF']); $__dir = basename(dirname($_SERVER['PHP_SELF'])); ?>
<nav class="admin-nav">
    <div class="admin-nav-brand"><?= APP_NAME ?></div>
    <div class="admin-nav-links">
        <a href="/candidate-form-system/admin/dashboard.php" class="<?= $__cur === 'dashboard.php' && $__dir === 'admin' ? 'active' : '' ?>">Dashboard</a>
        <a href="/candidate-form-system/admin/forms/index.php" class="<?= $__dir === 'forms' ? 'active' : '' ?>">Forms</a>
        <a href="/candidate-form-system/admin/tasks/index.php" class="<?= $__dir === 'tasks' ? 'active' : '' ?>">Tasks</a>
        <a href="/candidate-form-system/admin/documents/index.php" class="<?= $__dir === 'documents' ? 'active' : '' ?>">Documents</a>
        <div class="nav-user-badge">
            <div class="nav-avatar"><?= strtoupper(substr($_SESSION['name'] ?? 'A', 0, 1)) ?></div>
            <a href="/candidate-form-system/public/logout.php">Logout</a>
        </div>
    </div>
</nav>
