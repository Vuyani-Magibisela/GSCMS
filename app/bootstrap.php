<?php
// app/bootstrap.php - Application Bootstrap

// Load Composer autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Load environment variables
if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->load();
}

// Set error reporting
ini_set('display_errors', $_ENV['APP_DEBUG'] ?? 'true');
error_reporting(E_ALL);

// Set timezone
date_default_timezone_set($_ENV['APP_TIMEZONE'] ?? 'Africa/Johannesburg');

// Configure session settings before starting
if (session_status() === PHP_SESSION_NONE) {
    // Configure session settings
    ini_set('session.name', 'GSCMS_SESSION');
    ini_set('session.cookie_lifetime', 7200); // 2 hours
    ini_set('session.cookie_path', '/');
    ini_set('session.cookie_domain', '');
    ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
    ini_set('session.cookie_httponly', true);
    ini_set('session.cookie_samesite', 'Strict');
    ini_set('session.use_strict_mode', 1);
    ini_set('session.use_only_cookies', 1);
    
    // Start session
    session_start();
}

// Define application constants
define('APP_ROOT', dirname(__DIR__));
define('APP_PATH', __DIR__);
define('CONFIG_PATH', APP_ROOT . '/config');
define('VIEW_PATH', APP_ROOT . '/app/Views');
define('STORAGE_PATH', APP_ROOT . '/storage');
define('LOG_PATH', STORAGE_PATH . '/logs');

// Ensure required directories exist
if (!is_dir(STORAGE_PATH)) {
    mkdir(STORAGE_PATH, 0755, true);
}

if (!is_dir(LOG_PATH)) {
    mkdir(LOG_PATH, 0755, true);
}

// Set up error handling
use App\Core\ErrorHandler;
use App\Core\Logger;

$logger = new Logger();
$errorHandler = new ErrorHandler($logger);

set_error_handler([$errorHandler, 'handleError']);
set_exception_handler([$errorHandler, 'handleException']);
register_shutdown_function([$errorHandler, 'handleShutdown']);

// Load helper functions
require_once APP_PATH . '/Core/helpers.php';