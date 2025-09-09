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