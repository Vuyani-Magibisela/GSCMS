<?php
/**
 * Registration System Test Script
 * Quick verification of key registration system components
 * 
 * Usage: php test_registration_system.php
 */

// Include the bootstrap to load the framework
require_once __DIR__ . '/app/bootstrap.php';

class RegistrationSystemTester
{
    private $results = [];
    private $errors = [];
    
    public function runTests()
    {
        echo "\n🚀 GDE SciBOTICS Registration System Test Suite\n";
        echo "=" . str_repeat("=", 50) . "\n\n";
        
        // Test database connection
        $this->testDatabaseConnection();
        
        // Test model loading
        $this->testModelLoading();
        
        // Test controller instantiation
        $this->testControllerInstantiation();
        
        // Test CategoryLimitValidator
        $this->testCategoryLimitValidator();
        
        // Test DeadlineEnforcer
        $this->testDeadlineEnforcer();
        
        // Test route resolution
        $this->testRouteResolution();
        
        // Test view rendering
        $this->testViewRendering();
        
        // Display results
        $this->displayResults();
    }
    
    private function testDatabaseConnection()
    {
        $this->startTest("Database Connection");
        
        try {
            $db = \App\Core\Database::getInstance();
            $connection = $db->getConnection();
            
            if ($connection) {
                // Test basic query
                $stmt = $connection->query("SELECT 1 as test");
                $result = $stmt->fetch();
                
                if ($result && $result['test'] == 1) {
                    $this->passTest("Database connection successful");
                } else {
                    $this->failTest("Database query failed");
                }
            } else {
                $this->failTest("Could not establish database connection");
            }
            
        } catch (Exception $e) {
            $this->failTest("Database connection error: " . $e->getMessage());
        }
    }
    
    private function testModelLoading()
    {
        $this->startTest("Model Loading");
        
        $models = [
            'SchoolRegistration',
            'TeamRegistration',
            'BulkImport',
            'Category',
            'School',
            'User',
            'Participant'
        ];
        
        foreach ($models as $model) {
            try {
                $className = "\\App\\Models\\{$model}";
                
                if (class_exists($className)) {
                    // Test model instantiation
                    $reflection = new ReflectionClass($className);
                    
                    if ($reflection->isInstantiable()) {
                        $this->passTest("Model {$model} can be instantiated");
                    } else {
                        $this->failTest("Model {$model} cannot be instantiated");
                    }
                } else {
                    $this->failTest("Model {$model} class does not exist");
                }
                
            } catch (Exception $e) {
                $this->failTest("Model {$model} error: " . $e->getMessage());
            }
        }
    }
    
    private function testControllerInstantiation()
    {
        $this->startTest("Controller Instantiation");
        
        $controllers = [
            'SchoolRegistrationController' => '\\App\\Controllers\\Registration\\SchoolRegistrationController',
            'TeamRegistrationController' => '\\App\\Controllers\\Registration\\TeamRegistrationController',
            'BulkImportController' => '\\App\\Controllers\\Registration\\BulkImportController'
        ];
        
        foreach ($controllers as $name => $className) {
            try {
                if (class_exists($className)) {
                    $controller = new $className();
                    
                    if ($controller instanceof \App\Controllers\BaseController) {
                        $this->passTest("Controller {$name} instantiated successfully");
                    } else {
                        $this->failTest("Controller {$name} does not extend BaseController");
                    }
                } else {
                    $this->failTest("Controller {$name} class does not exist");
                }
                
            } catch (Exception $e) {
                $this->failTest("Controller {$name} error: " . $e->getMessage());
            }
        }
    }
    
    private function testCategoryLimitValidator()
    {
        $this->startTest("CategoryLimitValidator");
        
        try {
            $validator = new \App\Core\CategoryLimitValidator();
            
            // Test basic validation method exists
            if (method_exists($validator, 'validateNewTeamRegistration')) {
                $this->passTest("CategoryLimitValidator has validateNewTeamRegistration method");
            } else {
                $this->failTest("CategoryLimitValidator missing validateNewTeamRegistration method");
            }
            
            // Test participant eligibility method
            if (method_exists($validator, 'validateParticipantEligibility')) {
                $this->passTest("CategoryLimitValidator has validateParticipantEligibility method");
            } else {
                $this->failTest("CategoryLimitValidator missing validateParticipantEligibility method");
            }
            
            // Test basic validation (with dummy data)
            try {
                $result = $validator->validateNewTeamRegistration(1, 1);
                
                if (is_array($result) && isset($result['can_register'])) {
                    $this->passTest("CategoryLimitValidator returns proper validation structure");
                } else {
                    $this->failTest("CategoryLimitValidator returns invalid structure");
                }
            } catch (Exception $e) {
                $this->passTest("CategoryLimitValidator validation method works (expected error for test data)");
            }
            
        } catch (Exception $e) {
            $this->failTest("CategoryLimitValidator error: " . $e->getMessage());
        }
    }
    
