<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

if (empty($_SESSION['pending_reset_user_id'])) {
    redirect('public/forgot-password.php');
}

$userId = $_SESSION['pending_reset_user_id'];
$email  = $_SESSION['pending_reset_email'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $enteredOtp = sanitize($_POST['otp']);
    $password   = $_POST['password'] ?? '';
    $confirm    = $_POST['confirm_password'] ?? '';

    $stmt = $pdo->prepare(
        'SELECT id, otp_code, expires_at FROM email_verifications
         WHERE user_id = ? AND is_verified = 0
         ORDER BY id DESC LIMIT 1'
    );
    $stmt->execute([$userId]);
    $record = $stmt->fetch();

    if (!$record) {
        set_flash('error', 'No pending OTP found. Please request a new one.');
    } elseif (strtotime($record['expires_at']) < time()) {
        set_flash('error', 'OTP expired. Please request a new one.');
    } elseif ($enteredOtp !== $record['otp_code']) {
        set_flash('error', 'Incorrect OTP.');
    } elseif (strlen($password) < 6) {
        set_flash('error', 'Password must be at least 6 characters.');
    } elseif ($password !== $confirm) {
        set_flash('error', 'Passwords do not match.');
    } else {
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('UPDATE email_verifications SET is_verified = 1 WHERE id = ?');
            $stmt->execute([$record['id']]);

            $stmt = $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
            $stmt->execute([password_hash($password, PASSWORD_DEFAULT), $userId]);

            $stmt = $pdo->prepare('SELECT role FROM users WHERE id = ?');
            $stmt->execute([$userId]);
            $role = $stmt->fetchColumn();

            if ($role === 'ADMIN') {
                $stmt = $pdo->prepare('SELECT name FROM admin_profiles WHERE user_id = ?');
            } else {
                $stmt = $pdo->prepare('SELECT name FROM candidate_profiles WHERE user_id = ?');
            }
            $stmt->execute([$userId]);
            $name = $stmt->fetchColumn() ?: 'there';

            $pdo->commit();

            send_password_changed_email($email, $name);

            unset($_SESSION['pending_reset_user_id'], $_SESSION['pending_reset_email']);

            set_flash('success', 'Password reset successful. Please login with your new password.');
            redirect('public/login.php');
        } catch (Exception $e) {
            $pdo->rollBack();
            set_flash('error', 'Something went wrong. Please try again.');
        }
    }
}

if (isset($_GET['resend'])) {
    $otp = generate_otp();
    $expiresAt = date('Y-m-d H:i:s', strtotime('+' . OTP_EXPIRY_MINUTES . ' minutes'));
    $stmt = $pdo->prepare(
        'INSERT INTO email_verifications (user_id, email, otp_code, expires_at) VALUES (?, ?, ?, ?)'
    );
    $stmt->execute([$userId, $email, $otp, $expiresAt]);
    send_password_reset_otp_email($email, $otp);
    set_flash('success', 'A new OTP has been sent.');
}

$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reset Password - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <div class="auth-container">
        <h1>Reset Password</h1>
        <p style="font-size:14px;text-align:center;">OTP sent to <strong><?= sanitize($email) ?></strong></p>
        <?php if ($flash): ?>
            <div class="flash <?= $flash['type'] ?>"><?= sanitize($flash['message']) ?></div>
        <?php endif; ?>
        <form method="POST">
            <input type="text" name="otp" placeholder="Enter 6-digit OTP" maxlength="6" required>
            <input type="password" name="password" placeholder="New password" required>
            <input type="password" name="confirm_password" placeholder="Confirm new password" required>
            <button type="submit">Reset Password</button>
        </form>
        <div class="bottom-link">
            Didn't get the code? <a href="reset-password.php?resend=1">Resend OTP</a>
        </div>
    </div>
</body>
</html>
