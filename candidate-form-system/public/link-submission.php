<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

if (empty($_SESSION['pending_link_submission_id'])) {
    redirect('public/index.php');
}

$email = sanitize($_GET['email'] ?? '');

$stmt = $pdo->prepare('SELECT id, email_verified FROM users WHERE email = ? AND role = "CANDIDATE"');
$stmt->execute([$email]);
$existing = $stmt->fetch();

$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Save Your Application - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <?php
    $customMessage = $_SESSION['pending_link_message'] ?? '';
    $customButtonText = $_SESSION['pending_link_button_text'] ?? '';
    ?>
    <div class="auth-container">
        <h1>One Last Step</h1>
        <p style="font-size:13.5px;color:var(--text-muted);text-align:center;">
            <?= $customMessage !== '' ? sanitize($customMessage) : 'To track your application status and get updates, save it to a ' . APP_NAME . ' account.' ?>
        </p>

        <?php if ($flash): ?>
            <div class="flash <?= $flash['type'] ?>"><?= sanitize($flash['message']) ?></div>
        <?php endif; ?>

        <?php if ($existing && $existing['email_verified']): ?>
            <p style="font-size:13px;text-align:center;">
                <strong><?= sanitize($email) ?></strong> is already registered. Login to link this application.
            </p>
            <form method="POST" action="link-login.php">
                <input type="hidden" name="email" value="<?= sanitize($email) ?>">
                <input type="password" name="password" placeholder="Password" required>
                <button type="submit">Login & Save Application</button>
            </form>
        <?php else: ?>
            <p style="font-size:13px;text-align:center;">
                <strong><?= sanitize($email) ?></strong> is new here. Create an account to save this application.
            </p>
            <a href="candidate-register.php?email=<?= urlencode($email) ?>" class="btn" style="display:block;text-align:center;text-decoration:none;padding:10px;">
                <?= $customButtonText !== '' ? sanitize($customButtonText) : 'Register with this email' ?>
            </a>
        <?php endif; ?>

        <div class="bottom-link">
            <a href="form-success.php?slug=<?= urlencode($_SESSION['pending_link_slug'] ?? '') ?>&skip=1">Skip for now</a>
        </div>
    </div>
</body>
</html>
