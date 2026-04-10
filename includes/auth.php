<?php
if (session_status() === PHP_SESSION_NONE) session_start();

function checkUserAccess(PDO $pdo): void {
    if (empty($_SESSION['user_id'])) {
        header('Location: login.php?reason=not_logged_in');
        exit;
    }

    $stmt = $pdo->prepare("SELECT status, role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    if (!$user) {
        session_destroy();
        header('Location: login.php?reason=not_found');
        exit;
    }

    if ($user['status'] === 'revoked') {
        session_destroy();
        header('Location: login.php?reason=revoked');
        exit;
    }

    if ($user['status'] === 'deleted') {
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