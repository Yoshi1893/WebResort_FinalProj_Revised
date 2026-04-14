<?php
session_start();
require_once '../../db.php';
require_once '../../includes/auth.php';

header('Content-Type: application/json');

if (!$pdo) {
    echo json_encode(['success' => false, 'message' => 'Database unavailable.']);
    exit;
}

checkUserAccess($pdo);

$userId = (int) ($_SESSION['user_id'] ?? 0);
if ($userId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Not logged in.']);
    exit;
}

$payload = json_decode(file_get_contents('php://input'), true);
if (!is_array($payload)) {
    echo json_encode(['success' => false, 'message' => 'Invalid inquiry payload.']);
    exit;
}

$eventType = trim((string) ($payload['event'] ?? 'Not specified'));
$venueName = trim((string) ($payload['venue'] ?? ''));
$preferredDate = trim((string) ($payload['preferredDate'] ?? ''));
$backupDate = trim((string) ($payload['backupDate'] ?? ''));
$packageName = trim((string) ($payload['packageName'] ?? ''));
$packageKey = trim((string) ($payload['packageKey'] ?? ''));
$requestedRooms = (int) ($payload['requestedRooms'] ?? 0);
$estimatedTotal = (float) ($payload['estimatedTotal'] ?? 0);
$notes = trim((string) ($payload['notes'] ?? ''));
$addOns = $payload['addOns'] ?? [];

$stmt = $pdo->prepare('SELECT first_name, last_name, email, phone FROM users WHERE id = ? LIMIT 1');
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    echo json_encode(['success' => false, 'message' => 'User not found.']);
    exit;
}

$fullName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
$email = (string) ($user['email'] ?? '');

$columns = [];
$colStmt = $pdo->query('SHOW COLUMNS FROM inquiries');
foreach ($colStmt->fetchAll(PDO::FETCH_ASSOC) as $col) {
    $columns[] = strtolower((string) ($col['Field'] ?? ''));
}

$has = fn(string $name): bool => in_array(strtolower($name), $columns, true);

$hasTable = function (string $table) use ($pdo): bool {
    try {
        $stmt = $pdo->prepare('SHOW TABLES LIKE ?');
        $stmt->execute([$table]);
        return (bool) $stmt->fetchColumn();
    } catch (Throwable $e) {
        return false;
    }
};

$tableColumns = function (string $table) use ($pdo): array {
    $result = [];
    try {
        $stmt = $pdo->query('SHOW COLUMNS FROM ' . $table);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $col) {
            $result[] = strtolower((string) ($col['Field'] ?? ''));
        }
    } catch (Throwable $e) {
        return [];
    }
    return $result;
};

$packagesTableExists = $hasTable('packages');
$venuesTableExists = $hasTable('venues');
$inquiryAmenitiesTableExists = $hasTable('inquiry_amenities');
$amenitiesTableExists = $hasTable('amenities');

$packageId = null;
$venueId = null;

if ($packagesTableExists && $has('package_id')) {
    $packageColumns = $tableColumns('packages');
    $packageHas = fn(string $name): bool => in_array(strtolower($name), $packageColumns, true);

    try {
        if ($packageKey !== '' && $packageHas('key')) {
            $stmt = $pdo->prepare('SELECT id FROM packages WHERE `key` = ? LIMIT 1');
            $stmt->execute([$packageKey]);
            $packageId = $stmt->fetchColumn() ?: null;
        }
        if ($packageId === null && $packageName !== '' && $packageHas('name')) {
            $stmt = $pdo->prepare('SELECT id FROM packages WHERE LOWER(name) = LOWER(?) LIMIT 1');
            $stmt->execute([$packageName]);
            $packageId = $stmt->fetchColumn() ?: null;
        }
    } catch (Throwable $e) {
        $packageId = null;
    }
}

