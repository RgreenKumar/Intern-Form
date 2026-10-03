<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

require_admin_login();
$adminId = $_SESSION['user_id'];

function generate_unique_slug($pdo, $title) {
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

// Preset selection: 'internship' (default) or 'workshop'
$presetType = $_GET['type'] ?? 'internship';

if ($presetType === 'workshop') {
    $title       = 'Web Development Workshop';
    $description = 'Register for our hands-on weekend workshop. Seats are limited.';
    $sampleFields = [
        ['label' => 'Full Name',        'type' => 'TEXT',     'required' => 1, 'placeholder' => 'Enter your full name', 'options' => null],
        ['label' => 'Email',            'type' => 'EMAIL',    'required' => 1, 'placeholder' => 'you@example.com',      'options' => null],
        ['label' => 'Phone Number',     'type' => 'PHONE',    'required' => 1, 'placeholder' => '10-digit mobile number', 'options' => null],
        ['label' => 'College Name',     'type' => 'TEXT',     'required' => 1, 'placeholder' => 'Your college/university', 'options' => null],
        ['label' => 'Year of Study',    'type' => 'SELECT',   'required' => 1, 'placeholder' => '', 'options' => json_encode(['1st Year', '2nd Year', '3rd Year', 'Final Year'])],
        ['label' => 'Prior Experience', 'type' => 'RADIO',    'required' => 1, 'placeholder' => '', 'options' => json_encode(['Beginner', 'Some Experience', 'Advanced'])],
        ['label' => 'Bring your own laptop?', 'type' => 'CHECKBOX', 'required' => 0, 'placeholder' => '', 'options' => null],
        ['label' => 'Anything else you want us to know?', 'type' => 'TEXTAREA', 'required' => 0, 'placeholder' => 'Optional', 'options' => null],
    ];
} else {
    $title       = 'Internship Registration';
    $description = 'Apply for the internship program. Fill in your details below.';
    $sampleFields = [
        ['label' => 'Full Name',       'type' => 'TEXT',     'required' => 1, 'placeholder' => 'Enter your full name', 'options' => null],
        ['label' => 'Email',           'type' => 'EMAIL',    'required' => 1, 'placeholder' => 'you@example.com',      'options' => null],
        ['label' => 'Phone Number',    'type' => 'PHONE',    'required' => 1, 'placeholder' => '10-digit mobile number', 'options' => null],
        ['label' => 'Date of Birth',   'type' => 'DATE',     'required' => 1, 'placeholder' => '',                     'options' => null],
        ['label' => 'College Name',    'type' => 'TEXT',     'required' => 1, 'placeholder' => 'Your college/university', 'options' => null],
        ['label' => 'Course & Year',   'type' => 'TEXT',     'required' => 1, 'placeholder' => 'e.g. B.E CSE - Final Year', 'options' => null],
        ['label' => 'Preferred Domain','type' => 'SELECT',   'required' => 1, 'placeholder' => '', 'options' => json_encode(['Web Development', 'AI/ML', 'App Development', 'Data Science', 'Other'])],
        ['label' => 'Why do you want this internship?', 'type' => 'TEXTAREA', 'required' => 0, 'placeholder' => 'Tell us briefly', 'options' => null],
        ['label' => 'Resume Upload',   'type' => 'FILE',     'required' => 1, 'placeholder' => '', 'options' => null],
    ];
}

$slug = generate_unique_slug($pdo, $title);

$pdo->beginTransaction();
try {
    $stmt = $pdo->prepare(
        'INSERT INTO forms (admin_id, title, description, slug, status, allow_multiple_submissions, login_requirement, public_visibility)
         VALUES (?, ?, ?, ?, "DRAFT", 0, 0, "PUBLIC")'
    );
    $stmt->execute([$adminId, $title, $description, $slug]);
    $formId = $pdo->lastInsertId();

    $order = 1;
    foreach ($sampleFields as $f) {
        $name = strtolower(trim(preg_replace('/[^A-Za-z0-9]+/', '_', $f['label']), '_'));
        $stmt = $pdo->prepare(
            'INSERT INTO form_fields (form_id, field_label, field_name, field_type, is_required, placeholder, display_order, options)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$formId, $f['label'], $name, $f['type'], $f['required'], $f['placeholder'], $order, $f['options']]);
        $order++;
    }

    $pdo->commit();
    set_flash('success', 'Sample "' . $title . '" form generated with ' . count($sampleFields) . ' fields. Review it below, then click Publish when ready.');
    redirect('admin/forms/fields.php?form_id=' . $formId);
} catch (Exception $e) {
    $pdo->rollBack();
    set_flash('error', 'Could not generate sample form. Please try again.');
    redirect('admin/forms/index.php');
}
