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
$name = trim((string) ($_POST['name'] ?? ''));
$description = trim((string) ($_POST['description'] ?? ''));
$guestCapacity = max(0, (int) ($_POST['guestCapacity'] ?? 0));
$active = ((string) ($_POST['active'] ?? 'true')) === 'true' ? 1 : 0;

if ($name === '') {
    echo json_encode(['success' => false, 'message' => 'Venue name is required.']);
    exit;
}
if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Creating new venues is disabled. Edit an existing venue instead.']);
    exit;
}

try {
    $columns = [];
    $colStmt = $pdo->query('SHOW COLUMNS FROM venues');
    foreach ($colStmt->fetchAll(PDO::FETCH_ASSOC) as $col) {
        $columns[] = strtolower((string) ($col['Field'] ?? ''));
    }

    $has = fn(string $name): bool => in_array(strtolower($name), $columns, true);

    if (!$has('guest_capacity') && !$has('guestcapacity') && !$has('capacity')) {
        try {
            $pdo->exec('ALTER TABLE venues ADD COLUMN guest_capacity INT(11) NOT NULL DEFAULT 0');
            $columns[] = 'guest_capacity';
        } catch (Throwable $ignored) {
            // Keep running in schema-tolerant mode if DDL is blocked.
        }
    }

    $fields = [];
    $params = [];

    if ($has('name')) {
        $fields[] = 'name = ?';
        $params[] = $name;
    }
    if ($has('description')) {
        $fields[] = 'description = ?';
        $params[] = $description;
    } elseif ($has('note')) {
        $fields[] = 'note = ?';
        $params[] = $description;
    }
    if ($has('guest_capacity')) {
        $fields[] = 'guest_capacity = ?';
        $params[] = $guestCapacity;
    } elseif ($has('guestcapacity')) {
        $fields[] = 'guestCapacity = ?';
        $params[] = $guestCapacity;
    } elseif ($has('capacity')) {
        $fields[] = 'capacity = ?';
        $params[] = $guestCapacity;
    }
    if ($has('active')) {
        $fields[] = 'active = ?';
        $params[] = $active;
    } elseif ($has('is_active')) {
        $fields[] = 'is_active = ?';
        $params[] = $active;
    }

    if (empty($fields)) {
        echo json_encode(['success' => false, 'message' => 'Venues table has no writable columns.']);
        exit;
    }

    $existsStmt = $pdo->prepare('SELECT id FROM venues WHERE id = ? LIMIT 1');
    $existsStmt->execute([$id]);
    if (!$existsStmt->fetchColumn()) {
        echo json_encode(['success' => false, 'message' => 'Venue not found.']);
        exit;
    }

    $sql = 'UPDATE venues SET ' . implode(', ', $fields) . ' WHERE id = ?';
    $params[] = $id;
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    echo json_encode(['success' => true, 'id' => $id, 'guestCapacity' => $guestCapacity]);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => 'Failed to save venue: ' . $e->getMessage()]);
}
