<?php
// app/Core/helpers.php

if (!function_exists('config')) {
    function config($key, $default = null)
    {
        static $config = null;
        
        if ($config === null) {
            $config = [];
            
            // Load all config files
            $configFiles = glob(APP_ROOT . '/config/*.php');
            foreach ($configFiles as $file) {
                $name = basename($file, '.php');
                $config[$name] = require $file;
            }
        }
        
        $keys = explode('.', $key);
        $value = $config;
        
        foreach ($keys as $segment) {
            if (is_array($value) && array_key_exists($segment, $value)) {
                $value = $value[$segment];
            } else {
                return $default;
            }
        }
        
        return $value;
    }
}

// View Helper Functions for Templates
if (!function_exists('auth')) {
    function auth()
    {
        return \App\Core\Auth::getInstance();
    }
}

if (!function_exists('user')) {
    function user()
    {
        return \App\Core\ViewHelpers::user();
    }
}

if (!function_exists('isAuth')) {
    function isAuth()
    {
        return \App\Core\ViewHelpers::isAuth();
    }
}

if (!function_exists('hasRole')) {
    function hasRole($role)
    {
        return \App\Core\ViewHelpers::hasRole($role);
    }
}

if (!function_exists('hasAnyRole')) {
    function hasAnyRole($roles)
    {
        return \App\Core\ViewHelpers::hasAnyRole($roles);
    }
}

if (!function_exists('hasPermission')) {
    function hasPermission($permission)
    {
        return \App\Core\ViewHelpers::hasPermission($permission);
    }
}

if (!function_exists('hasAnyPermission')) {
    function hasAnyPermission($permissions)
    {
        return \App\Core\ViewHelpers::hasAnyPermission($permissions);
    }
}

if (!function_exists('canAccess')) {
    function canAccess($requiredRole)
    {
        return \App\Core\ViewHelpers::canAccess($requiredRole);
    }
}

if (!function_exists('canManage')) {
    function canManage($targetRole)
    {
        return \App\Core\ViewHelpers::canManage($targetRole);
    }
}

if (!function_exists('ownsResource')) {
    function ownsResource($resourceType, $resourceId)
    {
        return \App\Core\ViewHelpers::ownsResource($resourceType, $resourceId);
    }
}

if (!function_exists('isAdmin')) {
    function isAdmin()
    {
        return \App\Core\ViewHelpers::isAdmin();
    }
}

if (!function_exists('ifRole')) {
    function ifRole($role, $content)
    {
        return \App\Core\ViewHelpers::ifRole($role, $content);
    }
}

if (!function_exists('ifPermission')) {
    function ifPermission($permission, $content)
    {
        return \App\Core\ViewHelpers::ifPermission($permission, $content);
    }
}

if (!function_exists('ifAuth')) {
    function ifAuth($content)
    {
        return \App\Core\ViewHelpers::ifAuth($content);
    }
}

if (!function_exists('ifGuest')) {
    function ifGuest($content)
    {
        return \App\Core\ViewHelpers::ifGuest($content);
    }
}

if (!function_exists('userName')) {
    function userName()
    {
        return \App\Core\ViewHelpers::userName();
    }
}

if (!function_exists('roleDisplayName')) {
    function roleDisplayName($role = null)
    {
        return \App\Core\ViewHelpers::roleDisplayName($role);
    }
}

if (!function_exists('generateNavigation')) {
    function generateNavigation()
    {
        return \App\Core\ViewHelpers::generateNavigation();
    }
}

if (!function_exists('isActivePage')) {
    function isActivePage($path)
    {
        return \App\Core\ViewHelpers::isActivePage($path);
    }
}

if (!function_exists('activeClass')) {
    function activeClass($path, $activeClass = 'active', $inactiveClass = '')
    {
        return \App\Core\ViewHelpers::activeClass($path, $activeClass, $inactiveClass);
    }
}

if (!function_exists('url')) {
    function url($path = '/')
    {
        return \App\Core\ViewHelpers::url($path);
    }
}

// CSRF Helper Functions
if (!function_exists('csrf_token')) {
    function csrf_token($form = null)
    {
        return \App\Core\CSRF::getInstance()->getToken($form);
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field($form = null)
    {
        return \App\Core\CSRF::getInstance()->field($form);
    }
}

if (!function_exists('csrf_meta')) {
    function csrf_meta($form = null)
    {
        return \App\Core\CSRF::getInstance()->getMetaTag($form);
    }
}