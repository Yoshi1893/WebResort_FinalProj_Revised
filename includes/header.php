<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

require_once __DIR__ . '/../db.php';

$navUser = null;
$navAvatarText = 'GU';

function resolveProfileImageColumn(PDO $pdo): ?string {
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

if ($pdo && !empty($_SESSION['user_id'])) {
  $profileImageColumn = resolveProfileImageColumn($pdo);
  $imageSelect = $profileImageColumn ? ($profileImageColumn . ' AS profile_image') : 'NULL AS profile_image';
  $stmt = $pdo->prepare("SELECT id, first_name, last_name, role, status, $imageSelect FROM users WHERE id = ? LIMIT 1");
  $stmt->execute([$_SESSION['user_id']]);
  $navUser = $stmt->fetch();

  if ($navUser && $navUser['status'] !== 'deleted') {
    $firstInitial = strtoupper(substr((string) ($navUser['first_name'] ?? ''), 0, 1));
    $lastInitial = strtoupper(substr((string) ($navUser['last_name'] ?? ''), 0, 1));
    $navAvatarText = trim($firstInitial . $lastInitial) ?: 'GU';
    $_SESSION['user_role'] = $navUser['role'] ?? 'customer';
  } else {
    $navUser = null;
  }
}

$appSessionUser = null;
if ($navUser) {
  $phone = $_SESSION['user_phone'] ?? '';
  if ($phone === '' && $pdo) {
    $phoneStmt = $pdo->prepare("SELECT phone, email FROM users WHERE id = ? LIMIT 1");
    $phoneStmt->execute([$navUser['id']]);
    $profileRow = $phoneStmt->fetch();
    if ($profileRow) {
      $phone = (string) ($profileRow['phone'] ?? '');
      $_SESSION['user_email'] = (string) ($profileRow['email'] ?? ($_SESSION['user_email'] ?? ''));
      $_SESSION['user_phone'] = $phone;
    }
  }

  $appSessionUser = [
    'isLoggedIn' => true,
    'id' => (int) $navUser['id'],
    'firstName' => (string) ($navUser['first_name'] ?? ''),
    'lastName' => (string) ($navUser['last_name'] ?? ''),
    'email' => (string) ($_SESSION['user_email'] ?? ''),
    'phone' => (string) $phone,
    'role' => (string) ($navUser['role'] ?? 'customer'),
  ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>9 Waves Events Place</title>
  <link
    href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300;1,400&family=Jost:wght@200;300;400;500&display=swap"
    rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
  <link rel="stylesheet" type="text/css" href="https://npmcdn.com/flatpickr/dist/themes/airbnb.css">
  <link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="confirm-overlay" id="confirmOverlay">
  <div class="confirm-modal">
    <div class="confirm-brand">Action Required</div>
    <h2 class="confirm-title" id="confirmTitle">Confirm Action</h2>
    <p class="confirm-msg" id="confirmMsg">Are you sure you want to proceed?</p>
    <div class="confirm-actions">
      <button class="confirm-btn confirm-no" id="confirmNo">Keep Inquiry</button>
      <button class="confirm-btn confirm-yes" id="confirmYes">Yes, Continue</button>
    </div>
  </div>
</div>

<nav id="navbar">
  <a href="#" class="nav-logo"><img src="image/9waves_LOGO.png" alt="9 Waves Logo" class="logo-img"><span>9 Waves Events Place</span></a>
  <ul class="nav-links" id="navLinks">
    <li><a href="#about">About</a></li>
    <li><a href="#venues">Venues</a></li>
    <li><a href="#packages">Packages</a></li>
    <li><a href="#gallery">Gallery</a></li>
    <li><a href="#contact">Inquiry</a></li>
    <?php if ($navUser): ?>
      <?php if (($navUser['role'] ?? '') === 'admin'): ?>
        <li><a href="admin.php">Admin</a></li>
      <?php endif; ?>
      <li>
        <a href="account.php" class="nav-settings-link" title="Profile & Settings">
          <span class="nav-settings-avatar" data-initials="<?php echo htmlspecialchars($navAvatarText); ?>">
            <?php if (!empty($navUser['profile_image'])): ?>
              <img src="<?php echo htmlspecialchars($navUser['profile_image']); ?>" alt="Profile Picture" onerror="this.style.display='none'; this.parentElement.classList.add('show-initials');">
            <?php else: ?>
              <span class="nav-settings-fallback" aria-hidden="true"><?php echo htmlspecialchars($navAvatarText); ?></span>
            <?php endif; ?>
          </span>
          <span>Profile</span>
        </a>
      </li>
      <li><a href="logout.php">Logout</a></li>
    <?php else: ?>
      <li><a href="login.php">Login</a></li>
      <li><a href="register.php" class="nav-cta">Register</a></li>
    <?php endif; ?>
  </ul>
  <div class="hamburger" id="hamburger"><span></span><span></span><span></span></div>
</nav>

<div class="mobile-menu" id="mobileMenu">
  <button class="mobile-close" id="mobileClose">X</button>
  <a href="#about" onclick="closeMobile()">About</a>
  <a href="#venues" onclick="closeMobile()">Venues</a>
  <a href="#packages" onclick="closeMobile()">Packages</a>
  <a href="#gallery" onclick="closeMobile()">Gallery</a>
  <a href="#contact" onclick="closeMobile()">Start Inquiry</a>
  <?php if ($navUser): ?>
    <?php if (($navUser['role'] ?? '') === 'admin'): ?>
      <a href="admin.php">Admin</a>
    <?php endif; ?>
    <a href="account.php">Profile</a>
    <a href="logout.php">Logout</a>
  <?php else: ?>
    <a href="login.php">Login</a>
    <a href="register.php">Register</a>
  <?php endif; ?>
</div>

<script>
  window.AppSessionUser = <?php echo json_encode($appSessionUser, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
</script>
