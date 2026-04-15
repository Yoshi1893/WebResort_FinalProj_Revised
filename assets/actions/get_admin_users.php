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

try {
    $stmt = $pdo->query(
        "SELECT id, first_name, last_name, email, phone, role, status, archived, created_at
         FROM users
         ORDER BY created_at DESC, id DESC"
    );
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'users' => $users]);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => 'Failed to load users: ' . $e->getMessage()]);
}
