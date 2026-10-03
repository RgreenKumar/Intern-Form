<?php $__cur = basename($_SERVER['PHP_SELF']); $__dir = basename(dirname($_SERVER['PHP_SELF'])); ?>
<nav class="admin-nav">
    <div class="admin-nav-brand"><?= APP_NAME ?></div>
    <div class="admin-nav-links">
        <a href="/candidate-form-system/candidate/dashboard.php" class="<?= $__cur === 'dashboard.php' && $__dir === 'candidate' ? 'active' : '' ?>">Dashboard</a>
        <a href="/candidate-form-system/public/forms.php" class="<?= $__cur === 'forms.php' ? 'active' : '' ?>">Browse Forms</a>
        <a href="/candidate-form-system/candidate/profile/index.php" class="<?= $__dir === 'profile' ? 'active' : '' ?>">My Profile</a>
        <a href="/candidate-form-system/candidate/applications/index.php" class="<?= $__dir === 'applications' ? 'active' : '' ?>">My Applications</a>
        <a href="/candidate-form-system/candidate/tasks/index.php" class="<?= $__dir === 'tasks' ? 'active' : '' ?>">My Tasks</a>
        <a href="/candidate-form-system/candidate/documents/index.php" class="<?= $__dir === 'documents' ? 'active' : '' ?>">My Documents</a>
        <div class="nav-user-badge">
            <div class="nav-avatar"><?= strtoupper(substr($_SESSION['name'] ?? 'C', 0, 1)) ?></div>
            <a href="/candidate-form-system/public/logout.php">Logout</a>
        </div>
    </div>
</nav>
