# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is the GDE SciBOTICS Competition Management System (GSCMS), a PHP-based MVC web application for managing science competitions. The system handles user authentication, school management, team registration, participant management, judging, and competition logistics.

## About Competition
https://gdescibotics.co.za/ 

## Development Commands

### Database Management
- **Setup database**: `php database/console/setup.php` - Creates database, runs migrations, and seeds data
- **Run migrations**: `php database/console/migrate.php`
- **Rollback migrations**: `php database/console/migrate.php --rollback` or `php database/console/migrate.php --rollback=3`
- **Migration status**: `php database/console/migrate.php --status`
- **Reset database**: `php database/console/migrate.php --reset` (destructive!)

### Database Seeding
- **Seed development data**: `php database/console/seed.php`
- **Seed production data**: `php database/console/seed.php --env=production`
- **Run specific seeder**: `php database/console/seed.php --class=UserSeeder`
- **Fresh seed**: `php database/console/seed.php --fresh` (development only)

### Testing
- **Run tests**: `vendor/bin/phpunit` (PHPUnit is installed via Composer)

### Development Server
This is a standard PHP application that can be served via:
- Built-in PHP server: `php -S localhost:8000 -t public/`
- Apache/Nginx with document root set to `public/`

## Architecture Overview

### MVC Structure
- **Models** (`app/Models/`): Database entities extending BaseModel with ActiveRecord pattern
- **Views** (`app/Views/`): PHP templates with layout system (admin.php, app.php, public.php)
- **Controllers** (`app/Controllers/`): Handle requests, organized by user role (Admin/, Auth, Judge, etc.)

### Core Framework (`app/Core/`)
- **Router**: Custom routing system with middleware support, route groups, and named routes
- **Database**: PDO wrapper with query builder functionality
- **Migration**: Database schema versioning system
- **Seeder**: Database seeding system with environment-specific seeders
- **Auth**: Session-based authentication and authorization
- **Request/Response**: HTTP abstraction layer
- **Validator**: Input validation system
- **ErrorHandler/Logger**: Error handling and logging system

### Key Components

#### Routing System
Routes are defined in `routes/web.php` using a fluent API:
```php
$router->get('/path', 'Controller@method', 'route.name');
$router->group(['prefix' => '/admin'], function($router) { ... });
```

#### User Roles & Authentication
The system supports multiple user roles:
- **Admin**: Full system access via `/admin/*` routes
- **School Coordinator**: Manage school teams and participants
- **Team Coach**: Team-specific management
- **Judge**: Scoring and evaluation interface

#### Database Layer
- Migrations in `database/migrations/` with automatic dependency resolution
- Model factories in `database/factories/` for testing data
- Environment-specific seeders in `database/seeds/development/` and `database/seeds/production/`

### Configuration
- **App config**: `config/app.php` - Application settings
- **Database config**: `config/database.php` - Database connection settings
- **Routes config**: `config/routes.php` - Route definitions loaded by Router
- **Services config**: `config/services.php` - Service container bindings

### File Structure
- **Entry point**: `public/index.php` loads bootstrap and dispatches routes
- **Bootstrap**: `app/bootstrap.php` initializes environment, error handling, and constants
- **Static assets**: `public/css/`, `public/js/`, `public/images/`
- **File uploads**: `public/uploads/consent_forms/`, `public/uploads/team_submissions/`
- **Logs**: `storage/logs/` for application logs

### Database Schema
The system manages:
- **Users** with role-based permissions
- **Schools** and their coordinators
- **Teams** with participants and coaches
- **Competitions** with phases, categories, and schedules
- **Judging** with rubrics and scoring
- **Resources** and venue logistics

## Development Notes

### Environment Setup
1. Copy `config/database.php.example` to `config/database.php` and configure database settings
2. Run `composer install` to install dependencies
3. Run `php database/console/setup.php` to initialize the database
4. Configure web server to serve from `public/` directory

