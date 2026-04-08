<?php
session_start();
include 'db.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - 9waves Website</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <h2>Register</h2>
        <?php if ($error): ?>
            <p style="color: red;"><?php echo $error; ?></p>
        <?php endif; ?>
        <?php if ($success): ?>
            <p style="color: green;"><?php echo $success; ?></p>
        <?php endif; ?>
        <form method="POST">
            <input type="text" name="firstName" placeholder="First Name" value="<?php echo htmlspecialchars($_POST['firstName'] ?? $_POST['regFirst'] ?? ''); ?>" required>
            <input type="text" name="lastName" placeholder="Last Name" value="<?php echo htmlspecialchars($_POST['lastName'] ?? $_POST['regLast'] ?? ''); ?>" required>
            <input type="email" name="email" placeholder="Email" value="<?php echo htmlspecialchars($_POST['email'] ?? $_POST['regEmail'] ?? ''); ?>" required>
            <input type="tel" name="phone" placeholder="Phone" value="<?php echo htmlspecialchars($_POST['phone'] ?? $_POST['regPhone'] ?? ''); ?>">
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Register</button>
        </form>
        <p>Already have an account? <a href="login.php">Login</a></p>
        <a href="index.html">Home</a>
    </div>
</body>
</html>
