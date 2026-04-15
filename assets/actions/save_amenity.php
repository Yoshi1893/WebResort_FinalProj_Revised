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
$price = (float) ($_POST['price'] ?? 0);
$active = ((string) ($_POST['active'] ?? 'true')) === 'true' ? 1 : 0;

if ($name === '') {
    echo json_encode(['success' => false, 'message' => 'Amenity name is required.']);
    exit;
}

try {
    $columns = [];
    $colStmt = $pdo->query('SHOW COLUMNS FROM amenities');
    foreach ($colStmt->fetchAll(PDO::FETCH_ASSOC) as $col) {
        $columns[] = strtolower((string) ($col['Field'] ?? ''));
    }
    $has = fn(string $name): bool => in_array(strtolower($name), $columns, true);

    $fields = [];
    $params = [];

    if ($has('name')) {
        $fields[] = 'name = ?';
        $params[] = $name;
    }
    if ($has('price')) {
        $fields[] = 'price = ?';
        $params[] = $price;
    }
    if ($has('active')) {
        $fields[] = 'active = ?';
        $params[] = $active;
    }

    if (empty($fields)) {
        echo json_encode(['success' => false, 'message' => 'Amenities table has no writable columns.']);
        exit;
    }

    if ($id > 0) {
        $sql = 'UPDATE amenities SET ' . implode(', ', $fields) . ' WHERE id = ?';
        $params[] = $id;
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        echo json_encode(['success' => true, 'id' => $id]);
        exit;
    }

    $insertCols = [];
    $insertVals = [];
    $insertParams = [];

    if ($has('name')) { $insertCols[] = 'name'; $insertVals[] = '?'; $insertParams[] = $name; }
    if ($has('price')) { $insertCols[] = 'price'; $insertVals[] = '?'; $insertParams[] = $price; }
    if ($has('active')) { $insertCols[] = 'active'; $insertVals[] = '?'; $insertParams[] = $active; }

    $sql = 'INSERT INTO amenities (' . implode(', ', $insertCols) . ') VALUES (' . implode(', ', $insertVals) . ')';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($insertParams);

    echo json_encode(['success' => true, 'id' => (int) $pdo->lastInsertId()]);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => 'Failed to save amenity: ' . $e->getMessage()]);
}
