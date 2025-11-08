# API Endpoint Testing Guide

This guide explains how to write and run tests for the API endpoints in this CodeIgniter 4 application.

## Table of Contents

1. [Setup](#setup)
2. [Test Structure](#test-structure)
3. [Writing Tests](#writing-tests)
4. [Running Tests](#running-tests)
5. [Available Assertions](#available-assertions)
6. [Examples](#examples)

## Setup

### 1. Configure Test Database

The test database configuration is in `phpunit.xml.dist`. Update these values for your environment:

```xml
<env name="database.tests.hostname" value="localhost"/>
<env name="database.tests.database" value="mdc_test"/>
<env name="database.tests.username" value="root"/>
<env name="database.tests.password" value=""/>
```

### 2. Create Test Database

Create a separate database for testing:

```bash
mysql -u root -p -e "CREATE DATABASE mdc_test;"
```

### 3. Run Migrations

Tests will automatically run migrations before each test suite. Ensure all migrations are up to date:

```bash
php spark migrate --all
```

## Test Structure

### Directory Organization

```
tests/
├── ApiTestCase.php           # Base test class with helper methods
├── api/                      # API endpoint tests
│   ├── AuthEndpointTest.php
│   ├── LicensesEndpointTest.php
│   └── ApplicationsEndpointTest.php
├── unit/                     # Unit tests
├── database/                 # Database tests
└── _support/                 # Test support files
```

### Base Test Class: `ApiTestCase`

All API endpoint tests should extend `ApiTestCase`, which provides:

- **Authentication helpers**: Create users, generate tokens
- **Permission management**: Assign roles and permissions
- **API request methods**: Simplified authenticated requests
- **Custom assertions**: Response validation helpers

## Writing Tests

### Basic Test Structure

```php
<?php

namespace Tests\Api;

use Tests\ApiTestCase;

class MyEndpointTest extends ApiTestCase
{
    protected string $apiPrefix = '/api-group-name';

    public function testEndpointReturnsSuccess()
    {
        // Create authenticated user with permissions
        $user = $this->createTestUser([], 'viewer', ['View_Something']);
        $this->generateToken($user);

        // Make API request
        $response = $this->apiGet('/endpoint');

        // Assert response
        $this->assertResponseSuccess();
    }
}
```

### Testing Public Endpoints

For endpoints that don't require authentication:

```php
public function testPublicEndpointReturnsData()
{
    // No authentication needed
    $response = $this->get('/api/app-settings');

    // Assert response
    $response->assertStatus(200);

    $data = $this->getResponseArray();
    $this->assertArrayHasKey('appName', $data);
}
```

### Testing Authenticated Endpoints

For endpoints requiring authentication:

```php
public function testAuthenticatedEndpointReturnsData()
{
    // Create user and generate token
    $user = $this->createTestUser();
    $this->generateToken($user);

    // Make authenticated request
    $response = $this->apiGet('/protected-endpoint');

    $this->assertResponseSuccess();
}
```

### Testing Permission-Based Endpoints

For endpoints requiring specific permissions:

```php
public function testEndpointRequiresPermission()
{
    // Test without permission - should return 403
    $user = $this->createTestUser([], 'basic_user', []);
    $this->generateToken($user);

    $response = $this->apiGet('/licenses/details');
    $this->assertForbidden();
}

public function testEndpointWithPermissionReturnsData()
{
    // Test with permission - should succeed
    $user = $this->createTestUser([], 'admin', ['View_License_Details']);
    $this->generateToken($user);

    $response = $this->apiGet('/licenses/details');
    $this->assertResponseSuccess();
}
```

### Testing Different HTTP Methods

```php
// GET request
public function testGetEndpoint()
{
    $this->generateToken($this->createTestUser());
    $response = $this->apiGet('/resource');
    $this->assertResponseSuccess();
}

// POST request
public function testCreateResource()
{
    $this->generateToken($this->createTestUser([], 'creator', ['Create_Resource']));

    $data = [
        'name' => 'Test Resource',
        'value' => 'test',
    ];

    $response = $this->apiPost('/resource', $data);
    $this->assertResponseSuccess();
}

// PUT request
public function testUpdateResource()
{
    $this->generateToken($this->createTestUser([], 'updater', ['Update_Resource']));

    $data = ['name' => 'Updated Name'];
    $response = $this->apiPut('/resource/123', $data);
    $this->assertResponseSuccess();
}

// DELETE request
public function testDeleteResource()
{
    $this->generateToken($this->createTestUser([], 'deleter', ['Delete_Resource']));

    $response = $this->apiDelete('/resource/123');
    $this->assertResponseSuccess();
}
```

### Testing Validation

```php
public function testCreateResourceWithInvalidDataReturnsValidationError()
{
    $this->generateToken($this->createTestUser([], 'creator', ['Create_Resource']));

    // Send incomplete data
    $data = [
        // Missing required fields
    ];

    $response = $this->apiPost('/resource', $data);
    $this->assertValidationError();
}
```

## Running Tests

### Run All Tests

```bash
composer test
# OR
./phpunit
```

### Run Specific Test File

```bash
./phpunit tests/api/AuthEndpointTest.php
```

### Run Specific Test Method

```bash
./phpunit --filter testLoginWithValidCredentials tests/api/AuthEndpointTest.php
```

### Run Tests with Coverage

```bash
./phpunit --coverage-text
# OR
./phpunit --coverage-html tests/coverage/
```

### Run Only API Tests

```bash
./phpunit tests/api/
```

## Available Assertions

### Response Status Assertions

```php
$this->assertResponseSuccess();           // 2xx status
$this->assertResponseStatus(200);         // Specific status
$this->assertUnauthorized();              // 401 status
$this->assertForbidden();                 // 403 status
$this->assertNotFound();                  // 404 status
$this->assertValidationError();           // 400 or 422 status
```

### JSON Response Assertions

```php
// Assert specific data in response
$this->assertResponseHasJson([
    'status' => 'success',
    'message' => 'Created successfully'
]);

// Assert JSON structure
$this->assertResponseJsonStructure([
    'data' => [
        'id',
        'name',
        'created_at'
    ]
]);

// Get response as array
$data = $this->getResponseArray();
$this->assertArrayHasKey('token', $data);
$this->assertEquals('value', $data['key']);
```

### Standard PHPUnit Assertions

All standard PHPUnit assertions are available:

```php
$this->assertTrue($condition);
$this->assertFalse($condition);
$this->assertEquals($expected, $actual);
$this->assertNotEquals($expected, $actual);
$this->assertNull($value);
$this->assertNotNull($value);
$this->assertArrayHasKey($key, $array);
$this->assertCount($expectedCount, $array);
$this->assertStringContainsString($needle, $haystack);
```

## Examples

### Complete Test Example

```php
<?php

namespace Tests\Api;

use Tests\ApiTestCase;

class LicensesEndpointTest extends ApiTestCase
{
    protected string $apiPrefix = '/licenses';

    public function testGetLicensesRequiresAuthentication()
    {
        // Test without authentication
        $response = $this->get($this->apiPrefix . '/details');
        $this->assertUnauthorized();
    }

    public function testGetLicensesRequiresPermission()
    {
        // Test with auth but without permission
        $user = $this->createTestUser([], 'basic_user', []);
        $this->generateToken($user);

        $response = $this->apiGet('/details');
        $this->assertForbidden();
    }

    public function testGetLicensesWithPermissionReturnsData()
    {
        // Test with auth and permission
        $user = $this->createTestUser(
            ['username' => 'admin', 'email' => 'admin@test.com'],
            'admin',
            ['View_License_Details']
        );
        $this->generateToken($user);

        $response = $this->apiGet('/details');

        // Assert success
        $this->assertResponseSuccess();

        // Assert response structure
        $data = $this->getResponseArray();
        $this->assertIsArray($data);
    }

    public function testCreateLicenseWithValidData()
    {
        $user = $this->createTestUser([], 'creator', ['Create_License_Details']);
        $this->generateToken($user);

        $licenseData = [
            'license_type' => 'medical',
            'license_number' => 'MED-' . uniqid(),
            'issue_date' => date('Y-m-d'),
            'expiry_date' => date('Y-m-d', strtotime('+1 year')),
        ];

        $response = $this->apiPost('/details', $licenseData);

        $this->assertTrue(
            in_array($response->getStatusCode(), [200, 201]),
            'Expected successful creation'
        );

        $data = $this->getResponseArray();
        $this->assertArrayHasKey('id', $data);
    }
}
```

### Testing with Database Fixtures

```php
public function testGetLicenseReturnsCorrectData()
{
    // Create test data
    $licenseModel = new LicenseModel();
    $licenseId = $licenseModel->insert([
        'license_type' => 'medical',
        'license_number' => 'TEST-123',
        'issue_date' => date('Y-m-d'),
    ]);

    // Create authenticated user
    $user = $this->createTestUser([], 'viewer', ['View_License_Details']);
    $this->generateToken($user);

    // Test endpoint
    $response = $this->apiGet("/details/{$licenseId}");

    $this->assertResponseSuccess();

    $data = $this->getResponseArray();
    $this->assertEquals('TEST-123', $data['license_number']);
}
```

## Best Practices

1. **One assertion per test**: Focus each test on a single behavior
2. **Descriptive test names**: Use `testMethodName_Scenario_ExpectedBehavior` pattern
3. **Arrange-Act-Assert**: Structure tests clearly with setup, execution, and verification
4. **Clean up**: Let the test framework handle cleanup with `DatabaseTestTrait`
5. **Independent tests**: Each test should be runnable independently
6. **Test edge cases**: Include tests for validation errors, missing permissions, etc.
7. **Use helper methods**: Leverage `ApiTestCase` helpers for common operations

## Troubleshooting

### Database Connection Errors

Ensure your test database exists and credentials in `phpunit.xml.dist` are correct:

```bash
mysql -u root -p -e "CREATE DATABASE mdc_test;"
```

### Migration Errors

Run migrations manually to check for issues:

```bash
CI_ENVIRONMENT=testing php spark migrate --all
```

### Permission Issues

If permission tests fail, verify permissions exist in database or are created in the test:

```php
$user = $this->createTestUser([], 'admin', ['View_License_Details']);
```

### JWT Token Issues

Ensure RSA keys exist for JWT signing:

```bash
openssl genrsa -out certs/private_key.pem 2048
openssl rsa -in certs/private_key.pem -pubout -out certs/public_key.pem
```

## Additional Resources

- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [CodeIgniter 4 Testing Guide](https://codeigniter.com/user_guide/testing/index.html)
- [CodeIgniter Shield Testing](https://shield.codeigniter.com/testing/)