### Code Style
- PSR-4 autoloading with `App\` namespace mapping to `app/` directory
- Models use singular names (User, Team, School)
- Controllers use descriptive names with Controller suffix
- Views are organized by controller/action structure

## Production Deployment

### Deployment Preparation
The system includes deployment preparation tools and guidelines to ensure smooth production deployments.

#### Shared Hosting Deployment
For shared hosting environments (cPanel, etc.):

1. **Database Setup**:
   - Use `schema_hosting.sql` (without CREATE DATABASE commands)
   - Import `seeds_clean.sql` for essential production data
   - Update database credentials in `config/database.php`

2. **File Structure**:
   - Upload all files to web server
   - Set proper permissions: Files (644), Directories (755)
   - Ensure `storage/logs/` and `public/uploads/` are writable (777)

3. **Configuration**:
   - Update `config/app.php` with production settings:
     - Set `environment` to 'production'
     - Set `debug` to false
     - Configure proper `url` and `admin_email`
   - Ensure proper .htaccess files for URL rewriting

#### Common Deployment Issues & Solutions

1. **Session Header Errors**:
   - Session.php includes `!headers_sent()` checks
   - Bootstrap.php handles session initialization safely

2. **View Output Buffering**:
   - All view files must start with `ob_start();` after layout declaration
   - Views end with `$content = ob_get_clean(); include VIEW_PATH . '/' . $layout . '.php';`

3. **Model Static Methods**:
   - BaseModel includes both static and instance methods for `find()`
   - Use `User::find($id)` for static calls, `$user->findInstance($id)` for instance calls

4. **URL Rewriting**:
   - Root .htaccess redirects all requests to `public/` directory
   - Public .htaccess handles application routing
   - Simplified versions available for problematic hosting environments

#### Security Considerations

1. **Default Credentials**:
   - Default admin: `admin@gscms.local` / `password`
   - **IMMEDIATELY** change default password after deployment
   - Update admin email to real address

2. **File Permissions**:
   - Restrict sensitive directories via .htaccess
   - Block access to config/, app/, database/ directories
   - Enable security headers and compression

3. **Error Handling**:
   - Production mode disables detailed error display
   - Errors logged to `storage/logs/` directory
   - Monitor error logs regularly

#### Deployment Checklist

- [ ] Upload all application files to web server
- [ ] Set correct file permissions (644/755/777)
- [ ] Import database schema and production seeds
- [ ] Update configuration files with production settings
- [ ] Test all major functionality (login, dashboard, auth flows)
- [ ] Change default admin password
- [ ] Configure proper error logging
- [ ] Verify .htaccess URL rewriting works
- [ ] Test on multiple devices/browsers
- [ ] Set up regular database backups

#### Troubleshooting

1. **500 Internal Server Error**:
   - Check error logs in hosting control panel
   - Verify file permissions and .htaccess syntax
   - Ensure all dependencies are uploaded (vendor/ directory)

2. **Database Connection Issues**:
   - Verify credentials in `config/database.php`
   - Check database user permissions
   - Ensure database exists and is accessible

3. **URL Rewriting Problems**:
   - Verify mod_rewrite is enabled
   - Test with simplified .htaccess files
   - Check hosting provider documentation

4. **View Rendering Errors**:
   - Ensure all view files have proper output buffering
   - Check for missing layout files
   - Verify VIEW_PATH constant is defined

### Maintenance

1. **Regular Tasks**:
   - Monitor error logs in `storage/logs/`
   - Backup database regularly
   - Update dependencies with `composer update`
   - Clear old log files to save space

2. **Updates**:
   - Test changes in development environment first
   - Use migration system for database changes
   - Keep staging environment for pre-production testing

3. **Monitoring**:
   - Track user registrations and submissions
   - Monitor server resources and performance
   - Review security logs for suspicious activity

## Frontend Development Guidelines

### CSS Framework Priority
1. **Primary**: Pure CSS - Use custom CSS for styling whenever possible
2. **Secondary**: Tailwind CSS - Only when utility classes provide significant benefit
3. **Avoid**: Bootstrap - Do not use Bootstrap classes or components

### Animation Libraries
- **Primary**: GSAP (GreenSock Animation Platform) - For all animations and transitions
- **Secondary**: Pure JavaScript - For simple interactions without animation
- **Avoid**: CSS animations for complex sequences, jQuery animations

### JavaScript Guidelines
- Use vanilla JavaScript as much as possible
- GSAP for animations and timeline-based interactions
- Minimize external dependencies

## Common Issues & Solutions

### Registration System Issues

#### Email Sending "Headers Already Sent" Error
**Problem**: Registration fails after email sending with "Cannot modify header information - headers already sent"
**Root Cause**: PHPMailer debug output being sent to browser before redirect headers
**Solution**:
```php
// In app/Core/Mail.php - Disable SMTP debug completely
$this->mailer->SMTPDebug = SMTP::DEBUG_OFF;

