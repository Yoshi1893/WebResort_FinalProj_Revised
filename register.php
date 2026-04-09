<?php
session_start();
require_once 'db.php';

$error = '';
$success = '';

if ($db_error) {
    $error = $db_error;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pdo) {
    $first_name = trim($_POST['firstName'] ?? $_POST['regFirst'] ?? '');
    $last_name = trim($_POST['lastName'] ?? $_POST['regLast'] ?? '');
    $email = trim($_POST['email'] ?? $_POST['regEmail'] ?? '');
    $phone = trim($_POST['phone'] ?? $_POST['regPhone'] ?? '');
    $password = $_POST['password'] ?? $_POST['regPassword'] ?? '';

    if (empty($first_name) || empty($last_name) || empty($email) || empty($password)) {
        $error = 'Name, email, and password required.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = 'Email already exists.';
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, email, phone, password, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
            if ($stmt->execute([$first_name, $last_name, $email, $phone, $hashed_password])) {
                $success = 'Registration successful! <a href="login.php">Login now</a>.';
            } else {
                $error = 'Registration failed. Try again.';
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
                <h1 class="auth-shell-title">Set up your <em>customer profile</em></h1>
                <p class="auth-shell-copy">This frontend pass keeps registration as a real PHP form, while matching the newer account and admin interfaces visually.</p>
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
                        <input type="text" id="registerFirstName" name="firstName" placeholder="Maria" value="<?php echo htmlspecialchars($_POST['firstName'] ?? $_POST['regFirst'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="registerLastName">Last Name</label>
                        <input type="text" id="registerLastName" name="lastName" placeholder="Santos" value="<?php echo htmlspecialchars($_POST['lastName'] ?? $_POST['regLast'] ?? ''); ?>" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="registerEmail">Email Address</label>
                        <input type="email" id="registerEmail" name="email" placeholder="maria.santos@example.com" value="<?php echo htmlspecialchars($_POST['email'] ?? $_POST['regEmail'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="registerPhone">Phone Number</label>
                        <input type="tel" id="registerPhone" name="phone" placeholder="+63 917 000 0000" value="<?php echo htmlspecialchars($_POST['phone'] ?? $_POST['regPhone'] ?? ''); ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label for="registerPassword">Password</label>
                    <input type="password" id="registerPassword" name="password" placeholder="Minimum 6 characters" required>
                </div>
                <button type="submit" class="auth-btn">Register</button>
            </form>

            <div class="auth-shell-links">
                <p>Already have an account? <a href="login.php">Login here</a>.</p>
                <p>The future backend can later add redirect rules and role-aware routing on top of this UI.</p>
            </div>
        </section>
    </main>
</body>
</html>
