# Authentication and RBAC Architecture Summary
## CodeIgniter 4 Medical/Healthcare Management System

### Overview
This application implements a multi-layered authentication and authorization system combining CodeIgniter Shield with custom RBAC (Role-Based Access Control). The architecture supports multiple user types with different authentication patterns and granular permission management.

---

## 1. Authentication Implementation

### 1.1 Authentication Methods (Multiple Authenticators)

**Location:** `/Users/carl/Documents/projects/mdc/mdcServer/app/Config/Auth.php` (lines 118-129)

The application uses a **multi-authenticator strategy**:

```php
public array $authenticators = [
    'tokens' => AccessTokens::class,      // Access Token authentication
    'session' => Session::class,           // Session-based authentication
    'hmac' => HmacSha256::class,          // HMAC SHA256 API key authentication
    'practitioners' => [                   // Custom Practitioner authenticator
        'model' => PractitionerModel::class,
        'table' => 'practitioners',
        'identityColumn' => 'registration_number',
        'passwordColumn' => 'password_hash'
    ]
    // 'jwt' => JWT::class,  // Commented out but available
];

public array $authenticationChain = [
    'session',
    'tokens',
    'hmac',
];

public string $defaultAuthenticator = 'session';
```

### 1.2 Authentication Framework
- **Base Framework:** CodeIgniter Shield (built-in authentication library)
- **Primary Auth Method:** Token-based (AccessTokens)
- **Supported Protocols:**
  - **JWT (commented out)** - Available but not active
  - **HMAC SHA256** - For API key authentication
  - **Access Tokens** - For session/bearer token authentication
  - **Session** - Traditional session authentication

### 1.3 Key Authentication Endpoints

**Location:** `/Users/carl/Documents/projects/mdc/mdcServer/app/Config/Routes.php` (lines 104-118)

```php
$routes->group("api", ["namespace" => "App\Controllers"], function (RouteCollection $routes) {
    $routes->post("register", [AuthController::class, "register"]);
    $routes->post("login", [AuthController::class, "login"]);
    $routes->post("mobile-login", [AuthController::class, "mobileLogin"]);
    $routes->post("practitioner-login", [AuthController::class, "practitionerLogin"]);
    $routes->post("send-reset-token", [AuthController::class, "sendResetToken"]);
    $routes->post("reset-password", [AuthController::class, "resetPassword"]);
    $routes->post("verify-recaptcha", [AuthController::class, "verifyRecaptcha"]);
});
```

**Guest Authentication Endpoints:**
```php
$routes->post("guest/signup", [AuthController::class, "guestSignup"]);
$routes->post("guest/verify-email", [AuthController::class, "verifyGuestEmail"]);
$routes->post("guest/resend-verification", [AuthController::class, "resendVerificationCode"]);
$routes->post("guest/request-verification", [AuthController::class, "requestVerificationByEmail"]);
$routes->post("guest/complete-signup", [AuthController::class, "completeGuestSignup"]);
```

### 1.4 Two-Factor Authentication (2FA)

**Implementation:** Google Authenticator-based 2FA

**Location:** `/Users/carl/Documents/projects/mdc/mdcServer/app/Controllers/AuthController.php` (lines 651-822)

**2FA Workflow:**
1. User attempts login with username/password
2. If 2FA is enabled on account:
   - User receives temporary 2FA verification token
   - Response returns `requires_2fa: true` with token
   - Client must verify with 6-digit code
3. 2FA deadline enforcement:
   - System can mandate 2FA setup by deadline
   - If deadline passes without 2FA setup, login denied with setup instructions
   - Setup instructions sent via email

**Key Fields:**
```php
$userData->google_auth_secret      // Stores 2FA secret key
$userData->two_fa_verification_token // Temporary token during 2FA verification
$userData->two_fa_deadline         // Deadline to complete 2FA setup
```

### 1.5 API Key (HMAC) Authentication

**Location:** `/Users/carl/Documents/projects/mdc/mdcServer/app/Controllers/AuthController.php` (lines 1847-1857)

```php
public function createApiKey()
{
    $userObject = new UsersModel();
    $userData = $userObject->findById(auth()->id());
    $token = $userData->generateHmacToken(service('request')->getVar('token_name'));
    return json_encode(['key' => $token->secret, 'secretKey' => $token->rawSecretKey]);
}
```

