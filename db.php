<?php
/**
 * Database connection for 9wavesWebsite
 * Assumes XAMPP: localhost, root, no password, DB '9waves_db'
 */

// Database configuration
$host = 'localhost:3307';
$dbname = '9waves_db';
$username = 'root';
$password = '';

try {
$pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
$pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage() . ". Please create DB '9waves_db' first.");
}
?>
