<?php
require_once '../../db.php';

header('Content-Type: application/json');

if (!$pdo) {
    echo json_encode(['success' => false, 'message' => 'Database unavailable.']);
    exit;
}

try {
    $inquiryCols = [];
    $inqColStmt = $pdo->query('SHOW COLUMNS FROM inquiries');
    foreach ($inqColStmt->fetchAll(PDO::FETCH_ASSOC) as $col) {
        $inquiryCols[] = strtolower((string) ($col['Field'] ?? ''));
    }
    $inqHas = fn(string $name): bool => in_array(strtolower($name), $inquiryCols, true);

    $packageCols = [];
    $pkgColStmt = $pdo->query('SHOW COLUMNS FROM packages');
    foreach ($pkgColStmt->fetchAll(PDO::FETCH_ASSOC) as $col) {
        $packageCols[] = strtolower((string) ($col['Field'] ?? ''));
    }
    $pkgHas = fn(string $name): bool => in_array(strtolower($name), $packageCols, true);

    $amenityCols = [];
    $amColStmt = $pdo->query('SHOW COLUMNS FROM amenities');
    foreach ($amColStmt->fetchAll(PDO::FETCH_ASSOC) as $col) {
        $amenityCols[] = strtolower((string) ($col['Field'] ?? ''));
    }
    $amHas = fn(string $name): bool => in_array(strtolower($name), $amenityCols, true);

    $pkgKeyExpr = $pkgHas('key') ? '`key`' : 'LOWER(REPLACE(name, " ", "-"))';
    $pkgBaseExpr = $pkgHas('base_price') ? 'base_price' : ($pkgHas('baseprice') ? 'basePrice' : '0');
    $pkgCapExpr = $pkgHas('guest_capacity') ? 'guest_capacity' : ($pkgHas('guestcapacity') ? 'guestCapacity' : '0');
    $pkgActiveExpr = $pkgHas('active') ? 'active' : ($pkgHas('is_active') ? 'is_active' : '1');
    $pkgRoomsExpr = $pkgHas('max_private_rooms') ? 'max_private_rooms' : ($pkgHas('maxprivaterooms') ? 'maxPrivateRooms' : '0');

    $amActiveExpr = $amHas('active') ? 'active' : ($amHas('is_active') ? 'is_active' : '1');

    $venueCols = [];
    $venueColStmt = $pdo->query('SHOW COLUMNS FROM venues');
    foreach ($venueColStmt->fetchAll(PDO::FETCH_ASSOC) as $col) {
        $venueCols[] = strtolower((string) ($col['Field'] ?? ''));
    }
    $venueHas = fn(string $name): bool => in_array(strtolower($name), $venueCols, true);

    $venueDescExpr = $venueHas('description') ? 'description' : ($venueHas('note') ? 'note' : "''");
    $venueCapExpr = $venueHas('guest_capacity')
        ? 'guest_capacity'
        : ($venueHas('guestcapacity') ? 'guestCapacity' : ($venueHas('capacity') ? 'capacity' : '0'));
    $venueActiveExpr = $venueHas('active') ? 'active' : ($venueHas('is_active') ? 'is_active' : '1');

    $packages = $pdo->query(
        "SELECT id, {$pkgKeyExpr} AS package_key, name, {$pkgBaseExpr} AS base_price, {$pkgCapExpr} AS guest_capacity, {$pkgActiveExpr} AS active, {$pkgRoomsExpr} AS max_private_rooms
         FROM packages
         WHERE {$pkgActiveExpr} = 1
         ORDER BY id ASC"
    )->fetchAll(PDO::FETCH_ASSOC);

    $amenities = $pdo->query(
        "SELECT id, name, price, {$amActiveExpr} AS active
         FROM amenities
         WHERE {$amActiveExpr} = 1
         ORDER BY id ASC"
    )->fetchAll(PDO::FETCH_ASSOC);

    $venues = $pdo->query(
        "SELECT id, name, {$venueDescExpr} AS description, {$venueCapExpr} AS guest_capacity, {$venueActiveExpr} AS active
         FROM venues
         ORDER BY id ASC"
    )->fetchAll(PDO::FETCH_ASSOC);

    $packageData = [];
    foreach ($packages as $pkg) {
        $key = (string) ($pkg['package_key'] ?? '');
        if ($key === '') continue;
        $packageData[$key] = [
            'name' => (string) ($pkg['name'] ?? ''),
            'base' => (float) ($pkg['base_price'] ?? 0),
            'pax' => (int) ($pkg['guest_capacity'] ?? 0),
            'extra' => 0,
            'roomLimit' => (int) ($pkg['max_private_rooms'] ?? 0),
            'active' => true,
        ];
    }

    $normalizedPackages = array_map(static function (array $pkg): array {
        return [
            'id' => (int) ($pkg['id'] ?? 0),
            'key' => (string) ($pkg['package_key'] ?? ''),
            'name' => (string) ($pkg['name'] ?? ''),
            'basePrice' => (float) ($pkg['base_price'] ?? 0),
            'guestCapacity' => (int) ($pkg['guest_capacity'] ?? 0),
            'tagline' => (string) ($pkg['tagline'] ?? ''),
            'maxPrivateRooms' => (int) ($pkg['max_private_rooms'] ?? 0),
        ];
    }, $packages);

    $normalizedAmenities = array_map(static function (array $am): array {
        return [
            'id' => (int) ($am['id'] ?? 0),
            'name' => (string) ($am['name'] ?? ''),
            'price' => (float) ($am['price'] ?? 0),
        ];
    }, $amenities);

    $normalizedVenues = array_map(static function (array $venue): array {
        return [
            'id' => (int) ($venue['id'] ?? 0),
            'name' => (string) ($venue['name'] ?? ''),
            'description' => (string) ($venue['description'] ?? ''),
            'guestCapacity' => (int) ($venue['guest_capacity'] ?? 0),
        ];
    }, $venues);

    $unavailablePreferredDates = [];
    if ($inqHas('preferred_date')) {
        $rows = $pdo->query("SELECT DISTINCT DATE(preferred_date) AS reserved_date FROM inquiries WHERE preferred_date IS NOT NULL")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $row) {
            $value = trim((string) ($row['reserved_date'] ?? ''));
            if ($value !== '') $unavailablePreferredDates[] = $value;
        }
    } elseif ($inqHas('event_date')) {
        $rows = $pdo->query("SELECT DISTINCT DATE(event_date) AS reserved_date FROM inquiries WHERE event_date IS NOT NULL")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $row) {
            $value = trim((string) ($row['reserved_date'] ?? ''));
            if ($value !== '') $unavailablePreferredDates[] = $value;
        }
    }
    $unavailablePreferredDates = array_values(array_unique($unavailablePreferredDates));

    echo json_encode([
        'success' => true,
        'packages' => $normalizedPackages,
        'amenities' => $normalizedAmenities,
        'venues' => $normalizedVenues,
        'packageData' => $packageData,
        'unavailablePreferredDates' => $unavailablePreferredDates,
    ]);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => 'Failed to load catalog: ' . $e->getMessage()]);
}
