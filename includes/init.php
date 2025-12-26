<?php
/**
 * Application Bootstrap - Single entry point for all includes
 */

// Define constants only if not already defined
if (!defined('BASE_URL')) {
    define('BASE_URL', 'http://localhost/marrak-rent-car');
    define('ROOT_PATH', __DIR__ . '/..');
}

// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', 0); // Set to 1 for HTTPS
    ini_set('session.use_strict_mode', 1);
    session_start();
}

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Timezone
date_default_timezone_set('UTC');

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

// Auto-require common files
require_once __DIR__ . '/db.php';

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// CSRF helper function
function generateCSRFToken() {
    return $_SESSION['csrf_token'];
}

function validateCSRF($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Security helper functions
function sanitizeInput($input) {
    if (is_array($input)) {
        return array_map('sanitizeInput', $input);
    }
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) && strlen($email) <= 100;
}

function isValidPhone($phone) {
    return preg_match('/^[\d\s\-\+\(\)]+$/', $phone) && strlen($phone) >= 10;
}

function rateLimitCheck($key, $limit = 5, $window = 300) {
    $current = time();
    $attempts = $_SESSION[$key] ?? [];
    
    // Clean old attempts
    $attempts = array_filter($attempts, function($timestamp) use ($current, $window) {
        return $current - $timestamp < $window;
    });
    
    if (count($attempts) >= $limit) {
        return false;
    }
    
    $attempts[] = $current;
    $_SESSION[$key] = $attempts;
    return true;
}

function logSecurityEvent($event, $details = []) {
    $logEntry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        'event' => $event,
        'details' => $details
    ];
    
    error_log("SECURITY: " . json_encode($logEntry));
}

function checkUserSession() {
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        return false;
    }
    
    // Check session timeout (30 minutes)
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
        session_destroy();
        return false;
    }
    
    // Update last activity
    $_SESSION['last_activity'] = time();
    return true;
}

function requireLogin() {
    if (!checkUserSession()) {
        $_SESSION['flash_message'] = 'Please log in to continue.';
        $_SESSION['flash_type'] = 'error';
        header('Location: login.php');
        exit;
    }
}

function requireAdmin() {
    requireLogin();
    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
        $_SESSION['flash_message'] = 'Access denied. Admin privileges required.';
        $_SESSION['flash_type'] = 'error';
        header('Location: index.php');
        exit;
    }
}

// Note: Sanitization should be applied at output, not input for database operations
// Only sanitize GET data for display purposes
if (isset($_SERVER['REQUEST_METHOD'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $_GET = sanitizeInput($_GET);
    }
    // POST data will be sanitized at output/display time
    // $_FILES = sanitizeInput($_FILES); // Commented out to preserve file upload functionality
}
?>