<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name           = sanitize($_POST['name']);
    $email          = sanitize($_POST['email']);
    $password       = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    $accessKey      = $_POST['access_key'] ?? '';

    if ($name === '' || $email === '' || $password === '' || $confirmPassword === '') {
        set_flash('error', 'All fields are required.');
    } elseif (strlen($password) < 6) {
        set_flash('error', 'Password must be at least 6 characters.');
    } elseif ($password !== $confirmPassword) {
        set_flash('error', 'Passwords do not match.');
    } elseif (!hash_equals(ADMIN_REGISTRATION_KEY, $accessKey)) {
        set_flash('error', 'Invalid admin access key. Admin accounts can only be created by authorised people.');
    } else {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);

        if ($stmt->fetch()) {
            set_flash('error', 'This email is already registered.');
        } else {
            $pdo->beginTransaction();
            try {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare(
                    'INSERT INTO users (email, password_hash, role, email_verified) VALUES (?, ?, "ADMIN", 1)'
                );
                $stmt->execute([$email, $hash]);
                $userId = $pdo->lastInsertId();

                $stmt = $pdo->prepare('INSERT INTO admin_profiles (user_id, name) VALUES (?, ?)');
                $stmt->execute([$userId, $name]);
                $adminProfileId = $pdo->lastInsertId();

                $pdo->commit();

                // Every new admin starts with the default Internship + Workshop forms (already published)
                seed_default_forms($pdo, $adminProfileId);

                set_flash('success', 'Registration successful. Please login.');
                redirect('public/login.php');
            } catch (Exception $e) {
                $pdo->rollBack();
                set_flash('error', 'Something went wrong. Please try again.');
            }
        }
    }
}

$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Register - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="assets/style.css">
    <script src="assets/app.js" defer></script>
</head>
<body>
    <div class="auth-container">
        <h1>Admin Register</h1>
        <?php if ($flash): ?>
            <div class="flash <?= $flash['type'] ?>"><?= sanitize($flash['message']) ?></div>
        <?php endif; ?>
        <form method="POST">
            <input type="text" name="name" placeholder="Full Name" required>
            <input type="email" name="email" placeholder="Email" required>
            <div class="password-field">
                <input type="password" name="password" placeholder="Password" required>
                <button type="button" class="password-toggle" onclick="togglePassword(this)">👁</button>
            </div>
            <div class="password-field">
                <input type="password" name="confirm_password" placeholder="Confirm Password" required>
                <button type="button" class="password-toggle" onclick="togglePassword(this)">👁</button>
            </div>
            <input type="password" name="access_key" placeholder="Admin Access Key" required autocomplete="off">
            <button type="submit">Register</button>
        </form>
        <div class="bottom-link">
            Already have an account? <a href="login.php">Login</a>
        </div>
    </div>
</body>
</html>
