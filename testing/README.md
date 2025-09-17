# GSCMS Testing Directory

This directory contains all test files, debug tools, and testing utilities for the GDE SciBOTICS Competition Management System.

## Directory Structure

### `/development-tests/`
**Active development test files** - Currently used for testing new features and functionality.

- `test_admin_scoring.php` - Tests for admin scoring system functionality
- `test_registration_system.php` - Main registration system tests
- `test_registration_quick.php` - Quick registration flow tests
- `test_category_system.php` - Competition category system tests
- `simple_category_test.php` - Simplified category testing
- `test_enhanced_contacts.php` - Enhanced contact management tests
- `test_enhanced_view.php` - Enhanced view functionality tests

### `/debug-tools/`
**Debug utilities and tools** - Helper scripts for debugging and troubleshooting.

- `debug-auth.php` - Authentication debugging
- `debug-judging-route.php` - Judging system route debugging
- `debug-session.php` - Session management debugging
- `debug_baseurl.php` - Base URL configuration debugging
- `debug_button.html` - Frontend button debugging
- `debug_scoring.php` - Scoring system debugging
- `debug_view_path.php` - View path resolution debugging

### `/archived-tests/`
**Legacy and completed test files** - Old test files kept for reference.

- Contact system tests: `test_contact_*.php`
- UI fix tests: `test_button_fix.php`, `test_toggle_fix.php`
- URL testing: `test_form_urls.php`, `test_hardcoded_urls.php`
- Frontend tests: `test_*.html`
- Network tests: `test-websocket-connection.php`, `test-route-resolution.php`
- View tests: `test_view_only.php`, `test-judge-view.php`

### `/test-data/`
**Test data and setup scripts** - Files for setting up test environments and data.

- `setup_test_data.php` - Main test data setup script
- `create_test_teams.php` - Creates test team data
- `assign_coaches_for_testing.php` - Assigns test coaches to teams
- `test_database_setup.sql` - Database setup for testing

## Usage Guidelines

### Running Development Tests
```bash
# Navigate to the development tests directory
cd /var/www/html/GSCMS/testing/development-tests/

# Run specific test files
php test_registration_system.php
php test_admin_scoring.php
```

### Using Debug Tools
```bash
# Navigate to debug tools directory
cd /var/www/html/GSCMS/testing/debug-tools/

# Run debug scripts
php debug-auth.php
php debug_scoring.php
```

### Setting Up Test Data
```bash
# Navigate to test data directory
cd /var/www/html/GSCMS/testing/test-data/

# Set up complete test environment
php setup_test_data.php

# Create specific test data
php create_test_teams.php
```

## Best Practices

1. **Development Tests** - Place new test files in `development-tests/` while actively developing
2. **Archive When Done** - Move completed test files to `archived-tests/` when no longer needed
3. **Document Changes** - Update this README when adding new test categories or tools
4. **Clean Naming** - Use descriptive names that clearly indicate what is being tested
5. **Environment Safety** - Ensure test files don't interfere with production data

## Test Environment Setup

Before running tests, ensure:

1. Database connection is configured for testing environment
2. Test data is properly set up using scripts in `/test-data/`
3. Appropriate permissions are set for test file execution
4. Any required dependencies are installed

## Contributing

When adding new test files:

1. Place in appropriate directory based on purpose
2. Use clear, descriptive file names
3. Include comments explaining test purpose and usage
4. Update this README if creating new test categories
5. Consider creating corresponding test data setup if needed

## Maintenance

Regular maintenance tasks:
- Review and archive completed test files
- Update test data scripts as schema changes
- Clean up obsolete debug tools
- Ensure all tests remain functional with system updates