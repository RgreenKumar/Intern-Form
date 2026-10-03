<?php
/**
 * General helper functions
 */

function sanitize($value) {
    return htmlspecialchars(trim($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function redirect($path) {
    header('Location: ' . BASE_URL . '/' . ltrim($path, '/'));
    exit;
}

function generate_otp($length = OTP_LENGTH) {
    $otp = '';
    for ($i = 0; $i < $length; $i++) {
        $otp .= random_int(0, 9);
    }
    return $otp;
}

/**
 * Removes a near-white/light background from a signature image and saves it
 * as a transparent PNG, so only the ink strokes remain visible.
 * $whiteThreshold: pixels with R,G,B all >= this value are made transparent.
 */
function remove_signature_background($srcPath, $destPngPath, $whiteThreshold = 200) {
    $info = @getimagesize($srcPath);
    if (!$info) return false;

    switch ($info['mime']) {
        case 'image/jpeg':
            $src = @imagecreatefromjpeg($srcPath);
            break;
        case 'image/png':
            $src = @imagecreatefrompng($srcPath);
            break;
        default:
            return false;
    }
    if (!$src) return false;

    $w = imagesx($src);
    $h = imagesy($src);

    $out = imagecreatetruecolor($w, $h);
    imagealphablending($out, false);
    imagesavealpha($out, true);
    $transparent = imagecolorallocatealpha($out, 0, 0, 0, 127);
    imagefill($out, 0, 0, $transparent);

    $fadeBand = 55; // pixels this much darker than the threshold are treated as pure ink

    for ($y = 0; $y < $h; $y++) {
        for ($x = 0; $x < $w; $x++) {
            $rgb = imagecolorat($src, $x, $y);
            $r = ($rgb >> 16) & 0xFF;
            $g = ($rgb >> 8) & 0xFF;
            $b = $rgb & 0xFF;

            // Use overall brightness (not a strict "must be white" check) so tan/beige/
            // shadowed paper backgrounds are treated the same as white ones.
            $brightness = (0.299 * $r + 0.587 * $g + 0.114 * $b);

            if ($brightness >= $whiteThreshold) {
                // Background / paper -> fully transparent
                imagesetpixel($out, $x, $y, $transparent);
            } elseif ($brightness <= $whiteThreshold - $fadeBand) {
                // Clearly darker than the paper -> solid ink, keep true color
                $color = imagecolorallocatealpha($out, $r, $g, $b, 0);
                imagesetpixel($out, $x, $y, $color);
            } else {
                // In between -> soft fade so stroke edges aren't jagged
                $ratio = ($brightness - ($whiteThreshold - $fadeBand)) / $fadeBand;
                $alpha = (int)round(127 * $ratio);
                $color = imagecolorallocatealpha($out, $r, $g, $b, $alpha);
                imagesetpixel($out, $x, $y, $color);
            }
        }
    }

    imagedestroy($src);
    $result = imagepng($out, $destPngPath);
    imagedestroy($out);
    return $result;
}

function set_flash($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function get_flash() {
    if (!empty($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

require_once __DIR__ . '/PHPMailer/Exception.php';
require_once __DIR__ . '/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/SMTP.php';

/**
 * Generic SMTP send helper (PHPMailer). Returns true/false.
 */
function send_email($to_email, $subject, $body) {
    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USERNAME;
        $mail->Password   = SMTP_PASSWORD;
        $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;

        $mail->setFrom(SMTP_USERNAME, SMTP_FROM_NAME);
        $mail->addAddress($to_email);

        $mail->Subject = $subject;
        $mail->Body    = $body;

        $mail->send();
        return true;
    } catch (\PHPMailer\PHPMailer\Exception $e) {
        return false;
    }
}

/**
 * Sends the OTP email via SMTP.
 * The OTP is also written to a local debug log as a fallback for testing.
 */
function send_otp_email($to_email, $otp) {
    $subject = 'Your Verification Code - ' . APP_NAME;
    $body = "Your OTP for registration is: {$otp}\nThis code expires in " . OTP_EXPIRY_MINUTES . " minutes.";

    send_email($to_email, $subject, $body);

    // Dev-only fallback log so OTP is visible even if the email fails
    $log_line = date('Y-m-d H:i:s') . " | {$to_email} | OTP: {$otp}" . PHP_EOL;
    @file_put_contents(__DIR__ . '/../uploads/otp_debug.log', $log_line, FILE_APPEND);
}

/**
 * Sends a confirmation email after successful registration (email verified).
 */
/**
 * Sends the OTP email for the "Forgot Password" flow.
 */
function send_password_reset_otp_email($to_email, $otp) {
    $subject = 'Password Reset Code - ' . APP_NAME;
    $body = "We received a request to reset your password.\n\nYour OTP is: {$otp}\nThis code expires in " . OTP_EXPIRY_MINUTES . " minutes.\n\nIf you didn't request this, you can safely ignore this email.";

    send_email($to_email, $subject, $body);

    // Dev-only fallback log so OTP is visible even if the email fails
    $log_line = date('Y-m-d H:i:s') . " | {$to_email} | RESET OTP: {$otp}" . PHP_EOL;
    @file_put_contents(__DIR__ . '/../uploads/otp_debug.log', $log_line, FILE_APPEND);
}

/**
 * Sends a confirmation email after a successful password reset.
 */
function send_password_changed_email($to_email, $name) {
    $subject = 'Your Password Was Changed - ' . APP_NAME;
    $body = "Hi {$name},\n\nYour " . APP_NAME . " password was just changed. If this wasn't you, please contact the admin team immediately.\n\n" . APP_NAME;

    send_email($to_email, $subject, $body);
}

function send_registration_success_email($to_email, $name) {
    $subject = 'Registration Successful - ' . APP_NAME;
    $body = "Hi {$name},\n\nYour email has been verified and your registration is complete. You can now log in to your account.\n\n" . APP_NAME;

    send_email($to_email, $subject, $body);
}

/* ------------------------------------------------------------------
 * Default forms (Internship + Workshop)
 * ------------------------------------------------------------------ */

function unique_form_slug($pdo, $title) {
    $base = strtolower(trim(preg_replace('/[^A-Za-z0-9]+/', '-', $title), '-'));
    if ($base === '') $base = 'form';
    $slug = $base;
    $i = 1;
    $stmt = $pdo->prepare('SELECT id FROM forms WHERE slug = ?');
    while (true) {
        $stmt->execute([$slug]);
        if (!$stmt->fetch()) break;
        $i++;
        $slug = $base . '-' . $i;
    }
    return $slug;
}

function default_form_presets() {
    return [
        'internship' => [
            'title' => 'Internship Registration',
            'description' => 'Apply for the internship program. Fill in your details below.',
            'fields' => [
                ['Full Name',        'TEXT',     1, 'Enter your full name', null],
                ['Email',            'EMAIL',    1, 'you@example.com', null],
                ['Phone Number',     'PHONE',    1, '10-digit mobile number', null],
                ['Date of Birth',    'DATE',     1, '', null],
                ['College Name',     'TEXT',     1, 'Your college/university', null],
                ['Course & Year',    'TEXT',     1, 'e.g. B.E CSE - Final Year', null],
                ['Preferred Domain', 'SELECT',   1, '', ['Web Development', 'AI/ML', 'App Development', 'Data Science', 'Other']],
                ['Why do you want this internship?', 'TEXTAREA', 0, 'Tell us briefly', null],
                ['Resume Upload',    'FILE',     1, '', null],
            ],
        ],
        'workshop' => [
            'title' => 'Web Development Workshop',
            'description' => 'Register for our hands-on weekend workshop. Seats are limited.',
            'fields' => [
                ['Full Name',        'TEXT',     1, 'Enter your full name', null],
                ['Email',            'EMAIL',    1, 'you@example.com', null],
                ['Phone Number',     'PHONE',    1, '10-digit mobile number', null],
                ['College Name',     'TEXT',     1, 'Your college/university', null],
                ['Year of Study',    'SELECT',   1, '', ['1st Year', '2nd Year', '3rd Year', 'Final Year']],
                ['Prior Experience', 'RADIO',    1, '', ['Beginner', 'Some Experience', 'Advanced']],
                ['Bring your own laptop?', 'CHECKBOX', 0, '', null],
                ['Anything else you want us to know?', 'TEXTAREA', 0, 'Optional', null],
            ],
        ],
    ];
}

function create_form_from_preset($pdo, $adminId, $presetKey, $status = 'ACTIVE') {
    $presets = default_form_presets();
    if (!isset($presets[$presetKey])) return false;
    $p = $presets[$presetKey];
    $slug = unique_form_slug($pdo, $p['title']);

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare(
            'INSERT INTO forms (admin_id, title, description, slug, status, allow_multiple_submissions, login_requirement, public_visibility, require_website_registration, registration_message, registration_button_text)
             VALUES (?, ?, ?, ?, ?, 0, 0, "PUBLIC", 1, ?, ?)'
        );
        $stmt->execute([
            $adminId, $p['title'], $p['description'], $slug, $status,
            'Create an account to track your application status and receive updates.',
            'Create Account',
        ]);
        $formId = $pdo->lastInsertId();

        $order = 1;
        foreach ($p['fields'] as $f) {
            [$label, $type, $required, $placeholder, $options] = $f;
            $name = strtolower(trim(preg_replace('/[^A-Za-z0-9]+/', '_', $label), '_'));
            $stmt = $pdo->prepare(
                'INSERT INTO form_fields (form_id, field_label, field_name, field_type, is_required, placeholder, display_order, options)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([$formId, $label, $name, $type, $required, $placeholder, $order, $options ? json_encode($options) : null]);
            $order++;
        }
        $pdo->commit();
        return $formId;
    } catch (Exception $e) {
        $pdo->rollBack();
        return false;
    }
}

/**
 * Creates any default form this admin doesn't already have (matched by title).
 * Returns how many were created.
 */
function seed_default_forms($pdo, $adminId) {
    $created = 0;
    foreach (default_form_presets() as $key => $p) {
        $stmt = $pdo->prepare('SELECT id FROM forms WHERE admin_id = ? AND title = ?');
        $stmt->execute([$adminId, $p['title']]);
        if (!$stmt->fetch()) {
            if (create_form_from_preset($pdo, $adminId, $key, 'ACTIVE')) $created++;
        }
    }
    return $created;
}

/* ------------------------------------------------------------------
 * Public marketing pages (home / internships / workshops)
 * ------------------------------------------------------------------ */

function landing_form_type($title) {
    $t = strtolower($title);
    if (strpos($t, 'workshop') !== false) return 'workshop';
    if (strpos($t, 'internship') !== false) return 'internship';
    return 'other';
}

function get_open_forms_grouped($pdo) {
    $stmt = $pdo->query(
        "SELECT f.*, (SELECT COUNT(*) FROM form_fields ff WHERE ff.form_id = f.id) AS field_count
         FROM forms f
         WHERE f.status = 'ACTIVE' AND f.public_visibility = 'PUBLIC'
         ORDER BY f.created_at DESC"
    );
    $all = $stmt->fetchAll();
    $grouped = ['internship' => [], 'workshop' => [], 'other' => []];
    foreach ($all as $f) {
        $grouped[landing_form_type($f['title'])][] = $f;
    }
    return [$grouped, count($all)];
}

function render_program_cards($forms, $emoji, $label, $emptyText) {
    if (empty($forms)) {
        echo '<div class="prog-empty">' . htmlspecialchars($emptyText) . '</div>';
        return;
    }
    echo '<div class="prog-grid">';
    foreach ($forms as $f) {
        $minutes = max(1, (int)ceil($f['field_count'] * 0.6));
        echo '<div class="prog-card">';
        echo '  <div class="prog-top"><span class="prog-tag">' . $emoji . ' ' . $label . '</span>';
        echo '  <span class="prog-meta">' . (int)$f['field_count'] . ' fields · ~' . $minutes . ' min</span></div>';
        echo '  <h3>' . sanitize($f['title']) . '</h3>';
        if ($f['description']) echo '  <p>' . sanitize($f['description']) . '</p>';
        $applyHref = !empty($f['external_url']) ? $f['external_url'] : 'form.php?slug=' . urlencode($f['slug']);
        $applyTarget = !empty($f['external_url']) ? ' target="_blank" rel="noopener"' : '';
        echo '  <a class="prog-apply" href="' . sanitize($applyHref) . '"' . $applyTarget . '>Apply Now →</a>';
        echo '</div>';
    }
    echo '</div>';
}
