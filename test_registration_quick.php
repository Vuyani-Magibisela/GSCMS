<?php
/**
 * Quick Registration System Test
 * Simplified test without session conflicts
 */

require_once __DIR__ . '/app/bootstrap.php';

echo "🚀 Quick Registration System Test\n";
echo str_repeat("=", 50) . "\n\n";

$tests = [
    'Database Connection' => function() {
        try {
            $db = \App\Core\Database::getInstance();
            return $db->getConnection() ? "✅ Connected" : "❌ Failed";
        } catch (Exception $e) {
            return "❌ Error: " . $e->getMessage();
        }
    },
    
    'Model Classes' => function() {
        $models = ['SchoolRegistration', 'TeamRegistration', 'BulkImport', 'Category', 'School', 'User', 'Participant'];
        $results = [];
        
        foreach ($models as $model) {
            $className = "\\App\\Models\\{$model}";
            $results[] = class_exists($className) ? "✅ {$model}" : "❌ {$model}";
        }
        
        return implode("\n        ", $results);
    },
    
    'Core Components' => function() {
        $components = [
            'CategoryLimitValidator' => '\\App\\Core\\CategoryLimitValidator',
            'DeadlineEnforcer' => '\\App\\Core\\DeadlineEnforcer',
            'Router' => '\\App\\Core\\Router'
        ];
        
        $results = [];
        
        foreach ($components as $name => $className) {
            $results[] = class_exists($className) ? "✅ {$name}" : "❌ {$name}";
        }
        
        return implode("\n        ", $results);
    },
    
    'View Files' => function() {
        $viewPaths = [
            'registration/school/index.php',
            'registration/school/step_1.php',
            'registration/team/index.php',
            'registration/bulk_import/index.php',
            'registration/bulk_import/wizard.php'
        ];
        
        $results = [];
        $basePath = VIEW_PATH ?? __DIR__ . '/app/Views';
        
        foreach ($viewPaths as $viewPath) {
            $fullPath = $basePath . '/' . $viewPath;
            $results[] = file_exists($fullPath) ? "✅ {$viewPath}" : "❌ {$viewPath}";
        }
        
        return implode("\n        ", $results);
    },
    
    'Route File' => function() {
        $routeFile = __DIR__ . '/routes/web.php';
        if (!file_exists($routeFile)) {
            return "❌ routes/web.php not found";
        }
        
        $content = file_get_contents($routeFile);
        $hasSchoolRoutes = strpos($content, '/school-registration') !== false;
        $hasTeamRoutes = strpos($content, '/team-registration') !== false;
        $hasBulkRoutes = strpos($content, '/bulk-import') !== false;
        
        $results = [
            $hasSchoolRoutes ? "✅ School registration routes" : "❌ School registration routes",
            $hasTeamRoutes ? "✅ Team registration routes" : "❌ Team registration routes", 
            $hasBulkRoutes ? "✅ Bulk import routes" : "❌ Bulk import routes"
        ];
        
        return implode("\n        ", $results);
    }
];

foreach ($tests as $testName => $testFunction) {
    echo "🧪 Testing: {$testName}\n";
    echo "        " . $testFunction() . "\n\n";
}

echo str_repeat("=", 50) . "\n";
echo "📊 QUICK TEST COMPLETE\n\n";

echo "💡 Next Steps:\n";
echo "   1. Run database setup: php database/console/setup.php\n";
echo "   2. Seed test data: php database/console/seed.php\n";
echo "   3. Start web server: php -S localhost:8000 -t public/\n";
echo "   4. Test registration workflows manually\n\n";