<?php
// app/Core/ViewHelpers.php

namespace App\Core;

use App\Core\Auth;

class ViewHelpers
{
    private static $auth = null;
    
    /**
     * Get Auth instance
     */
    private static function auth()
    {
        if (self::$auth === null) {
            self::$auth = Auth::getInstance();
        }
        return self::$auth;
    }
    
    /**
     * Check if user is authenticated
     */
    public static function isAuth()
    {
        return self::auth()->check();
    }
    
    /**
     * Get current user
     */
    public static function user()
    {
        return self::auth()->user();
    }
    
    /**
     * Check if user has specific role
     */
    public static function hasRole($role)
    {
        return self::auth()->hasRole($role);
    }
    
    /**
     * Check if user has any of the given roles
     */
    public static function hasAnyRole($roles)
    {
        return self::auth()->hasAnyRole($roles);
    }
    
    /**
     * Check if user has specific permission
     */
    public static function hasPermission($permission)
    {
        return self::auth()->hasPermission($permission);
    }
    
    /**
     * Check if user has any of the given permissions
     */
    public static function hasAnyPermission($permissions)
    {
        return self::auth()->hasAnyPermission($permissions);
    }
    
    /**
     * Check if user has all of the given permissions
     */
    public static function hasAllPermissions($permissions)
    {
        return self::auth()->hasAllPermissions($permissions);
    }
    
    /**
     * Check if user can access resource based on role hierarchy
     */
    public static function canAccess($requiredRole)
    {
        return self::auth()->canAccess($requiredRole);
    }
    
    /**
     * Check if user can manage another role
     */
    public static function canManage($targetRole)
    {
        return self::auth()->canManage($targetRole);
    }
    
    /**
     * Check if user owns specific resource
     */
    public static function ownsResource($resourceType, $resourceId)
    {
        return self::auth()->ownsResource($resourceType, $resourceId);
    }
    
    /**
     * Check if user is admin (super admin or competition admin)
     */
    public static function isAdmin()
    {
        return self::auth()->isAdmin();
    }
    
    /**
     * Check if user is super admin
     */
    public static function isSuperAdmin()
    {
        return self::hasRole('super_admin');
    }
    
    /**
     * Check if user is competition admin
     */
    public static function isCompetitionAdmin()
    {
        return self::hasRole('competition_admin');
    }
    
    /**
     * Check if user is school coordinator
     */
    public static function isSchoolCoordinator()
    {
        return self::hasRole('school_coordinator');
    }
    
    /**
     * Check if user is team coach
     */
    public static function isTeamCoach()
    {
        return self::hasRole('team_coach');
    }
    
    /**
     * Check if user is judge
     */
    public static function isJudge()
    {
        return self::hasRole('judge');
    }
    
    /**
     * Check if user is participant
     */
    public static function isParticipant()
    {
        return self::hasRole('participant');
    }
    
    /**
     * Render content only if user has role
     */
    public static function ifRole($role, $content)
    {
        return self::hasRole($role) ? $content : '';
    }
    
    /**
     * Render content only if user has any of the given roles
     */
    public static function ifAnyRole($roles, $content)
    {
        return self::hasAnyRole($roles) ? $content : '';
    }
    
    /**
     * Render content only if user has permission
     */
    public static function ifPermission($permission, $content)
    {
        return self::hasPermission($permission) ? $content : '';
    }
    
    /**
     * Render content only if user has any of the given permissions
     */
    public static function ifAnyPermission($permissions, $content)
    {
        return self::hasAnyPermission($permissions) ? $content : '';
    }
    
    /**
     * Render content only if user can access resource
     */
    public static function ifCanAccess($requiredRole, $content)
    {
        return self::canAccess($requiredRole) ? $content : '';
    }
    
    /**
     * Render content only if user can manage role
     */
    public static function ifCanManage($targetRole, $content)
    {
        return self::canManage($targetRole) ? $content : '';
    }
    
    /**
     * Render content only if user owns resource
     */
    public static function ifOwnsResource($resourceType, $resourceId, $content)
    {
        return self::ownsResource($resourceType, $resourceId) ? $content : '';
    }
    
