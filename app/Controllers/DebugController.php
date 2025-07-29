<?php
// app/Controllers/DebugController.php

namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Core\Auth;

class DebugController extends BaseController
{
    /**
     * Show debug information about routes and system
     */
    public function routes(Request $request, Response $response)
    {
        // Get router instance from container or create new one
        $router = app('router');
        
        // Get all routes using reflection to access private routes property
        $reflection = new \ReflectionClass($router);
        $routesProperty = $reflection->getProperty('routes');
        $routesProperty->setAccessible(true);
        $routes = $routesProperty->getValue($router);
        
        // Get named routes
        $namedRoutesProperty = $reflection->getProperty('namedRoutes');
        $namedRoutesProperty->setAccessible(true);
        $namedRoutes = $namedRoutesProperty->getValue($router);
        
        // Current route info
        $currentRoute = $router->current();
        
        // System information
        $systemInfo = [
            'request_uri' => $_SERVER['REQUEST_URI'] ?? 'N/A',
            'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'N/A',
            'script_name' => $_SERVER['SCRIPT_NAME'] ?? 'N/A',
            'server_name' => $_SERVER['SERVER_NAME'] ?? 'N/A',
            'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'N/A',
            'php_self' => $_SERVER['PHP_SELF'] ?? 'N/A',
            'query_string' => $_SERVER['QUERY_STRING'] ?? 'N/A'
        ];
        
        // Authentication info
        $authInfo = [
            'is_authenticated' => Auth::check(),
            'user_id' => Auth::id(),
            'user_role' => Auth::user() ? Auth::user()->role : 'Not authenticated',
            'session_id' => session_id(),
            'session_status' => session_status(),
            'session_data' => $_SESSION ?? []
        ];
        
        // Middleware information
        $middlewareInfo = [];
        if ($currentRoute && isset($currentRoute['middleware'])) {
            foreach ($currentRoute['middleware'] as $middleware) {
                $middlewareInfo[] = [
                    'name' => $middleware,
                    'class_exists' => $this->checkMiddlewareClassExists($middleware),
                    'file_path' => $this->getMiddlewareFilePath($middleware)
                ];
            }
        }
        
        // Filter routes for display
        $filteredRoutes = [];
        foreach ($routes as $route) {
            $filteredRoutes[] = [
                'methods' => implode('|', $route['methods']),
                'uri' => $route['uri'],
                'action' => is_string($route['action']) ? $route['action'] : 'Closure',
                'name' => $route['name'] ?? 'unnamed',
                'middleware' => implode(', ', $route['middleware'] ?? []),
                'controller_exists' => $this->checkControllerExists($route['action'])
            ];
        }
        
        $data = [
            'title' => 'Debug: Routes & System Information',
            'routes' => $filteredRoutes,
            'named_routes' => $namedRoutes,
            'current_route' => $currentRoute,
            'system_info' => $systemInfo,
            'auth_info' => $authInfo,
            'middleware_info' => $middlewareInfo,
            'total_routes' => count($routes),
            'navigation_links' => $this->getNavigationLinks()
        ];
        
        return $this->view('debug/routes', $data);
    }
    
    /**
     * Test specific navigation links
     */
    public function testNavigation(Request $request, Response $response)
    {
        $testLinks = ['/dashboard', '/profile', '/settings'];
        $results = [];
        
        foreach ($testLinks as $link) {
            $results[$link] = $this->testLink($link);
        }
        
        $data = [
            'title' => 'Navigation Test Results',
            'test_results' => $results,
            'auth_info' => [
                'is_authenticated' => Auth::check(),
                'user_role' => Auth::user() ? Auth::user()->role : 'Not authenticated'
            ]
        ];
        
        return $this->view('debug/navigation_test', $data);
    }
    
