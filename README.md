# Inventory SaaS

A modern, multi-tenant inventory management system built with PHP and Slim Framework.

The application is designed to help businesses manage users, roles, permissions, products, suppliers, orders, reports, and other inventory-related operations from a centralized web-based platform.

## Features

### Multi-Tenant Business Management

* Register and manage businesses
* Business-specific data isolation
* Business profile management
* Business logo upload
* Business activation/deactivation
* System-level business administration

### Authentication & Users

* Secure user authentication
* Username/email login
* Password hashing using PHP password hashing
* CSRF protection
* User activation and deactivation
* System administrator accounts
* Business users
* User profile information

### Roles & Permissions

* Role-based access control
* Business-specific roles
* System roles
* Permission-based authorization
* Assign permissions to roles
* View-only and management permissions
* Permission-aware navigation and actions

### Audit Logging

The system records important activities such as:

* Login
* Failed login attempts
* Logout
* Create operations
* Update operations
* Delete operations
* User activation/deactivation
* Business management
* Role and permission changes

Audit records can include:

* User
* Business
* Action
* Module
* Table
* Record ID
* Record UUID
* Old values
* New values
* IP address
* User agent
* Timestamp

### Inventory Management

Planned and active inventory modules include:

* Products
* Product categories
* Suppliers
* Customers
* Orders
* Stock management
* Reports

## Technology Stack

| Technology      | Purpose                    |
| --------------- | -------------------------- |
| PHP             | Backend application        |
| Slim Framework  | HTTP/application framework |
| PHP-DI          | Dependency injection       |
| PDO             | Database access            |
| MariaDB/MySQL   | Database                   |
| Bootstrap 5.3   | UI framework               |
| Bootstrap Icons | Icons                      |
| DataTables      | Interactive tables         |
| Dotenv          | Environment configuration  |
| Ramsey UUID     | UUID generation            |

## Requirements

Before installing the application, make sure the development environment includes:

* PHP 8.2+
* Composer
* MariaDB 10+
* PHP PDO extension
* PHP Fileinfo extension
* PHP GD extension
* PHP Mbstring extension
* PHP OpenSSL extension
* PHP JSON extension
* PHP XML extension

Check the PHP version:

```bash
php -v
```

Check Composer:

```bash
composer --version
```

## Installation

### 1. Clone the repository

```bash
git clone <repository-url>
cd inventory-saas
```

### 2. Install dependencies

```bash
composer install
```

### 3. Configure environment

Create the environment file:

```bash
cp .env.example .env
```

Update the database configuration in `.env`.

Example:

```env
APP_ENV=development
APP_DEBUG=true

DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=inventory
DB_USERNAME=root
DB_PASSWORD=
```

Do not commit `.env` to Git.

## Database

Create the database:

```sql
CREATE DATABASE inventory
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;
```

Import the project's database schema or migrations according to the version of the repository.

Make sure the configured database user has the required permissions.

## Storage

Uploaded files are intentionally stored outside the public web directory.

The application uses:

```text
storage/
└── uploads/
    ├── businesses/
    │   └── logos/
    ├── products/
    ├── users/
    └── documents/
```

This structure keeps uploaded files separate from publicly accessible application files.

Make sure the application has permission to write to the storage directory:

```bash
mkdir -p storage/uploads
chmod -R 775 storage
```

For production deployments, configure ownership and permissions according to the web server user instead of using unnecessarily broad permissions.

## Running the Application

For local development, start PHP's built-in development server:

```bash
php -S localhost:8000 -t public
```

Then open:

```text
http://localhost:8000
```

The `public` directory is the application's web root.

## Project Structure

```text
inventory-saas/
│
├── app/
│   ├── Controllers/
│   ├── Helpers/
│   ├── Models/
│   ├── Services/
│   └── Views/
│
├── config/
│   └── container.php
│
├── public/
│   ├── assets/
│   └── index.php
│
├── routes/
│   └── web.php
│
├── storage/
│   └── uploads/
│
├── vendor/
│
├── .env
├── .env.example
├── composer.json
└── README.md
```

## Application Architecture

The project follows a layered architecture.

```text
HTTP Request
     │
     ▼
Routes
     │
     ▼
Middleware
     │
     ▼
Controller
     │
     ▼
Service
     │
     ▼
Model
     │
     ▼
PDO / MariaDB
```

### Routes

Routes define application endpoints and connect requests to controllers.

Location:

```text
routes/web.php
```

### Middleware

Middleware is responsible for concerns such as:

* Authentication
* Authorization
* System administrator access
* Permission checking
* CSRF protection

### Controllers

Controllers handle HTTP requests and responses.

They should remain focused on:

* Reading request data
* Calling services
* Preparing views
* Returning responses

### Services

Services contain application and business logic.

Examples:

```text
BusinessRegistrationService
UserRegistrationService
RoleService
AuditLogger
FileUploadService
StorageService
```

### Models

Models are responsible for database operations using PDO.

Examples:

```text
UserModel
BusinessModel
RoleModel
PermissionModel
AuditLogModel
```

## Authentication and Authorization

The application distinguishes between authentication and authorization.

### Authentication

Authentication determines whether a user is logged in.

### Authorization

Authorization determines what an authenticated user is allowed to do.

