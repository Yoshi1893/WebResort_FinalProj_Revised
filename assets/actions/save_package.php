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

$id = (int) ($_POST['id'] ?? 0);
$key = trim((string) ($_POST['key'] ?? ''));
$name = trim((string) ($_POST['name'] ?? ''));
$basePrice = (float) ($_POST['basePrice'] ?? 0);
$guestCapacity = (int) ($_POST['guestCapacity'] ?? 0);
$tagline = trim((string) ($_POST['tagline'] ?? ''));
$active = ((string) ($_POST['active'] ?? 'true')) === 'true' ? 1 : 0;
$maxPrivateRooms = (int) ($_POST['maxPrivateRooms'] ?? 0);

if ($name === '') {
    echo json_encode(['success' => false, 'message' => 'Package name is required.']);
    exit;
}
if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Creating new packages is disabled. Edit an existing package instead.']);
    exit;
}
if ($key === '') {
    $key = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $name), '-'));
}

try {
    $columns = [];
    $colStmt = $pdo->query('SHOW COLUMNS FROM packages');
    foreach ($colStmt->fetchAll(PDO::FETCH_ASSOC) as $col) {
        $columns[] = strtolower((string) ($col['Field'] ?? ''));
    }
    $has = fn(string $name): bool => in_array(strtolower($name), $columns, true);

    $fields = [];
    $params = [];

    if ($has('key')) {
        $fields[] = '`key` = ?';
        $params[] = $key;
    }
    if ($has('name')) {
        $fields[] = 'name = ?';
        $params[] = $name;
    }
    if ($has('base_price')) {
        $fields[] = 'base_price = ?';
        $params[] = $basePrice;
    }
    if ($has('guest_capacity')) {
        $fields[] = 'guest_capacity = ?';
        $params[] = $guestCapacity;
    }
    if ($has('tagline')) {
        $fields[] = 'tagline = ?';
        $params[] = $tagline;
    }
    if ($has('active')) {
        $fields[] = 'active = ?';
        $params[] = $active;
    }
    if ($has('max_private_rooms')) {
        $fields[] = 'max_private_rooms = ?';
        $params[] = $maxPrivateRooms;
    }

    if (empty($fields)) {
        echo json_encode(['success' => false, 'message' => 'Packages table has no writable columns.']);
        exit;
    }

    $existsStmt = $pdo->prepare('SELECT id FROM packages WHERE id = ? LIMIT 1');
    $existsStmt->execute([$id]);
    if (!$existsStmt->fetchColumn()) {
        echo json_encode(['success' => false, 'message' => 'Package not found.']);
        exit;
    }

    $sql = 'UPDATE packages SET ' . implode(', ', $fields) . ' WHERE id = ?';
    $params[] = $id;
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    echo json_encode(['success' => true, 'id' => $id]);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => 'Failed to save package: ' . $e->getMessage()]);
}
