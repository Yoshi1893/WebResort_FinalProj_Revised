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
$payloadVenueId = (int) ($payload['venueId'] ?? 0);
$preferredDate = trim((string) ($payload['preferredDate'] ?? ''));
$backupDate = trim((string) ($payload['backupDate'] ?? ''));
$packageName = trim((string) ($payload['packageName'] ?? ''));
$packageKey = trim((string) ($payload['packageKey'] ?? ''));
$payloadPackageId = (int) ($payload['packageId'] ?? 0);
$guestCount = (int) ($payload['guestCount'] ?? 0);
$budgetRange = trim((string) ($payload['budgetRange'] ?? ''));
$requestedRooms = (int) ($payload['requestedRooms'] ?? 0);
$estimatedTotal = (float) ($payload['estimatedTotal'] ?? 0);
$notes = trim((string) ($payload['notes'] ?? ''));
$rawAddOns = $payload['addOns'] ?? [];
$addOns = [];
if (is_array($rawAddOns)) {
    foreach ($rawAddOns as $item) {
        if (is_string($item)) {
            $label = trim($item);
            if ($label !== '') $addOns[] = $label;
            continue;
        }
        if (is_array($item)) {
            $label = trim((string) ($item['name'] ?? $item['label'] ?? $item['value'] ?? ''));
            if ($label !== '') $addOns[] = $label;
        }
    }
} elseif (is_string($rawAddOns)) {
    foreach (explode(',', $rawAddOns) as $part) {
        $label = trim($part);
        if ($label !== '') $addOns[] = $label;
    }
}
$addOns = array_values(array_unique($addOns));

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

$normalizeText = static function (string $value): string {
    $value = strtolower(trim($value));
    if (strpos($value, 'the ') === 0) {
        $value = substr($value, 4);
    }
    return preg_replace('/[^a-z0-9]+/', '', $value) ?? '';
};

$resolveByFlexibleMatch = static function (PDO $pdo, string $table, array $fields, string $needle) use ($normalizeText): ?int {
    $normalizedNeedle = $normalizeText($needle);
    if ($normalizedNeedle === '') return null;

    $selectFields = implode(', ', array_map(static fn($f) => "`$f`", $fields));
    try {
        $stmt = $pdo->query("SELECT id, $selectFields FROM `$table`");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        return null;
    }

    $bestId = null;
    foreach ($rows as $row) {
        $tokens = [];
        foreach ($fields as $field) {
            $raw = (string) ($row[$field] ?? '');
            $normalized = $normalizeText($raw);
            if ($normalized !== '') $tokens[] = $normalized;
        }

        foreach ($tokens as $token) {
            if ($token === $normalizedNeedle) {
                return (int) ($row['id'] ?? 0);
            }
        }
    }

    foreach ($rows as $row) {
        $tokens = [];
        foreach ($fields as $field) {
            $raw = (string) ($row[$field] ?? '');
            $normalized = $normalizeText($raw);
            if ($normalized !== '') $tokens[] = $normalized;
        }

        foreach ($tokens as $token) {
            if (str_contains($token, $normalizedNeedle) || str_contains($normalizedNeedle, $token)) {
                $bestId = (int) ($row['id'] ?? 0);
                break 2;
            }
        }
    }

    return $bestId;
};

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

$packageId = $payloadPackageId > 0 ? $payloadPackageId : null;
$venueId = $payloadVenueId > 0 ? $payloadVenueId : null;

