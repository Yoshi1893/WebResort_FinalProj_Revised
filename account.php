<?php
session_start();
require_once 'db.php';
require_once 'includes/auth.php';

if ($pdo) checkUserAccess($pdo);

function resolveProfileImageColumn(PDO $pdo): ?string
{
  $candidates = ['profile_image', 'profile_picture', 'avatar'];
  $available = [];
  $stmt = $pdo->query("SHOW COLUMNS FROM users");
  foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $column) {
    $available[] = strtolower((string) ($column['Field'] ?? ''));
  }
  foreach ($candidates as $candidate) {
    if (in_array($candidate, $available, true)) {
      return $candidate;
    }
  }
  return null;
}

function extractInquiryLabelFromNotes(?string $notes, string $label): ?string
{
  $text = trim((string) $notes);
  if ($text === '') {
    return null;
  }

  $safeLabel = preg_quote($label, '/');
  if (preg_match('/(?:^|[|\\n\\r])\\s*' . $safeLabel . '\\s*:\\s*([^|\\n\\r]+)/i', $text, $matches)) {
    $value = trim((string) ($matches[1] ?? ''));
    return $value !== '' ? $value : null;
  }

  return null;
}

function stripInquiryContextFromNotes(?string $notes): string
{
  $text = trim((string) $notes);
  if ($text === '') {
    return '';
  }

  $clean = preg_replace('/\s*\|?\s*Event:\s*[^|]*\|\s*Venue:\s*[^|]*\|\s*Package:\s*[^|]*\s*$/i', '', $text);
  return trim((string) ($clean ?? $text));
}

$profileImageColumn = $pdo ? resolveProfileImageColumn($pdo) : null;

// Pull fresh user data from DB using session
$currentUser = null;
$userInquiries = [];
$inquiryActionSuccess = '';
$inquiryActionError = '';

