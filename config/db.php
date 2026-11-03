<?php
require_once __DIR__ . '/../includes/security.php';
$local = dirname(__DIR__, 2) . '/ahmad-private/config.php';
if (is_file($local)) { require $local; }
try {
    $conn = new PDO('mysql:host=' . (getenv('AHMAD_DB_HOST') ?: 'localhost') . ';dbname=' . getenv('AHMAD_DB_NAME') . ';charset=utf8mb4', getenv('AHMAD_DB_USER'), getenv('AHMAD_DB_PASSWORD'), [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES => false]);
} catch (PDOException $e) {
    error_log('Database connection failed. Check private configuration.');
    http_response_code(503); exit('The website is temporarily unavailable. Please try again later.');
}
