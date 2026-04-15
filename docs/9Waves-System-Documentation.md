# 9 Waves Events Place System Documentation

## 1. Project Scope and Architecture

This system is a PHP + MySQL transaction processing web application for event inquiry handling.

Core layers:

1. Presentation Layer (PHP pages + HTML/CSS)
   - Public flow: index page with inquiry wizard
   - Customer flow: account page, inquiry history
   - Admin flow: admin dashboard and management views

2. Client Logic Layer (Vanilla JavaScript)
   - Catalog rendering and dynamic UI behavior
   - Inquiry wizard state management
   - AJAX calls to backend actions

3. Application/Service Layer (PHP action endpoints)
   - Authentication and access control
   - Inquiry creation and status update
   - Admin CRUD for packages/venues/amenities/users

4. Data Layer (MySQL)
   - users, inquiries, packages, venues, amenities, inquiry_amenities, access_log


## 2. End-to-End Functional Flows

## 2.1 Catalog Load Flow (Frontend + Backend)

Goal:
- Load active packages, amenities, and venues and render them in estimator + wizard.

Algorithm:

1. Frontend calls the catalog API.
2. Backend checks available columns with SHOW COLUMNS for schema tolerance.
3. Backend builds dynamic SQL expressions for key/price/capacity/active columns.
4. Backend returns normalized JSON for frontend consumption.
5. Frontend renders:
   - package dropdowns/cards
   - amenity checkboxes
   - venue cards (with data attributes)

Key snippet:

Location: assets/actions/get_catalog_data.php (around lines 24-68)

```php
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
```

Location: assets/js/main.js (around lines 369-434)

```javascript
const packages = Array.isArray(catalog?.packages) ? catalog.packages : [];
const amenities = Array.isArray(catalog?.amenities) ? catalog.amenities : [];
const venues = Array.isArray(catalog?.venues) ? catalog.venues : [];

renderVenueUI(venues);
renderExplorerFromVenues(venues);
bindWizardVenueListeners();
```


## 2.2 Inquiry Wizard Submission Flow

Goal:
- Collect user-selected package, venue, dates, budget, add-ons, and notes.
- Persist inquiry row and amenity links.

Algorithm:

1. User completes wizard steps.
2. Frontend captures selected package and venue IDs from rendered catalog cards.
3. Frontend sends JSON payload to submit endpoint.
4. Backend validates session user.
5. Backend resolves package_id and venue_id (ID-first, fallback by key/name matching).
6. Backend inserts inquiry row with schema-aware field selection.
7. Backend backfills package_id/venue_id if needed.
8. Backend inserts entries to inquiry_amenities bridge table.
9. Backend returns reference + summary response.

Key frontend snippet:

Location: assets/js/main.js (around lines 730-754)

```javascript
body: JSON.stringify({
  event: draft.event,
  venue: draft.venue,
  venueId: draft.venueId,
  packageKey: draft.packageKey,
  packageId: draft.packageId,
  preferredDate: draft.preferredDate,
  backupDate: draft.backupDate,
  packageName: draft.packageName,
  guestCount: parseInt(String(draft.guestCount || '').replace(/[^0-9]/g, ''), 10) || 0,
  budgetRange: draft.budgetRange,
  requestedRooms: 0,
  estimatedTotal: draft.estimate.total,
  notes: $('wizNotes').value.trim(),
  addOns: selectedAddOns,
  contact: { first, last, email, phone }
})
```

Key backend snippet:

Location: assets/actions/submit_inquiry.php (around lines 143-196)

```php
$packageId = $payloadPackageId > 0 ? $payloadPackageId : null;
$venueId = $payloadVenueId > 0 ? $payloadVenueId : null;

if ($packageId === null && $packagesTableExists && $has('package_id')) {
    // fallback resolution by package_key/name/title
}

if ($venueId === null && $venuesTableExists && $has('venue_id') && $venueName !== '') {
    // fallback resolution by venue name/title
}
```

Location: assets/actions/submit_inquiry.php (around lines 245-272)

```php
if ($has('package_id')) $addField('package_id', $packageId);
if ($has('package_key')) $addField('package_key', $packageKey !== '' ? $packageKey : null);
if ($has('venue_id')) $addField('venue_id', $venueId);
if ($has('guest_count')) $addField('guest_count', $guestCount > 0 ? $guestCount : null);
if ($has('budget_range')) $addField('budget_range', $budgetRange !== '' ? $budgetRange : null);
if ($has('amenities')) $addField('amenities', !empty($addOns) ? implode(', ', $addOns) : null);
```

Location: assets/actions/submit_inquiry.php (around lines 280-355)

```php
if ($inquiryId > 0 && is_array($addOns) && count($addOns) && $inquiryAmenitiesTableExists && $amenitiesTableExists) {
    // resolve amenity id by name/title/etc
    // insert into inquiry_amenities(inquiry_id, amenity_id)
}
```


