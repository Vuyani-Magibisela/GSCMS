User Roles & Permissions - RBAC System
1. Role-Based Access Control (RBAC) Implementation

Create app/Core/Auth.php class for authentication and authorization
Define role hierarchy and permission levels in constants
Implement role checking methods: hasRole(), hasPermission(), canAccess()
Add role inheritance (e.g., Super Admin has all permissions)
Create permission matrix mapping roles to specific actions

2. Define User Roles System
// Role Constants and Permissions:
SUPER_ADMIN         → Full system access, user management, system config
COMPETITION_ADMIN   → Competition management, judge assignment, reporting
SCHOOL_COORDINATOR  → School teams, participant registration, documents
TEAM_COACH         → Team details, participant info (limited scope)
JUDGE              → Scoring interface, assigned competitions only
PUBLIC_VIEWER      → Read-only access to public information
PARTICIPANT        → Profile management, view team info
3. Middleware for Route Protection

Create app/Core/Middleware/ directory structure
Build AuthMiddleware.php → Check if user is logged in
Build RoleMiddleware.php → Verify user has required role
Build PermissionMiddleware.php → Check specific permissions
Integrate middleware with Router system for automatic protection
Add middleware groups: auth, admin, judge, etc.

4. Permission Checking System

Create permission constants for each system action
Build permission checking in BaseController and models
Implement resource-based permissions (own school/team only)
Add method-level permission decorators
Create view helpers to show/hide UI elements based on permissions

Implementation Structure:
// 1. Role Constants & Definitions
app/Core/Auth.php → Role constants and hierarchy

// 2. Middleware Classes  
app/Core/Middleware/AuthMiddleware.php → Login requirement
app/Core/Middleware/RoleMiddleware.php → Role verification
app/Core/Middleware/PermissionMiddleware.php → Action permissions

// 3. Permission Integration
BaseController → Permission checking methods
User Model → Role and permission methods
Router → Middleware assignment to routes

// 4. Database Structure
users table → role field (enum)
permissions table → Optional granular permissions
role_permissions table → Optional role-permission mapping

Key Features to Implement:

Role Hierarchy: Super Admin > Competition Admin > School Coordinator, etc.
Resource Ownership: Users can only access their own school/team data
Dynamic Permissions: Check permissions in real-time, not just at login
Middleware Chaining: Multiple middleware on routes (auth, role:admin)
View Authorization: Template helpers to show/hide content by role

Route Protection Examples:
// In routes/web.php:
$router->group(['middleware' => 'auth'], function($router) {
    $router->group(['prefix' => 'admin', 'middleware' => 'role:super_admin'], function($router) {
        // Admin-only routes
    });
    $router->group(['prefix' => 'judge', 'middleware' => 'role:judge'], function($router) {
        // Judge-only routes  
    });
});

Implementation Order:

Auth Class → Core role/permission logic
Middleware Classes → Route protection mechanisms
Router Integration → Middleware attachment system
Controller Helpers → Permission checking in actions
View Helpers → UI element authorization
Testing → Verify all role restrictions work