// Add output buffering around email sending
ob_start();
$result = $this->mailer->send();
ob_end_clean();
```

#### Array to String Conversion Error
**Problem**: "Array to string conversion" error when displaying validation errors
**Root Cause**: Multi-dimensional validation error arrays being passed to `implode()`
**Solution**:
```php
// In AuthController.php - Flatten validation errors properly
if (!$validation['valid']) {
    $errorMessages = [];
    foreach ($validation['errors'] as $field => $fieldErrors) {
        if (is_array($fieldErrors)) {
            $errorMessages = array_merge($errorMessages, $fieldErrors);
        } else {
            $errorMessages[] = $fieldErrors;
        }
    }
    throw new Exception('Validation failed: ' . implode(', ', $errorMessages));
}
```

### AJAX Issues

#### JSON Parse Error in User Management
**Problem**: "JSON.parse: unexpected character at line 1 column 1" when updating user status
**Root Causes**: 
1. Route ordering conflict (parameterized routes matching before specific routes)
2. AJAX requests not sending session cookies
3. PHP not handling JSON request bodies properly

**Solutions**:
```php
// 1. Route Ordering - In routes/web.php, place specific routes BEFORE parameterized ones:
$router->get('/admin/users', 'Controller@index');
$router->post('/admin/users/update-status', 'Controller@updateStatus'); // Specific route first
$router->get('/admin/users/{id}', 'Controller@show'); // Parameterized route after

// 2. AJAX Credentials - In JavaScript, add credentials to fetch requests:
fetch('/admin/users/update-status', {
    method: 'POST',
    credentials: 'same-origin', // Important for session cookies
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(data)
})