## 2.3 Account History Retrieval Flow

Goal:
- Show customer inquiries with package, venue, status, dates, and amenities.

Algorithm:

1. Account page validates session access.
2. It loads inquiries by user_id (or email fallback).
3. It LEFT JOINs packages and venues.
4. It joins inquiry_amenities + amenities to collect add-ons.
5. If linked amenities are empty, it falls back to inquiries.amenities text.
6. It renders cards and details with status badges.

Key query snippet:

Location: account.php (around lines 159-199)

```php
$joins = [];
if ($hasInquiryCol('venue_id')) {
  $joins[] = 'LEFT JOIN venues v ON v.id = i.venue_id';
}
if ($hasInquiryCol('package_id')) {
  $joins[] = 'LEFT JOIN packages p ON p.id = i.package_id';
}
if ($hasInquiryCol('package_key')) {
  $joins[] = 'LEFT JOIN packages pk ON pk.package_key = i.package_key';
}

// SELECT includes COALESCE(p.name, pk.name) AS package_name
```

Amenities retrieval snippet:

Location: account.php (around lines 300-345)

```php
$amenityStmt = $pdo->prepare("\
  SELECT a.`{$amenityNameCol}`
  FROM inquiry_amenities ia
  JOIN amenities a ON a.`{$amenityIdCol}` = ia.`{$iaAmenityCol}`
  WHERE ia.`{$iaInquiryCol}` = ?
");

// fallback to inquiries.amenities if no linked rows
```


## 2.4 Admin Inquiry Operations Flow

Goal:
- List inquiries in admin and update workflow status.

Algorithm:

1. Admin endpoint checks admin session.
2. It runs dynamic SELECT with user/package/venue joins.
3. It loads amenity links for all inquiry IDs.
4. It returns normalized admin payload.
5. Status update endpoint validates allowed status set and updates inquiry row.

Key listing snippet:

Location: assets/actions/get_admin_inquiries.php (around lines 95-115)

```php
$sql = "
  SELECT
    i.id,
    {$referenceExpr} AS reference,
    {$customerNameExpr} AS customer_name,
    {$eventExpr} AS event_type,
    {$venueNameExpr} AS venue_name,
    {$packageNameExpr} AS package_name,
    {$packageKeyExpr} AS package_key,
    {$preferredExpr} AS preferred_date,
    {$backupExpr} AS backup_date,
    {$totalExpr} AS estimated_total,
    {$statusExpr} AS status
  FROM inquiries i
  " . implode("\n", $joins) . "
";
```

Status update snippet:

Location: assets/actions/update_inquiry_status.php (around lines 13-37)

```php
$allowed = ['submitted', 'review', 'proposal', 'closed'];
if ($inquiryId <= 0 || !in_array($status, $allowed, true)) {
    echo json_encode(['success' => false, 'message' => 'Invalid inquiry status request.']);
    exit;
}

$stmt = $pdo->prepare('UPDATE inquiries SET status = ? WHERE id = ?');
$stmt->execute([$status, $inquiryId]);
```


## 2.5 Admin CRUD: Packages, Venues, Amenities

Goal:
- Allow admins to maintain core catalog entities.

Algorithm common pattern:

1. Validate admin session.
2. Validate payload and IDs.
3. Inspect columns via SHOW COLUMNS (schema tolerance).
4. Build dynamic update fields list.
5. Execute prepared UPDATE/INSERT.

Package save snippet:

Location: assets/actions/save_package.php (around lines 39-85)

```php
if ($has('name')) {
    $fields[] = 'name = ?';
    $params[] = $name;
}
if ($has('base_price')) {
    $fields[] = 'base_price = ?';
    $params[] = $basePrice;
}
// ... guest_capacity, tagline, active, max_private_rooms
```

Venue save snippet:

Location: assets/actions/save_venue.php (around lines 36-67)

```php
if (!$has('guest_capacity') && !$has('guestcapacity') && !$has('capacity')) {
    $pdo->exec('ALTER TABLE venues ADD COLUMN guest_capacity INT(11) NOT NULL DEFAULT 0');
}

if ($has('description')) {
    $fields[] = 'description = ?';
} elseif ($has('note')) {
    $fields[] = 'note = ?';
}
```

Amenity save snippet:

Location: assets/actions/save_amenity.php (around lines 30-66)

```php
if ($id > 0) {
    $sql = 'UPDATE amenities SET ' . implode(', ', $fields) . ' WHERE id = ?';
} else {
    $sql = 'INSERT INTO amenities (' . implode(', ', $insertCols) . ') VALUES (' . implode(', ', $insertVals) . ')';
}
```


## 2.6 Security and Access Control Flow

Goal:
- Protect customer/admin pages and block revoked/deleted/archived users.

