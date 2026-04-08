<?php
include 'db.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inquiry - 9waves Website</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <h2>Send Inquiry</h2>
        <?php if ($error): ?>
            <p style="color: red;"><?php echo $error; ?></p>
        <?php endif; ?>
        <?php if ($success): ?>
            <p style="color: green;"><?php echo $success; ?></p>
        <?php endif; ?>
        <form method="POST">
            <input type="text" name="full_name" placeholder="Full Name" value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>" required>
            <input type="email" name="email" placeholder="Email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
            <input type="text" name="event_type" placeholder="Event Type" value="<?php echo htmlspecialchars($_POST['event_type'] ?? ''); ?>">
            <input type="date" name="event_date" value="<?php echo htmlspecialchars($_POST['event_date'] ?? ''); ?>">
            <textarea name="message" placeholder="Details/notes" rows="5" required><?php echo htmlspecialchars($_POST['message'] ?? ''); ?></textarea>
            <button type="submit">Send</button>
        </form>
        <a href="index.html">Home</a>
    </div>
</body>
</html>
