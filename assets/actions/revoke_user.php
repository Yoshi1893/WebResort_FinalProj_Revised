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

$target_id = (int) ($_POST['user_id'] ?? 0);
$action = trim((string) ($_POST['action'] ?? ''));
$reason = trim((string) ($_POST['reason'] ?? 'No reason provided'));
$admin_id = (int) ($_SESSION['user_id'] ?? 0);

if ($target_id <= 0 || !in_array($action, ['revoke', 'restore'], true)) {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit;
}

if ($target_id === $admin_id) {
    echo json_encode(['success' => false, 'message' => 'You cannot revoke your own account.']);
    exit;
}

$stmt = $pdo->prepare('SELECT role FROM users WHERE id = ? LIMIT 1');
$stmt->execute([$target_id]);
$target = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$target) {
    echo json_encode(['success' => false, 'message' => 'User not found.']);
    exit;
}

if (($target['role'] ?? '') === 'admin') {
    echo json_encode(['success' => false, 'message' => 'Cannot modify an admin account.']);
    exit;
}

$new_status = $action === 'revoke' ? 'revoked' : 'active';
$log_action = $action === 'revoke' ? 'revoked' : 'granted';

$update = $pdo->prepare('UPDATE users SET status = ? WHERE id = ?');
$update->execute([$new_status, $target_id]);

try {
    $log = $pdo->prepare('INSERT INTO access_log (user_id, action, reason, actioned_by) VALUES (?, ?, ?, ?)');
    $log->execute([$target_id, $log_action, $reason, $admin_id]);
} catch (Throwable $e) {
    // Optional log table in some local setups.
}

echo json_encode(['success' => true, 'new_status' => $new_status]);