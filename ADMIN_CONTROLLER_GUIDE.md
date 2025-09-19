# Admin Controller Development Guide

## Common Routing Issues & Solutions

This guide provides solutions to common routing and controller issues encountered when developing admin controllers in the GSCMS application.

## Issue #1: "Call to undefined method requireAdmin()"

### Problem
```
HTTP 500 Error
Call to undefined method App\Controllers\Admin\CompetitionController::requireAdmin()
```

### Root Cause
Admin controllers calling `$this->requireAdmin()` in constructor, but this method was missing from `BaseController`.

### Solution
The `requireAdmin()` method has been added to `BaseController.php` (lines 307-312):

```php
/**
 * Require admin access (super_admin or competition_admin)
 */
protected function requireAdmin()
{
    $this->requireAnyRole(['super_admin', 'competition_admin']);
}
```

### Prevention
Always verify that methods called in constructors exist in the parent class before using them.

## Issue #2: "Call to private Database::__construct()"

### Problem
```
HTTP 500 Error
Call to private App\Core\Database::__construct() from scope App\Core\PhaseManager
```

### Root Cause
Core classes incorrectly instantiating Database directly with `new Database()`, but Database uses singleton pattern with private constructor.

### Solution
Replace `new Database()` with `Database::getInstance()` in all Core classes:

**❌ WRONG:**
```php
$this->db = new Database(); // Constructor is private!
```

**✅ CORRECT:**
```php
$this->db = Database::getInstance(); // Use singleton pattern
```

### Files Fixed
- `app/Core/PhaseManager.php:19`
- `app/Core/PilotPhaseProgression.php:15`
- `app/Core/FullSystemPhaseProgression.php:15`

### Prevention
Always use `Database::getInstance()` instead of `new Database()` throughout the application.

## Issue #3: "CompetitionSetupController redirecting to dashboard"

### Problem
Accessing `/admin/competition-setup` redirects to dashboard instead of showing the competition setup page.

### Root Causes
Multiple issues in the `CompetitionSetupController`:
1. **Missing `render()` method** - Controller called `$this->render()` but this method doesn't exist in `BaseController`
2. **Incorrect request handling** - Controller used `$this->request->getMethod()` but `$this->request` property doesn't exist
3. **Missing view files** - The view directory `admin/competition-setup/` didn't exist

### Solutions Applied

**1. Fixed render() method calls:**
```php
// ❌ WRONG - render() method doesn't exist
return $this->render('admin/competition-setup/index', $data);

// ✅ CORRECT - Use view() method from BaseController
return $this->view('admin/competition-setup/index', $data);
```

**2. Fixed request method checking:**
```php
// ❌ WRONG - $this->request property doesn't exist
if ($this->request->getMethod() === 'POST') {

// ✅ CORRECT - Use $_SERVER superglobal
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
```

**3. Created missing view files:**
- Created `/app/Views/admin/competition-setup/` directory
- Created `index.php` view with proper admin layout structure

### Files Fixed
- `app/Controllers/Admin/CompetitionSetupController.php` - Fixed method calls and request handling
- `app/Views/admin/competition-setup/index.php` - Created missing view file

### Prevention
1. Always use `$this->view()` instead of `$this->render()` in controllers
2. Use `$_SERVER['REQUEST_METHOD']` for HTTP method checking
3. Ensure view directories and files exist before using them in controllers
4. Test controller routes after creating new functionality

## Issue #4: "Call to undefined method Session::setFlash()"

### Problem
```
HTTP 500 Error
Call to undefined method App\Core\Session::setFlash()
```

### Root Cause
Multiple admin controllers were calling `$this->session->setFlash()` but this method doesn't exist in the Session class.

### Solution
The Session class only has `flash()` method, and BaseController provides `$this->flash()` wrapper method.

**❌ WRONG:**
```php
$this->session->setFlash('error', 'Message');  // setFlash() doesn't exist
$this->setFlash('error', 'Message');           // setFlash() doesn't exist
```

**✅ CORRECT:**
```php
$this->flash('error', 'Message');              // Use BaseController method
```