The application uses permission-based authorization such as:

```text
users.view
users.create
users.update
users.deactivate

roles.view
roles.create
roles.update
roles.permissions.view
roles.permissions.manage

businesses.view
businesses.create
businesses.update
```

Views can use the permission helper:

```php
<?php if (PermissionHelper::can('users.create')): ?>

    <a href="/users/create" class="btn btn-primary">
        Register User
    </a>

<?php endif; ?>
```

Permission checks in views are for the user interface.

**Authorization must also be enforced by middleware/controller/service logic.**

Hiding a button does not provide security by itself.

## System Administrator

System administrators operate at the system level and are not restricted to a specific business.

A system administrator can manage system-wide resources including:

* Businesses
* Users
* Roles
* Permissions
* System configuration
* Audit logs

System administrator status is represented by:

```text
is_system_admin = 1
```

## Multi-Tenancy

Business users are associated with a business through:

```text
business_id
```

System administrators may have:

```text
business_id = NULL
is_system_admin = 1
```

Business-specific records should always be scoped to the authenticated user's business where appropriate.

The application should never rely only on UI restrictions for tenant isolation.

## File Uploads

Uploaded files are handled through dedicated services.

### StorageService

Responsible for:

* Storage paths
* Directory creation
* Absolute path resolution
* File existence
* Safe file deletion

### FileUploadService

Responsible for:

* Upload validation
* MIME-type detection
* File-size validation
* Image validation
* Random filenames
* Moving uploaded files into storage

Business logos support:

```text
JPG
PNG
WEBP
```

Maximum logo size:

```text
5 MB
```

Uploaded filenames are not used directly as storage filenames. Random filenames are generated to reduce filename collisions and avoid trusting client-provided filenames.

## Security

Security is an important part of the application architecture.

The project uses or is designed to use:

* Password hashing
* CSRF protection
* Prepared SQL statements
* Permission-based authorization
* Authentication middleware
* File MIME validation
* File-size validation
* Random upload filenames
* Storage outside the public directory
* Content Security Policy
* Escaped HTML output
* Audit logging

Never commit secrets, passwords, API keys, or production credentials to the repository.

## Content Security Policy

The application uses Content Security Policy protections.

Inline JavaScript should not be used where it violates the configured CSP.

When scripts need to be embedded in a view, use the application's CSP nonce mechanism.

Example:

```php
<script
    nonce="<?= htmlspecialchars(
        $_SESSION['csp_nonce'] ?? '',
        ENT_QUOTES,
        'UTF-8'
    ) ?>"
>
    // JavaScript
</script>
```

## Development Guidelines

When adding a new feature:

1. Define the database requirements.
2. Create/update the Model.
3. Implement business logic in a Service.
4. Add authorization requirements.
5. Add Controller actions.
6. Register routes.
7. Create/update views.
8. Add audit logging where appropriate.
9. Test authentication and authorization.
10. Test both system-admin and business-user behavior.

## Example Feature Flow

For registering a business:

```text
Business Registration Form
          │
          ▼
BusinessController
          │
          ▼
BusinessRegistrationService
          │
          ├── Validate input
          │
          ├── Upload logo
          │
          ├── Generate UUID
          │
          ▼
BusinessModel
          │
          ▼
MariaDB
          │
          ▼
AuditLogger
```

## Error Handling

Application errors should be logged server-side without exposing sensitive implementation details to users.

Development environments may enable detailed errors:

```env
APP_DEBUG=true
```

Production environments should disable detailed error output:

```env
APP_DEBUG=false
```

## Git Workflow

Check the current status:

```bash
git status
```

Create a feature branch:

```bash
git checkout -b feature/feature-name
```

Commit changes:

```bash
git add .
git commit -m "Add feature description"
```

Push the branch:

```bash
git push -u origin feature/feature-name
```

## Environment Files

Never commit the real `.env` file.

The repository should contain:

```text
.env.example
```

while the actual:

```text
.env
```

remains local or is managed securely by the deployment environment.

## Production Deployment

For production deployment:

1. Clone the repository on the server.
2. Install Composer dependencies.
3. Configure the production `.env`.
4. Configure MariaDB.
5. Configure the web server.
6. Set the web root to:

```text
/public
```

7. Configure storage permissions.
8. Disable application debug mode.
9. Enable HTTPS.
10. Verify authentication and authorization.
11. Verify file uploads.
12. Verify audit logging.
13. Verify database backups.

## Roadmap

Planned areas include:

* [ ] Product management
* [ ] Product categories
* [ ] Supplier management
* [ ] Customer management
* [ ] Stock management
* [ ] Purchase orders
* [ ] Sales orders
* [ ] Inventory reports
* [ ] Dashboard analytics
* [ ] User profile management
* [ ] Advanced audit log reporting
* [ ] Business document/letter generation
* [ ] Business logo integration in generated documents
* [ ] PDF document generation
* [ ] Notifications
* [ ] API endpoints

## License

Add the project's license information here.

If this repository is private or proprietary, specify the applicable ownership and usage terms.

## Author

**Warka Hub**

Developed as a multi-tenant inventory management platform for business operations.

```

This README is ready to save as **`README.md`** in the repository root. It also intentionally documents your current architecture without claiming that roadmap features are already implemented.
```
