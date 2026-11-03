<?php
require_once __DIR__ . '/../includes/security.php';

// Log the logout if user is logged in
if (isset($_SESSION['user_id'])) {
    require_once '../config/db.php';
    
    try {
        $stmt = $conn->prepare("INSERT INTO user_activity_log (user_id, action, details, ip_address) VALUES (?, 'logout', ?, ?)");
        $details = json_encode(['browser' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown']);
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $stmt->execute([$_SESSION['user_id'], $details, $ip]);
    } catch (PDOException $e) {
        // Silent fail - continue with logout even if logging fails
    }
}

// Clear all session variables
$_SESSION = [];

// Delete the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Redirect to login page
header("Location: index.php");
exit;
?>