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
    $columns = [];
    $colStmt = $pdo->query('SHOW COLUMNS FROM inquiries');
    foreach ($colStmt->fetchAll(PDO::FETCH_ASSOC) as $col) {
        $columns[] = strtolower((string) ($col['Field'] ?? ''));
    }

    $has = fn(string $name): bool => in_array(strtolower($name), $columns, true);

    $hasTable = function (string $table) use ($pdo): bool {
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?'
        );
        $stmt->execute([$table]);
        return ((int) $stmt->fetchColumn()) > 0;
    };

    $tableColumns = function (string $table) use ($pdo): array {
        $result = [];
        $stmt = $pdo->query('SHOW COLUMNS FROM ' . $table);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $col) {
            $result[] = strtolower((string) ($col['Field'] ?? ''));
        }
        return $result;
    };

    $packagesTableExists = $hasTable('packages');
    $venuesTableExists = $hasTable('venues');
    $iaTableExists = $hasTable('inquiry_amenities');
    $amenitiesTableExists = $hasTable('amenities');
    $usersTableExists = $hasTable('users');

    $packageCols = $packagesTableExists ? $tableColumns('packages') : [];
    $venueCols = $venuesTableExists ? $tableColumns('venues') : [];
    $userCols = $usersTableExists ? $tableColumns('users') : [];
    $pkgHas = fn(string $name): bool => in_array(strtolower($name), $packageCols, true);
    $venueHas = fn(string $name): bool => in_array(strtolower($name), $venueCols, true);
    $userHas = fn(string $name): bool => in_array(strtolower($name), $userCols, true);

    $referenceExpr = $has('reference')
        ? 'i.reference'
        : "CONCAT('INQ-', DATE_FORMAT(COALESCE(i.created_at, NOW()), '%Y%m%d'), '-', LPAD(i.id, 5, '0'))";
    $customerNameExpr = $has('full_name')
        ? 'i.full_name'
        : (($usersTableExists && $userHas('first_name') && $userHas('last_name'))
            ? "CONCAT(COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, ''))"
            : "'Unknown Customer'");
    $emailExpr = $has('email')
        ? 'i.email'
        : (($usersTableExists && $userHas('email')) ? 'u.email' : "'No email'");
    $eventExpr = $has('event_type') ? 'i.event_type' : "'Not specified'";
    $preferredExpr = $has('preferred_date') ? 'i.preferred_date' : ($has('event_date') ? 'i.event_date' : 'NULL');
    $backupExpr = $has('backup_date') ? 'i.backup_date' : 'NULL';
    $roomsExpr = $has('requested_rooms') ? 'i.requested_rooms' : '0';
    $totalExpr = $has('estimated_total') ? 'i.estimated_total' : '0';
    $notesExpr = $has('notes') ? 'i.notes' : ($has('message') ? 'i.message' : "''");
    $statusExpr = $has('status') ? 'i.status' : "'submitted'";
    $createdExpr = $has('created_at') ? 'i.created_at' : 'NOW()';

    $packageNameExpr = ($packagesTableExists && $has('package_id') && $pkgHas('name')) ? 'p.name' : "'No package'";
    $packageKeyExpr = ($packagesTableExists && $has('package_id') && $pkgHas('key')) ? 'p.`key`' : "''";
    $venueNameExpr = ($venuesTableExists && $has('venue_id') && $venueHas('name')) ? 'v.name' : "'No venue'";

    $joins = [];
    if ($usersTableExists && $has('user_id') && $userHas('id')) {
        $joins[] = 'LEFT JOIN users u ON u.id = i.user_id';
    } else if ($usersTableExists && $has('email') && $userHas('email')) {
        $joins[] = 'LEFT JOIN users u ON u.email = i.email';
    }
    if ($packagesTableExists && $has('package_id') && $pkgHas('id')) {
        $joins[] = 'LEFT JOIN packages p ON p.id = i.package_id';
    }
    if ($venuesTableExists && $has('venue_id') && $venueHas('id')) {
        $joins[] = 'LEFT JOIN venues v ON v.id = i.venue_id';
    }

    $sql = "
        SELECT
            i.id,
            {$referenceExpr} AS reference,
            {$customerNameExpr} AS customer_name,
            {$emailExpr} AS customer_email,
            {$eventExpr} AS event_type,
            {$venueNameExpr} AS venue_name,
            {$packageNameExpr} AS package_name,
            {$packageKeyExpr} AS package_key,
            {$preferredExpr} AS preferred_date,
            {$backupExpr} AS backup_date,
            {$roomsExpr} AS requested_rooms,
            {$totalExpr} AS estimated_total,
            {$notesExpr} AS notes,
            {$statusExpr} AS status,
            {$createdExpr} AS created_at
        FROM inquiries i
        " . implode("\n", $joins) . "
        ORDER BY {$createdExpr} DESC, i.id DESC
    ";

    $stmt = $pdo->query($sql);
    $inquiries = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $amenitiesByInquiryId = [];
    if ($iaTableExists && $amenitiesTableExists && count($inquiries) > 0) {
        $amStmt = $pdo->query(
            "SELECT ia.inquiry_id, a.name
             FROM inquiry_amenities ia
             JOIN amenities a ON a.id = ia.amenity_id"
        );
        foreach ($amStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $inqId = (int) ($row['inquiry_id'] ?? 0);
            if ($inqId <= 0) continue;
            if (!isset($amenitiesByInquiryId[$inqId])) {
                $amenitiesByInquiryId[$inqId] = [];
            }
            $amenitiesByInquiryId[$inqId][] = (string) ($row['name'] ?? '');
        }
    }

    foreach ($inquiries as &$row) {
        $id = (int) ($row['id'] ?? 0);
        $row['amenities'] = $amenitiesByInquiryId[$id] ?? [];
    }
    unset($row);

    echo json_encode(['success' => true, 'inquiries' => $inquiries]);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => 'Failed to load inquiries: ' . $e->getMessage()]);
}
