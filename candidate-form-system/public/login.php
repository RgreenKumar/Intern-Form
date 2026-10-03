<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = sanitize($_POST['email']);
    $password = $_POST['password'];

    // One lookup on the shared users table — role decides where we go next.
    $stmt = $pdo->prepare(
        'SELECT id, role, password_hash, email_verified, account_status
         FROM users WHERE email = ?'
    );
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        set_flash('error', 'Invalid email or password.');
    } elseif ($user['role'] === 'CANDIDATE' && !$user['email_verified']) {
        set_flash('error', 'Please verify your email first.');
        $_SESSION['pending_verification_user_id'] = $user['id'];
        $_SESSION['pending_verification_email']   = $email;
        redirect('public/candidate-verify-otp.php');
    } elseif ($user['account_status'] !== 'ACTIVE') {
        set_flash('error', 'This account is not active.');
    } elseif ($user['role'] === 'ADMIN') {
        $stmt2 = $pdo->prepare('SELECT id, name FROM admin_profiles WHERE user_id = ?');
        $stmt2->execute([$user['id']]);
        $admin = $stmt2->fetch();

        // user_id here is admin_profiles.id — the id every foreign key (forms.admin_id, tasks.admin_id, etc.) points to
        $_SESSION['user_id']      = $admin['id'];
        $_SESSION['auth_user_id'] = $user['id'];
        $_SESSION['role']         = 'ADMIN';
        $_SESSION['name']         = $admin['name'];
        redirect('admin/dashboard.php');
    } else {
        $stmt2 = $pdo->prepare('SELECT id, name FROM candidate_profiles WHERE user_id = ?');
        $stmt2->execute([$user['id']]);
        $candidate = $stmt2->fetch();

        // user_id here is candidate_profiles.id — the id every foreign key (form_submissions.candidate_id, tasks.candidate_id, etc.) points to
        $_SESSION['user_id']      = $candidate['id'];
        $_SESSION['auth_user_id'] = $user['id'];
        $_SESSION['role']         = 'CANDIDATE';
        $_SESSION['name']         = $candidate['name'];
        redirect('candidate/dashboard.php');
    }
}

$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <div class="auth-container">
        <h1>Login</h1>
        <?php if ($flash): ?>
            <div class="flash <?= $flash['type'] ?>"><?= sanitize($flash['message']) ?></div>
        <?php endif; ?>
        <form method="POST">
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Login</button>
        </form>
        <div class="bottom-link">
            <a href="forgot-password.php">Forgot password?</a>
        </div>
        <div class="bottom-link">
            Don't have an account? <a href="candidate-register.php">Register</a>
        </div>
    </div>
</body>
</html>
