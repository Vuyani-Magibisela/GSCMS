# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is the GDE SciBOTICS Competition Management System (GSCMS), a PHP-based MVC web application for managing science competitions. The system handles user authentication, school management, team registration, participant management, judging, and competition logistics.

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