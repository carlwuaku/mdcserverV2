# API Integration System - HMAC Authentication

## Overview
Complete HMAC-based API authentication system for granting external institutions secure access to MDC APIs.

## ✅ Backend Components Built

### 1. Database Schema
- **institutions** - External organization management
- **api_keys** - API credentials with HMAC secrets
- **api_key_permissions** - Granular permission system
- **api_requests_log** - Complete audit trail

### 2. Models
- `InstitutionModel` - Implements FormInterface & TableDisplayInterface
- `ApiKeyModel` - Implements FormInterface & TableDisplayInterface
- `ApiKeyPermissionModel` - Permission management
- `ApiRequestLogModel` - Request logging and analytics

### 3. Services
- `ApiKeyService` - Key generation, rotation, documentation
- `HmacAuthService` - HMAC signature verification, rate limiting, IP whitelisting

### 4. Security Filter
- `HmacAuthFilter` - Validates HMAC-SHA256 signatures on incoming requests
  - Timestamp validation (5-minute window)
  - Rate limiting (per-minute & per-day)
  - IP whitelisting support
  - Endpoint restrictions
  - Permission checking

### 5. Controllers
- `InstitutionsController` - Full CRUD for institution management
- `ApiKeysController` - Key generation, revocation, rotation, stats, logs, documentation

### 6. Routes
All routes under `/api-integration` with permission-based access:

**Institutions:**
- `GET /api-integration/institutions` - List institutions
- `GET /api-integration/institutions/{id}` - View institution details
- `POST /api-integration/institutions` - Create institution
- `PUT /api-integration/institutions/{id}` - Update institution
- `DELETE /api-integration/institutions/{id}` - Delete institution

**API Keys:**
- `GET /api-integration/api-keys` - List API keys
- `GET /api-integration/api-keys/{id}` - View API key details
- `GET /api-integration/api-keys/{id}/stats` - Usage statistics
- `GET /api-integration/api-keys/{id}/logs` - Request logs
- `GET /api-integration/api-keys/{id}/documentation` - Integration guide
- `POST /api-integration/api-keys` - Generate new API key
- `PUT /api-integration/api-keys/{id}` - Update API key
- `PUT /api-integration/api-keys/{id}/permissions` - Update permissions
- `POST /api-integration/api-keys/{id}/revoke` - Revoke API key
- `POST /api-integration/api-keys/{id}/rotate` - Rotate credentials
- `DELETE /api-integration/api-keys/{id}` - Delete API key

### 7. Permissions
```
View_Institutions
Create_Institutions
Edit_Institutions
Delete_Institutions
View_API_Keys
Create_API_Keys
Edit_API_Keys
Delete_API_Keys
Revoke_API_Keys
```

## 🔐 HMAC Authentication Flow

### Request Format
External institutions must include these headers:
```
X-API-Key: {key_id}
X-Signature: {hmac_signature}
X-Timestamp: {unix_timestamp}
Content-Type: application/json
```

### Signature Generation
```
message = "{METHOD}:{PATH}:{TIMESTAMP}:{BODY_HASH}"
signature = HMAC-SHA256(message, hmac_secret)
```

### Example (PHP):
```php
$keyId = 'mdc_...';
$hmacSecret = '...';
$method = 'GET';
$path = '/api/external/practitioners';
$timestamp = time();
$body = '';

$bodyHash = hash('sha256', $body);
$message = "{$method}:{$path}:{$timestamp}:{$bodyHash}";
$signature = hash_hmac('sha256', $message, $hmacSecret);

$headers = [
    'X-API-Key: ' . $keyId,
    'X-Signature: ' . $signature,
    'X-Timestamp: ' . $timestamp,
    'Content-Type: application/json',
];
```

## 📊 Features

### Security
- ✅ HMAC-SHA256 signature verification
- ✅ Encrypted HMAC secret storage (using CI4 encrypter)
- ✅ Timestamp validation (5-minute window)
- ✅ Rate limiting (per-minute & per-day limits)
- ✅ IP whitelisting (supports CIDR notation)
- ✅ Endpoint restrictions (pattern matching)
- ✅ Permission-based access control
- ✅ API key revocation
- ✅ Credential rotation

### Management
- ✅ Institution CRUD operations
- ✅ API key generation with unique credentials
- ✅ Permission assignment per key
- ✅ Usage statistics and analytics
- ✅ Complete request audit logging
- ✅ Auto-generated integration documentation

### Monitoring
- ✅ Request logging (method, endpoint, status, response time, IP)
- ✅ Statistics (total requests, success/failure rates, avg response time)
- ✅ Failed request tracking
- ✅ Rate limit enforcement and tracking

