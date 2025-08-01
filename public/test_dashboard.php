<?php
/**
 * Browser Login Test - Creates a simple form to test admin login
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard Login Test</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; }
        .test-form { background: #f5f5f5; padding: 20px; border-radius: 8px; margin: 20px 0; }
        .test-button { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; }
        .test-button:hover { background: #0056b3; }
        .test-result { margin: 20px 0; padding: 15px; border-radius: 4px; }
        .success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
        .error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }
    </style>
</head>
<body>
    <h1>Admin Dashboard Test</h1>
    <p>This page helps test the admin dashboard functionality</p>
    
    <div class="test-form">
        <h2>Quick Tests</h2>
        <p><a href="http://localhost/GSCMS/public/" target="_blank" class="test-button">Test Main Site</a></p>
        <p><a href="http://localhost/GSCMS/public/auth/login" target="_blank" class="test-button">Test Login Page</a></p>
        <p><a href="http://localhost/GSCMS/public/admin/dashboard" target="_blank" class="test-button">Test Admin Dashboard (Login Required)</a></p>
    </div>

    <div class="test-form">
        <h2>Manual Login Instructions</h2>
        <ol>
            <li>Click "Test Login Page" above</li>
            <li>Use these credentials:
                <ul>
                    <li><strong>Email:</strong> admin@gscms.local</li>
                    <li><strong>Password:</strong> password</li>
                </ul>
            </li>
            <li>After successful login, you should be redirected to the admin dashboard</li>
            <li>The dashboard should show statistics cards, quick actions, and navigation</li>
        </ol>
    </div>

    <div class="test-form">
        <h2>Test Results from Backend</h2>
        
        <?php
        try {
            // Test basic file existence
            $files_to_check = [
                '/var/www/html/GSCMS/app/Controllers/Admin/DashboardController.php' => 'Admin Controller',
                '/var/www/html/GSCMS/app/Views/admin/dashboard.php' => 'Dashboard View',
                '/var/www/html/GSCMS/public/css/admin-dashboard.css' => 'Dashboard CSS',
                '/var/www/html/GSCMS/public/js/admin-dashboard.js' => 'Dashboard JavaScript'
            ];
            
            echo "<div class='test-result success'>";
            echo "<h3>✅ File Check Results</h3>";
            foreach ($files_to_check as $file => $description) {
                if (file_exists($file)) {
                    $size = round(filesize($file) / 1024, 2);
                    echo "<p>✅ {$description}: Found ({$size} KB)</p>";
                } else {
                    echo "<p>❌ {$description}: Missing</p>";
                }
            }
            echo "</div>";
            
            // Test URL accessibility
            echo "<div class='test-result success'>";
            echo "<h3>✅ URL Accessibility Test</h3>";
            
            $urls_to_test = [
                'http://localhost/GSCMS/public/' => 'Main Site',
                'http://localhost/GSCMS/public/auth/login' => 'Login Page'
            ];
            
            foreach ($urls_to_test as $url => $description) {
                $headers = @get_headers($url);
                if ($headers && strpos($headers[0], '200') !== false) {
                    echo "<p>✅ {$description}: Accessible</p>";
                } elseif ($headers && strpos($headers[0], '302') !== false) {
                    echo "<p>✅ {$description}: Redirecting (OK)</p>";
                } else {
                    echo "<p>❌ {$description}: Not accessible</p>";
                }
            }
            echo "</div>";
            
        } catch (Exception $e) {
            echo "<div class='test-result error'>";
            echo "<h3>❌ Test Error</h3>";
            echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
            echo "</div>";
        }
        ?>
    </div>

    <div class="test-form">
        <h2>Expected Dashboard Features</h2>
        <p>When you successfully log in as admin, you should see:</p>
        <ul>
            <li>Welcome message with your name</li>
            <li>Statistics cards showing system metrics</li>
            <li>Quick actions panel with shortcuts</li>
            <li>Recent activity feed</li>
            <li>System health indicators</li>
            <li>Responsive design that adapts to screen size</li>
            <li>Interactive elements with hover effects</li>
        </ul>
    </div>

    <script>
        // Add some basic interactivity
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Admin Dashboard Test Page Loaded');
            
            // Test basic JavaScript functionality
            const buttons = document.querySelectorAll('.test-button');
            buttons.forEach(button => {
                button.addEventListener('click', function() {
                    console.log('Testing:', this.textContent);
                });
            });
        });
    </script>
</body>
</html>