### Files Fixed
- `app/Controllers/Admin/CompetitionController.php` - 21 instances
- `app/Controllers/Admin/ScoringController.php`
- `app/Controllers/Admin/LiveScoringController.php`
- `app/Controllers/Admin/TournamentController.php`
- `app/Controllers/Admin/CategoryManagerController.php`
- `app/Controllers/Admin/PhaseSchedulerController.php`
- `app/Controllers/Admin/CompetitionWizardController.php`
- `app/Controllers/Admin/_AdminControllerTemplate.php`

### Prevention
Always use `$this->flash('type', 'message')` for flash messages in controllers.

## Issue #5: "URL Generation - Missing Base Path"

### Problem
```
Not Found
The requested URL was not found on this server.
```
When clicking admin navigation links like "Create Competition", users get redirected to URLs missing the application base path (e.g., `http://localhost/admin/competitions` instead of `http://localhost/GSCMS/public/admin/competitions`).

### Root Cause
The `ViewHelpers::url()` method had different URL generation logic than `BaseController::baseUrl()`, causing inconsistent URLs between controllers and views.

### Solution
Updated `ViewHelpers::url()` to use the same robust URL generation logic as `BaseController::baseUrl()`.

**The fixed method now properly handles:**
- Protocol detection (http/https)
- Host detection with CLI fallback
- Script path detection and `/public` handling
- Consistent URL structure across the application

### Files Fixed
- `app/Core/ViewHelpers.php` - Updated `url()` method to match BaseController logic

### Prevention
The URL generation is now consistent across both controllers and views. All `url()` calls in templates will generate proper URLs with the correct base path.

## Issue #6: "Redirect URLs Missing Base Path"

### Problem
After fixing view URL generation, redirects in controllers were still causing "Not Found" errors because the `redirect()` method wasn't using proper URL generation.

### Root Cause
The `BaseController::redirect()` method used raw URLs without base path generation:
```php
// Controller calls:
$this->redirect('/admin/competitions');

// Redirect method sent:
header("Location: /admin/competitions");  // ❌ Missing base path
```

### Solution
Updated `BaseController::redirect()` method to automatically generate proper URLs using the existing `baseUrl()` method for relative paths.

**Before:**
```php
header("Location: {$url}", true, $statusCode);  // Raw URL
```

**After:**
```php
// Generate full URL if it's a relative path
if (strpos($url, 'http') !== 0) {
    $url = $this->baseUrl($url);
}
header("Location: {$url}", true, $statusCode);  // Full URL with base path
```

### Files Fixed
- `app/Controllers/BaseController.php` - Updated `redirect()` method to use `baseUrl()`

### Prevention
All `$this->redirect('/path')` calls in controllers now automatically generate proper full URLs with base path.

## Admin Controller Best Practices

### 1. Proper Constructor Pattern

**✅ CORRECT - Use this pattern for all admin controllers:**

```php
<?php
namespace App\Controllers\Admin;

use App\Controllers\BaseController;

class YourAdminController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireAdmin(); // ✅ Now available in BaseController
    }
}
```

**❌ WRONG - Don't use undefined methods:**

```php
public function __construct()
{
    parent::__construct();
    $this->someUndefinedMethod(); // ❌ Will cause 500 error
}
```

### 2. Available Authentication Methods in BaseController

Use these methods for access control:

```php
// Basic authentication
$this->requireAuth()                    // Require any authenticated user
$this->requireAdmin()                   // Require super_admin OR competition_admin
$this->requireRole('super_admin')       // Require specific role
$this->requireAnyRole(['role1', 'role2']) // Require any of specified roles

// Permission checks (boolean)
$this->isAuthenticated()               // Check if user is logged in
$this->hasRole('super_admin')          // Check if user has specific role
$this->hasAnyRole(['role1', 'role2'])  // Check if user has any of roles
$this->isAdmin()                       // Check if user is admin (any admin role)
```

### 3. Route Definition Requirements

**Admin routes MUST be defined within the admin route group:**