**Route (Admin only):**
```php
$routes->post("api-user", [AuthController::class, "createApiKey"], 
    ["filter" => ["hasPermission:Create_Api_User"]]);
```

**HMAC Configuration:**
- **Secret Key Size:** 32 bytes
- **Encryption:** SHA512 with OpenSSL driver
- **Header:** Authorization (same as tokens)
- **Route Filter:** Line 116 in Routes.php uses `['filter' => 'hmac']`

---

## 2. Database Schema for Authentication

### 2.1 Core Auth Tables (CodeIgniter Shield)

**Shield provides these tables (from vendor):**
- `users` - User records
- `auth_identities` - Authentication identities (email, HMAC keys, etc.)
- `auth_tokens` - Active tokens

**Extended with custom fields in migrations:**

**Location:** `/Users/carl/Documents/projects/mdc/mdcServer/app/Database/Migrations/2024-12-28-120251_AddRoleNameToUsers.php`

```php
// Added to 'users' table:
$this->forge->addColumn("users", [
    'role_name' => [
        'type' => 'VARCHAR',
        'null' => true,
        'constraint' => 255,
    ],
]);
$this->forge->addForeignKey('role_name', 'roles', 'role_name', 'CASCADE', 'RESTRICT');
```

**Users table additional fields:**
- `role_name` (VARCHAR 255) - FK to roles.role_name
- `username` - Login identifier
- `email_address` - User email
- `display_name` - Display name
- `user_type` - Type of user (admin, license, guest, etc.)
- `google_auth_secret` - 2FA secret
- `two_fa_verification_token` - Temporary 2FA token
- `two_fa_deadline` - 2FA setup deadline
- `status` - User status
- `active` - Active/inactive flag
- `position` - User position
- `picture` - Profile picture
- `phone` - Phone number
- `region` - Region
- `profile_table` - Link to user's profile table
- `profile_table_uuid` - UUID in profile table
- `institution_uuid` - Institution reference
- `institution_type` - Type of institution

### 2.2 RBAC Tables

**Roles Table**
**Location:** `/Users/carl/Documents/projects/mdc/mdcServer/app/Database/Migrations/2023-12-27-105914_AddRolesTable.php`

```sql
CREATE TABLE roles (
    role_id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    role_name VARCHAR(255) UNIQUE NOT NULL,
    description VARCHAR(1000),
    default TINYINT(1) DEFAULT 0,
    can_delete TINYINT(1) DEFAULT 1,
    login_destination VARCHAR(255) DEFAULT '/',
    default_context VARCHAR(255) DEFAULT 'content',
    deleted INT(1) DEFAULT 0,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

**Permissions Table**
**Location:** `/Users/carl/Documents/projects/mdc/mdcServer/app/Database/Migrations/2023-12-27-105856_AddPermissionsTable.php`

```sql
CREATE TABLE permissions (
    permission_id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) UNIQUE NOT NULL,
    description VARCHAR(1000),
    status ENUM('active', 'inactive', 'deleted') DEFAULT 'active',
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

**Role Permissions Mapping Table**
**Location:** `/Users/carl/Documents/projects/mdc/mdcServer/app/Database/Migrations/2023-12-27-105922_AddRolesPermissionsTable.php`

```sql
CREATE TABLE role_permissions (
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    permission VARCHAR(255) NOT NULL,  -- Permission name
    role VARCHAR(255) NOT NULL,         -- Role name
    created_at TIMESTAMP,
    FOREIGN KEY (role) REFERENCES roles(role_name) CASCADE,
    FOREIGN KEY (permission) REFERENCES permissions(name) CASCADE
);
```

**Structure Evolution:**
- Migrated from role_id/permission_id based references to string-based (role_name/permission_name)
- Migration: `2024-12-27-125019_AddPermissionNameToRolePermissions.php`
- Migration: `2024-12-27-132456_AddRolenameFK.php`

### 2.3 Guest Authentication Table

**Location:** `/Users/carl/Documents/projects/mdc/mdcServer/app/Models/GuestsModel.php`