    /**
     * Check system logs for errors
     */
    public function logs(Request $request, Response $response)
    {
        $logDir = STORAGE_PATH . '/logs';
        $logFiles = [];
        $recentErrors = [];
        
        if (is_dir($logDir)) {
            $files = scandir($logDir);
            foreach ($files as $file) {
                if ($file !== '.' && $file !== '..' && pathinfo($file, PATHINFO_EXTENSION) === 'log') {
                    $logFiles[] = [
                        'name' => $file,
                        'path' => $logDir . '/' . $file,
                        'size' => filesize($logDir . '/' . $file),
                        'modified' => filemtime($logDir . '/' . $file)
                    ];
                }
            }
            
            // Get recent errors from latest log file
            if (!empty($logFiles)) {
                $latestLog = array_reduce($logFiles, function($carry, $item) {
                    return (!$carry || $item['modified'] > $carry['modified']) ? $item : $carry;
                });
                
                if ($latestLog) {
                    $logContent = file_get_contents($latestLog['path']);
                    $lines = explode("\n", $logContent);
                    $recentErrors = array_slice(array_reverse($lines), 0, 50);
                }
            }
        }
        
        $data = [
            'title' => 'System Logs',
            'log_files' => $logFiles,
            'recent_errors' => $recentErrors,
            'log_directory' => $logDir
        ];
        
        return $this->view('debug/logs', $data);
    }
    
    /**
     * Test a specific link
     */
    private function testLink($uri)
    {
        $router = app('router');
        
        try {
            $reflection = new \ReflectionClass($router);
            $method = $reflection->getMethod('findRoute');
            $method->setAccessible(true);
            
            $route = $method->invoke($router, $uri, 'GET');
            
            if (!$route) {
                return [
                    'status' => 'Route not found',
                    'route' => null,
                    'controller_exists' => false,
                    'middleware_status' => 'N/A'
                ];
            }
            
            return [
                'status' => 'Route found',
                'route' => $route,
                'controller_exists' => $this->checkControllerExists($route['action']),
                'middleware_status' => $this->checkMiddlewareStatus($route['middleware'] ?? [])
            ];
            
        } catch (\Exception $e) {
            return [
                'status' => 'Error: ' . $e->getMessage(),
                'route' => null,
                'controller_exists' => false,
                'middleware_status' => 'Error'
            ];
        }
    }
    
    /**
     * Check if controller exists
     */
    private function checkControllerExists($action)
    {
        if (!is_string($action) || strpos($action, '@') === false) {
            return false;
        }
        
        [$controller, $method] = explode('@', $action);
        
        if (!class_exists($controller)) {
            return false;
        }
        
        return method_exists($controller, $method);
    }
    
    /**
     * Check middleware class exists
     */
    private function checkMiddlewareClassExists($middleware)
    {
        if (is_string($middleware)) {
            $middlewareParts = explode(':', $middleware, 2);
            $middlewareName = $middlewareParts[0];
            
            $possibleClasses = [
                "App\\Core\\Middleware\\{$middlewareName}Middleware",
                "App\\Http\\Middleware\\{$middlewareName}Middleware",
                "App\\Http\\Middleware\\{$middlewareName}"
            ];
            
            foreach ($possibleClasses as $class) {
                if (class_exists($class)) {
                    return $class;
                }
            }
        }
        
        return false;
    }
    
    /**
     * Get middleware file path
     */
    private function getMiddlewareFilePath($middleware)
    {
        $className = $this->checkMiddlewareClassExists($middleware);
        if (!$className) {
            return 'Not found';
        }
        
        try {
            $reflection = new \ReflectionClass($className);
            return $reflection->getFileName();
        } catch (\Exception $e) {
            return 'Error: ' . $e->getMessage();
        }
    }
    
    /**
     * Check middleware status
     */
    private function checkMiddlewareStatus($middlewareArray)
    {
        $status = [];
        foreach ($middlewareArray as $middleware) {
            $className = $this->checkMiddlewareClassExists($middleware);
            $status[] = [
                'name' => $middleware,
                'exists' => $className !== false,
                'class' => $className
            ];
        }
        return $status;
    }
    
    /**
     * Get navigation links for testing
     */
    private function getNavigationLinks()
    {
        return [
            'Dashboard' => '/dashboard',
            'Profile' => '/profile', 
            'Settings' => '/settings',
            'Home' => '/',
            'Login' => '/auth/login',
            'Register' => '/auth/register'
        ];
    }
}