```php
// In routes/web.php
$router->group(['middleware' => 'role:super_admin,competition_admin', 'prefix' => 'admin', 'namespace' => 'Admin'], function($router) {
    $router->get('/yourfeature', 'YourFeatureController@index', 'admin.yourfeature');
    $router->get('/yourfeature/create', 'YourFeatureController@create', 'admin.yourfeature.create');
    $router->post('/yourfeature', 'YourFeatureController@store', 'admin.yourfeature.store');
    $router->get('/yourfeature/{id}', 'YourFeatureController@show', 'admin.yourfeature.show');
    $router->get('/yourfeature/{id}/edit', 'YourFeatureController@edit', 'admin.yourfeature.edit');
    $router->put('/yourfeature/{id}', 'YourFeatureController@update', 'admin.yourfeature.update');
    $router->delete('/yourfeature/{id}', 'YourFeatureController@destroy', 'admin.yourfeature.destroy');
});
```

**Key requirements:**
- `namespace => 'Admin'` - Maps to `App\Controllers\Admin\` directory
- `prefix => 'admin'` - URLs become `/admin/yourfeature`
- Middleware restricts access to admin roles only

### 4. View Layout Requirements

**ALL admin views MUST use the correct layout path:**

```php
<?php
$layout = 'layouts/admin';  // ✅ CORRECT - Points to /app/Views/layouts/admin.php
ob_start();
?>

<!-- Your admin page content here -->

<?php
$content = ob_get_clean();
include VIEW_PATH . '/' . $layout . '.php';
?>
```

**❌ WRONG - This will cause "File not found" errors:**

```php
<?php
$layout = 'admin';  // ❌ WRONG - Looks for /app/Views/admin.php
?>
```

### 5. Database Query Best Practices

**Always verify column names match your database schema:**

```php
// ❌ WRONG - Assuming column names without verification
$query = "SELECT s.school_name FROM schools s";

// ✅ CORRECT - Use actual column names (verify first with DESCRIBE table)
$query = "SELECT s.name as school_name FROM schools s";  // 'name' is the actual column
```

**Use proper error handling:**

```php
try {
    $data = $this->db->query("SELECT * FROM your_table WHERE status = ?", ['active']);
} catch (\Exception $e) {
    error_log("Database error: " . $e->getMessage());
    $this->session->setFlash('error', 'Error loading data');
    return $this->redirect('/admin/dashboard');
}
```

## Quick Debugging Checklist

When you encounter admin controller issues:

- [ ] **Route Defined?** - Check if route is in admin group in `routes/web.php`
- [ ] **Controller Exists?** - Verify controller file exists in `app/Controllers/Admin/`
- [ ] **Namespace Correct?** - Controller should have `namespace App\Controllers\Admin;`
- [ ] **Constructor Valid?** - Only call methods that exist in `BaseController`
- [ ] **View Layout Correct?** - Use `$layout = 'layouts/admin';`
- [ ] **Database Columns?** - Verify column names match actual schema

## Testing Admin Controllers

**Before committing new admin controllers:**

1. **Test the route directly:** Visit `http://localhost/GSCMS/public/admin/yourfeature`
2. **Check error logs:** Look in browser console and server error logs
3. **Verify authentication:** Ensure admin user can access the page
4. **Test CRUD operations:** Create, read, update, delete functionality
5. **Validate error handling:** Test with invalid data and missing parameters

## Common Error Patterns & Solutions

| Error | Root Cause | Solution |
|-------|-----------|----------|
| `Call to undefined method requireAdmin()` | Method doesn't exist in BaseController | Use `$this->requireAdmin()` (now available) |
| `File not found: layouts/admin.php` | Wrong layout path in view | Use `$layout = 'layouts/admin';` |
| `404 Not Found` | Route not defined or wrong group | Add route to admin group in `routes/web.php` |
| `Unknown column in field list` | Database column name mismatch | Verify actual column names with `DESCRIBE table` |
| `Headers already sent` | Output before redirect | Use proper error handling and output buffering |

## Admin Controller Template

See `app/Controllers/Admin/_AdminControllerTemplate.php` for a complete template you can copy for new admin controllers.

---

**Remember:** When in doubt, refer to working examples like `DashboardController`, `SchoolManagementController`, or `TeamManagementController` in the `app/Controllers/Admin/` directory.