```sql
CREATE TABLE guests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    uuid VARCHAR(255) UNIQUE,
    unique_id VARCHAR(255),
    first_name VARCHAR(255),
    last_name VARCHAR(255),
    email VARCHAR(255),
    phone_number VARCHAR(50),
    id_type VARCHAR(50),
    id_number VARCHAR(100),
    postal_address TEXT,
    sex VARCHAR(50),
    picture LONGBLOB,
    date_of_birth DATE,
    country VARCHAR(255),
    email_verified BOOLEAN DEFAULT 0,
    email_verified_at TIMESTAMP,
    id_image LONGBLOB,
    verified BOOLEAN DEFAULT 0,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP
);
```

### 2.4 Email Verification Tokens Table

**Location:** `/Users/carl/Documents/projects/mdc/mdcServer/app/Models/EmailVerificationTokenModel.php`

```sql
CREATE TABLE email_verification_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    guest_uuid VARCHAR(255),
    email VARCHAR(255),
    token VARCHAR(255),
    token_hash VARCHAR(255),
    expires_at TIMESTAMP,
    verified_at TIMESTAMP,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP
);
```

---

## 3. Permission System

### 3.1 Permission Structure

**Pattern:** `Action_Resource` or `Action_Resource_Subresource`

**Examples from migrations:**
- `View_License_Details`
- `Create_License_Details`
- `Update_License_Renewal`
- `Delete_License_Renewal`
- `Restore_License_Details`
- `View_CPD_Details`
- `Create_CPD_Providers`
- `View_Payment_Fees`
- `Create_Application_Forms`
- `Manage_Examination_Data`
- `Create_Or_Edit_User_Role`
- `Create_Or_Delete_User_Permissions`
- `View_Training_Institutions`
- `Create_Print_Templates`
- `Create_Api_User`

### 3.2 Permission Checking Implementation

**Models:**

**RolePermissionsModel**
**Location:** `/Users/carl/Documents/projects/mdc/mdcServer/app/Models/RolePermissionsModel.php`

```php
public function hasPermission(string $role_name, string $permission): bool
{
    $rows = $this->where('role', $role_name)
                  ->where("permission", $permission)
                  ->findAll();
    return count($rows) > 0;
}
```

**PermissionsModel**
**Location:** `/Users/carl/Documents/projects/mdc/mdcServer/app/Models/PermissionsModel.php`

```php
// Get permissions assigned to a role
public function getRolePermissions(string $role_name, bool $namesOnly = false): array

// Get permissions NOT assigned to a role
public function getRoleExcludedPermissions(string $role_name): array
```

### 3.3 Permission Filters

**Two Filters Exist:**

**1. AuthFilter (Basic Authentication Check)**
**Location:** `/Users/carl/Documents/projects/mdc/mdcServer/app/Filters/AuthFilter.php`

```php
public function before(RequestInterface $request, $arguments = null)
{
    if (!auth("tokens")->loggedIn()) {
        return $response->setStatusCode(401)->setJSON(["message" => 'you are not logged in']);
    }
    
    if ($arguments) {
        $rpModel = new RolePermissionsModel();
        if (!$rpModel->hasPermission(auth()->getUser()->role_id, $arguments)) {
            return $response->setStatusCode(401)->setJSON(["message" => "you are not permitted"]);
        }
    }
}
```

**2. PermissionFilter (Granular Permission Check)**
**Location:** `/Users/carl/Documents/projects/mdc/mdcServer/app/Filters/PermissionFilter.php`

```php
public function before(RequestInterface $request, $arguments = null)
{
    if (!auth(alias: "tokens")->loggedIn()) {
        return $response->setStatusCode(401)->setJSON(['you are not logged in']);
    }
    
    if ($arguments) {
        $rpModel = new RolePermissionsModel();
        foreach ($arguments as $permission) {
            if (!$rpModel->hasPermission(auth()->getUser()->role_name, $permission)) {
                log_message("error", "User attempted action without permission: $permission");
                return $response->setStatusCode(401)->setJSON(["message" => "Not permitted"]);
            }
        }
    }
}
```

**Filter Registration:**
**Location:** `/Users/carl/Documents/projects/mdc/mdcServer/app/Config/Filters.php`

