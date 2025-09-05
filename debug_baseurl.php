<?php
// debug_baseurl.php - Debug baseUrl generation

require_once 'app/bootstrap.php';

echo "=== BaseURL Debug ===\n\n";

// Simulate the environment
$_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'localhost';
$_SERVER['SCRIPT_NAME'] = $_SERVER['SCRIPT_NAME'] ?? '/GSCMS/public/index.php';
$_SERVER['HTTPS'] = $_SERVER['HTTPS'] ?? '';

echo "Current SERVER variables:\n";
echo "HTTP_HOST: " . $_SERVER['HTTP_HOST'] . "\n";
echo "SCRIPT_NAME: " . $_SERVER['SCRIPT_NAME'] . "\n";
echo "HTTPS: " . ($_SERVER['HTTPS'] ?: 'not set') . "\n\n";

// Test the BaseController baseUrl method
use App\Controllers\BaseController;

class TestController extends BaseController {
    public function testBaseUrl($path = '') {
        return $this->baseUrl($path);
    }
}

try {
    $testController = new TestController();
    $baseUrl = $testController->testBaseUrl();
    $baseUrlWithPath = $testController->testBaseUrl('admin/contacts');
    
    echo "Generated baseUrl: '$baseUrl'\n";
    echo "BaseUrl with path: '$baseUrlWithPath'\n\n";
    
    // Manual calculation like BaseController does
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'];
    $scriptPath = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
    $scriptPath = $scriptPath === '/' ? '' : $scriptPath;
    
    echo "Manual calculation:\n";
    echo "Protocol: '$protocol'\n";
    echo "Host: '$host'\n";
    echo "Script path: '$scriptPath'\n";
    echo "Full baseUrl: '$protocol$host$scriptPath'\n\n";
    
    // Expected vs actual
    echo "=== EXPECTED vs ACTUAL ===\n";
    echo "Expected baseUrl: '/GSCMS'\n";
    echo "Actual baseUrl: '$baseUrl'\n";
    
    if ($baseUrl === '/GSCMS') {
        echo "✅ BaseUrl is correct!\n";
    } else {
        echo "❌ BaseUrl is incorrect - this is causing the routing issue\n";
        echo "\nFix needed: BaseUrl should be '/GSCMS' but is '$baseUrl'\n";
    }
    
} catch (Exception $e) {
    echo "Error testing baseUrl: " . $e->getMessage() . "\n";
}