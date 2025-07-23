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