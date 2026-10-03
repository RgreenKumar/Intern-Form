<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email']);

    $stmt = $pdo->prepare('SELECT id, role FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
        set_flash('error', 'No account found with that email address.');
    } else {
        $otp = generate_otp();
        $expiresAt = date('Y-m-d H:i:s', strtotime('+' . OTP_EXPIRY_MINUTES . ' minutes'));

        $stmt = $pdo->prepare(
            'INSERT INTO email_verifications (user_id, email, otp_code, expires_at) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$user['id'], $email, $otp, $expiresAt]);

        send_password_reset_otp_email($email, $otp);

        $_SESSION['pending_reset_user_id'] = $user['id'];
        $_SESSION['pending_reset_email']   = $email;

        set_flash('success', 'An OTP has been sent to ' . $email . '.');
        redirect('public/reset-password.php');
    }
}

$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Forgot Password - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <div class="auth-container">
        <h1>Forgot Password</h1>
        <p style="font-size:14px;text-align:center;color:var(--text-muted,#777);margin-top:-8px;">
            Enter the email on your account and we'll send you an OTP to reset your password.
        </p>
        <?php if ($flash): ?>
            <div class="flash <?= $flash['type'] ?>"><?= sanitize($flash['message']) ?></div>
        <?php endif; ?>
        <form method="POST">
            <input type="email" name="email" placeholder="Email" required>
            <button type="submit">Send OTP</button>
        </form>
        <div class="bottom-link">
            Remembered your password? <a href="login.php">Login</a>
        </div>
    </div>
</body>
</html>