if ($pdo && !empty($_SESSION['user_id'])) {
  // Fetch user profile
  $imageSelect = $profileImageColumn ? ($profileImageColumn . ' AS profile_image') : 'NULL AS profile_image';
  $stmt = $pdo->prepare("
  SELECT id, first_name, last_name, email, phone, role, status, $imageSelect, created_at
        FROM users
        WHERE id = ?
    ");
  $stmt->execute([$_SESSION['user_id']]);
  $currentUser = $stmt->fetch();

  // Allow users to cancel their own active inquiries from account history.
  if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cancel_inquiry') {
    $inquiryId = (int) ($_POST['inquiry_id'] ?? 0);
    if ($inquiryId <= 0) {
      $inquiryActionError = 'Invalid inquiry selected.';
    } else {
      try {
        $colNames = [];
        $colStmt = $pdo->query('SHOW COLUMNS FROM inquiries');
        foreach ($colStmt->fetchAll(PDO::FETCH_ASSOC) as $col) {
          $colNames[] = strtolower((string) ($col['Field'] ?? ''));
        }

        if (!in_array('status', $colNames, true)) {
          $inquiryActionError = 'Inquiry status updates are not available in this schema.';
        } else {
          $whereSql = 'id = ?';
          $params = [$inquiryId];

          if (in_array('user_id', $colNames, true)) {
            $whereSql .= ' AND user_id = ?';
            $params[] = (int) $_SESSION['user_id'];
          } elseif (in_array('email', $colNames, true) && !empty($currentUser['email'])) {
            $whereSql .= ' AND email = ?';
            $params[] = (string) $currentUser['email'];
          }

          $stmt = $pdo->prepare("UPDATE inquiries SET status = 'closed' WHERE {$whereSql} AND status <> 'closed'");
          $stmt->execute($params);

          if ($stmt->rowCount() > 0) {
            $inquiryActionSuccess = 'Inquiry cancelled successfully.';
          } else {
            $inquiryActionError = 'Inquiry not found or already closed.';
          }
        }
      } catch (Throwable $e) {
        $inquiryActionError = 'Failed to cancel inquiry.';
      }
    }
  }

  // Fetch inquiries using schema-aware fallbacks for package/venue labels.
  try {
    $inquiryColumns = [];
    $colStmt = $pdo->query('SHOW COLUMNS FROM inquiries');
    foreach ($colStmt->fetchAll(PDO::FETCH_ASSOC) as $col) {
      $inquiryColumns[] = strtolower((string) ($col['Field'] ?? ''));
    }

    $hasInquiryCol = static function (string $name) use ($inquiryColumns): bool {
      return in_array(strtolower($name), $inquiryColumns, true);
    };

    $hasTable = static function (PDO $pdoRef, string $table): bool {
      try {
        $stmt = $pdoRef->prepare('SHOW TABLES LIKE ?');
        $stmt->execute([$table]);
        return (bool) $stmt->fetchColumn();
      } catch (Throwable $e) {
        return false;
      }
    };

    $tableColumns = static function (PDO $pdoRef, string $table): array {
      $result = [];
      try {
        $stmt = $pdoRef->query('SHOW COLUMNS FROM ' . $table);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $col) {
          $result[] = strtolower((string) ($col['Field'] ?? ''));
        }
      } catch (Throwable $e) {
        return [];
      }
      return $result;
    };

    $referenceExpr = $hasInquiryCol('reference')
      ? 'i.reference'
      : "CONCAT('INQ-', DATE_FORMAT(COALESCE(i.created_at, NOW()), '%Y%m%d'), '-', LPAD(i.id, 5, '0'))";
    $eventExpr = $hasInquiryCol('event_type') ? 'i.event_type' : "'Not specified'";
    $preferredExpr = $hasInquiryCol('preferred_date') ? 'i.preferred_date' : ($hasInquiryCol('event_date') ? 'i.event_date' : 'NULL');
    $backupExpr = $hasInquiryCol('backup_date') ? 'i.backup_date' : 'NULL';
    $roomsExpr = $hasInquiryCol('requested_rooms') ? 'i.requested_rooms' : '0';
    $totalExpr = $hasInquiryCol('estimated_total') ? 'i.estimated_total' : '0';
    $notesExpr = $hasInquiryCol('notes') ? 'i.notes' : ($hasInquiryCol('message') ? 'i.message' : "''");
    $amenitiesExpr = $hasInquiryCol('amenities') ? 'i.amenities' : "''";
    $messageExpr = $hasInquiryCol('message') ? 'i.message' : "''";
    $statusExpr = $hasInquiryCol('status') ? 'i.status' : "'submitted'";
    $createdExpr = $hasInquiryCol('created_at') ? 'i.created_at' : 'NOW()';
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

    $baseSelect = "
        SELECT
          i.id,
          {$referenceExpr} AS reference,
          {$eventExpr} AS event_type,
          {$preferredExpr} AS preferred_date,
          {$backupExpr} AS backup_date,
          {$roomsExpr} AS requested_rooms,
          {$totalExpr} AS estimated_total,
          {$notesExpr} AS notes,
          {$amenitiesExpr} AS amenities_text,
          {$messageExpr} AS message_text,
          {$statusExpr} AS status,
          {$createdExpr} AS created_at,
          v.name AS venue_name,
          COALESCE(p.name, pk.name) AS package_name
        FROM inquiries i
        " . implode("\n", $joins);

    if ($hasInquiryCol('user_id')) {
      $stmt = $pdo->prepare($baseSelect . " WHERE i.user_id = ? ORDER BY {$createdExpr} DESC, i.id DESC");
      $stmt->execute([$_SESSION['user_id']]);
      $userInquiries = $stmt->fetchAll();
    }

    if (empty($userInquiries) && $hasInquiryCol('email') && !empty($currentUser['email'])) {
      $stmt = $pdo->prepare($baseSelect . " WHERE i.email = ? ORDER BY {$createdExpr} DESC, i.id DESC");
      $stmt->execute([(string) $currentUser['email']]);
      $userInquiries = $stmt->fetchAll();
    }
    // echo '<pre>';
    // echo 'Session user_id: ' . $_SESSION['user_id'] . "\n";
    // echo 'Inquiries found: ' . count($userInquiries) . "\n";
    // print_r($userInquiries[0] ?? 'EMPTY');
    // echo '</pre>';
  } catch (Throwable $e) {
    // echo '<pre style="color:red">QUERY ERROR: ' . $e->getMessage() . '</pre>';

    $userInquiries = [];
  }

  // Fallback for simpler inquiry schemas.
  if (empty($userInquiries)) {
    try {
      $inquiryColumns = [];
      $colStmt = $pdo->query("SHOW COLUMNS FROM inquiries");
      foreach ($colStmt->fetchAll(PDO::FETCH_ASSOC) as $col) {
        $inquiryColumns[] = strtolower((string) ($col['Field'] ?? ''));
      }

      $hasInquiryCol = function (string $name) use ($inquiryColumns): bool {
        return in_array(strtolower($name), $inquiryColumns, true);
      };

      $referenceExpr = $hasInquiryCol('reference')
        ? 'reference'
        : "CONCAT('INQ-', DATE_FORMAT(created_at, '%Y%m%d'), '-', LPAD(id, 5, '0'))";
      $eventExpr = $hasInquiryCol('event_type') ? 'event_type' : "'Not specified'";
      $preferredDateExpr = $hasInquiryCol('preferred_date')
        ? 'preferred_date'
        : ($hasInquiryCol('event_date') ? 'event_date' : 'NULL');
      $notesExpr = $hasInquiryCol('notes')
        ? 'notes'
        : ($hasInquiryCol('message') ? 'message' : "''");
      $messageExpr = $hasInquiryCol('message') ? 'message' : "''";
      $statusExpr = $hasInquiryCol('status') ? 'status' : "'submitted'";
      $createdAtExpr = $hasInquiryCol('created_at') ? 'created_at' : 'NOW()';
      $roomsExpr = $hasInquiryCol('requested_rooms') ? 'requested_rooms' : '0';
      $totalExpr = $hasInquiryCol('estimated_total') ? 'estimated_total' : '0';
        $amenitiesExpr = $hasInquiryCol('amenities') ? 'amenities' : "''";

      $baseSelect = "
          SELECT
            id,
            {$referenceExpr} AS reference,
            {$eventExpr} AS event_type,
            {$preferredDateExpr} AS preferred_date,
            NULL AS backup_date,
            {$roomsExpr} AS requested_rooms,
            {$totalExpr} AS estimated_total,
            {$notesExpr} AS notes,
            {$amenitiesExpr} AS amenities_text,
            {$messageExpr} AS message_text,
            {$statusExpr} AS status,
            {$createdAtExpr} AS created_at,
            NULL AS venue_name,
            NULL AS package_name
          FROM inquiries
        ";

      if ($hasInquiryCol('user_id')) {
        $stmt = $pdo->prepare($baseSelect . " WHERE user_id = ? ORDER BY {$createdAtExpr} DESC");
        $stmt->execute([$_SESSION['user_id']]);
        $userInquiries = $stmt->fetchAll();
      }

      if (empty($userInquiries) && $hasInquiryCol('email') && !empty($currentUser['email'])) {
        $stmt = $pdo->prepare($baseSelect . " WHERE email = ? ORDER BY {$createdAtExpr} DESC");
        $stmt->execute([$currentUser['email']]);
        $userInquiries = $stmt->fetchAll();
      }
    } catch (Throwable $e) {
      $userInquiries = [];
    }
  }

  // Amenities are optional and may not exist in all schemas.
  try {
    $hasTable = static function (PDO $pdoRef, string $table): bool {
      try {
        $stmt = $pdoRef->prepare('SHOW TABLES LIKE ?');
        $stmt->execute([$table]);
        return (bool) $stmt->fetchColumn();
      } catch (Throwable $e) {
        return false;
      }
    };

    $tableColumns = static function (PDO $pdoRef, string $table): array {
      $result = [];
      try {
        $stmt = $pdoRef->query('SHOW COLUMNS FROM ' . $table);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $col) {
          $result[] = strtolower((string) ($col['Field'] ?? ''));
        }
      } catch (Throwable $e) {
        return [];
      }
      return $result;
    };

    $pickFirst = static function (array $columns, array $candidates): ?string {
      foreach ($candidates as $candidate) {
        if (in_array(strtolower($candidate), $columns, true)) {
          return $candidate;
        }
      }
      return null;
    };

    $iaExists = $hasTable($pdo, 'inquiry_amenities');
    $amenitiesExists = $hasTable($pdo, 'amenities');
    $iaCols = $iaExists ? $tableColumns($pdo, 'inquiry_amenities') : [];
    $amenityCols = $amenitiesExists ? $tableColumns($pdo, 'amenities') : [];

    $iaInquiryCol = $pickFirst($iaCols, ['inquiry_id', 'inquiryid']);
    $iaAmenityCol = $pickFirst($iaCols, ['amenity_id', 'amenityid']);
    $amenityIdCol = $pickFirst($amenityCols, ['id', 'amenity_id']);
    $amenityNameCol = $pickFirst($amenityCols, ['name', 'title', 'amenity_name', 'label']);

    if ($iaExists && $amenitiesExists && $iaInquiryCol && $iaAmenityCol && $amenityIdCol && $amenityNameCol) {
      $amenityStmt = $pdo->prepare("\
        SELECT a.`{$amenityNameCol}`
        FROM inquiry_amenities ia
        JOIN amenities a ON a.`{$amenityIdCol}` = ia.`{$iaAmenityCol}`
        WHERE ia.`{$iaInquiryCol}` = ?
      ");

      foreach ($userInquiries as &$inq) {
        $amenityStmt->execute([(int) ($inq['id'] ?? 0)]);
        $linkedAmenities = array_values(array_filter(array_map('strval', $amenityStmt->fetchAll(PDO::FETCH_COLUMN))));
        if (!empty($linkedAmenities)) {
          $inq['amenities'] = $linkedAmenities;
        } else {
          $textAmenities = trim((string) ($inq['amenities_text'] ?? ''));
          if ($textAmenities !== '') {
            $inq['amenities'] = array_values(array_filter(array_map('trim', explode(',', $textAmenities))));
          } else {
            $inq['amenities'] = [];
          }
        }
      }
      unset($inq);
    } else {
      foreach ($userInquiries as &$inq) {
        if (!isset($inq['amenities'])) {
          $textAmenities = trim((string) ($inq['amenities_text'] ?? ''));
          $inq['amenities'] = $textAmenities !== ''
            ? array_values(array_filter(array_map('trim', explode(',', $textAmenities))))
            : [];
        }
      }
      unset($inq);
    }
  } catch (Throwable $e) {
    foreach ($userInquiries as &$inq) {
      if (!isset($inq['amenities'])) {
        $inq['amenities'] = [];
      }
    }
    unset($inq);
  }
}