// 3. JSON Input Handling - In controller methods, handle both form and JSON input:
public function updateStatus() {
    header('Content-Type: application/json');
    
    // Handle JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    if ($input === null) {
        // Fallback to form input
        $userId = $this->input('user_id');
        $status = $this->input('status');
    } else {
        $userId = $input['user_id'] ?? null;
        $status = $input['status'] ?? null;
    }
    
    // Always use direct json_encode and exit
    echo json_encode(['success' => true]);
    exit;
}
```

### Session Management Issues

#### Auth Class Infinite Loops
**Problem**: `Auth::getInstance()` causing hanging or infinite loops
**Solution**: Use manual session checks instead:
```php
// Instead of: Auth::getInstance()->check()
// Use manual session validation:
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
    return $this->redirect('/auth/login');
}
```

### Route Configuration Best Practices

#### Route Ordering Rules
1. **Specific routes first**: `/admin/users/update-status` 
2. **Parameterized routes last**: `/admin/users/{id}`
3. **Static routes before dynamic**: `/admin/dashboard` before `/admin/{section}`

#### Middleware Bypass
For problematic middleware, create temporary routes outside middleware groups:
```php
// Bypass middleware for specific functionality
$router->get('/admin/users', 'UserController@index', 'temp.route.name');
```

### Error Handling Patterns

#### Robust Redirect Method
```php
protected function redirect($url, $statusCode = 302) {
    if (headers_sent($file, $line)) {
        // JavaScript fallback for when headers already sent
        echo "<script>window.location.href = '{$url}';</script>";
        echo "<noscript><meta http-equiv='refresh' content='0; url={$url}'></noscript>";
    } else {
        header("Location: {$url}", true, $statusCode);
    }
    exit;
}
```

### Development Debugging

#### AJAX Response Debugging
```javascript
fetch(url, options)
.then(response => {
    console.log('Response status:', response.status);
    return response.text().then(text => {
        console.log('Response text:', text);
        try {
            return JSON.parse(text);
        } catch (e) {
            throw new Error('Server returned non-JSON: ' + text.substring(0, 100));
        }
    });
})
```

#### Rate Limiting During Development
Temporarily disable rate limiting for testing:
```php
// Comment out during development/testing
// $this->rateLimit->enforceRegistration();
```

### Email System Configuration

#### Production Email Setup
- Always disable SMTP debug in production: `SMTPDebug = SMTP::DEBUG_OFF`
- Use output buffering to prevent header conflicts
- Test email functionality thoroughly before deployment

## Testing Checklist

### User Management System
- [ ] User registration with email verification
- [ ] User status updates (activate/deactivate) 
- [ ] User editing and deletion
- [ ] Session persistence across AJAX requests
- [ ] Proper error message display
- [ ] Route resolution for specific and parameterized URLs

### Email System
- [ ] Registration emails sent successfully
- [ ] No header conflicts during redirect
- [ ] Email verification links working
- [ ] SMTP configuration working in production

### AJAX Functionality  
- [ ] Status updates working without page refresh
- [ ] Proper JSON responses
- [ ] Error handling for failed requests
- [ ] Session authentication maintained

## Routing System & Admin Page Development

### Overview

The GSCMS uses a custom routing system with middleware support, route groups, and role-based access control. Understanding this system is critical for creating new admin pages and avoiding common development issues.

### Routing Architecture

#### Core Components
- **Router Class**: `app/Core/Router.php` - Custom router with middleware and grouping support
- **Route Definitions**: `routes/web.php` - All application routes defined here  
- **Middleware System**: Role-based access control with session validation
- **View System**: Layout-based templates with consistent admin interface

#### Route Definition Patterns

**Individual Routes:**
```php
$router->get('/path', 'Controller@method', 'route.name');
$router->post('/path', 'Controller@method', 'route.name');
$router->put('/path/{id}', 'Controller@method', 'route.name');
$router->delete('/path/{id}', 'Controller@method', 'route.name');
```

**Route Groups (Recommended for Admin):**
```php
$router->group([
    'middleware' => 'role:super_admin,competition_admin', 
    'prefix' => 'admin', 
    'namespace' => 'Admin'
], function($router) {
    $router->get('/participants', 'ParticipantManagementController@index', 'admin.participants');
    $router->get('/participants/create', 'ParticipantManagementController@create', 'admin.participants.create');
    $router->post('/participants', 'ParticipantManagementController@store', 'admin.participants.store');
    $router->get('/participants/{id}', 'ParticipantManagementController@show', 'admin.participants.show');
    $router->get('/participants/{id}/edit', 'ParticipantManagementController@edit', 'admin.participants.edit');
    $router->put('/participants/{id}', 'ParticipantManagementController@update', 'admin.participants.update');
    $router->delete('/participants/{id}', 'ParticipantManagementController@destroy', 'admin.participants.destroy');
});
```

### Admin Route Group Structure

#### Group Configuration
- **Middleware**: `role:super_admin,competition_admin` - Restricts access to admin roles
- **Prefix**: `admin` - All URLs become `/admin/...`  
- **Namespace**: `Admin` - Maps to `App\Controllers\Admin\` directory

#### Controller Resolution
Inside the admin group:
- `'DashboardController@index'` → `App\Controllers\Admin\DashboardController@index`
- `'ParticipantManagementController@index'` → `App\Controllers\Admin\ParticipantManagementController@index`
- `'SchoolManagementController@index'` → `App\Controllers\Admin\SchoolManagementController@index`

#### URL Generation
With `prefix => 'admin'`:
- `/participants` becomes `/admin/participants`
- `/schools/create` becomes `/admin/schools/create`
- `/teams/{id}/edit` becomes `/admin/teams/{id}/edit`

### View Layout System

#### Critical Layout Configuration

**ALL admin views must use the correct layout path:**
```php
<?php 
$layout = 'layouts/admin';  // ✅ CORRECT
ob_start(); 
?>
<!-- View content -->
<?php 
$content = ob_get_clean(); 
include VIEW_PATH . '/' . $layout . '.php'; 
?>
```

**Common Mistake (Causes File Not Found Error):**
```php
<?php 
$layout = 'admin';  // ❌ WRONG - Looks for /app/Views/admin.php
?>
```

#### Layout File Location
- **Correct Layout**: `/app/Views/layouts/admin.php` 
- **Working Examples**: Dashboard, Schools, Teams all use `layouts/admin`
- **View Files**: Located in `/app/Views/admin/[feature]/` directories

### Best Practices for Creating New Admin Pages

#### 1. Route Definition Checklist

**Add routes inside the admin group:**
```php
$router->group(['middleware' => 'role:super_admin,competition_admin', 'prefix' => 'admin', 'namespace' => 'Admin'], function($router) {
    // Your new routes here
    $router->get('/newfeature', 'NewFeatureController@index', 'admin.newfeature');
    $router->get('/newfeature/create', 'NewFeatureController@create', 'admin.newfeature.create');
    $router->post('/newfeature', 'NewFeatureController@store', 'admin.newfeature.store');
    $router->get('/newfeature/{id}', 'NewFeatureController@show', 'admin.newfeature.show');
    $router->get('/newfeature/{id}/edit', 'NewFeatureController@edit', 'admin.newfeature.edit');
    $router->put('/newfeature/{id}', 'NewFeatureController@update', 'admin.newfeature.update');
    $router->delete('/newfeature/{id}', 'NewFeatureController@destroy', 'admin.newfeature.destroy');
});
```

#### 2. Controller Creation

**Controller Location:** `/app/Controllers/Admin/NewFeatureController.php`

**Controller Template:**
```php
<?php
namespace App\Controllers\Admin;

