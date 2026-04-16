<?php
session_start();
require_once '../../db.php';
require_once '../../includes/auth.php';

if ($pdo) checkUserAccess($pdo);

header('Content-Type: application/json');

$user_id = (int) ($_SESSION['user_id'] ?? 0);

if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'Not logged in.']);
    exit;
}

if (!$pdo) {
    echo json_encode(['success' => false, 'message' => 'Database unavailable.']);
    exit;
}

$stmt = $pdo->prepare("UPDATE users SET status = 'revoked' WHERE id = ?");
$stmt->execute([$user_id]);

try {
    $stmt = $pdo->prepare(
        "INSERT INTO access_log (user_id, action, reason, actioned_by) VALUES (?, 'revoked', 'User self-requested account deactivation', NULL)"
    );
    $stmt->execute([$user_id]);
} catch (Throwable $e) {
    // access_log is optional in some local schemas.
}

session_destroy();

echo json_encode(['success' => true, 'redirect' => 'login.php?reason=revoked']);