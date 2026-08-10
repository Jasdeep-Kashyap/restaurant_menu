<?php
// db.php — Database connection (PDO)
// Local XAMPP defaults or environment variable overrides for InfinityFree / live hosting.

$host     = getenv('DB_HOST')     ?: 'localhost';
$dbname   = getenv('DB_NAME')     ?: 'restaurant_db';
$username = getenv('DB_USER')     ?: 'root';
$password = getenv('DB_PASS') !== false ? getenv('DB_PASS') : '';

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
    $GLOBALS['pdo'] = $pdo;
    
    // Set default timezone for MySQL connection (safely guarded for shared hosting)
    try {
        $pdo->exec("SET time_zone = '+05:30'");
    } catch (Exception $tz_ex) {
        // Safe fallback if MySQL time_zone table or privilege is restricted on shared hosting
    }
} catch (PDOException $e) {
    die(
        "<div style='font-family:sans-serif;color:#ff4757;padding:20px;background:#0c0e11;border:1px solid rgba(255,71,87,0.3);border-radius:12px;max-width:600px;margin:40px auto;'>
         <h3 style='margin-top:0;'>⚠️ Database Connection Failed</h3>
         <p>" . htmlspecialchars($e->getMessage()) . "</p>
         <p style='color:#7a8090;'>If deploying to <strong>InfinityFree</strong>, ensure your MySQL Host (e.g. <code>sqlxxx.infinityfree.com</code>), Database Name, Username, and Password in <code>includes/db.php</code> match your InfinityFree Control Panel, and that you have imported <code>setup.sql</code> in phpMyAdmin.</p>
         </div>"
    );
}
?>
