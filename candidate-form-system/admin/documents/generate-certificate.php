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
    $program     = sanitize($_POST['program']);
    $duration    = sanitize($_POST['duration']);

    $stmt = $pdo->prepare('SELECT name FROM candidate_profiles WHERE id = ?');
    $stmt->execute([$candidateId]);
    $candidateName = $stmt->fetchColumn();

    if (!$candidateName || $program === '' || $duration === '') {
        set_flash('error', 'Please fill in all fields.');
        redirect('admin/documents/generate-certificate.php');
    }

    // ---- Build the PDF (landscape) ----
    $pdf = new FPDF('L', 'mm', 'A4');
    $pdf->AddPage();
    $w = 297; $h = 210;

    // Border
    $pdf->SetDrawColor(232, 163, 61);
    $pdf->SetLineWidth(1.2);
    $pdf->Rect(10, 10, $w - 20, $h - 20);
    $pdf->SetDrawColor(18, 23, 43);
    $pdf->SetLineWidth(0.4);
    $pdf->Rect(14, 14, $w - 28, $h - 28);

    $pdf->SetTextColor(232, 163, 61);
    $pdf->SetFont('Helvetica', 'B', 14);
    $pdf->SetXY(0, 30);
    $pdf->Cell($w, 8, strtoupper(APP_NAME), 0, 1, 'C');

    $pdf->SetTextColor(18, 23, 43);
    $pdf->SetFont('Helvetica', 'B', 28);
    $pdf->SetXY(0, 45);
    $pdf->Cell($w, 12, 'Certificate of Completion', 0, 1, 'C');

    $pdf->SetTextColor(107, 114, 128);
    $pdf->SetFont('Helvetica', '', 12);
    $pdf->SetXY(0, 65);
    $pdf->Cell($w, 8, 'This certificate is proudly presented to', 0, 1, 'C');

    $pdf->SetTextColor(18, 23, 43);
    $pdf->SetFont('Helvetica', 'B', 24);
    $pdf->SetXY(0, 78);
    $pdf->Cell($w, 12, $candidateName, 0, 1, 'C');

    $pdf->SetTextColor(107, 114, 128);
    $pdf->SetFont('Helvetica', '', 12);
    $pdf->SetXY(0, 95);
    $pdf->Cell($w, 7, "for successfully completing the {$duration} Internship Program", 0, 1, 'C');
    $pdf->SetX(0);
    $pdf->Cell($w, 7, "in {$program}, demonstrating consistent dedication and skill.", 0, 1, 'C');

    $pdf->SetTextColor(18, 23, 43);
    $pdf->SetFont('Helvetica', '', 10);
    $pdf->SetXY(0, 115);
    $pdf->Cell($w, 6, 'Issued on ' . date('d F Y'), 0, 1, 'C');

    // Signature block, centered near bottom
    $sigCenterX = $w / 2;
    if ($hasSignature) {
        $pdf->Image($sigPath, $sigCenterX - 17, 145, 34);
    }
    $pdf->SetDrawColor(18, 23, 43);
    $pdf->Line($sigCenterX - 35, 168, $sigCenterX + 35, 168);
    $pdf->SetFont('Helvetica', '', 10);
    $pdf->SetXY(0, 170);
    $pdf->Cell($w, 6, $adminName . ' — ' . APP_NAME, 0, 1, 'C');

    $pdf->SetTextColor(107, 114, 128);
    $pdf->SetFont('Helvetica', '', 7);
    $pdf->SetXY(0, $h - 18);
    $pdf->Cell($w, 6, 'Generated via ' . APP_NAME, 0, 1, 'C');

    $fileName = 'certificate_' . $candidateId . '_' . time() . '.pdf';
    $fullPath = UPLOAD_PATH_DOCUMENTS . $fileName;
    $pdf->Output('F', $fullPath);

    $stmt = $pdo->prepare(
        'INSERT INTO candidate_documents
         (candidate_id, admin_id, document_type, document_title, file_path, original_filename, mime_type, file_size, message_note, document_status)
         VALUES (?, ?, "Internship Completion Certificate", ?, ?, ?, "application/pdf", ?, ?, "AVAILABLE")'
    );
    $title = "Completion Certificate - {$candidateName}";
    $note = "Program: {$program} · Duration: {$duration}";
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
            'Your Completion Certificate - ' . APP_NAME,
            "Congratulations {$candidateName}! Your completion certificate for the {$program} internship is now available. Log in to your " . APP_NAME . " account to view and download it."
        );
    }

    set_flash('success', 'Certificate generated and sent to ' . $candidateName . '.');
    redirect('admin/documents/index.php');
}

$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Generate Certificate - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="../../public/assets/style.css">
</head>
<body>
    <?php include __DIR__ . '/../../includes/admin-header.php'; ?>
    <div class="admin-page">
        <div class="page-header">
            <h1>Generate Completion Certificate</h1>
            <a href="index.php" class="btn btn-secondary">Back to Documents</a>
        </div>

        <?php if ($flash): ?>
            <div class="flash <?= $flash['type'] ?>"><?= sanitize($flash['message']) ?></div>
        <?php endif; ?>

        <?php if (!$hasSignature): ?>
            <div class="flash draft" style="background:#fef3c7;color:#92400e;">
                You haven't uploaded a signature yet. <a href="signature.php">Upload one here</a> — the certificate will still generate without it.
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

                    <label class="form-label">Program</label>
                    <input type="text" name="program" placeholder="e.g. Web Development" required>

                    <label class="form-label">Duration</label>
                    <input type="text" name="duration" placeholder="e.g. 8-week" required>

                    <button type="submit" class="btn" style="margin-top:10px;">Generate & Send Certificate</button>
                </form>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
