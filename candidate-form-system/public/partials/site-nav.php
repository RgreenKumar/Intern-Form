<?php
// Expects $activePage to be set by the including page: 'home' | 'internships' | 'workshops' | 'how'
$activePage = $activePage ?? '';
$dashboardUrl = null;
if (!empty($_SESSION['role'])) {
    $dashboardUrl = $_SESSION['role'] === 'ADMIN' ? '../admin/dashboard.php' : '../candidate/dashboard.php';
}
?>
<header class="site-nav">
    <a href="index.php" class="brand">Talent<span>Track</span></a>
    <nav class="nav-links">
        <a href="internships.php" class="<?= $activePage === 'internships' ? 'active' : '' ?>">Internships</a>
        <a href="workshops.php" class="<?= $activePage === 'workshops' ? 'active' : '' ?>">Workshops</a>
        <a href="how-it-works.php" class="<?= $activePage === 'how' ? 'active' : '' ?>">How it works</a>
    </nav>
    <div class="nav-actions">
        <?php if ($dashboardUrl): ?>
            <a href="<?= $dashboardUrl ?>" class="dash-btn">Go to Dashboard</a>
        <?php else: ?>
            <a href="login.php" class="nav-login">Login</a>
            <a href="candidate-register.php" class="nav-register">Register</a>
        <?php endif; ?>
    </div>
</header>