```php
public array $aliases = [
    'apiauth' => AuthFilter::class,
    'hasPermission' => PermissionFilter::class
];
```

### 3.4 Permission Usage in Routes

**Basic Authentication (apiauth filter):**
```php
$routes->get("home-menu", [PortalController::class, "getHomeMenu"], 
    ["filter" => ["apiauth"]]);
```

**With Specific Permissions (hasPermission filter):**
```php
$routes->post("users", [AuthController::class, "createUser"], 
    ["filter" => ["hasPermission:Create_Or_Edit_User"]]);

$routes->delete("details/(:segment)", [LicensesController::class, "deleteLicense/$1"], 
    ["filter" => ["hasPermission:Delete_License_Details"]]);

$routes->post("templates/upload-docx", [PrintQueueController::class, "docxToHtml"], 
    ["filter" => ["hasPermission:Create_Print_Templates"]]);
```

---

## 4. User Types and Role Support

### 4.1 Supported User Types

**Location:** `/Users/carl/Documents/projects/mdc/mdcServer/app/Config/Constants.php`

```php
define("USER_TYPES", [
    'admin',                    // Administrative users
    'license',                  // License holders
    'cpd',                     // CPD (Continuing Professional Development) users
    'student_index',           // Students in index
    'guest',                   // Guest users
    'housemanship_facility',   // Housemanship facility users
    'exam_candidate',          // Examination candidates
    'training_institution'     // Training institution users
]);

define("USER_TYPES_LICENSED_USERS", [
    'exam_candidate',
    'license'
]);
```

### 4.2 User Profile Linking

Users can be linked to profile data from different tables:

```php
$user->profile_table        // Table name (e.g., 'licenses', 'practitioners')
$user->profile_table_uuid   // UUID in that table
$user->institution_uuid     // Institution reference
$user->institution_type     // Type of institution

// For licensed users, profile_data is loaded from the license table
// For others, profile_data is loaded from their profile_table
```

---

## 5. API Authentication Patterns

### 5.1 Route Protection Patterns

**Pattern 1: Portal Routes (Practitioners)**
```php
$routes->group("portal", ["namespace" => "App\Controllers"], function (RouteCollection $routes) {
    $routes->post("guest/signup", [AuthController::class, "guestSignup"]);
    $routes->get("profile", [PortalController::class, "getProfileFields"], 
        ["filter" => ["apiauth"]]);
    // All authenticated routes use apiauth filter
});
```

**Pattern 2: Admin Routes (All require authentication)**
```php
$routes->group("admin", ["namespace" => "App\Controllers", "filter" => "apiauth"], 
    function (RouteCollection $routes) {
        $routes->post("users", [AuthController::class, "createUser"], 
            ["filter" => ["hasPermission:Create_Or_Edit_User"]]);
        // Group filter + route filter
    }
);
```

**Pattern 3: Print Queue Routes (Authentication + Permissions)**
```php
$routes->group("print-queue", ["namespace" => "App\Controllers", "filter" => "apiauth"], 
    function (RouteCollection $routes) {
        $routes->post("templates", [PrintQueueController::class, "createPrintTemplate"], 
            ["filter" => ["hasPermission:Create_Print_Templates"]]);
    }
);
```

### 5.2 Public Routes (No Authentication)

**Location:** `/Users/carl/Documents/projects/mdc/mdcServer/app/Config/Routes.php` (lines 104-118)

```php
$routes->group("api", ["namespace" => "App\Controllers"], function (RouteCollection $routes) {
    $routes->get("app-settings", [AuthController::class, "appSettings"]);
    $routes->post("register", [AuthController::class, "register"]);
    $routes->post("login", [AuthController::class, "login"]);
    $routes->post("mobile-login", [AuthController::class, "mobileLogin"]);
    $routes->post("practitioner-login", [AuthController::class, "practitionerLogin"]);
    $routes->post("send-reset-token", [AuthController::class, "sendResetToken"]);
    $routes->post("reset-password", [AuthController::class, "resetPassword"]);
    $routes->post("verify-recaptcha", [AuthController::class, "verifyRecaptcha"]);
    $routes->get("migrate-cmd", [AuthController::class, "runShieldMigration"]);
});
```

---

## 6. Key Controllers and Models

### 6.1 AuthController