use App\Controllers\BaseController;

class NewFeatureController extends BaseController
{
    public function index()
    {
        // Verify database column names match your queries
        $data = $this->db->query("SELECT * FROM table_name");
        
        return $this->view('admin/newfeature/index', [
            'data' => $data,
            'title' => 'New Feature Management',
            'pageTitle' => 'New Feature',
            'pageSubtitle' => 'Manage new features'
        ]);
    }
}
```

#### 3. View Creation

**View Location:** `/app/Views/admin/newfeature/index.php`

**View Template:**
```php
<?php 
$layout = 'layouts/admin';  // ✅ CRITICAL - Use correct layout path
ob_start(); 
?>

<div class="admin-newfeature">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1"><?= htmlspecialchars($pageTitle ?? 'New Feature') ?></h1>
            <p class="text-muted"><?= htmlspecialchars($pageSubtitle ?? '') ?></p>
        </div>
    </div>
    
    <!-- Your content here -->
</div>

<?php 
$content = ob_get_clean(); 
include VIEW_PATH . '/' . $layout . '.php'; 
?>
```

#### 4. Database Query Best Practices

**Always verify column names:**
```php
// ❌ WRONG - Assuming column names
$query = "SELECT s.school_name FROM schools s";

// ✅ CORRECT - Check actual schema first
$query = "SELECT s.name as school_name FROM schools s";  // schools.name is the actual column
```

**Check table existence:**
```php
try {
    $data = $this->db->query("SELECT * FROM new_table WHERE status = ?", ['active']);
} catch (\Exception $e) {
    error_log("Table not found: " . $e->getMessage());
    $data = []; // Graceful fallback
}
```

### Route Ordering Best Practices

#### Specific Routes Before Parameterized

```php
// ✅ CORRECT ORDER
$router->get('/admin/users', 'UserController@index');
$router->get('/admin/users/create', 'UserController@create');         // Specific
$router->post('/admin/users/update-status', 'UserController@updateStatus'); // Specific  
$router->get('/admin/users/{id}', 'UserController@show');             // Parameterized
$router->get('/admin/users/{id}/edit', 'UserController@edit');         // Parameterized

