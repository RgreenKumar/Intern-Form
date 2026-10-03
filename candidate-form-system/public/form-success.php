<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

$slug = sanitize($_GET['slug'] ?? '');
unset($_SESSION['pending_link_submission_id'], $_SESSION['pending_link_slug'], $_SESSION['pending_link_message'], $_SESSION['pending_link_button_text']);

$stmt = $pdo->prepare('SELECT title FROM forms WHERE slug = ?');
$stmt->execute([$slug]);
$form = $stmt->fetch();

if (!empty($_SESSION['role']) && $_SESSION['role'] === 'CANDIDATE') {
    $backLink = '../candidate/dashboard.php';
    $backLabel = 'Go to Dashboard';
} elseif (!empty($_SESSION['role']) && $_SESSION['role'] === 'ADMIN') {
    $backLink = '../admin/dashboard.php';
    $backLabel = 'Go to Dashboard';
} else {
    $backLink = 'index.php';
    $backLabel = 'Back to Home';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Submitted - <?= APP_NAME ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:wght@500;600&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/form-style.css">
</head>
<body class="form-page">
    <div class="success-shell">
        <div class="success-card">
            <div class="success-mark">✓</div>
            <h1>Application received</h1>
            <p>
                Your response to "<?= sanitize($form['title'] ?? 'the form') ?>" has been recorded.
                The <?= APP_NAME ?> team will get back to you soon.
            </p>
            <a href="<?= $backLink ?>"><?= $backLabel ?></a>
        </div>
    </div>
</body>
</html>
