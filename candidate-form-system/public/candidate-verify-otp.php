<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

if (empty($_SESSION['pending_verification_user_id'])) {
    redirect('public/candidate-register.php');
}

$userId = $_SESSION['pending_verification_user_id'];
$email  = $_SESSION['pending_verification_email'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $enteredOtp = sanitize($_POST['otp']);

    $stmt = $pdo->prepare(
        'SELECT id, otp_code, expires_at FROM email_verifications
         WHERE user_id = ? AND is_verified = 0
         ORDER BY id DESC LIMIT 1'
    );
    $stmt->execute([$userId]);
    $record = $stmt->fetch();

    if (!$record) {
        set_flash('error', 'No pending OTP found. Please register again.');
    } elseif (strtotime($record['expires_at']) < time()) {
        set_flash('error', 'OTP expired. Please request a new one.');
    } elseif ($enteredOtp !== $record['otp_code']) {
        set_flash('error', 'Incorrect OTP.');
    } else {
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('UPDATE email_verifications SET is_verified = 1 WHERE id = ?');
            $stmt->execute([$record['id']]);

            $stmt = $pdo->prepare('UPDATE users SET email_verified = 1 WHERE id = ?');
            $stmt->execute([$userId]);

            $stmt = $pdo->prepare('SELECT id, name FROM candidate_profiles WHERE user_id = ?');
            $stmt->execute([$userId]);
            $profile = $stmt->fetch();
            $candidateName = $profile['name'];
            $profileId = $profile['id'];

            $pdo->commit();

            send_registration_success_email($email, $candidateName);

            unset($_SESSION['pending_verification_user_id'], $_SESSION['pending_verification_email']);

            // If this verification came from the "link submission" flow, auto-link now and log in directly
            if (!empty($_SESSION['pending_link_submission_id'])) {
                $stmt = $pdo->prepare('UPDATE form_submissions SET candidate_id = ? WHERE id = ?');
                $stmt->execute([$profileId, $_SESSION['pending_link_submission_id']]);

                $slug = $_SESSION['pending_link_slug'] ?? '';
                unset($_SESSION['pending_link_submission_id'], $_SESSION['pending_link_slug'], $_SESSION['pending_link_message'], $_SESSION['pending_link_button_text']);

                $_SESSION['user_id'] = $profileId;
                $_SESSION['auth_user_id'] = $userId;
                $_SESSION['role'] = 'CANDIDATE';
                $_SESSION['name'] = $candidateName;

                redirect('public/form-success.php?slug=' . urlencode($slug));
            }

            set_flash('success', 'Email verified. Please login.');
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
    send_otp_email($email, $otp);
    set_flash('success', 'A new OTP has been sent.');
}

$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Verify OTP - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <div class="auth-container">
        <h1>Verify Your Email</h1>
        <p style="font-size:14px;text-align:center;">OTP sent to <strong><?= sanitize($email) ?></strong></p>
        <?php if ($flash): ?>
            <div class="flash <?= $flash['type'] ?>"><?= sanitize($flash['message']) ?></div>
        <?php endif; ?>
        <form method="POST">
            <input type="text" name="otp" placeholder="Enter 6-digit OTP" maxlength="6" required>
            <button type="submit">Verify</button>
        </form>
        <div class="bottom-link">
            Didn't get the code? <a href="candidate-verify-otp.php?resend=1">Resend OTP</a>
        </div>
    </div>
</body>
</html>
