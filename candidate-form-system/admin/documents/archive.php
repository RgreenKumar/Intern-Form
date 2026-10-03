<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

require_admin_login();
$adminId = $_SESSION['user_id'];
$docId   = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare('SELECT id, document_status FROM candidate_documents WHERE id = ? AND admin_id = ?');
$stmt->execute([$docId, $adminId]);
$doc = $stmt->fetch();

if (!$doc) {
    set_flash('error', 'Document not found.');
    redirect('admin/documents/index.php');
}

$newStatus = $doc['document_status'] === 'AVAILABLE' ? 'ARCHIVED' : 'AVAILABLE';
$stmt = $pdo->prepare('UPDATE candidate_documents SET document_status = ? WHERE id = ?');
$stmt->execute([$newStatus, $docId]);

set_flash('success', $newStatus === 'ARCHIVED' ? 'Document archived — hidden from the candidate.' : 'Document restored — visible to the candidate again.');
redirect('admin/documents/index.php');
