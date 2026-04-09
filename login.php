<?php
session_start();
require_once 'db.php';

$error = '';
$success = '';

if ($db_error) {
    $error = $db_error;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pdo) {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Email and password required.';
    } else {
        $stmt = $pdo->prepare("SELECT id, CONCAT(first_name, ' ', last_name) as name, email, password FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $success = 'Login successful! Redirecting...';
            header('Refresh: 1.5; url=account.php');
        } else {
            $error = 'Invalid email or password.';
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && !$pdo && !$error) {
    $error = 'Database unavailable right now. Start MySQL or update db.php before testing login.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - 9 Waves Events Place</title>
    <link
      href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300;1,400&family=Jost:wght@200;300;400;500&display=swap"
      rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>
<body class="auth-page">
    <div class="customer-topbar account-topbar auth-page-topbar">
        <div class="customer-topbar-title"><img src="image/9waves_LOGO.png" alt="9 Waves Logo" class="logo-img">9 Waves <em>Login</em></div>
        <div class="account-topbar-actions">
            <a href="index.php" class="cust-signout">Back to Website</a>
        </div>
    </div>

    <main class="auth-page-shell">
        <section class="auth-shell-card">
            <div class="auth-shell-copyblock">
                <div class="auth-shell-eyebrow">Welcome Back</div>
                <h1 class="auth-shell-title">Sign in to your <em>9 Waves</em> account</h1>
                <p class="auth-shell-copy">This page is now aligned with the new frontend pages. The form still uses the existing PHP login flow, but it degrades gracefully if the database is offline.</p>
            </div>

            <?php if ($error): ?>
                <div class="auth-feedback auth-feedback-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="auth-feedback auth-feedback-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <form method="POST" class="auth-shell-form">
                <div class="form-group">
                    <label for="loginEmail">Email Address</label>
                    <input type="email" id="loginEmail" name="email" placeholder="maria.santos@example.com" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label for="loginPassword">Password</label>
                    <input type="password" id="loginPassword" name="password" placeholder="Enter your password" required>
                </div>
                <button type="submit" class="auth-btn">Login</button>
            </form>

            <div class="auth-shell-links">
                <p>No account yet? <a href="register.php">Create one here</a>.</p>
                <p>Frontend demo pages stay directly viewable in this phase: <a href="account.php">Account</a> and <a href="admin.php">Admin</a>.</p>
            </div>
        </section>
    </main>
</body>
</html>
