<?php
// app/Core/Router.php

namespace App\Core;

use Exception;

class Router
{
    private $routes = [];
    private $middlewareStack = [];
    private $groupStack = [];
    private $namedRoutes = [];
    private $currentRoute = null;
    
    /**
     * Add GET route
     */
    public function get($uri, $action, $name = null)
    {
        return $this->addRoute(['GET'], $uri, $action, $name);
    }
    
    /**
     * Add POST route
     */
    public function post($uri, $action, $name = null)
    {
        return $this->addRoute(['POST'], $uri, $action, $name);
    }
    
    /**
     * Add PUT route
     */
    public function put($uri, $action, $name = null)
    {
        return $this->addRoute(['PUT'], $uri, $action, $name);
    }
    
    /**
     * Add DELETE route
     */
    public function delete($uri, $action, $name = null)
    {
        return $this->addRoute(['DELETE'], $uri, $action, $name);
    }
    
    /**
     * Add PATCH route
     */
    public function patch($uri, $action, $name = null)
    {
        return $this->addRoute(['PATCH'], $uri, $action, $name);
    }
    
    /**
     * Add route that matches any HTTP method
     */
    public function any($uri, $action, $name = null)
    {
        return $this->addRoute(['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'OPTIONS'], $uri, $action, $name);
    }
    
    /**
     * Add route with multiple HTTP methods
     */
    public function match($methods, $uri, $action, $name = null)
    {
        return $this->addRoute(array_map('strtoupper', (array)$methods), $uri, $action, $name);
    }
    
    /**
     * Create route group with shared attributes
     */
    public function group($attributes, $callback)
    {
        $this->updateGroupStack($attributes);
        
        call_user_func($callback, $this);
        
        array_pop($this->groupStack);
    }
    
    /**
     * Add middleware to current route group
     */
    public function middleware($middleware)
    {
        $this->middlewareStack = array_merge($this->middlewareStack, (array)$middleware);
        return $this;
    }
    
    /**
     * Set route prefix
     */
    public function prefix($prefix)
    {
        $this->updateGroupStack(['prefix' => $prefix]);
        return $this;
    }
    
    /**
     * Set route namespace
     */
    public function namespace($namespace)
    {
        $this->updateGroupStack(['namespace' => $namespace]);
        return $this;
    }
    
    /**
     * Dispatch route
     */
    public function dispatch($uri, $method)
    {
        $route = $this->findRoute($uri, $method);
        
        if (!$route) {
            throw new Exception("Route not found: {$method} {$uri}", 404);
        }
        
        $this->currentRoute = $route;
        
        // Execute middleware chain
        $middlewareChain = $this->buildMiddlewareChain($route);
        
        return $this->executeMiddlewareChain($middlewareChain, $route);
    }
    
    /**
     * Generate URL for named route
     */
    public function route($name, $parameters = [])
    {
        if (!isset($this->namedRoutes[$name])) {
            throw new Exception("Route [{$name}] not found");
        }
        
        $route = $this->namedRoutes[$name];
        $uri = $route['uri'];
        
        // Replace parameters in URI
        foreach ($parameters as $key => $value) {
            $uri = str_replace('{' . $key . '}', $value, $uri);
            $uri = str_replace('{' . $key . '?}', $value, $uri);
        }
        
        // Remove optional parameters that weren't provided
        $uri = preg_replace('/\{[^}]+\?\}/', '', $uri);
        
        return $uri;
    }
    
    /**
     * Get current route
     */
    public function current()
    {
        return $this->currentRoute;
    }
    
    /**
     * Get current route name
     */
    public function currentRouteName()
    {
        return $this->currentRoute ? $this->currentRoute['name'] : null;
    }
    
    /**
     * Check if current route matches pattern
     */
    public function is($patterns)
    {
        $patterns = is_array($patterns) ? $patterns : func_get_args();
        
        if (!$this->currentRoute) {
            return false;
        }
        
        $currentUri = $this->currentRoute['uri'];
        
        foreach ($patterns as $pattern) {
            if (fnmatch($pattern, $currentUri)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Add route to collection
     */
    private function addRoute($methods, $uri, $action, $name = null)
    {
        $uri = $this->prepareUri($uri);
        $action = $this->prepareAction($action);
        $middleware = $this->gatherMiddleware();
        
        $route = [
            'methods' => $methods,
            'uri' => $uri,
            'action' => $action,
            'middleware' => $middleware,
            'name' => $name,
            'parameters' => []
        ];
        
        $this->routes[] = $route;
        
        // Store named route
        if ($name) {
            $this->namedRoutes[$name] = $route;
        }
        
        return $this;
    }
    
    /**
     * Find matching route
     */
    private function findRoute($uri, $method)
    {
        foreach ($this->routes as $route) {
            if (in_array($method, $route['methods']) && $this->uriMatches($route, $uri)) {
                return $route;
            }
        }
        
        return null;
    }
    
    /**
     * Check if URI matches route pattern
     */
    private function uriMatches($route, $uri)
    {
        $pattern = preg_replace('/\{([^}?]+)\??\}/', '([^/]*)', $route['uri']);
        $pattern = '/^' . str_replace('/', '\/', $pattern) . '$/';
        
        if (preg_match($pattern, $uri, $matches)) {
            // Extract parameters
            $parameters = [];
            preg_match_all('/\{([^}?]+)\??\}/', $route['uri'], $paramNames);
            
            for ($i = 1; $i < count($matches); $i++) {
                if (isset($paramNames[1][$i - 1])) {
                    $parameters[$paramNames[1][$i - 1]] = $matches[$i];
                }
            }
            
            $route['parameters'] = $parameters;
            return true;
        }
        
        return false;
    }
    
    /**
     * Build middleware chain
     */
    private function buildMiddlewareChain($route)
    {
        $middleware = $route['middleware'] ?? [];
        
        // Add global middleware here if needed
        $globalMiddleware = []; // Can be configured from config
        
        return array_merge($globalMiddleware, $middleware);
    }
    
    /**
     * Execute middleware chain
     */
    private function executeMiddlewareChain($middlewareChain, $route)
    {
        $request = new Request();
        $response = new Response();
        
        // Set route parameters in request
        foreach ($route['parameters'] as $key => $value) {
            $request->setParameter($key, $value);
        }
        
        $next = function($request) use ($route, $response) {
            return $this->callControllerAction($route, $request, $response);
        };
        
        // Execute middleware in reverse order
        foreach (array_reverse($middlewareChain) as $middleware) {
            $next = function($request) use ($middleware, $next, $response) {
                return $this->callMiddleware($middleware, $request, $response, $next);
            };
        }
        
        return $next($request);
    }
    
    /**
     * Call middleware
     */
    private function callMiddleware($middleware, $request, $response, $next)
    {
        if (is_string($middleware)) {
            // Parse middleware with parameters (e.g., 'role:admin' or 'permission:user.manage')
            $middlewareParts = explode(':', $middleware, 2);
            $middlewareName = $middlewareParts[0];
            $middlewareParams = isset($middlewareParts[1]) ? $middlewareParts[1] : null;
            
            // Try Core middleware first, then Http middleware
            $middlewareClass = "App\\Core\\Middleware\\{$middlewareName}Middleware";
            if (!class_exists($middlewareClass)) {
                $middlewareClass = "App\\Http\\Middleware\\{$middlewareName}Middleware";
            }
            if (!class_exists($middlewareClass)) {
                $middlewareClass = "App\\Http\\Middleware\\{$middlewareName}";
            }
            
            if (class_exists($middlewareClass)) {
                $middlewareInstance = new $middlewareClass();
                
                // Pass parameters to middleware if they exist
                if ($middlewareParams !== null) {
                    return $middlewareInstance->handle($request, $next, $middlewareParams);
                } else {
                    return $middlewareInstance->handle($request, $next);
                }
            }
        } elseif (is_callable($middleware)) {
            return $middleware($request, $next);
        }
        
        return $next($request);
    }
    
    /**
     * Call controller action
     */
    private function callControllerAction($route, $request, $response)
    {
        $action = $route['action'];
        
        if (is_callable($action)) {
            // Closure action
            return call_user_func_array($action, [$request, $response]);
        } elseif (is_string($action) && strpos($action, '@') !== false) {
            // Controller@method action
            [$controller, $method] = explode('@', $action);
            
            if (!class_exists($controller)) {
                throw new Exception("Controller [{$controller}] not found");
            }
            
            $controllerInstance = new $controller();
            
            if (!method_exists($controllerInstance, $method)) {
                throw new Exception("Method [{$method}] not found in controller [{$controller}]");
            }
            
            // Pass route parameters as method arguments
            $parameters = array_values($route['parameters']);
            array_unshift($parameters, $request, $response);
            
            return call_user_func_array([$controllerInstance, $method], $parameters);
        }
        
        throw new Exception("Invalid route action");
    }
    
    /**
     * Prepare URI with group prefix
     */
    private function prepareUri($uri)
    {
        $prefix = $this->getGroupAttribute('prefix', '');
        
        $uri = '/' . trim($uri, '/');
        $prefix = '/' . trim($prefix, '/');
        
        return $prefix === '/' ? $uri : $prefix . $uri;
    }
    
    /**
     * Prepare action with group namespace
     */
    private function prepareAction($action)
    {
        if (is_string($action) && strpos($action, '@') !== false) {
            $baseNamespace = 'App\\Controllers';
            $groupNamespace = $this->getGroupAttribute('namespace');
            [$controller, $method] = explode('@', $action);
            
            if (strpos($controller, '\\') === false) {
                // Combine base namespace with group namespace
                if ($groupNamespace) {
                    $controller = $baseNamespace . '\\' . $groupNamespace . '\\' . $controller;
                } else {
                    $controller = $baseNamespace . '\\' . $controller;
                }
            }
            
            return $controller . '@' . $method;
        }
        
        return $action;
    }
    
    /**
     * Gather middleware from group stack
     */
    private function gatherMiddleware()
    {
        $middleware = [];
        
        foreach ($this->groupStack as $group) {
            if (isset($group['middleware'])) {
                $middleware = array_merge($middleware, (array)$group['middleware']);
            }
        }
        
        return array_merge($middleware, $this->middlewareStack);
    }
    
    /**
     * Update group stack
     */
    private function updateGroupStack($attributes)
    {
        $this->groupStack[] = $attributes;
    }
    
    /**
     * Get group attribute
     */
    private function getGroupAttribute($key, $default = null)
    {
        foreach (array_reverse($this->groupStack) as $group) {
            if (isset($group[$key])) {
                return $group[$key];
            }
        }
        
        return $default;
    }
}