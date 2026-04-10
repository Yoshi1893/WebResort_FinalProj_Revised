<?php
session_start();
require_once '../db.php';
require_once '../includes/auth.php';

if ($pdo) checkUserAccess($pdo);

header('Content-Type: application/json');

$user_id = (int) ($_SESSION['user_id'] ?? 0);

if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'Not logged in.']);
    exit;
}

$stmt = $pdo->prepare("UPDATE users SET status = 'deleted', archived = 1 WHERE id = ?");
$stmt->execute([$user_id]);

$stmt = $pdo->prepare(
    "INSERT INTO access_log (user_id, action, reason, actioned_by) VALUES (?, 'deleted', 'User self-deleted account', NULL)"
);
$stmt->execute([$user_id]);

session_destroy();

echo json_encode(['success' => true, 'redirect' => 'login.php?reason=deleted']);