// Handle profile update form submission
$profileSuccess = '';
$profileError   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'remove_profile_photo' && $pdo) {
  if (!$profileImageColumn) {
    $profileError = 'Profile photo column is missing in the users table.';
  } else {
    $oldPath = (string) ($currentUser['profile_image'] ?? '');
    $stmt = $pdo->prepare("UPDATE users SET {$profileImageColumn} = NULL WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $profileSuccess = 'Profile photo removed.';

    if ($oldPath !== '' && strpos($oldPath, 'image/profiles/') === 0) {
      $oldAbsolute = __DIR__ . '/' . $oldPath;
      if (is_file($oldAbsolute)) {
        @unlink($oldAbsolute);
      }
    }
  }

  $imageSelect = $profileImageColumn ? ($profileImageColumn . ' AS profile_image') : 'NULL AS profile_image';
  $stmt = $pdo->prepare("SELECT id, first_name, last_name, email, phone, role, status, $imageSelect, created_at FROM users WHERE id = ?");
  $stmt->execute([$_SESSION['user_id']]);
  $currentUser = $stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile' && $pdo) {
  $first_name = trim($_POST['first_name'] ?? '');
  $last_name  = trim($_POST['last_name']  ?? '');
  $phone      = trim($_POST['phone']      ?? '');
  $uploadedPath = null;

  if (empty($first_name) || empty($last_name)) {
    $profileError = 'First and last name are required.';
  } elseif (!$profileImageColumn) {
    $profileError = 'Profile photo column is missing in the users table.';
  } else {
    // Optional image upload when clicking Save Profile.
    if (!empty($_FILES['profile_image']) && ($_FILES['profile_image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
      if ($_FILES['profile_image']['error'] !== UPLOAD_ERR_OK) {
        $profileError = 'Image upload failed (code ' . (int) $_FILES['profile_image']['error'] . ').';
      } else {
        $tmpPath = $_FILES['profile_image']['tmp_name'];
        $size = (int) $_FILES['profile_image']['size'];
        $imgMeta = @getimagesize($tmpPath);
        $mime = is_array($imgMeta) ? (string) ($imgMeta['mime'] ?? '') : '';
        $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

        if (!in_array($mime, $allowed, true)) {
          $profileError = 'Only JPG, PNG, WEBP, and GIF images are allowed.';
        } elseif ($size > 3 * 1024 * 1024) {
          $profileError = 'Image must be 3MB or smaller.';
        } else {
          $extMap = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
            'image/gif'  => 'gif',
          ];
          $ext = $extMap[$mime] ?? 'jpg';
          $uploadDir = __DIR__ . '/image/profiles';

          if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
            $profileError = 'Failed to create profile image directory.';
          } else {
            $fileName = 'user_' . (int) $_SESSION['user_id'] . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            $absolutePath = $uploadDir . '/' . $fileName;
            $uploadedPath = 'image/profiles/' . $fileName;

            if (!move_uploaded_file($tmpPath, $absolutePath)) {
              $profileError = 'Could not move uploaded file. Please check folder permissions.';
            }
          }
        }
      }
    }

    if ($profileError === '') {
      if ($uploadedPath !== null) {
        $stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, phone = ?, {$profileImageColumn} = ? WHERE id = ?");
        $stmt->execute([$first_name, $last_name, $phone, $uploadedPath, $_SESSION['user_id']]);

        $oldPath = (string) ($currentUser['profile_image'] ?? '');
        if ($oldPath !== '' && strpos($oldPath, 'image/profiles/') === 0 && $oldPath !== $uploadedPath) {
          $oldAbsolute = __DIR__ . '/' . $oldPath;
          if (is_file($oldAbsolute)) {
            @unlink($oldAbsolute);
          }
        }

        $profileSuccess = 'Profile and photo updated successfully.';
      } else {
        $stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, phone = ? WHERE id = ?");
        $stmt->execute([$first_name, $last_name, $phone, $_SESSION['user_id']]);
        $profileSuccess = 'Profile updated successfully.';
      }
      $_SESSION['user_name'] = $first_name . ' ' . $last_name;
    }

    // Refresh currentUser after update
    $imageSelect = $profileImageColumn ? ($profileImageColumn . ' AS profile_image') : 'NULL AS profile_image';
    $stmt = $pdo->prepare("SELECT id, first_name, last_name, email, phone, role, status, $imageSelect, created_at FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $currentUser = $stmt->fetch();
  }
}

// Handle password change form submission
$passwordSuccess = '';
$passwordError   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password' && $pdo) {
  $current  = $_POST['current_password'] ?? '';
  $new      = $_POST['new_password']     ?? '';
  $confirm  = $_POST['confirm_password'] ?? '';

  $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
  $stmt->execute([$_SESSION['user_id']]);
  $row = $stmt->fetch();

  if (!password_verify($current, $row['password'])) {
    $passwordError = 'Current password is incorrect.';
  } elseif (strlen($new) < 6) {
    $passwordError = 'New password must be at least 6 characters.';
  } elseif ($new !== $confirm) {
    $passwordError = 'New passwords do not match.';
  } else {
    $hashed = password_hash($new, PASSWORD_DEFAULT);
    $stmt   = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
    $stmt->execute([$hashed, $_SESSION['user_id']]);
    $passwordSuccess = 'Password updated successfully.';
  }
}

// Status badge helper
function inquiryStatusInfo(string $status): array
{
  $map = [
    'submitted' => ['label' => 'Submitted',     'class' => 'status-submitted'],
    'review'    => ['label' => 'Under Review',   'class' => 'status-review'],
    'proposal'  => ['label' => 'Proposal Sent',  'class' => 'status-proposal'],
    'closed'    => ['label' => 'Closed',         'class' => 'status-closed'],
  ];
  return $map[$status] ?? $map['submitted'];
}

function profileInitials(array $user): string
{
  $first = strtoupper(substr((string) ($user['first_name'] ?? ''), 0, 1));
  $last = strtoupper(substr((string) ($user['last_name'] ?? ''), 0, 1));
  return trim($first . $last) ?: 'U';
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Account - 9 Waves Events Place</title>
  <link
    href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300;1,400&family=Jost:wght@200;300;400;500&display=swap"
    rel="stylesheet">
  <link rel="stylesheet" href="styles.css">
</head>

<body class="account-page">
  <div class="customer-topbar account-topbar">
    <div class="customer-topbar-title">
      <img src="image/9waves_LOGO.png" alt="9 Waves Logo" class="logo-img">9 Waves <em>My Account</em>
    </div>
    <div class="account-topbar-actions">
      <a href="index.php" class="cust-signout">Back to Website</a>
    </div>
  </div>

  <main class="customer-body account-body">
    <section class="panel-welcome account-hero">
      <h2>Hello, <em><?php echo htmlspecialchars($currentUser['first_name'] ?? 'Guest'); ?></em></h2>
      <p>Update your profile, review your inquiry history, and manage your account from one place.</p>
    </section>

    <section class="cust-cards account-grid">

      <!-- PROFILE CARD -->
      <article class="cust-card account-profile-card">
        <div class="cust-card-title">Profile Details</div>
        <div class="cust-card-sub">Customize your contact information</div>

        <div class="account-avatar-wrap">
          <div class="account-avatar-preview">
            <?php if (!empty($currentUser['profile_image'])): ?>
              <img
                src="<?php echo htmlspecialchars($currentUser['profile_image']); ?>"
                alt="Profile Picture"
                onerror="this.style.display='none'; this.closest('.account-avatar-preview').classList.add('show-initials'); this.closest('.account-avatar-preview').setAttribute('data-initials', '<?php echo htmlspecialchars(profileInitials($currentUser ?? [])); ?>');">
            <?php else: ?>
              <?php echo htmlspecialchars(profileInitials($currentUser ?? [])); ?>
            <?php endif; ?>
          </div>
          <div class="account-avatar-controls">
            <?php if (!empty($currentUser['profile_image'])): ?>
              <form method="POST">
                <input type="hidden" name="action" value="remove_profile_photo">
                <button class="btn-outline" type="submit">Remove Photo</button>
              </form>
            <?php endif; ?>
          </div>
        </div>

        <?php if ($profileSuccess): ?>
          <div class="auth-feedback auth-feedback-success"><?php echo htmlspecialchars($profileSuccess); ?></div>
        <?php endif; ?>
        <?php if ($profileError): ?>
          <div class="auth-feedback auth-feedback-error"><?php echo htmlspecialchars($profileError); ?></div>
        <?php endif; ?>

        <form class="account-form" method="POST" enctype="multipart/form-data">
          <input type="hidden" name="action" value="update_profile">
          <div class="form-row">
            <div class="form-group">
              <label for="accountFirstName">First Name</label>
              <input type="text" id="accountFirstName" name="first_name"
                value="<?php echo htmlspecialchars($currentUser['first_name'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
              <label for="accountLastName">Last Name</label>
              <input type="text" id="accountLastName" name="last_name"
                value="<?php echo htmlspecialchars($currentUser['last_name'] ?? ''); ?>" required>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label for="accountEmail">Email Address</label>
              <input type="email" id="accountEmail"
                value="<?php echo htmlspecialchars($currentUser['email'] ?? ''); ?>" readonly>
            </div>
            <div class="form-group">
              <label for="accountPhone">Phone Number</label>
              <input type="tel" id="accountPhone" name="phone"
                value="<?php echo htmlspecialchars($currentUser['phone'] ?? ''); ?>">
            </div>
          </div>
          <div class="form-group">
            <label for="accountProfileImage">Profile Photo (optional)</label>
            <input type="file" id="accountProfileImage" name="profile_image" accept="image/*">
          </div>
          <div class="account-meta">
            <div class="profile-row">
              <span class="profile-label">Role</span>
              <span class="profile-value"><?php echo htmlspecialchars(ucfirst($currentUser['role'] ?? 'Customer')); ?></span>
            </div>
            <div class="profile-row">
              <span class="profile-label">Joined</span>
              <span class="profile-value"><?php echo htmlspecialchars(date('Y-m-d', strtotime($currentUser['created_at'] ?? 'now'))); ?></span>
            </div>
            <div class="profile-row">
              <span class="profile-label">Status</span>
              <span class="profile-value"><?php echo htmlspecialchars(ucfirst($currentUser['status'] ?? 'active')); ?></span>
            </div>
          </div>
          <button class="auth-btn" type="submit">Save Profile</button>
        </form>
      </article>

      <!-- CHANGE PASSWORD CARD -->
      <article class="cust-card">
        <div class="cust-card-title">Change Password</div>
        <div class="cust-card-sub">Update your password securely</div>

        <?php if ($passwordSuccess): ?>
          <div class="auth-feedback auth-feedback-success"><?php echo htmlspecialchars($passwordSuccess); ?></div>
        <?php endif; ?>
        <?php if ($passwordError): ?>
          <div class="auth-feedback auth-feedback-error"><?php echo htmlspecialchars($passwordError); ?></div>
        <?php endif; ?>

        <form class="account-form" method="POST">
          <input type="hidden" name="action" value="change_password">
          <div class="form-group">
            <label for="currentPassword">Current Password</label>
            <input type="password" id="currentPassword" name="current_password"
              placeholder="Enter current password" required>
          </div>
          <div class="form-group">
            <label for="newPassword">New Password</label>
            <input type="password" id="newPassword" name="new_password"
              placeholder="Minimum 6 characters" required>
          </div>
          <div class="form-group">
            <label for="confirmNewPassword">Confirm New Password</label>
            <input type="password" id="confirmNewPassword" name="confirm_password"
              placeholder="Re-enter new password" required>
          </div>
          <button class="auth-btn" type="submit">Update Password</button>
        </form>
      </article>
    </section>

    <!-- INQUIRY HISTORY -->
    <section class="cust-card account-history-card">
      <div class="cust-card-title">Inquiry History</div>
      <div class="cust-card-sub">Your submitted inquiries</div>

      <?php if ($inquiryActionSuccess): ?>
        <div class="auth-feedback auth-feedback-success"><?php echo htmlspecialchars($inquiryActionSuccess); ?></div>
      <?php endif; ?>
      <?php if ($inquiryActionError): ?>
        <div class="auth-feedback auth-feedback-error"><?php echo htmlspecialchars($inquiryActionError); ?></div>
      <?php endif; ?>

      <div class="customer-inquiry-list account-inquiry-list">
        <?php if (empty($userInquiries)): ?>
          <p class="customer-empty-state">No inquiries submitted yet.</p>
        <?php else: ?>
          <?php foreach ($userInquiries as $index => $inq):
            $statusInfo = inquiryStatusInfo($inq['status']);
            $amenityList = !empty($inq['amenities']) ? implode(', ', $inq['amenities']) : 'None';
            $total = 'PHP ' . number_format($inq['estimated_total'], 0, '.', ',');
            $displayNotes = stripInquiryContextFromNotes($inq['notes'] ?? '');
            $fallbackSource = trim((string) (($inq['notes'] ?? '') . ' | ' . ($inq['message_text'] ?? '')));
            $resolvedVenueName = trim((string) ($inq['venue_name'] ?? ''));
            if ($resolvedVenueName === '') {
              $resolvedVenueName = extractInquiryLabelFromNotes($fallbackSource, 'Venue') ?? '';
            }
            $resolvedPackageName = trim((string) ($inq['package_name'] ?? ''));
            if ($resolvedPackageName === '') {
              $resolvedPackageName = extractInquiryLabelFromNotes($fallbackSource, 'Package') ?? '';
            }
          ?>
            <article class="customer-inquiry-item account-inquiry-item">
              <button class="account-inquiry-toggle" type="button"
                onclick="this.classList.toggle('open'); document.getElementById('inquiryDetail<?php echo $index; ?>').classList.toggle('open')">
                <div class="customer-inquiry-header">
                  <strong><?php echo htmlspecialchars($inq['reference']); ?></strong>
                  <span class="status-badge <?php echo $statusInfo['class']; ?>">
                    <?php echo $statusInfo['label']; ?>
                  </span>
                </div>
                <div class="customer-inquiry-meta">
                  <?php echo htmlspecialchars($inq['created_at']); ?> |
                  <?php echo htmlspecialchars($inq['event_type']); ?> |
                  <?php echo htmlspecialchars($resolvedVenueName !== '' ? $resolvedVenueName : 'No venue'); ?>
                </div>
                <div class="customer-inquiry-meta">
                  <?php echo htmlspecialchars($resolvedPackageName !== '' ? $resolvedPackageName : 'No package'); ?> |
                  <?php echo $total; ?>
                </div>
              </button>
              <?php if (($inq['status'] ?? '') !== 'closed'): ?>
                <div class="account-inquiry-actions">
                  <form method="POST" onsubmit="return confirm('Cancel this inquiry?');">
                    <input type="hidden" name="action" value="cancel_inquiry">
                    <input type="hidden" name="inquiry_id" value="<?php echo (int) ($inq['id'] ?? 0); ?>">
                    <button class="btn-outline" type="submit">Cancel Inquiry</button>
                  </form>
                </div>
              <?php endif; ?>
              <div class="account-inquiry-details" id="inquiryDetail<?php echo $index; ?>">
                <div class="summary-item">
                  <label>Preferred Date</label>
                  <span><?php echo htmlspecialchars($inq['preferred_date'] ?? 'Not set'); ?></span>
                </div>
                <div class="summary-item">
                  <label>Backup Date</label>
                  <span><?php echo htmlspecialchars($inq['backup_date'] ?? 'Not set'); ?></span>
                </div>
                <div class="summary-item">
                  <label>Amenities</label>
                  <span><?php echo htmlspecialchars($amenityList); ?></span>
                </div>
                <div class="summary-item">
                  <label>Notes</label>
                  <span><?php echo htmlspecialchars($displayNotes !== '' ? $displayNotes : 'No notes provided.'); ?></span>
                </div>
              </div>
            </article>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </section>

    <!-- DEACTIVATE ACCOUNT -->
    <section class="cust-cta-card account-archive-card">
      <h3>Deactivate This <em>Account</em></h3>
      <p>Deleting your account removes your access immediately. Your inquiries remain preserved for our records.</p>
      <div class="account-archive-actions">
        <button class="panel-btn panel-btn-outline-white" id="deleteAccountBtn" type="button">
          Deactivate My Account
        </button>
      </div>
    </section>
  </main>

  <div class="toast" id="toast"></div>

  <script>
    // Deactivate account — calls backend
    document.getElementById('deleteAccountBtn').addEventListener('click', () => {
      if (!confirm('Are you sure you want to deactivate your account? You will lose access immediately.')) return;
      fetch('assets/actions/delete_account.php', {
          method: 'POST'
        })
        .then(res => res.json())
        .then(data => {
          if (data.success) window.location.href = data.redirect;
          else alert(data.message || 'Failed to deactivate account.');
        })
        .catch(() => alert('Could not reach the server. Please try again.'));
    });
  </script>
</body>

</html>