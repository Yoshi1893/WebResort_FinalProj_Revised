<?php
session_start();
require_once '../db.php';
require_once '../includes/auth.php';

if ($pdo) checkAdminAccess($pdo);

header('Content-Type: application/json');

$target_id = (int) ($_POST['user_id'] ?? 0);
$action    = $_POST['action'] ?? '';
$reason    = trim($_POST['reason'] ?? 'No reason provided');
$admin_id  = (int) ($_SESSION['user_id'] ?? 0);

if (!$target_id || !in_array($action, ['revoke', 'restore'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit;
}

if ($target_id === $admin_id) {
    echo json_encode(['success' => false, 'message' => 'You cannot revoke your own account.']);
    exit;
}

$stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([$target_id]);
$target = $stmt->fetch();

if (!$target || $target['role'] === 'admin') {
    echo json_encode(['success' => false, 'message' => 'Cannot modify an admin account.']);
    exit;
}

$new_status = $action === 'revoke' ? 'revoked' : 'active';
$log_action = $action === 'revoke' ? 'revoked' : 'granted';

$stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ?");
$stmt->execute([$new_status, $target_id]);

$stmt = $pdo->prepare(
    "INSERT INTO access_log (user_id, action, reason, actioned_by) VALUES (?, ?, ?, ?)"
);
$stmt->execute([$target_id, $log_action, $reason, $admin_id]);

try {
    if ($action === 'revoke') {
        $pdo->exec("REVOKE ALL PRIVILEGES ON 9waves_db.* FROM 'app_customer'@'localhost'");
    } else {
        $pdo->exec("GRANT SELECT, INSERT, UPDATE ON 9waves_db.users            TO 'app_customer'@'localhost'");
        $pdo->exec("GRANT SELECT, INSERT          ON 9waves_db.inquiries        TO 'app_customer'@'localhost'");
        $pdo->exec("GRANT SELECT, INSERT          ON 9waves_db.inquiry_amenities TO 'app_customer'@'localhost'");
        $pdo->exec("GRANT SELECT                  ON 9waves_db.packages         TO 'app_customer'@'localhost'");
        $pdo->exec("GRANT SELECT                  ON 9waves_db.amenities        TO 'app_customer'@'localhost'");
        $pdo->exec("GRANT SELECT                  ON 9waves_db.venues           TO 'app_customer'@'localhost'");
        $pdo->exec("GRANT SELECT                  ON 9waves_db.rooms            TO 'app_customer'@'localhost'");
    }
    $pdo->exec("FLUSH PRIVILEGES");
} catch (PDOException $e) {
    // MySQL-level grant/revoke failed but app-level already handled — safe to continue
}

echo json_encode(['success' => true, 'new_status' => $new_status]);