    /**
     * Render content only if user is authenticated
     */
    public static function ifAuth($content)
    {
        return self::isAuth() ? $content : '';
    }
    
    /**
     * Render content only if user is not authenticated
     */
    public static function ifGuest($content)
    {
        return !self::isAuth() ? $content : '';
    }
    
    /**
     * Get role display name
     */
    public static function roleDisplayName($role = null)
    {
        $user = self::user();
        if (!$user) return '';
        
        $targetRole = $role ?? $user->role;
        return $user->getRoleDisplayName($targetRole);
    }
    
    /**
     * Get user's full name or display name
     */
    public static function userName()
    {
        $user = self::user();
        return $user ? $user->getDisplayName() : '';
    }
    
    /**
     * Get user's permissions
     */
    public static function userPermissions()
    {
        return self::auth()->getPermissions();
    }
    
    /**
     * Generate navigation menu based on user permissions
     */
    public static function generateNavigation()
    {
        $nav = [];
        
        // Dashboard (always available for authenticated users)
        if (self::isAuth()) {
            $nav[] = [
                'title' => 'Dashboard',
                'url' => '/dashboard',
                'icon' => 'dashboard'
            ];
        }
        
        // Admin sections
        if (self::hasPermission(Auth::PERM_SYSTEM_ADMIN)) {
            $nav[] = [
                'title' => 'System Administration',
                'url' => '/admin/system',
                'icon' => 'settings',
                'submenu' => [
                    ['title' => 'Users', 'url' => '/admin/users'],
                    ['title' => 'System Settings', 'url' => '/admin/settings'],
                    ['title' => 'Logs', 'url' => '/admin/logs']
                ]
            ];
        }
        
        if (self::hasPermission(Auth::PERM_USER_MANAGE)) {
            $nav[] = [
                'title' => 'User Management',
                'url' => '/admin/users',
                'icon' => 'users'
            ];
        }
        
        if (self::hasPermission(Auth::PERM_COMPETITION_MANAGE)) {
            $nav[] = [
                'title' => 'Competition Management',
                'url' => '/admin/competitions',
                'icon' => 'trophy',
                'submenu' => [
                    ['title' => 'Competitions', 'url' => '/admin/competitions'],
                    ['title' => 'Categories', 'url' => '/admin/categories'],
                    ['title' => 'Judges', 'url' => '/admin/judges']
                ]
            ];
        }
        
        if (self::hasPermission(Auth::PERM_SCHOOL_MANAGE)) {
            $nav[] = [
                'title' => 'School Management',
                'url' => '/coordinator/schools',
                'icon' => 'school'
            ];
        }
        
        if (self::hasPermission(Auth::PERM_TEAM_MANAGE)) {
            $nav[] = [
                'title' => 'Team Management',
                'url' => '/coach/teams',
                'icon' => 'group'
            ];
        }
        
        if (self::hasPermission(Auth::PERM_JUDGE_SCORE)) {
            $nav[] = [
                'title' => 'Judging',
                'url' => '/judge/scoring',
                'icon' => 'judge'
            ];
        }
        
        if (self::hasPermission(Auth::PERM_REPORT_VIEW)) {
            $nav[] = [
                'title' => 'Reports',
                'url' => '/reports',
                'icon' => 'chart'
            ];
        }
        
        // Profile (always available for authenticated users)
        if (self::isAuth()) {
            $nav[] = [
                'title' => 'Profile',
                'url' => '/profile',
                'icon' => 'user'
            ];
        }
        
        return $nav;
    }
    
    /**
     * Check if current page matches path
     */
    public static function isActivePage($path)
    {
        $currentPath = $_SERVER['REQUEST_URI'] ?? '/';
        $currentPath = parse_url($currentPath, PHP_URL_PATH);
        
        return $currentPath === $path || strpos($currentPath, $path . '/') === 0;
    }
    
    /**
     * Get CSS class for active navigation item
     */
    public static function activeClass($path, $activeClass = 'active', $inactiveClass = '')
    {
        return self::isActivePage($path) ? $activeClass : $inactiveClass;
    }
}