**Location:** `/Users/carl/Documents/projects/mdc/mdcServer/app/Controllers/AuthController.php`

**Key Methods:**
- `appSettings()` - Cached app settings with auth state differentiation
- `mobileLogin()` - Token-based login with 2FA support
- `practitionerLogin()` - Practitioner-specific login
- `createUser()` - Create new user with role assignment
- `createRole()` - Create new role
- `createApiKey()` - Generate HMAC token for API access
- `setupGoogleAuth()` - Initialize 2FA setup
- `verifyAndEnableGoogleAuth()` - Activate 2FA
- `guestSignup()` - Guest registration flow
- `verifyGuestEmail()` - Guest email verification
- `sendResetToken()` - Password reset token
- `resetPassword()` - Password reset handler

### 6.2 Authentication Helper

**Location:** `/Users/carl/Documents/projects/mdc/mdcServer/app/Helpers/AuthHelper.php`

```php
// Get authenticated user with cached data (5 minutes)
AuthHelper::getAuthUser($userId)

// Get user permissions for admin users
AuthHelper::getAuthUserPermissions($userData)

// Get unique identifier (license number or username)
AuthHelper::getAuthUserUniqueId($userId)

// Check if user is admin
AuthHelper::isUserAdmin($userId)

// Clear user cache after updates
AuthHelper::clearAuthUserCache($userId)
```

### 6.3 Models

**UsersModel**
**Location:** `/Users/carl/Documents/projects/mdc/mdcServer/app/Models/UsersModel.php`
- Extends CodeIgniter Shield UserModel
- Implements TableDisplayInterface for UI
- Search functionality with complex filtering
- Profile data loading for non-admin users

**RolePermissionsModel**
**Location:** `/Users/carl/Documents/projects/mdc/mdcServer/app/Models/RolePermissionsModel.php`
- `hasPermission(role_name, permission)` - Check permission
- Supports both numeric IDs and string names

**PermissionsModel**
**Location:** `/Users/carl/Documents/projects/mdc/mdcServer/app/Models/PermissionsModel.php`
- `getRolePermissions(role_name)` - Get assigned permissions
- `getRoleExcludedPermissions(role_name)` - Get unassigned permissions

**RolesModel**
**Location:** `/Users/carl/Documents/projects/mdc/mdcServer/app/Models/RolesModel.php`
- Soft deletes support
- Implements TableDisplayInterface

---

## 7. Configuration Files

### 7.1 Auth Configuration

**Location:** `/Users/carl/Documents/projects/mdc/mdcServer/app/Config/Auth.php`

Key Settings:
- Authenticators: tokens, session, hmac, practitioners
- Default: session
- Chain: session → tokens → hmac
- 2FA Actions: Email-based
- JWT: Available but commented out

### 7.2 AuthToken Configuration

**Location:** `/Users/carl/Documents/projects/mdc/mdcServer/app/Config/AuthToken.php`

Key Settings:
- HMAC secret key byte size: 32 bytes
- HMAC encryption driver: OpenSSL
- HMAC encryption digest: SHA512
- Unused token lifetime: 1 year
- Header: Authorization

### 7.3 Filters Configuration

**Location:** `/Users/carl/Documents/projects/mdc/mdcServer/app/Config/Filters.php`

Aliases:
- `apiauth` → AuthFilter
- `hasPermission` → PermissionFilter

CORS enabled on all API routes

---

## 8. Security Features

### 8.1 Password Reset Flow

**Location:** Routes (lines 34-35)

```php
$routes->post("send-reset-token", [AuthController::class, "sendResetToken"]);
$routes->post("reset-password", [AuthController::class, "resetPassword"]);
```

**Models:**
- `PasswordResetTokenModel` - Manages reset tokens
- `PasswordResetAttemptModel` - Tracks reset attempts
- `PasswordResetHistoryModel` - Audit trail

### 8.2 Email Verification

**Token Generation:**
- 6-digit numeric tokens
- Argon2ID hashing
- Expiration timestamps
- Verification status tracking

### 8.3 Rate Limiting/Attempts

**Models:**
- `EmailVerificationTokenModel` - Email token tracking
- `PasswordResetAttemptModel` - Login/reset attempt tracking
- Support for configuring max attempts