## 🚀 Setup Instructions

### 1. Run Migrations
```bash
php spark migrate
```

This will create:
- institutions table
- api_keys table (with hmac_secret field)
- api_key_permissions table
- api_requests_log table
- API integration permissions

### 2. Configure Encryption Key
Ensure `.env` has encryption key set:
```
encryption.key = your-hex-key-here
```

### 3. Assign Permissions
Grant admin users the API integration permissions in the admin UI.

### 4. Register the HMAC Filter
The `HmacAuthFilter` is already created. To protect external API endpoints, add to your routes:
```php
$routes->group("external", ["filter" => "hmacauth"], function($routes) {
    $routes->get("practitioners", [ExternalApiController::class, "getPractitioners"]);
    $routes->get("licenses/(:segment)", [ExternalApiController::class, "getLicense/$1"]);
});
```

## 📱 Angular UI Components Needed

### Institution Management Page
- List view using `load-data-list` component
- Form using `form-generator` component
- Actions: Create, Edit, Delete, View Details

### API Key Management Page
- List view showing keys per institution
- Create key form
- View credentials (one-time display)
- Revoke/Rotate actions
- Permission management UI
- Stats dashboard
- Integration documentation viewer

### Suggested Routes (Angular):
```
/admin/api-integration/institutions
/admin/api-integration/institutions/new
/admin/api-integration/institutions/{id}
/admin/api-integration/api-keys
/admin/api-integration/api-keys/new
/admin/api-integration/api-keys/{id}
/admin/api-integration/api-keys/{id}/stats
/admin/api-integration/api-keys/{id}/documentation
```

## 🔄 Integration Documentation

The system auto-generates integration documentation including:
- Authentication method details
- Available endpoints based on permissions
- Code examples (PHP, Python, JavaScript/Node.js)
- Rate limits
- Best practices

Access via: `GET /api-integration/api-keys/{id}/documentation`

## 📝 Example Workflow

### 1. Create Institution
```json
POST /api-integration/institutions
{
  "name": "Ghana Health Service",
  "code": "GHS",
  "email": "api@ghs.gov.gh",
  "phone": "+233...",
  "contact_person_name": "John Doe",
  "contact_person_email": "john@ghs.gov.gh",
  "status": "active",
  "ip_whitelist": ["197.255.0.0/16"]
}
```

### 2. Generate API Key
```json
POST /api-integration/api-keys
{
  "institution_id": "{uuid}",
  "name": "GHS Production Key",
  "rate_limit_per_minute": 60,
  "rate_limit_per_day": 10000
}
```

**Response includes:**
```json
{
  "message": "API key created successfully",
  "data": {
    "id": "...",
    "key_id": "mdc_...",
    "key_secret": "...",
    "hmac_secret_plaintext": "...",
    "institution": {...}
  },
  "warning": "IMPORTANT: Save these secrets now. They will never be shown again!"
}
```

### 3. Assign Permissions
```json
PUT /api-integration/api-keys/{id}/permissions
{
  "permissions": [
    "View_Practitioners",
    "View_License_Details",
    "Verify_License"
  ]
}
```

### 4. Send Documentation
```
GET /api-integration/api-keys/{id}/documentation
```

Returns complete integration guide with code examples.

## 🧪 Testing

### Test HMAC Authentication
1. Create a test institution and API key
2. Use the generated credentials to make a test request
3. Verify signature validation works
4. Test rate limiting
5. Test permission enforcement

### Monitor Requests
```
GET /api-integration/api-keys/{id}/logs
GET /api-integration/api-keys/{id}/stats
```

## 🔧 Configuration

### Rate Limits
Configured per API key (defaults):
- Per minute: 60 requests
- Per day: 10,000 requests

### Timestamp Window
5 minutes (configurable in `HmacAuthService::MAX_TIMESTAMP_DIFF`)

### Encryption
Uses CodeIgniter 4's encryption service with key from `.env`

## 📚 Next Steps

1. **Run migrations** to create database tables
2. **Test backend** API endpoints
3. **Build Angular UI** components
4. **Create external API endpoints** that will use HMAC auth
5. **Write integration tests**
6. **Document external API** endpoints for institutions

## 🛡️ Security Best Practices

1. **Never log secrets** - Only log key IDs
2. **Rotate keys regularly** - Use the rotation endpoint
3. **Monitor for suspicious activity** - Check logs regularly
4. **Enforce IP whitelisting** for sensitive integrations
5. **Use HTTPS only** for all API communications
6. **Set appropriate rate limits** per institution
7. **Revoke compromised keys immediately**
