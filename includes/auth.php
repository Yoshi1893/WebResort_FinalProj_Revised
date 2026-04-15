<?php
if (session_status() === PHP_SESSION_NONE) session_start();

function checkUserAccess(PDO $pdo): void {
    if (empty($_SESSION['user_id'])) {
        header('Location: login.php?reason=not_logged_in');
        exit;
    }

    $stmt = $pdo->prepare("SELECT status, role, archived FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    if (!$user) {
        session_destroy();
        header('Location: login.php?reason=not_found');
        exit;
    }

    $status = strtolower((string) ($user['status'] ?? 'active'));
    $isArchived = (int) ($user['archived'] ?? 0) === 1;

    if ($status === 'revoked' || $isArchived) {
        session_destroy();
        header('Location: login.php?reason=revoked');
        exit;
    }

    if ($status === 'deleted') {
        session_destroy();
        header('Location: login.php?reason=deleted');
        exit;
    }

    $_SESSION['user_role'] = $user['role'];
}

function checkAdminAccess(PDO $pdo): void {
    checkUserAccess($pdo);
    if ($_SESSION['user_role'] !== 'admin') {
        header('Location: account.php?reason=unauthorized');
        exit;
    }
}