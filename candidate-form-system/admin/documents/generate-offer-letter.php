<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/fpdf/fpdf.php';

require_admin_login();
$adminId = $_SESSION['user_id'];
$adminName = $_SESSION['name'];

$stmt = $pdo->query('SELECT id, name FROM candidate_profiles ORDER BY name');
$candidates = $stmt->fetchAll();

$sigPath = null;
foreach (['png', 'jpg'] as $ext) {
    $p = UPLOAD_PATH_PROFILES . 'signature_' . $adminId . '.' . $ext;
    if (file_exists($p)) { $sigPath = $p; break; }
}
$hasSignature = $sigPath !== null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $candidateId = (int)$_POST['candidate_id'];
    $role        = sanitize($_POST['role']);
    $startDate   = sanitize($_POST['start_date']);
    $duration    = sanitize($_POST['duration']);
    $stipend     = sanitize($_POST['stipend']);

    $stmt = $pdo->prepare('SELECT name FROM candidate_profiles WHERE id = ?');
    $stmt->execute([$candidateId]);
    $candidateName = $stmt->fetchColumn();

    if (!$candidateName || $role === '' || $startDate === '' || $duration === '') {
        set_flash('error', 'Please fill in all fields.');
        redirect('admin/documents/generate-offer-letter.php');
    }

    // ---- Build the PDF ----
    $pdf = new FPDF('P', 'mm', 'A4');
    $pdf->AddPage();
    $pdf->SetAutoPageBreak(true, 25);

    // Header bar
    $pdf->SetFillColor(18, 23, 43);
    $pdf->Rect(0, 0, 210, 25, 'F');
    $pdf->SetTextColor(232, 163, 61);
    $pdf->SetFont('Helvetica', 'B', 16);
    $pdf->SetXY(20, 8);
    $pdf->Cell(0, 10, APP_NAME, 0, 1);

    $pdf->SetTextColor(18, 23, 43);
    $pdf->SetFont('Helvetica', 'B', 20);
    $pdf->SetXY(20, 40);
    $pdf->Cell(0, 10, 'Internship Offer Letter', 0, 1);

    $pdf->SetTextColor(107, 114, 128);
    $pdf->SetFont('Helvetica', '', 10);
    $pdf->SetXY(20, 50);
    $pdf->Cell(0, 6, 'Date: ' . date('d F Y'), 0, 1);

    $pdf->SetTextColor(18, 23, 43);
    $pdf->SetFont('Helvetica', '', 11);
    $pdf->SetXY(20, 65);

    $body = "Dear {$candidateName},\n\n"
        . "We are pleased to offer you the position of {$role} with " . APP_NAME . ", "
        . "based on your application and evaluation.\n\n"
        . "Internship Details:\n"
        . "   Role:            {$role}\n"
        . "   Duration:        {$duration}\n"
        . "   Start Date:      {$startDate}\n"
        . ($stipend !== '' ? "   Stipend:         {$stipend}\n" : "")
        . "\nPlease confirm your acceptance of this offer through your " . APP_NAME . " candidate dashboard.\n\n"
        . "We look forward to having you on the team.\n\n"
        . "Warm regards,";

    $pdf->MultiCell(170, 6.2, $body);

    // Signature
    $sigY = $pdf->GetY() + 4;
    if ($hasSignature) {
        $pdf->Image($sigPath, 20, $sigY, 35);
        $sigY += 22;
    } else {
        $sigY += 8;
    }
    $pdf->SetXY(20, $sigY);
    $pdf->SetFont('Helvetica', 'B', 11);
    $pdf->Cell(0, 6, $adminName, 0, 1);
    $pdf->SetFont('Helvetica', '', 10);
    $pdf->SetX(20);
    $pdf->Cell(0, 6, APP_NAME, 0, 1);

    // Footer
    $pdf->SetY(-20);
    $pdf->SetDrawColor(227, 229, 236);
    $pdf->Line(20, $pdf->GetY(), 190, $pdf->GetY());
    $pdf->SetFont('Helvetica', '', 7);
    $pdf->SetTextColor(107, 114, 128);
    $pdf->Cell(0, 10, 'Generated via ' . APP_NAME, 0, 0, 'L');

    $fileName = 'offer_letter_' . $candidateId . '_' . time() . '.pdf';
    $fullPath = UPLOAD_PATH_DOCUMENTS . $fileName;
    $pdf->Output('F', $fullPath);

    $stmt = $pdo->prepare(
        'INSERT INTO candidate_documents
         (candidate_id, admin_id, document_type, document_title, file_path, original_filename, mime_type, file_size, message_note, document_status)
         VALUES (?, ?, "Offer Letter", ?, ?, ?, "application/pdf", ?, ?, "AVAILABLE")'
    );
    $title = "Internship Offer Letter - {$candidateName}";
    $note = "Role: {$role} · Duration: {$duration} · Starts: {$startDate}";
    $stmt->execute([
        $candidateId, $adminId, $title, 'uploads/documents/' . $fileName,
        $fileName, filesize($fullPath), $note,
    ]);

    $stmt = $pdo->prepare('SELECT u.email FROM candidate_profiles c JOIN users u ON u.id = c.user_id WHERE c.id = ?');
    $stmt->execute([$candidateId]);
    $candidateEmail = $stmt->fetchColumn();
    if ($candidateEmail) {
        send_email(
            $candidateEmail,
            'Your Offer Letter - ' . APP_NAME,
            "Congratulations {$candidateName}! Your internship offer letter for the {$role} position is now available. Log in to your " . APP_NAME . " account to view and download it."
        );
    }

    set_flash('success', 'Offer letter generated and sent to ' . $candidateName . '.');
    redirect('admin/documents/index.php');
}

$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Generate Offer Letter - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="../../public/assets/style.css">
</head>
<body>
    <?php include __DIR__ . '/../../includes/admin-header.php'; ?>
    <div class="admin-page">
        <div class="page-header">
            <h1>Generate Offer Letter</h1>
            <a href="index.php" class="btn btn-secondary">Back to Documents</a>
        </div>

        <?php if ($flash): ?>
            <div class="flash <?= $flash['type'] ?>"><?= sanitize($flash['message']) ?></div>
        <?php endif; ?>

        <?php if (!$hasSignature): ?>
            <div class="flash draft" style="background:#fef3c7;color:#92400e;">
                You haven't uploaded a signature yet. <a href="signature.php">Upload one here</a> — the letter will still generate without it.
            </div>
        <?php endif; ?>

        <?php if (empty($candidates)): ?>
            <div class="card empty-state">No candidates registered yet.</div>
        <?php else: ?>
            <div class="card">
                <form method="POST">
                    <label class="form-label">Candidate</label>
                    <select name="candidate_id" required>
                        <option value="">-- Select Candidate --</option>
                        <?php foreach ($candidates as $c): ?>
                            <option value="<?= $c['id'] ?>"><?= sanitize($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>

                    <label class="form-label">Role / Position</label>
                    <input type="text" name="role" placeholder="e.g. Web Development Intern" required>

                    <label class="form-label">Start Date</label>
                    <input type="text" name="start_date" placeholder="e.g. 1st October 2026" required>

                    <label class="form-label">Duration</label>
                    <input type="text" name="duration" placeholder="e.g. 8 Weeks" required>

                    <label class="form-label">Stipend (optional)</label>
                    <input type="text" name="stipend" placeholder="e.g. ₹5,000/month">

                    <button type="submit" class="btn" style="margin-top:10px;">Generate & Send Offer Letter</button>
                </form>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