// ❌ WRONG ORDER - Parameterized routes will catch everything
$router->get('/admin/users/{id}', 'UserController@show');             // This catches /users/create!
$router->get('/admin/users/create', 'UserController@create');         // Never reached
```

#### HTTP Method Conventions

- **GET**: Display/list pages (`index`, `show`, `edit` forms)
- **POST**: Create new resources (`store`), form submissions
- **PUT**: Update existing resources (`update`)  
- **DELETE**: Remove resources (`destroy`)

### Common Issues & Solutions

#### Issue 1: File Not Found - Layout Missing

**Error:** `include(/app/Views/admin.php): Failed to open stream: No such file or directory`

**Root Cause:** View using wrong layout path
```php
$layout = 'admin';  // ❌ Looks for /app/Views/admin.php
```

**Solution:** Use correct layout path
```php
$layout = 'layouts/admin';  // ✅ Looks for /app/Views/layouts/admin.php
```

#### Issue 2: Database Column Not Found

**Error:** `Unknown column 's.school_name' in 'field list'`

**Root Cause:** Database schema mismatch - assuming wrong column name

**Solution:** Verify actual column names
```bash
mysql> DESCRIBE schools;
# Check actual column names in database
```

```php
// Update queries to use correct column names
$query = "SELECT s.name as school_name FROM schools s";  // Use actual column 's.name'
```

#### Issue 3: Route Not Found/404 Error

**Possible Causes:**
1. Route not defined in correct group
2. Controller namespace mismatch  
3. Route ordering issue (parameterized route catching specific route)

**Solutions:**
1. Verify route is in admin group with correct middleware
2. Check controller is in `App\Controllers\Admin\` namespace
3. Move specific routes before parameterized routes

### Middleware Bypass for Debugging

**For testing routes without authentication:**
```php
// Temporary bypass route outside middleware groups
$router->get('/test-newfeature', 'Admin\\NewFeatureController@index', 'test.newfeature');
```

**Full namespace bypass:**
```php
$router->get('/debug/users', 'App\\Controllers\\Admin\\UserController@index', 'debug.users');
```

### Route Naming Conventions

**Standard Pattern:**
- Index: `admin.feature`  
- Create: `admin.feature.create`
- Store: `admin.feature.store`
- Show: `admin.feature.show` 
- Edit: `admin.feature.edit`
- Update: `admin.feature.update`
- Destroy: `admin.feature.destroy`

**Examples:**
- `admin.participants`, `admin.participants.create`, `admin.participants.show`
- `admin.schools`, `admin.schools.create`, `admin.schools.edit`
- `admin.teams`, `admin.teams.show`, `admin.teams.update`

### Creating New Admin Pages - Quick Checklist

- [ ] **Route**: Added to admin group in `routes/web.php`
- [ ] **Controller**: Created in `app/Controllers/Admin/` with correct namespace
- [ ] **View**: Created with `$layout = 'layouts/admin';` 
- [ ] **Database**: Verified actual column names match queries
- [ ] **Testing**: Tested route resolution and page loading
- [ ] **Layout**: Confirmed consistent styling with other admin pages
- [ ] **Route Names**: Follow `admin.feature.action` convention
- [ ] **HTTP Methods**: Use appropriate methods (GET/POST/PUT/DELETE)

### Working Examples to Reference

**Confirmed Working Admin Pages:**
- **Dashboard**: `/admin/dashboard` → `DashboardController@index` → `admin/dashboard.php` → `layouts/admin`
- **Schools**: `/admin/schools` → `SchoolManagementController@index` → `admin/schools/index.php` → `layouts/admin`  
- **Teams**: `/admin/teams` → `TeamManagementController@index` → `admin/teams/index.php` → `layouts/admin`
- **Participants**: `/admin/participants` → `ParticipantManagementController@index` → `admin/participants/index.php` → `layouts/admin`

**Use these as templates when creating new admin functionality.**