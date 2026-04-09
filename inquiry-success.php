<?php
$reference = trim($_GET['ref'] ?? 'INQ-20260409-00001');
$package = trim($_GET['package'] ?? 'Ripple Pack');
$rooms = trim($_GET['rooms'] ?? '2');
$amenities = trim($_GET['amenities'] ?? 'Extra Event Hour, Flower Wall Backdrop');
$total = trim($_GET['total'] ?? 'PHP 57,000');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Inquiry Submitted - 9 Waves Events Place</title>
  <link
    href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300;1,400&family=Jost:wght@200;300;400;500&display=swap"
    rel="stylesheet">
  <link rel="stylesheet" href="styles.css">
</head>
<body class="auth-page">
  <div class="customer-topbar account-topbar auth-page-topbar">
    <div class="customer-topbar-title"><img src="image/9waves_LOGO.png" alt="9 Waves Logo" class="logo-img">9 Waves <em>Confirmation</em></div>
    <div class="account-topbar-actions">
      <a href="index.php" class="cust-signout">Back to Website</a>
    </div>
  </div>

  <main class="auth-page-shell success-shell">
    <section class="auth-shell-card success-card">
      <div class="auth-shell-eyebrow">Inquiry Submitted</div>
      <h1 class="auth-shell-title">Your inquiry has been <em>received</em></h1>
      <p class="auth-shell-copy">This replaces the old PDF soft quote flow. The screen is designed to act as the confirmation receipt until backend submission is wired fully.</p>

      <div class="success-reference">
        <span>Reference Number</span>
        <strong><?php echo htmlspecialchars($reference); ?></strong>
      </div>

      <div class="success-grid">
        <div class="success-item">
          <span>Selected Package</span>
          <strong><?php echo htmlspecialchars($package); ?></strong>
        </div>
        <div class="success-item">
          <span>Requested Rooms</span>
          <strong><?php echo htmlspecialchars($rooms); ?></strong>
        </div>
        <div class="success-item success-item-wide">
          <span>Selected Amenities</span>
          <strong><?php echo htmlspecialchars($amenities); ?></strong>
        </div>
        <div class="success-item success-item-wide">
          <span>Estimated Total</span>
          <strong><?php echo htmlspecialchars($total); ?></strong>
        </div>
      </div>

      <div class="auth-shell-actions">
        <a href="index.php#inquiry" class="auth-btn auth-link-btn">Start Another Inquiry</a>
        <a href="account.php" class="btn-outline auth-secondary-link">View Account Page</a>
      </div>
    </section>
  </main>
</body>
</html>