### 8.4 HMAC Authentication Security

- 32-byte secret keys
- SHA512 encryption
- Supports key rotation
- Separate encryption keys configuration

---

## 9. Multi-Tenant Support

The application supports multiple business contexts through app settings:

```php
// Each organization can have different:
- User roles and permissions
- License types and requirements
- CPD settings
- Payment configurations
- Examination settings
```

User type system allows same database to serve different user types with different permission sets.

---

## 10. Data Flow Examples

### 10.1 Login Flow (Token-Based)

1. **POST /api/mobile-login**
   - Username, password, device_name, user_type
   
2. **AuthController::mobileLogin()**
   - Validate credentials
   - Check user type
   - Check 2FA deadline
   - If 2FA enabled but not set up: send setup email
   - If 2FA enabled: return requires_2fa=true with token
   - Otherwise: generate access token

3. **Generate Token**
   ```php
   $token = auth()->user()->generateAccessToken('device_name');
   // Returns token->raw_token for client
   ```

4. **Client includes in requests**
   ```
   Authorization: Bearer {raw_token}
   ```

### 10.2 Permission Check Flow

1. **Request arrives**
   - Filter: `["hasPermission:Create_License_Details"]`

2. **PermissionFilter::before()**
   - Check if user logged in
   - Loop through required permissions
   - For each permission:
     ```php
     $rpModel->hasPermission(auth()->getUser()->role_name, 'Create_License_Details')
     ```
   - Query role_permissions table
   - Allow or deny

### 10.3 Guest Registration Flow

1. **POST /portal/guest/signup**
   - Email, phone, basic info
   - Creates guest record
   - Sends verification email with 6-digit token

2. **POST /portal/guest/verify-email**
   - Verifies email token
   - Marks email as verified

3. **POST /portal/guest/complete-signup**
   - Final registration step
   - Creates user account if not exists
   - Assigns guest role

---

## 11. Summary Table

| Component | Location | Type | Purpose |
|-----------|----------|------|---------|
| AuthController | app/Controllers/AuthController.php | Controller | Main auth operations |
| UsersModel | app/Models/UsersModel.php | Model | User management |
| RolePermissionsModel | app/Models/RolePermissionsModel.php | Model | Permission checking |
| PermissionsModel | app/Models/PermissionsModel.php | Model | Permission management |
| RolesModel | app/Models/RolesModel.php | Model | Role management |
| AuthFilter | app/Filters/AuthFilter.php | Filter | Basic auth check |
| PermissionFilter | app/Filters/PermissionFilter.php | Filter | Permission check |
| AuthHelper | app/Helpers/AuthHelper.php | Helper | Auth utilities |
| Auth.php | app/Config/Auth.php | Config | Auth configuration |
| AuthToken.php | app/Config/AuthToken.php | Config | Token/HMAC config |
| AuthGroups.php | app/Config/AuthGroups.php | Config | Groups & permissions matrix |
| Routes.php | app/Config/Routes.php | Config | Route protection |
| Migrations | app/Database/Migrations/ | DB | Schema definitions |

---

## 12. External Authentication References

The system integrates with:

1. **CodeIgniter Shield** - Core authentication framework
2. **Google Authenticator** - 2FA TOTP generation
3. **ReCAPTCHA** - Bot prevention on public endpoints
4. **Email Service (Brevo)** - Email delivery for verifications
5. **OpenSSL/Sodium** - Encryption for HMAC keys

---

## 13. Key Points for Development

1. **Permissions are string-based** - Use role_name and permission name, not IDs
2. **Always use filters** - All API endpoints should use either `apiauth` or `hasPermission`
3. **2FA is optional but configurable** - Can set deadline for enforcement
4. **User types affect authorization** - Non-admin users have different permission model
5. **Caching is important** - AuthHelper caches user data for 5 minutes
6. **Guest workflow is separate** - Guests have different signup/verification flow
7. **HMAC for programmatic access** - Create API keys for external integrations
8. **Multiple devices supported** - Device name tracked in token generation

---

**Document Generated:** 2024-12-04
**Framework:** CodeIgniter 4
**Shield Version:** Latest (from config imports)
**File Count Analyzed:** 30+ files
