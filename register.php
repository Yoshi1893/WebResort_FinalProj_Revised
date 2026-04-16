<?php
session_start();
require_once 'db.php';

$error = '';
$success = '';
$hasUsernameColumn = false;

if ($db_error) {
    $error = $db_error;
}

if ($pdo) {
    try {
        $stmt = $pdo->query('SHOW COLUMNS FROM users LIKE "username"');
        $hasUsernameColumn = (bool) $stmt->fetchColumn();
    } catch (Throwable $e) {
        $hasUsernameColumn = false;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pdo) {
    if (!$hasUsernameColumn) {
        $error = 'The users table is missing the username column. Please update the database schema first.';
    } else {
    $first_name = trim($_POST['firstName'] ?? $_POST['regFirst'] ?? '');
    $last_name = trim($_POST['lastName'] ?? $_POST['regLast'] ?? '');
    $username = trim($_POST['username'] ?? $_POST['regUsername'] ?? '');
    $email = trim($_POST['email'] ?? $_POST['regEmail'] ?? '');
    $phone = trim($_POST['phone'] ?? $_POST['regPhone'] ?? '');
    $password = $_POST['password'] ?? $_POST['regPassword'] ?? '';
    $confirm_password = $_POST['confirmPassword'] ?? $_POST['regConfirmPassword'] ?? '';
    $normalized_phone = preg_replace('/\D+/', '', $phone) ?? '';

    if (empty($first_name) || empty($last_name) || empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = 'All fields are required.';
    } elseif (!preg_match('/^[a-zA-Z\s\'-]+$/', $first_name)) {
        $error = 'First name can only contain letters, spaces, apostrophes, and hyphens.';
    } elseif (!preg_match('/^[a-zA-Z\s\'-]+$/', $last_name)) {
        $error = 'Last name can only contain letters, spaces, apostrophes, and hyphens.';
    } elseif (strlen($username) < 3) {
        $error = 'Username must be at least 3 characters.';
    } elseif (!preg_match('/^[a-zA-Z0-9_.-]+$/', $username)) {
        $error = 'Username can only contain letters, numbers, underscores, dots, and hyphens.';
    } elseif ($phone !== '' && !preg_match('/^[0-9]+$/', $normalized_phone)) {
        $error = 'Contact number must contain numbers only.';
    } elseif ($normalized_phone !== '' && strlen($normalized_phone) !== 11) {
        $error = 'Contact number must be exactly 11 digits.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = 'Email already exists.';
        } else {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                $error = 'Username already exists.';
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, username, email, phone, password, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
                if ($stmt->execute([$first_name, $last_name, $username, $email, $normalized_phone !== '' ? $normalized_phone : null, $hashed_password])) {
                    $newUserId = (int) $pdo->lastInsertId();
                    $_SESSION['user_id'] = $newUserId;
                    $_SESSION['user_name'] = $first_name . ' ' . $last_name;
                    $_SESSION['user_email'] = $email;
                    $_SESSION['user_role'] = 'customer';
                    $success = 'Registration successful! Redirecting to dashboard...';
                    header('Refresh: 1.5; url=index.php');
                } else {
                    $error = 'Registration failed. Try again.';
                }
            }
        }
    }
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && !$pdo && !$error) {
    $error = 'Database unavailable right now. Start MySQL or update db.php before testing registration.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - 9 Waves Events Place</title>
    <link
      href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300;1,400&family=Jost:wght@200;300;400;500&display=swap"
      rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>
<body class="auth-page">
    <div class="customer-topbar account-topbar auth-page-topbar">
        <div class="customer-topbar-title"><img src="image/9waves_LOGO.png" alt="9 Waves Logo" class="logo-img">9 Waves <em>Register</em></div>
        <div class="account-topbar-actions">
            <a href="index.php" class="cust-signout">Back to Website</a>
        </div>
    </div>

    <main class="auth-page-shell">
        <section class="auth-shell-card auth-shell-card-wide">
            <div class="auth-shell-copyblock">
                <div class="auth-shell-eyebrow">Create Account</div>
                <h1 class="auth-shell-title">Set up your <em> profile</em></h1>
                <!-- <p class="auth-shell-copy">This frontend pass keeps registration as a real PHP form, while matching the newer account and admin interfaces visually.</p> -->
            </div>

            <?php if ($error): ?>
                <div class="auth-feedback auth-feedback-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="auth-feedback auth-feedback-success"><?php echo $success; ?></div>
            <?php endif; ?>

            <form method="POST" class="auth-shell-form">
                <div class="form-row">
                    <div class="form-group">
                        <label for="registerFirstName">First Name</label>
                        <input type="text" id="registerFirstName" name="firstName" placeholder="Maria" value="<?php echo htmlspecialchars($_POST['firstName'] ?? $_POST['regFirst'] ?? ''); ?>" required pattern="[A-Za-z\s'-]+" title="Use letters only.">
                    </div>
                    <div class="form-group">
                        <label for="registerLastName">Last Name</label>
                        <input type="text" id="registerLastName" name="lastName" placeholder="Santos" value="<?php echo htmlspecialchars($_POST['lastName'] ?? $_POST['regLast'] ?? ''); ?>" required pattern="[A-Za-z\s'-]+" title="Use letters only.">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="registerUsername">Username</label>
                        <input type="text" id="registerUsername" name="username" placeholder="maria.santos" value="<?php echo htmlspecialchars($_POST['username'] ?? $_POST['regUsername'] ?? ''); ?>" required minlength="3" maxlength="50" pattern="[a-zA-Z0-9_.-]+" title="Use only letters, numbers, underscores, dots, and hyphens.">
                        <small style="color:var(--text-light); font-size:11px; margin-top:4px; display:block;">Letters, numbers, underscores, dots, and hyphens. Minimum 3 characters.</small>
                    </div>
                    <div class="form-group">
                        <label for="registerEmail">Email Address</label>
                        <input type="email" id="registerEmail" name="email" placeholder="maria.santos@example.com" value="<?php echo htmlspecialchars($_POST['email'] ?? $_POST['regEmail'] ?? ''); ?>" required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="registerPhone">Phone Number</label>
                    <input type="tel" id="registerPhone" name="phone" placeholder="09170000000" value="<?php echo htmlspecialchars($_POST['phone'] ?? $_POST['regPhone'] ?? ''); ?>" inputmode="numeric" pattern="[0-9]{11}" maxlength="11" minlength="11" title="Enter exactly 11 numbers." oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 11)">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="registerPassword">Password</label>
                        <input type="password" id="registerPassword" name="password" placeholder="Minimum 6 characters" required minlength="6" autocomplete="new-password">
                    </div>
                    <div class="form-group">
                        <label for="registerConfirmPassword">Confirm Password</label>
                        <input type="password" id="registerConfirmPassword" name="confirmPassword" placeholder="Repeat your password" required minlength="6" autocomplete="new-password">
                    </div>
                </div>
                <button type="submit" class="auth-btn">Register</button>
            </form>

            <div class="auth-shell-links">
                <p>Already have an account? <a href="login.php">Login here</a>.</p>
                <!-- <p>The future backend can later add redirect rules and role-aware routing on top of this UI.</p> -->
            </div>
        </section>
    </main>
</body>
</html>
