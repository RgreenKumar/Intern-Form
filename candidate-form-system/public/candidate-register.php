<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name           = sanitize($_POST['name']);
    $email          = sanitize($_POST['email']);
    $password       = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    $dob            = sanitize($_POST['dob']);
    $phone          = sanitize($_POST['phone']);

    if ($name === '' || $email === '' || $password === '' || $confirmPassword === '' || $dob === '' || $phone === '') {
        set_flash('error', 'All fields are required.');
    } elseif (strlen($password) < 6) {
        set_flash('error', 'Password must be at least 6 characters.');
    } elseif ($password !== $confirmPassword) {
        set_flash('error', 'Passwords do not match.');
    } else {
        $stmt = $pdo->prepare('SELECT id, email_verified FROM users WHERE email = ? AND role = "CANDIDATE"');
        $stmt->execute([$email]);
        $existing = $stmt->fetch();

        if ($existing && $existing['email_verified']) {
            set_flash('error', 'This email is already registered. Please login.');
        } else {
            $pdo->beginTransaction();
            try {
                $hash = password_hash($password, PASSWORD_DEFAULT);

                if ($existing) {
                    // Re-registration attempt before OTP was verified: update details
                    $userId = $existing['id'];
                    $stmt = $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
                    $stmt->execute([$hash, $userId]);

                    $stmt = $pdo->prepare(
                        'UPDATE candidate_profiles SET name = ?, date_of_birth = ?, phone = ? WHERE user_id = ?'
                    );
                    $stmt->execute([$name, $dob, $phone, $userId]);
                } else {
                    $stmt = $pdo->prepare(
                        'INSERT INTO users (email, password_hash, role, email_verified) VALUES (?, ?, "CANDIDATE", 0)'
                    );
                    $stmt->execute([$email, $hash]);
                    $userId = $pdo->lastInsertId();

                    $stmt = $pdo->prepare(
                        'INSERT INTO candidate_profiles (user_id, name, date_of_birth, phone) VALUES (?, ?, ?, ?)'
                    );
                    $stmt->execute([$userId, $name, $dob, $phone]);
                }

                $otp = generate_otp();
                $expiresAt = date('Y-m-d H:i:s', strtotime('+' . OTP_EXPIRY_MINUTES . ' minutes'));

                $stmt = $pdo->prepare(
                    'INSERT INTO email_verifications (user_id, email, otp_code, expires_at) VALUES (?, ?, ?, ?)'
                );
                $stmt->execute([$userId, $email, $otp, $expiresAt]);

                $pdo->commit();

                send_otp_email($email, $otp);

                $_SESSION['pending_verification_user_id'] = $userId;
                $_SESSION['pending_verification_email']   = $email;
                redirect('public/candidate-verify-otp.php');
            } catch (Exception $e) {
                $pdo->rollBack();
                set_flash('error', 'Something went wrong. Please try again.');
            }
        }
    }
}

$prefillEmail = sanitize($_GET['email'] ?? '');
$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Candidate Register - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="assets/style.css">
    <script src="assets/app.js" defer></script>
</head>
<body>
    <div class="auth-container">
        <h1>Candidate Register</h1>
        <?php if (!empty($_SESSION['pending_link_submission_id'])): ?>
            <p style="font-size:12.5px;text-align:center;color:var(--text-muted);">Finish creating your account to save your application.</p>
        <?php endif; ?>
        <?php if ($flash): ?>
            <div class="flash <?= $flash['type'] ?>"><?= sanitize($flash['message']) ?></div>
        <?php endif; ?>
        <form method="POST">
            <input type="text" name="name" placeholder="Full Name" required>
            <input type="email" name="email" placeholder="Email" value="<?= $prefillEmail ?>" required>
            <div class="password-field">
                <input type="password" name="password" placeholder="Password" required>
                <button type="button" class="password-toggle" onclick="togglePassword(this)">👁</button>
            </div>
            <div class="password-field">
                <input type="password" name="confirm_password" placeholder="Confirm Password" required>
                <button type="button" class="password-toggle" onclick="togglePassword(this)">👁</button>
            </div>
            <input type="date" name="dob" required>
            <input type="text" name="phone" placeholder="Phone Number" required>
            <button type="submit">Register</button>
        </form>
        <div class="bottom-link">
            Already have an account? <a href="login.php">Login</a>
        </div>
    </div>
</body>
</html>
