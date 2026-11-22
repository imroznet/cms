<?php
// Shared bootstrap. Included before any output or database access.
if (defined('AHMAD_SECURITY')) { return; }
define('AHMAD_SECURITY', true);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params(['httponly'=>true, 'secure'=>!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off', 'samesite'=>'Lax', 'path'=>'/']);
    session_start();
}
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Cache-Control: no-store');
if (isset($_SESSION['user_id']) && time() - ($_SESSION['last_active'] ?? time()) > 1800) {
    $_SESSION = []; session_regenerate_id(true);
}
$_SESSION['last_active'] = time();
$_SESSION['csrf'] = $_SESSION['csrf'] ?? bin2hex(random_bytes(32));
function csrf_field() { return '<input type="hidden" name="csrf" value="' . htmlspecialchars($_SESSION['csrf'], ENT_QUOTES, 'UTF-8') . '">'; }
function security_stop($status, $message) { http_response_code($status); exit(htmlspecialchars($message, ENT_QUOTES, 'UTF-8')); }
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($method === 'POST' && (!is_string($_POST['csrf'] ?? null) || !hash_equals($_SESSION['csrf'], $_POST['csrf']))) {
    security_stop(403, 'Your form expired. Reload the page and try again.');
}
// Validate every upload before legacy handlers use it. No client MIME trust.
foreach ($_FILES as &$file) {
    if (is_array($file['name'] ?? null)) { security_stop(400, 'Multiple files are not supported.'); }
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) { continue; }
    if (($file['error'] ?? -1) !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) { security_stop(400, 'The upload failed. Please try again.'); }
    if ($file['size'] > 5 * 1024 * 1024) { security_stop(413, 'Files must be under 5 MB.'); }
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $types = ['jpg'=>['image/jpeg'],'jpeg'=>['image/jpeg'],'png'=>['image/png'],'gif'=>['image/gif'],'ico'=>['image/x-icon','image/vnd.microsoft.icon'],'pdf'=>['application/pdf'],'doc'=>['application/msword','application/x-ole-storage'],'docx'=>['application/vnd.openxmlformats-officedocument.wordprocessingml.document']];
    if (!isset($types[$ext]) || !in_array($mime, $types[$ext], true)) { security_stop(400, 'The file content does not match an allowed file type.'); }
    if (in_array($ext, ['jpg','jpeg','png','gif'], true) && !@getimagesize($file['tmp_name'])) { security_stop(400, 'Invalid image.'); }
    $file['name'] = bin2hex(random_bytes(16)) . '.' . $ext;
}
unset($file);
// Legacy forms receive tokens server-side; also buffers late redirects.
ob_start(function ($html) {
    return preg_replace_callback('/<form\b[^>]*>/i', function ($match) {
        return $match[0] . (preg_match('/method\s*=\s*["\']?post\b/i', $match[0]) ? csrf_field() : '');
    }, $html);
});