if ($venuesTableExists && $has('venue_id') && $venueName !== '') {
    try {
        $stmt = $pdo->prepare('SELECT id FROM venues WHERE LOWER(name) = LOWER(?) LIMIT 1');
        $stmt->execute([$venueName]);
        $venueId = $stmt->fetchColumn() ?: null;
    } catch (Throwable $e) {
        $venueId = null;
    }
}

$reference = 'INQ-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 5));

$insertFields = [];
$insertValues = [];
$params = [];

$addField = function (string $field, $value) use (&$insertFields, &$insertValues, &$params): void {
    $insertFields[] = $field;
    $insertValues[] = '?';
    $params[] = $value;
};

if ($has('user_id')) $addField('user_id', $userId);
if ($has('reference')) $addField('reference', $reference);
if ($has('full_name')) $addField('full_name', $fullName);
if ($has('email')) $addField('email', $email);
if ($has('event_type')) $addField('event_type', $eventType);
if ($has('event_date')) $addField('event_date', $preferredDate !== '' ? $preferredDate : null);
if ($has('preferred_date')) $addField('preferred_date', $preferredDate !== '' ? $preferredDate : null);
if ($has('backup_date')) $addField('backup_date', $backupDate !== '' ? $backupDate : null);
if ($has('package_id')) $addField('package_id', $packageId);
if ($has('venue_id')) $addField('venue_id', $venueId);
if ($has('requested_rooms')) $addField('requested_rooms', $requestedRooms);
if ($has('estimated_total')) $addField('estimated_total', $estimatedTotal);
if ($has('notes')) $addField('notes', $notes);
if ($has('message')) {
    $message = $notes !== ''
        ? $notes
        : ('Event: ' . $eventType . ' | Venue: ' . ($venueName !== '' ? $venueName : 'N/A') . ' | Package: ' . ($packageName !== '' ? $packageName : 'N/A'));
    $addField('message', $message);
}
if ($has('status')) $addField('status', 'submitted');

if (empty($insertFields)) {
    echo json_encode(['success' => false, 'message' => 'Inquiries table has no compatible columns.']);
    exit;
}

try {
    $pdo->beginTransaction();

    $sql = 'INSERT INTO inquiries (' . implode(', ', $insertFields) . ') VALUES (' . implode(', ', $insertValues) . ')';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    $inquiryId = (int) $pdo->lastInsertId();

    if ($inquiryId > 0 && is_array($addOns) && count($addOns) && $inquiryAmenitiesTableExists && $amenitiesTableExists) {
        $amenityColumns = $tableColumns('amenities');
        $iaColumns = $tableColumns('inquiry_amenities');
        $amenityHas = fn(string $name): bool => in_array(strtolower($name), $amenityColumns, true);
        $iaHas = fn(string $name): bool => in_array(strtolower($name), $iaColumns, true);

        if ($amenityHas('name') && $iaHas('inquiry_id') && $iaHas('amenity_id')) {
            $findAmenity = $pdo->prepare('SELECT id FROM amenities WHERE LOWER(name) = LOWER(?) LIMIT 1');
            $insertAmenity = $pdo->prepare('INSERT INTO inquiry_amenities (inquiry_id, amenity_id) VALUES (?, ?)');

            foreach ($addOns as $addOn) {
                $label = trim((string) $addOn);
                if ($label === '') continue;
                $findAmenity->execute([$label]);
                $amenityId = $findAmenity->fetchColumn();
                if ($amenityId) {
                    $insertAmenity->execute([$inquiryId, (int) $amenityId]);
                }
            }
        }
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'reference' => $reference,
        'package' => $packageName !== '' ? $packageName : 'Not set',
        'rooms' => $requestedRooms > 0 ? (string) $requestedRooms : 'N/A',
        'amenities' => is_array($addOns) && count($addOns) ? implode(', ', $addOns) : 'None',
        'total' => 'PHP ' . number_format($estimatedTotal, 0, '.', ','),
    ]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => 'Failed to save inquiry: ' . $e->getMessage()]);
}
