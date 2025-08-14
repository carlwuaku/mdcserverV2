# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a CodeIgniter 4 application for medical/healthcare regulatory management, serving as a backend API for license management, practitioner registration, CPD tracking, examinations, and payment processing. The application supports multiple business configurations through app-settings-*.json files.

## Essential Commands

### Development Setup
```bash
# Install dependencies
composer install

# Copy environment file and configure
cp env .env
# Edit .env to set database connection, APP_SETTINGS_FILE, and other configurations

# Run migrations (required for initial setup)
php spark migrate --all

# Generate RSA keys for JWT authentication
openssl genrsa -out certs/private_key.pem 2048
openssl rsa -in certs/private_key.pem -pubout -out certs/public_key.pem
```

### Testing
```bash
# Run all tests
composer test
# OR
./phpunit

# Run specific test suite
./phpunit tests/unit/
./phpunit tests/database/

# Run tests with coverage
./phpunit --colors --coverage-text=tests/coverage.txt --coverage-html=tests/coverage/ -d memory_limit=1024m
```

### Build and Deployment
```bash
# Production build (removes dev dependencies)
composer run build-prod
```

### Database Operations
```bash
# Run migrations
php spark migrate

# Create new migration
php spark make:migration MigrationName

# Rollback migrations
php spark migrate:rollback
```

## Architecture Overview

### Core Framework
- **CodeIgniter 4** with MVC architecture
- **Authentication**: CodeIgniter Shield with JWT tokens and 2FA support
- **Database**: MySQL with extensive migration system
- **API-First Design**: RESTful API endpoints with permission-based access control

### Key Architectural Components

#### Permission System
- Role-based access control with granular permissions
- Permissions are checked via `hasPermission` filters on routes
- Users have roles, roles have permissions
- Permission names follow pattern: `Action_Resource` (e.g., `View_License_Details`)

#### Multi-Tenant Configuration
- App settings loaded from `app-settings-*.json` files
- Environment variable `APP_SETTINGS_FILE` determines which config to use
- Different settings files support different business contexts (MDC, PC, etc.)

#### Data Models Organization
Models are organized by domain:
- `Applications/`: Application form management
- `Auth/`: Password reset functionality  
- `Cpd/`: Continuing Professional Development
- `Examinations/`: Examination management
- `Housemanship/`: Medical housemanship programs
- `Licenses/`: License and renewal management
- `Payments/`: Invoice and payment processing
- `Practitioners/`: Practitioner data and qualifications

#### Controller Structure
Controllers follow domain-driven design:
- `AuthController`: Authentication, user management, roles
- `LicensesController`: License CRUD and renewals
- `PaymentsController`: Payment processing and invoicing
- `ApplicationsController`: Application form workflows
- `ExaminationController`: Examination management
- `HousemanshipController`: Housemanship program management

### Database Design Patterns
- UUIDs used extensively for primary keys
- Soft deletes implemented across most entities
- Audit trails and timestamps maintained
- Foreign key relationships with cascade rules
- Database triggers for automatic UUID generation and data synchronization

### API Design Patterns
- RESTful endpoints grouped by domain under `/api/`
- Guest endpoints for public access under `/guest/`
- Protected endpoints require authentication and permissions
- Consistent JSON response format
- CORS support for frontend integration

## Development Guidelines

### Adding New Features
1. Create appropriate model(s) in domain-specific folders
2. Add migration(s) for database schema changes
3. Implement controller with proper permission filters
4. Add routes to `app/Config/Routes.php` with correct permissions
5. Add service layer if business logic is complex
6. Write tests for new functionality

### Permission Management
- New permissions must be added to database via migrations
- Follow naming convention: `Action_Resource_Subresource`
- Always use permission filters on protected routes
- Check existing permissions before creating new ones

### Database Migrations
- Use descriptive migration names with dates
- Include both up and down methods
- Add appropriate indexes and foreign keys
- Consider data migration needs for existing systems

### Testing Database Operations
- Configure test database in `phpunit.xml` 
- Use `DatabaseTestTrait` for database-dependent tests
- Ensure migrations run correctly in test environment
- Test both success and failure scenarios

## Key Dependencies

### Production
- `codeigniter4/framework`: Core framework
- `codeigniter4/shield`: Authentication and authorization
- `codeigniter4/settings`: Configuration management  
- `ramsey/uuid`: UUID generation
- `guzzlehttp/guzzle`: HTTP client for external APIs
- `phpoffice/phpword`: Document generation
- `endroid/qr-code` & `simplesoftwareio/simple-qrcode`: QR code generation
- `google/recaptcha`: CAPTCHA verification
- `getbrevo/brevo-php`: Email service integration

### Development
- `phpunit/phpunit`: Testing framework
- `fakerphp/faker`: Test data generation

## File Structure Conventions

### Configuration Files
- `app-settings-*.json`: Business-specific configurations
- `app/Config/`: CodeIgniter configuration files
- `.env`: Environment-specific settings

### Code Organization
- Controllers: Domain-grouped, inherit from `BaseController`
- Models: Domain-grouped in subfolders, extend `MyBaseModel`
- Services: Business logic abstraction layer
- Helpers: Utility functions and shared logic
- Filters: Request filtering (auth, permissions)

### Important Files
- `app/Config/Routes.php`: All route definitions with permission mappings
- `app/Common.php`: Global helper functions
- `app/Database/Migrations/`: All database schema changes
- `certs/`: RSA keys for JWT signing
- `writable/`: Application-generated files and logs

When working with this codebase, always consider the multi-tenant nature, maintain the permission system integrity, and follow the established domain-driven organization patterns.