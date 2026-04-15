<?php
session_start();
require_once '../../db.php';
require_once '../../includes/auth.php';

header('Content-Type: application/json');

if (!$pdo) {
    echo json_encode(['success' => false, 'message' => 'Database unavailable.']);
    exit;
}

checkAdminAccess($pdo);

$inquiryId = (int) ($_POST['inquiry_id'] ?? 0);
$status = trim((string) ($_POST['status'] ?? ''));
$allowed = ['submitted', 'review', 'proposal', 'closed'];

if ($inquiryId <= 0 || !in_array($status, $allowed, true)) {
    echo json_encode(['success' => false, 'message' => 'Invalid inquiry status request.']);
    exit;
}

try {
    $columns = [];
    $colStmt = $pdo->query('SHOW COLUMNS FROM inquiries');
    foreach ($colStmt->fetchAll(PDO::FETCH_ASSOC) as $col) {
        $columns[] = strtolower((string) ($col['Field'] ?? ''));
    }

    if (!in_array('status', $columns, true)) {
        echo json_encode(['success' => false, 'message' => 'Status column is missing in inquiries table.']);
        exit;
    }

    $stmt = $pdo->prepare('UPDATE inquiries SET status = ? WHERE id = ?');
    $stmt->execute([$status, $inquiryId]);

    if ($stmt->rowCount() === 0) {
        echo json_encode(['success' => false, 'message' => 'Inquiry not found or status unchanged.']);
        exit;
    }

    echo json_encode(['success' => true]);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => 'Failed to update inquiry status: ' . $e->getMessage()]);
}