Algorithm:

1. Check if session user exists.
2. Fetch user status and role from DB.
3. If revoked/archived/deleted, terminate session and redirect to login with reason.
4. Admin endpoints call checkAdminAccess, which wraps user check then role check.

Access guard snippet:

Location: includes/auth.php (around lines 4-38)

```php
function checkUserAccess(PDO $pdo): void {
    if (empty($_SESSION['user_id'])) {
        header('Location: login.php?reason=not_logged_in');
        exit;
    }

    $stmt = $pdo->prepare("SELECT status, role, archived FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);

    // revoked/deleted/archived handling
}
```

Login gate snippet:

Location: login.php (around lines 24-55)

```php
if ($user && password_verify($password, $user['password'])) {
    $status = strtolower((string) ($user['status'] ?? 'active'));
    $isArchived = (int) ($user['archived'] ?? 0) === 1;

    if ($status === 'revoked' || $isArchived) {
        $error = 'Your account access has been revoked. Please contact support.';
    } elseif ($status === 'deleted') {
        $error = 'This account has been deleted.';
    } else {
        // set session and redirect by role
    }
}
```


## 2.7 Account Deletion and Access Revocation Flow

Self-delete snippet:

Location: assets/actions/delete_account.php (around lines 14-23)

```php
$stmt = $pdo->prepare("UPDATE users SET status = 'deleted', archived = 1 WHERE id = ?");
$stmt->execute([$user_id]);

$stmt = $pdo->prepare(
    "INSERT INTO access_log (user_id, action, reason, actioned_by) VALUES (?, 'deleted', 'User self-deleted account', NULL)"
);
```

Admin revoke/restore snippet:

Location: assets/actions/revoke_user.php (around lines 38-51)

```php
$new_status = $action === 'revoke' ? 'revoked' : 'active';
$update = $pdo->prepare('UPDATE users SET status = ? WHERE id = ?');
$update->execute([$new_status, $target_id]);

$log = $pdo->prepare('INSERT INTO access_log (user_id, action, reason, actioned_by) VALUES (?, ?, ?, ?)');
```


## 3. SQL Join Map (Important)

1. inquiries -> users
   - i.user_id = u.id (or i.email = u.email fallback)

2. inquiries -> packages
   - i.package_id = p.id
   - i.package_key = pk.package_key (fallback path)

3. inquiries -> venues
   - i.venue_id = v.id

4. inquiry_amenities bridge
   - ia.inquiry_id = i.id
   - ia.amenity_id = a.id

5. access logging
   - access_log.user_id and actioned_by reference users


## 4. Known Implementation Decisions

1. Schema-tolerant coding
   - Many endpoints inspect table columns before building SQL.
   - This avoids total failure if local schema is slightly different.

2. ID-first for inquiry save
   - Frontend now sends packageId and venueId explicitly.
   - Backend still has fallback matching by key/name for resilience.

3. Amenities dual persistence
   - Primary: normalized inquiry_amenities links.
   - Secondary fallback: inquiries.amenities text for display continuity.


## 5. Complete Request Sequence (Algorithmic Summary)

1. User opens index page.
2. Frontend fetches catalog.
3. User selects venue/package/add-ons and dates.
4. Frontend sends payload with explicit IDs and summary fields.
5. Backend validates session and resolves missing IDs.
6. Backend inserts inquiry row.
7. Backend inserts amenity bridge rows.
8. Frontend redirects to success page.
9. Account page retrieves inquiries with joins and displays status/summary.
10. Admin page retrieves all inquiries with joins and can update status.


## 6. Recommended Validation Checklist

1. Submit inquiry with each package (ripple/crest/sovereign).
2. Submit inquiry with each venue.
3. Submit inquiry with 0 amenities and with multiple amenities.
4. Verify inquiries row fields:
   - package_id
   - package_key
   - venue_id
   - guest_count
   - budget_range
5. Verify inquiry_amenities gets rows.
6. Verify account and admin pages show package/venue/amenities.
7. Verify revoke/restore and deleted-account login blocks.


## 7. File Index for Key Logic

1. assets/js/main.js
   - Wizard state, payload construction, catalog rendering

2. assets/actions/submit_inquiry.php
   - Inquiry insert transaction, ID resolution, amenities bridge inserts

3. account.php
   - Customer inquiry history query and display logic

4. assets/actions/get_catalog_data.php
   - Catalog API for packages/venues/amenities

5. assets/actions/get_admin_inquiries.php
   - Admin inquiry listing with joins

6. includes/auth.php
   - Session access guard + role enforcement

7. assets/actions/update_inquiry_status.php
   - Inquiry workflow state transitions

8. assets/actions/save_package.php
9. assets/actions/save_venue.php
10. assets/actions/save_amenity.php
11. assets/actions/revoke_user.php
12. assets/actions/delete_account.php
