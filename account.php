<?php
session_start();
require_once 'db.php';
require_once 'includes/auth.php';

if ($pdo) checkUserAccess($pdo);

// Pull fresh user data from DB using session
$currentUser = null;
$userInquiries = [];

if ($pdo && !empty($_SESSION['user_id'])) {
    // Fetch user profile
    $stmt = $pdo->prepare("
        SELECT id, first_name, last_name, email, phone, role, status, created_at
        FROM users
        WHERE id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $currentUser = $stmt->fetch();

    // Fetch this user's inquiries with venue and package names
    $stmt = $pdo->prepare("
        SELECT
            i.id,
            i.reference,
            i.event_type,
            i.preferred_date,
            i.backup_date,
            i.requested_rooms,
            i.estimated_total,
            i.notes,
            i.status,
            i.created_at,
            v.name AS venue_name,
            p.name AS package_name
        FROM inquiries i
        LEFT JOIN venues   v ON v.id = i.venue_id
        LEFT JOIN packages p ON p.id = i.package_id
        WHERE i.user_id = ?
        ORDER BY i.created_at DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $userInquiries = $stmt->fetchAll();

    // Fetch amenities per inquiry
    $amenityStmt = $pdo->prepare("
        SELECT a.name
        FROM inquiry_amenities ia
        JOIN amenities a ON a.id = ia.amenity_id
        WHERE ia.inquiry_id = ?
    ");
    foreach ($userInquiries as &$inq) {
        $amenityStmt->execute([$inq['id']]);
        $inq['amenities'] = $amenityStmt->fetchAll(PDO::FETCH_COLUMN);
    }
    unset($inq);
}

// Handle profile update form submission
$profileSuccess = '';
$profileError   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile' && $pdo) {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name  = trim($_POST['last_name']  ?? '');
    $phone      = trim($_POST['phone']      ?? '');

    if (empty($first_name) || empty($last_name)) {
        $profileError = 'First and last name are required.';
    } else {
        $stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, phone = ? WHERE id = ?");
        $stmt->execute([$first_name, $last_name, $phone, $_SESSION['user_id']]);
        $_SESSION['user_name'] = $first_name . ' ' . $last_name;
        $profileSuccess = 'Profile updated successfully.';

        // Refresh currentUser after update
        $stmt = $pdo->prepare("SELECT id, first_name, last_name, email, phone, role, status, created_at FROM users WHERE id = ?");
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
function inquiryStatusInfo(string $status): array {
    $map = [
        'submitted' => ['label' => 'Submitted',     'class' => 'status-submitted'],
        'review'    => ['label' => 'Under Review',   'class' => 'status-review'],
        'proposal'  => ['label' => 'Proposal Sent',  'class' => 'status-proposal'],
        'closed'    => ['label' => 'Closed',         'class' => 'status-closed'],
    ];
    return $map[$status] ?? $map['submitted'];
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

        <?php if ($profileSuccess): ?>
          <div class="auth-feedback auth-feedback-success"><?php echo htmlspecialchars($profileSuccess); ?></div>
        <?php endif; ?>
        <?php if ($profileError): ?>
          <div class="auth-feedback auth-feedback-error"><?php echo htmlspecialchars($profileError); ?></div>
        <?php endif; ?>

        <form class="account-form" method="POST">
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

      <div class="customer-inquiry-list account-inquiry-list">
        <?php if (empty($userInquiries)): ?>
          <p class="customer-empty-state">No inquiries submitted yet.</p>
        <?php else: ?>
          <?php foreach ($userInquiries as $index => $inq):
            $statusInfo = inquiryStatusInfo($inq['status']);
            $amenityList = !empty($inq['amenities']) ? implode(', ', $inq['amenities']) : 'None';
            $total = 'PHP ' . number_format($inq['estimated_total'], 0, '.', ',');
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
                <?php echo htmlspecialchars($inq['venue_name'] ?? 'No venue'); ?>
              </div>
              <div class="customer-inquiry-meta">
                <?php echo htmlspecialchars($inq['package_name'] ?? 'No package'); ?> |
                <?php echo (int)$inq['requested_rooms']; ?> room(s) |
                <?php echo $total; ?>
              </div>
            </button>
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
                <span><?php echo htmlspecialchars($inq['notes'] ?? 'No notes provided.'); ?></span>
              </div>
            </div>
          </article>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </section>

    <!-- DELETE ACCOUNT -->
    <section class="cust-cta-card account-archive-card">
      <h3>Delete This <em>Account</em></h3>
      <p>Deleting your account removes your access immediately. Your inquiries remain preserved for our records.</p>
      <div class="account-archive-actions">
        <button class="panel-btn panel-btn-outline-white" id="deleteAccountBtn" type="button">
          Delete My Account
        </button>
      </div>
    </section>
  </main>

  <div class="toast" id="toast"></div>

  <script>
    // Delete account — calls backend
    document.getElementById('deleteAccountBtn').addEventListener('click', () => {
      if (!confirm('Are you sure you want to delete your account? This cannot be undone.')) return;
      fetch('actions/delete_account.php', { method: 'POST' })
        .then(res => res.json())
        .then(data => {
          if (data.success) window.location.href = data.redirect;
          else alert(data.message || 'Failed to delete account.');
        })
        .catch(() => alert('Could not reach the server. Please try again.'));
    });
  </script>
</body>
</html>