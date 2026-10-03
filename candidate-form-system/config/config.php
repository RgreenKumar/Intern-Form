<?php
/**
 * Application config
 */

define('APP_NAME', 'TalentTrack');
define('BASE_URL', 'http://localhost/candidate-form-system');

define('UPLOAD_PATH_TASKS', __DIR__ . '/../uploads/tasks/');
define('UPLOAD_PATH_SUBMISSIONS', __DIR__ . '/../uploads/submissions/');
define('UPLOAD_PATH_PROFILES', __DIR__ . '/../uploads/profiles/');
define('UPLOAD_PATH_DOCUMENTS', __DIR__ . '/../uploads/documents/');

// Secret key required to create an ADMIN account (share it only with people who should be admins).
// CHANGE THIS to your own value before using the site for real.
define('ADMIN_REGISTRATION_KEY', 'change-this-key');

define('OTP_LENGTH', 6);
define('OTP_EXPIRY_MINUTES', 10);

// ---- SMTP settings (used to send real OTP emails via PHPMailer) ----
// For Gmail: use an "App Password", NOT your normal Gmail password.
// Generate one at: https://myaccount.google.com/apppasswords
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your-email@gmail.com');
define('SMTP_PASSWORD', 'your-app-password-here');       // App Password (spaces removed)
define('SMTP_FROM_NAME', APP_NAME);

session_start();

require_once __DIR__ . '/db.php';