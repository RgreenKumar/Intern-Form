<footer class="site-footer">
    <div class="foot-grid">
        <div class="foot-about">
            <a href="index.php" class="brand">Talent<span>Track</span></a>
            <p>Apply for internships and workshops, track your application, complete tasks and collect your certificates — all in one place.</p>
        </div>
        <div class="foot-col">
            <h4>Candidates</h4>
            <?php if ($dashboardUrl): ?>
                <a href="<?= $dashboardUrl ?>">My Dashboard</a>
            <?php else: ?>
                <a href="login.php">Login</a>
                <a href="candidate-register.php">Create Account</a>
            <?php endif; ?>
        </div>
        <div class="foot-col">
            <h4>Opportunities</h4>
            <a href="internships.php">Internships</a>
            <a href="workshops.php">Workshops</a>
            <a href="forms.php">All Opportunities</a>
        </div>
        <div class="foot-col">
            <h4>Help</h4>
            <a href="how-it-works.php">How it works</a>
            <a href="internships.php">Apply now</a>
        </div>
    </div>
    <div class="foot-bottom">
        <span>© <?= date('Y') ?> <?= APP_NAME ?>. All rights reserved.</span>
        <span>Secure applications · Verified opportunities</span>
    </div>
</footer>
