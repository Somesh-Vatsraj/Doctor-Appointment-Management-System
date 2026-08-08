<?php
declare(strict_types=1);

define('DB_HOST', 'shinkansen.proxy.rlwy.net:10211');
define('DB_NAME', 'railway');
define('DB_USER', 'root');
define('DB_PASS', 'UQOuAebEJeoyxeUHuSnrHCgXMnrNvfGf');

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    die("Database Connection Failed: " . htmlspecialchars($e->getMessage()));
}