if ($packageId === null && $packagesTableExists && $has('package_id')) {
    $packageColumns = $tableColumns('packages');
    $packageHas = fn(string $name): bool => in_array(strtolower($name), $packageColumns, true);
    $packageIdCol = $packageHas('id') ? 'id' : ($packageHas('package_id') ? 'package_id' : null);

    try {
        if ($packageIdCol && $packageKey !== '' && $packageHas('key')) {
            $stmt = $pdo->prepare("SELECT `{$packageIdCol}` FROM packages WHERE LOWER(`key`) = LOWER(?) LIMIT 1");
            $stmt->execute([$packageKey]);
            $packageId = $stmt->fetchColumn() ?: null;
        }
        if ($packageIdCol && $packageId === null && $packageKey !== '' && $packageHas('package_key')) {
            $stmt = $pdo->prepare("SELECT `{$packageIdCol}` FROM packages WHERE LOWER(package_key) = LOWER(?) LIMIT 1");
            $stmt->execute([$packageKey]);
            $packageId = $stmt->fetchColumn() ?: null;
        }
        if ($packageIdCol && $packageId === null && $packageName !== '' && $packageHas('name')) {
            $stmt = $pdo->prepare("SELECT `{$packageIdCol}` FROM packages WHERE LOWER(name) = LOWER(?) LIMIT 1");
            $stmt->execute([$packageName]);
            $packageId = $stmt->fetchColumn() ?: null;
        }
        if ($packageIdCol && $packageId === null && $packageName !== '' && $packageHas('title')) {
            $stmt = $pdo->prepare("SELECT `{$packageIdCol}` FROM packages WHERE LOWER(title) = LOWER(?) LIMIT 1");
            $stmt->execute([$packageName]);
            $packageId = $stmt->fetchColumn() ?: null;
        }
        if ($packageIdCol && $packageId === null && $packageName !== '' && $packageHas('package_name')) {
            $stmt = $pdo->prepare("SELECT `{$packageIdCol}` FROM packages WHERE LOWER(package_name) = LOWER(?) LIMIT 1");
            $stmt->execute([$packageName]);
            $packageId = $stmt->fetchColumn() ?: null;
        }
        if ($packageId === null) {
            $fields = [];
            if ($packageHas('key')) $fields[] = 'key';
            if ($packageHas('package_key')) $fields[] = 'package_key';
            if ($packageHas('name')) $fields[] = 'name';
            if ($packageHas('title')) $fields[] = 'title';
            if ($packageHas('package_name')) $fields[] = 'package_name';
            $lookupNeedle = $packageKey !== '' ? $packageKey : $packageName;
            if (!empty($fields) && $lookupNeedle !== '') {
                $packageId = $resolveByFlexibleMatch($pdo, 'packages', $fields, $lookupNeedle);
            }
        }
        if ($packageId === null && $packageIdCol) {
            $stmt = $pdo->query("SELECT `{$packageIdCol}` FROM packages ORDER BY `{$packageIdCol}` ASC LIMIT 1");
            $packageId = $stmt->fetchColumn() ?: null;
        }
    } catch (Throwable $e) {
        $packageId = null;
    }
}

