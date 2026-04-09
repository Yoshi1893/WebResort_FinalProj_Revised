<?php
require_once 'db.php';

$error = '';
$success = '';

if ($db_error) {
    $error = $db_error;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pdo) {
    $full_name = trim($_POST['name'] ?? $_POST['wizFirst'] ?? $_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? $_POST['wizEmail'] ?? '');
    $event_type = trim($_POST['event'] ?? $_POST['wizEvent'] ?? $_POST['event_type'] ?? 'Not specified');
    $event_date = trim($_POST['preferredDate'] ?? $_POST['wizPreferredDate'] ?? $_POST['event_date'] ?? date('Y-m-d'));
    $message = trim($_POST['message'] ?? $_POST['wizNotes'] ?? $_POST['notes'] ?? '');

    if (empty($full_name) || empty($email) || empty($message)) {
        $error = 'Name, email, and message required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format.';
    } else {
        $stmt = $pdo->prepare("INSERT INTO inquiries (full_name, email, event_type, event_date, message, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        if ($stmt->execute([$full_name, $email, $event_type, $event_date, $message])) {
            $success = 'Inquiry sent successfully! We will contact you soon.';
        } else {
            $error = 'Failed to send inquiry. Try again.';
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && !$pdo && !$error) {
    $error = 'Database unavailable right now. Start MySQL or update db.php before testing inquiry submission.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inquiry - 9 Waves Events Place</title>
    <link
      href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300;1,400&family=Jost:wght@200;300;400;500&display=swap"
      rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>
<body class="auth-page">
    <div class="customer-topbar account-topbar auth-page-topbar">
        <div class="customer-topbar-title"><img src="image/9waves_LOGO.png" alt="9 Waves Logo" class="logo-img">9 Waves <em>Inquiry</em></div>
        <div class="account-topbar-actions">
            <a href="index.php" class="cust-signout">Back to Website</a>
        </div>
    </div>

    <main class="auth-page-shell">
        <section class="auth-shell-card auth-shell-card-wide">
            <div class="auth-shell-copyblock">
                <div class="auth-shell-eyebrow">Standalone Inquiry</div>
                <h1 class="auth-shell-title">Send a direct <em>inquiry</em></h1>
                <p class="auth-shell-copy">The landing-page wizard is the primary frontend flow now. This page remains as a lightweight backend-ready fallback form.</p>
            </div>

            <?php if ($error): ?>
                <div class="auth-feedback auth-feedback-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="auth-feedback auth-feedback-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <form method="POST" class="auth-shell-form">
                <div class="form-row">
                    <div class="form-group">
                        <label for="inquiryFullName">Full Name</label>
                        <input type="text" id="inquiryFullName" name="full_name" placeholder="Maria Santos" value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="inquiryEmail">Email Address</label>
                        <input type="email" id="inquiryEmail" name="email" placeholder="maria.santos@example.com" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="inquiryEventType">Event Type</label>
                        <input type="text" id="inquiryEventType" name="event_type" placeholder="Wedding" value="<?php echo htmlspecialchars($_POST['event_type'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="inquiryEventDate">Preferred Date</label>
                        <input type="date" id="inquiryEventDate" name="event_date" value="<?php echo htmlspecialchars($_POST['event_date'] ?? ''); ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label for="inquiryMessage">Details / Notes</label>
                    <textarea id="inquiryMessage" name="message" placeholder="Share your event details, guest count, and preferred setup." rows="6" required><?php echo htmlspecialchars($_POST['message'] ?? ''); ?></textarea>
                </div>
                <button type="submit" class="auth-btn">Send Inquiry</button>
            </form>
        </section>
    </main>
</body>
</html>
