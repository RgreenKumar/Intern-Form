<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

if (empty($_SESSION['pending_link_submission_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('public/index.php');
}

$email    = sanitize($_POST['email']);
$password = $_POST['password'];

$stmt = $pdo->prepare(
    'SELECT u.id AS auth_user_id, u.password_hash, u.email_verified, u.account_status, c.id AS profile_id, c.name
     FROM users u
     JOIN candidate_profiles c ON c.user_id = u.id
     WHERE u.email = ? AND u.role = "CANDIDATE"'
);
$stmt->execute([$email]);
$candidate = $stmt->fetch();

if (!$candidate || !password_verify($password, $candidate['password_hash']) || !$candidate['email_verified']) {
    set_flash('error', 'Invalid email or password.');
    redirect('public/link-submission.php?email=' . urlencode($email));
}

$submissionId = $_SESSION['pending_link_submission_id'];
$slug = $_SESSION['pending_link_slug'] ?? '';

$stmt = $pdo->prepare('UPDATE form_submissions SET candidate_id = ? WHERE id = ?');
$stmt->execute([$candidate['profile_id'], $submissionId]);

$_SESSION['user_id'] = $candidate['profile_id'];
$_SESSION['auth_user_id'] = $candidate['auth_user_id'];
$_SESSION['role']    = 'CANDIDATE';
$_SESSION['name']    = $candidate['name'];

unset($_SESSION['pending_link_submission_id'], $_SESSION['pending_link_slug'], $_SESSION['pending_link_message'], $_SESSION['pending_link_button_text']);

redirect('public/form-success.php?slug=' . urlencode($slug));
