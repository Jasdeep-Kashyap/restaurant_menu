<?php
// db.php — Database connection (PDO)
// ---------------------------------------------------------------
// Edit the credentials below to match your MySQL setup.
// For XAMPP defaults: username=root, password=''
// ---------------------------------------------------------------

$host     = 'localhost';
$dbname   = 'restaurant_db';
$username = 'root';   // ← change if needed
$password = '';       // ← change if needed

// Set default timezone for PHP
date_default_timezone_set('Asia/Kolkata');

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Set default timezone for MySQL database connection
    $pdo->exec("SET time_zone = '+05:30'");
} catch (PDOException $e) {
    die(
        "<div style='font-family:sans-serif;color:#ff4757;padding:20px;'>
         <h3>⚠️ Database Connection Failed</h3>
         <p>" . htmlspecialchars($e->getMessage()) . "</p>
         <p>Make sure MySQL is running and you have executed <code>setup.sql</code>.</p>
         </div>"
    );
}
?>