if ($venueId === null && $venuesTableExists && $has('venue_id') && $venueName !== '') {
    try {
        $venueColumns = $tableColumns('venues');
        $venueHas = fn(string $name): bool => in_array(strtolower($name), $venueColumns, true);
        $venueIdCol = $venueHas('id') ? 'id' : ($venueHas('venue_id') ? 'venue_id' : null);

        if ($venueIdCol && $venueHas('name')) {
            $stmt = $pdo->prepare("SELECT `{$venueIdCol}` FROM venues WHERE LOWER(TRIM(name)) = LOWER(TRIM(?)) LIMIT 1");
            $stmt->execute([$venueName]);
            $venueId = $stmt->fetchColumn() ?: null;
        }

        if ($venueIdCol && $venueId === null && $venueHas('title')) {
            $stmt = $pdo->prepare("SELECT `{$venueIdCol}` FROM venues WHERE LOWER(title) = LOWER(?) LIMIT 1");
            $stmt->execute([$venueName]);
            $venueId = $stmt->fetchColumn() ?: null;
        }

        if ($venueIdCol && $venueId === null && $venueHas('venue_name')) {
            $stmt = $pdo->prepare("SELECT `{$venueIdCol}` FROM venues WHERE LOWER(venue_name) = LOWER(?) LIMIT 1");
            $stmt->execute([$venueName]);
            $venueId = $stmt->fetchColumn() ?: null;
        }

        if ($venueId === null) {
            $fields = [];
            if ($venueHas('name')) $fields[] = 'name';
            if ($venueHas('title')) $fields[] = 'title';
            if ($venueHas('venue_name')) $fields[] = 'venue_name';
            if (!empty($fields)) {
                $venueId = $resolveByFlexibleMatch($pdo, 'venues', $fields, $venueName);
            }
        }

        if ($venueId === null && $venueIdCol) {
            $stmt = $pdo->query("SELECT `{$venueIdCol}` FROM venues ORDER BY `{$venueIdCol}` ASC LIMIT 1");
            $venueId = $stmt->fetchColumn() ?: null;
        }
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
if ($has('package_key')) $addField('package_key', $packageKey !== '' ? $packageKey : null);
if ($has('venue_id')) $addField('venue_id', $venueId);
if ($has('guest_count')) $addField('guest_count', $guestCount > 0 ? $guestCount : null);
if ($has('budget_range')) $addField('budget_range', $budgetRange !== '' ? $budgetRange : null);
if ($has('package_name')) $addField('package_name', $packageName !== '' ? $packageName : null);
if ($has('venue_name')) $addField('venue_name', $venueName !== '' ? $venueName : null);
if ($has('package')) $addField('package', $packageName !== '' ? $packageName : null);
if ($has('venue')) $addField('venue', $venueName !== '' ? $venueName : null);
if ($has('amenities')) $addField('amenities', !empty($addOns) ? implode(', ', $addOns) : null);
if ($has('requested_rooms')) $addField('requested_rooms', $requestedRooms);
if ($has('estimated_total')) $addField('estimated_total', $estimatedTotal);
if ($has('notes')) {
    // Keep notes as user-entered text only.
    $addField('notes', $notes !== '' ? $notes : null);
}
if ($has('message')) {
    $contextLine = 'Event: ' . $eventType . ' | Venue: ' . ($venueName !== '' ? $venueName : 'N/A') . ' | Package: ' . ($packageName !== '' ? $packageName : 'N/A');
    $message = $notes !== '' ? ($notes . ' | ' . $contextLine) : $contextLine;
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
    if ($inquiryId <= 0 && $has('reference')) {
        $findInquiryId = $pdo->prepare('SELECT id FROM inquiries WHERE reference = ? ORDER BY id DESC LIMIT 1');
        $findInquiryId->execute([$reference]);
        $inquiryId = (int) ($findInquiryId->fetchColumn() ?: 0);
    }

    // Ensure nullable relation columns are backfilled when lookup misses during insert.
    if ($inquiryId > 0 && $packagesTableExists && $has('package_id') && $packageId === null && $packageKey !== '') {
        try {
            $packageColumns = $tableColumns('packages');
            $packageHas = fn(string $name): bool => in_array(strtolower($name), $packageColumns, true);
            $packageIdCol = $packageHas('id') ? 'id' : ($packageHas('package_id') ? 'package_id' : null);
            $packageKeyCol = $packageHas('package_key') ? 'package_key' : ($packageHas('key') ? 'key' : null);
            if ($packageIdCol && $packageKeyCol) {
                $findPkg = $pdo->prepare("SELECT `{$packageIdCol}` FROM packages WHERE LOWER(TRIM(`{$packageKeyCol}`)) = LOWER(TRIM(?)) LIMIT 1");
                $findPkg->execute([$packageKey]);
                $resolvedPackageId = (int) ($findPkg->fetchColumn() ?: 0);
                if ($resolvedPackageId > 0) {
                    $updPkg = $pdo->prepare('UPDATE inquiries SET package_id = ? WHERE id = ? AND (package_id IS NULL OR package_id = 0)');
                    $updPkg->execute([$resolvedPackageId, $inquiryId]);
                    $packageId = $resolvedPackageId;
                }
            }
        } catch (Throwable $ignored) {
        }
    }

    if ($inquiryId > 0 && $venuesTableExists && $has('venue_id') && $venueId === null && $venueName !== '') {
        try {
            $venueColumns = $tableColumns('venues');
            $venueHas = fn(string $name): bool => in_array(strtolower($name), $venueColumns, true);
            $venueIdCol = $venueHas('id') ? 'id' : ($venueHas('venue_id') ? 'venue_id' : null);
            $venueNameCol = $venueHas('name') ? 'name' : ($venueHas('venue_name') ? 'venue_name' : ($venueHas('title') ? 'title' : null));
            if ($venueIdCol && $venueNameCol) {
                $findVenue = $pdo->prepare("SELECT `{$venueIdCol}` FROM venues WHERE LOWER(TRIM(`{$venueNameCol}`)) = LOWER(TRIM(?)) LIMIT 1");
                $findVenue->execute([$venueName]);
                $resolvedVenueId = (int) ($findVenue->fetchColumn() ?: 0);
                if ($resolvedVenueId > 0) {
                    $updVenue = $pdo->prepare('UPDATE inquiries SET venue_id = ? WHERE id = ? AND (venue_id IS NULL OR venue_id = 0)');
                    $updVenue->execute([$resolvedVenueId, $inquiryId]);
                    $venueId = $resolvedVenueId;
                }
            }
        } catch (Throwable $ignored) {
        }
    }

    if ($inquiryId > 0 && is_array($addOns) && count($addOns) && $inquiryAmenitiesTableExists && $amenitiesTableExists) {
        $amenityColumns = $tableColumns('amenities');
        $iaColumns = $tableColumns('inquiry_amenities');
        $amenityHas = fn(string $name): bool => in_array(strtolower($name), $amenityColumns, true);
        $iaHas = fn(string $name): bool => in_array(strtolower($name), $iaColumns, true);

        $amenityFields = [];
        if ($amenityHas('name')) $amenityFields[] = 'name';
        if ($amenityHas('title')) $amenityFields[] = 'title';
        if ($amenityHas('amenity_name')) $amenityFields[] = 'amenity_name';
        if ($amenityHas('label')) $amenityFields[] = 'label';

        $amenityIdColumn = $amenityHas('id') ? 'id' : ($amenityHas('amenity_id') ? 'amenity_id' : null);
        $iaInquiryColumn = $iaHas('inquiry_id') ? 'inquiry_id' : ($iaHas('inquiryid') ? 'inquiryid' : null);
        $iaAmenityColumn = $iaHas('amenity_id') ? 'amenity_id' : ($iaHas('amenityid') ? 'amenityid' : null);

        if (!empty($amenityFields) && $amenityIdColumn && $iaInquiryColumn && $iaAmenityColumn) {
            $insertAmenity = $pdo->prepare("INSERT INTO inquiry_amenities (`{$iaInquiryColumn}`, `{$iaAmenityColumn}`) VALUES (?, ?)");

            foreach ($addOns as $addOn) {
                $label = trim((string) $addOn);
                if ($label === '') continue;
                $amenityId = null;

                if ($amenityHas('name')) {
                    $findAmenity = $pdo->prepare("SELECT `{$amenityIdColumn}` FROM amenities WHERE LOWER(name) = LOWER(?) LIMIT 1");
                    $findAmenity->execute([$label]);
                    $amenityId = $findAmenity->fetchColumn() ?: null;
                }

                if ($amenityId === null && $amenityHas('title')) {
                    $findAmenity = $pdo->prepare("SELECT `{$amenityIdColumn}` FROM amenities WHERE LOWER(title) = LOWER(?) LIMIT 1");
                    $findAmenity->execute([$label]);
                    $amenityId = $findAmenity->fetchColumn() ?: null;
                }

                if ($amenityId === null && $amenityHas('amenity_name')) {
                    $findAmenity = $pdo->prepare("SELECT `{$amenityIdColumn}` FROM amenities WHERE LOWER(amenity_name) = LOWER(?) LIMIT 1");
                    $findAmenity->execute([$label]);
                    $amenityId = $findAmenity->fetchColumn() ?: null;
                }

                if ($amenityId === null && $amenityHas('label')) {
                    $findAmenity = $pdo->prepare("SELECT `{$amenityIdColumn}` FROM amenities WHERE LOWER(label) = LOWER(?) LIMIT 1");
                    $findAmenity->execute([$label]);
                    $amenityId = $findAmenity->fetchColumn() ?: null;
                }

                if ($amenityId === null && !empty($amenityFields)) {
                    $amenityId = $resolveByFlexibleMatch($pdo, 'amenities', $amenityFields, $label);
                }

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
        'venue' => $venueName !== '' ? $venueName : 'Not set',
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
