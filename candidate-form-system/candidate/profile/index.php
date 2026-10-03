<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

require_candidate_login();
$candidateId = $_SESSION['user_id'];

$stmt = $pdo->prepare(
    'SELECT c.*, u.email FROM candidate_profiles c JOIN users u ON u.id = c.user_id WHERE c.id = ?'
);
$stmt->execute([$candidateId]);
$profile = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = sanitize($_POST['name']);
    $dob     = sanitize($_POST['dob']);
    $phone   = sanitize($_POST['phone']);
    $college = sanitize($_POST['college']);
    $course  = sanitize($_POST['course']);
    $year    = sanitize($_POST['year']);

    if ($name === '' || $dob === '' || $phone === '') {
        set_flash('error', 'Name, date of birth and phone are required.');
    } else {
        $photoPath = $profile['profile_photo'];

        if (!empty($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
            $info = @getimagesize($_FILES['profile_photo']['tmp_name']);
            if ($info) {
                $ext = $info['mime'] === 'image/png' ? 'png' : 'jpg';
                $newName = 'profile_' . $candidateId . '_' . time() . '.' . $ext;
                if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], UPLOAD_PATH_PROFILES . $newName)) {
                    $photoPath = 'uploads/profiles/' . $newName;
                }
            }
        }

        $stmt = $pdo->prepare(
            'UPDATE candidate_profiles SET name = ?, date_of_birth = ?, phone = ?, college = ?, course = ?, year = ?, profile_photo = ?
             WHERE id = ?'
        );
        $stmt->execute([$name, $dob, $phone, $college, $course, $year, $photoPath, $candidateId]);

        $_SESSION['name'] = $name;
        set_flash('success', 'Profile updated.');
        redirect('candidate/profile/index.php');
    }
}

$stmt = $pdo->prepare(
    'SELECT c.*, u.email FROM candidate_profiles c JOIN users u ON u.id = c.user_id WHERE c.id = ?'
);
$stmt->execute([$candidateId]);
$profile = $stmt->fetch();

$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Profile - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="../../public/assets/style.css">
</head>
<body>
    <?php include __DIR__ . '/../../includes/candidate-header.php'; ?>
    <div class="admin-page" style="max-width:520px;">
        <div class="page-header">
            <h1>My Profile</h1>
        </div>

        <?php if ($flash): ?>
            <div class="flash <?= $flash['type'] ?>"><?= sanitize($flash['message']) ?></div>
        <?php endif; ?>

        <div class="card">
            <?php if ($profile['profile_photo']): ?>
                <img src="../../<?= sanitize($profile['profile_photo']) ?>" style="width:80px;height:80px;border-radius:50%;object-fit:cover;margin-bottom:14px;">
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <label class="form-label">Email (cannot be changed)</label>
                <input type="text" value="<?= sanitize($profile['email']) ?>" disabled style="background:var(--bg);">

                <label class="form-label">Full Name</label>
                <input type="text" name="name" value="<?= sanitize($profile['name']) ?>" required>

                <label class="form-label">Date of Birth</label>
                <input type="date" name="dob" value="<?= sanitize($profile['date_of_birth']) ?>" required>

                <label class="form-label">Phone</label>
                <input type="text" name="phone" value="<?= sanitize($profile['phone']) ?>" required>

                <label class="form-label">College</label>
                <input type="text" name="college" value="<?= sanitize($profile['college']) ?>" placeholder="Your college/university">

                <label class="form-label">Course</label>
                <input type="text" name="course" value="<?= sanitize($profile['course']) ?>" placeholder="e.g. B.E Computer Science">

                <label class="form-label">Year</label>
                <input type="text" name="year" value="<?= sanitize($profile['year']) ?>" placeholder="e.g. Final Year">

                <label class="form-label">Profile Photo</label>
                <input type="file" name="profile_photo" accept="image/png,image/jpeg">

                <button type="submit" class="btn" style="margin-top:10px;">Save Changes</button>
            </form>
        </div>
    </div>
</body>
</html>
