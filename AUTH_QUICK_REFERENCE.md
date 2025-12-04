# Authentication & RBAC Quick Reference Guide

## Authentication

### Login Endpoints
```
POST /api/mobile-login              # Token-based login with 2FA support
POST /api/login                      # Standard login
POST /api/practitioner-login         # Practitioner-specific login
POST /portal/login                   # Portal/practitioners login
```

### Request Format (Mobile Login)
```json
{
  "username": "user@example.com",
  "password": "password",
  "device_name": "admin portal|practitioners portal",
  "user_type": "admin|license|cpd|guest|etc"
}
```

### Response (No 2FA)
```json
{
  "token": "Bearer_token_here"
}
```

### Response (2FA Required)
```json
{
  "requires_2fa": true,
  "token": "temp_verification_token",
  "message": "2FA verification required"
}
```

### 2FA Verification
```
POST /api/mobile-login
{
  "verification_mode": "2fa",
  "token": "temp_verification_token",
  "code": "123456",
  "device_name": "admin portal",
  "user_type": "admin"
}
```

## Authorization

### File Locations
- **Auth Filter:** `/app/Filters/AuthFilter.php`
- **Permission Filter:** `/app/Filters/PermissionFilter.php`
- **Filters Config:** `/app/Config/Filters.php`
- **Routes Config:** `/app/Config/Routes.php`
- **Auth Config:** `/app/Config/Auth.php`

### Using Authentication Filter
```php
$routes->get("profile", [Controller::class, "method"], 
    ["filter" => ["apiauth"]]);
```

### Using Permission Filter
```php
$routes->post("users", [AuthController::class, "createUser"], 
    ["filter" => ["hasPermission:Create_Or_Edit_User"]]);
```

### Multiple Permissions
```php
["filter" => ["hasPermission:Permission1,Permission2,Permission3"]]
// User must have ALL permissions
```

## Common Permissions

### User Management
- `View_Users`
- `Create_Or_Edit_User`
- `Delete_User`
- `Activate_Or_Deactivate_User`
- `Create_Or_Edit_User_Role`
- `View_User_Roles`
- `Delete_User_Role`
- `Create_Or_Delete_User_Permissions`
- `Create_Api_User`

### License Management
- `View_License_Details`
- `Create_License_Details`
- `Update_License_Details`
- `Delete_License_Details`
- `Restore_License_Details`
- `View_License_Renewal`
- `Create_License_Renewal`
- `Update_License_Renewal`
- `Delete_License_Renewal`

### Practitioners
- `View_Practitioner_Qualifications`
- `Create_Or_Update_Practitioners_Qualifications`
- `Delete_Practitioners_Qualifications`
- `View_Practitioners_Work_History`
- `Create_Or_Update_Practitioners_Work_History`
- `Delete_Practitioners_Work_History`

### CPD Management
- `View_CPD_Details`
- `Create_CPD_Details`
- `Update_CPD_Details`
- `Delete_CPD_Details`
- `Restore_CPD_Details`
- `View_CPD_Providers`
- `Create_CPD_Providers`
- `View_CPD_Attendance`
- `Create_CPD_Attendance`

### Settings
- `View_Settings`
- `Modify_Settings`

### System
- `View_Activities`
- `Send_Email`
- `Create_Or_Edit_Assets`

## Database Tables

### Core Users
- `users` - User records
- `auth_identities` - Email, HMAC keys, etc.
- `auth_tokens` - Active tokens

### RBAC
- `roles` - User roles
- `permissions` - Available permissions
- `role_permissions` - Role-permission mappings

### Extended Auth
- `guests` - Guest user records
- `email_verification_tokens` - Email verification

## Password Reset

```
1. POST /api/send-reset-token
   { "username": "username" }

2. User receives token via email

3. POST /api/reset-password
   { "username": "username", "token": "token", "password": "new_password" }
```

## API Key (HMAC) Authentication

### Generate API Key
```
POST /admin/api-user
(Requires: Create_Api_User permission)

Response:
{
  "key": "api_key_here",
  "secretKey": "secret_key_here"
}
```

### Use API Key
```
Authorization: Bearer {key}:{secret}
```

## User Types
- `admin` - Admin users
- `license` - License holders
- `cpd` - CPD users
- `student_index` - Student index users
- `guest` - Guest users
- `housemanship_facility` - Housemanship facility staff
- `exam_candidate` - Examination candidates
- `training_institution` - Training institution users

## Getting Current User

### In Controllers
```php
auth()->getUser()                    // Get current user
auth()->id()                         // Get user ID
auth("tokens")->loggedIn()          // Check if logged in
auth()->logout()                     // Logout user
```

### Helper Functions
```php
AuthHelper::getAuthUser($userId)
AuthHelper::getAuthUserPermissions($userData)
AuthHelper::getAuthUserUniqueId($userId)
AuthHelper::isUserAdmin($userId)
AuthHelper::clearAuthUserCache($userId)
```

## Permission Checking in Code

```php
// Check if user has permission
$rpModel = new RolePermissionsModel();
$has_permission = $rpModel->hasPermission(
    auth()->getUser()->role_name, 
    'Create_License_Details'
);

// Get all user permissions
$permissions = (new PermissionsModel())->getRolePermissions(
    auth()->getUser()->role_name,
    true  // namesOnly
);
```

## Models

### AuthController
- `login()` - Standard login
- `mobileLogin()` - Token-based login with 2FA
- `practitionerLogin()` - Practitioner login
- `createUser()` - Create new user
- `createRole()` - Create new role
- `createApiKey()` - Generate API key
- `setupGoogleAuth()` - Setup 2FA
- `sendResetToken()` - Send password reset
- `resetPassword()` - Reset password

### Key Models
- `UsersModel` - User management
- `RolePermissionsModel` - Permission checking
- `PermissionsModel` - Permission management
- `RolesModel` - Role management
- `GuestsModel` - Guest user management

## Configuration

### /app/Config/Auth.php
```php
// Authenticators (session, tokens, hmac, practitioners)
// Default: session
// Chain: session -> tokens -> hmac
```

### /app/Config/AuthToken.php
```php
// HMAC Config:
// - Secret size: 32 bytes
// - Encryption: SHA512 with OpenSSL
// - Header: Authorization
// - Token lifetime: 1 year
```

## Security Notes

1. **Always use filters** - Every API endpoint should have either `apiauth` or `hasPermission`
2. **Permissions are case-sensitive** - Use exact permission names
3. **2FA is optional** - Can be set up or enforced by deadline
4. **Caching** - AuthHelper caches user data for 5 minutes
5. **Token expiry** - Configure in auth config if needed
6. **HMAC keys** - 32-byte keys with SHA512 encryption

## Troubleshooting

### User not authenticated
- Check token is sent in `Authorization: Bearer {token}` header
- Verify token is not expired (see auth_tokens table)
- Ensure user is not banned/deactivated

### Permission denied
- Check user has the exact permission name
- Verify permission is assigned to user's role
- Permissions are case-sensitive

### 2FA issues
- Verify google_auth_secret is set
- Check two_fa_verification_token during verification
- Ensure 6-digit code is correct
- Check device_name is valid: "admin portal" or "practitioners portal"

### Guest registration
- Email must be verified first
- Separate flow from standard user registration
- Creates guest record, then user account