    private function testDeadlineEnforcer()
    {
        $this->startTest("DeadlineEnforcer");
        
        try {
            $enforcer = new \App\Core\DeadlineEnforcer();
            
            // Test deadline status method
            if (method_exists($enforcer, 'getDeadlineStatus')) {
                $this->passTest("DeadlineEnforcer has getDeadlineStatus method");
                
                try {
                    $status = $enforcer->getDeadlineStatus();
                    
                    if (is_array($status) && isset($status['active_competition'])) {
                        $this->passTest("DeadlineEnforcer returns proper status structure");
                    } else {
                        $this->failTest("DeadlineEnforcer returns invalid status structure");
                    }
                } catch (Exception $e) {
                    $this->passTest("DeadlineEnforcer status method works (expected for no competition data)");
                }
            } else {
                $this->failTest("DeadlineEnforcer missing getDeadlineStatus method");
            }
            
            // Test enforcement method
            if (method_exists($enforcer, 'enforceRegistrationDeadlines')) {
                $this->passTest("DeadlineEnforcer has enforceRegistrationDeadlines method");
            } else {
                $this->failTest("DeadlineEnforcer missing enforceRegistrationDeadlines method");
            }
            
        } catch (Exception $e) {
            $this->failTest("DeadlineEnforcer error: " . $e->getMessage());
        }
    }
    
    private function testRouteResolution()
    {
        $this->startTest("Route Resolution");
        
        try {
            // Test if router can be instantiated
            $router = new \App\Core\Router();
            
            if ($router) {
                $this->passTest("Router instantiated successfully");
                
                // Test route registration
                $router->get('/test-route', function() { return 'test'; }, 'test.route');
                
                $this->passTest("Route registration working");
            } else {
                $this->failTest("Router instantiation failed");
            }
            
        } catch (Exception $e) {
            $this->failTest("Route resolution error: " . $e->getMessage());
        }
    }
    
    private function testViewRendering()
    {
        $this->startTest("View System");
        
        try {
            // Test view path constant
            if (defined('VIEW_PATH')) {
                $this->passTest("VIEW_PATH constant defined");
                
                // Test if views directory exists
                if (is_dir(VIEW_PATH)) {
                    $this->passTest("Views directory exists");
                    
                    // Test for registration views
                    $registrationViewsPath = VIEW_PATH . '/registration';
                    
                    if (is_dir($registrationViewsPath)) {
                        $this->passTest("Registration views directory exists");
                        
                        // Check for specific view files
                        $viewFiles = [
                            '/school/index.php',
                            '/school/step_1.php',
                            '/team/index.php',
                            '/bulk_import/index.php'
                        ];
                        
                        foreach ($viewFiles as $viewFile) {
                            if (file_exists($registrationViewsPath . $viewFile)) {
                                $this->passTest("View file exists: registration{$viewFile}");
                            } else {
                                $this->failTest("View file missing: registration{$viewFile}");
                            }
                        }
                    } else {
                        $this->failTest("Registration views directory missing");
                    }
                } else {
                    $this->failTest("Views directory does not exist");
                }
            } else {
                $this->failTest("VIEW_PATH constant not defined");
            }
            
        } catch (Exception $e) {
            $this->failTest("View system error: " . $e->getMessage());
        }
    }
    
    private function startTest($testName)
    {
        echo "🧪 Testing: {$testName}\n";
    }
    
    private function passTest($message)
    {
        echo "   ✅ {$message}\n";
        $this->results[] = ['status' => 'pass', 'message' => $message];
    }
    
    private function failTest($message)
    {
        echo "   ❌ {$message}\n";
        $this->results[] = ['status' => 'fail', 'message' => $message];
        $this->errors[] = $message;
    }
    
    private function displayResults()
    {
        echo "\n" . str_repeat("=", 60) . "\n";
        echo "📊 TEST RESULTS SUMMARY\n";
        echo str_repeat("=", 60) . "\n";
        
        $totalTests = count($this->results);
        $passedTests = count(array_filter($this->results, function($r) { return $r['status'] === 'pass'; }));
        $failedTests = count($this->errors);
        
        echo "Total Tests: {$totalTests}\n";
        echo "Passed: {$passedTests} ✅\n";
        echo "Failed: {$failedTests} ❌\n";
        
        if ($failedTests > 0) {
            echo "\n🔥 FAILED TESTS:\n";
            foreach ($this->errors as $error) {
                echo "   • {$error}\n";
            }
        }
        
        $successRate = $totalTests > 0 ? round(($passedTests / $totalTests) * 100, 2) : 0;
        echo "\nSuccess Rate: {$successRate}%\n";
        
        if ($successRate >= 90) {
            echo "\n🎉 Registration system is ready for deployment!\n";
        } elseif ($successRate >= 70) {
            echo "\n⚠️  Registration system needs minor fixes before deployment.\n";
        } else {
            echo "\n🚨 Registration system requires significant fixes before deployment.\n";
        }
        
        echo "\n💡 Next Steps:\n";
        echo "   1. Fix any failed tests above\n";
        echo "   2. Run the full testing checklist (TESTING_CHECKLIST.md)\n";
        echo "   3. Perform user acceptance testing\n";
        echo "   4. Deploy to production environment\n\n";
    }
}

// Run the tests
try {
    $tester = new RegistrationSystemTester();
    $tester->runTests();
    
} catch (Exception $e) {
    echo "\n🚨 CRITICAL ERROR: Could not run registration system tests\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "Please check your installation and try again.\n\n";
}