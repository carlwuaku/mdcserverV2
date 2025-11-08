<?php

namespace Tests;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use CodeIgniter\Shield\Entities\User;
use App\Models\UsersModel;
use App\Models\RolesModel;
use App\Models\PermissionsModel;
use App\Models\RolePermissionsModel;

/**
 * Base test case for API endpoint testing
 * Provides authentication and permission handling utilities
 */
abstract class ApiTestCase extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    /**
     * Should run seeding only once?
     */
    protected $seedOnce = false;

    /**
     * Should run migrations?
     */
    protected $migrate = true;

    /**
     * Should refresh the database?
     */
    protected $refresh = true;

    /**
     * Namespace for migrations
     */
    protected $namespace = null;

    /**
     * Test user instance
     */
    protected ?User $testUser = null;

    /**
     * Test JWT token
     */
    protected ?string $testToken = null;

    /**
     * Test role
     */
    protected ?object $testRole = null;

    /**
     * Base API URL prefix
     */
    protected string $apiPrefix = '';

    protected function setUp(): void
    {
        parent::setUp();
        $this->resetServices();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->testUser = null;
        $this->testToken = null;
        $this->testRole = null;
    }

    /**
     * Create a test user with optional role and permissions
     *
     * @param array $userData User data to create
     * @param string|null $roleName Role name to assign
     * @param array $permissions Permissions to assign to the role
     * @return User
     */
    protected function createTestUser(
        array $userData = [],
        ?string $roleName = null,
        array $permissions = []
    ): User {
        $userModel = new UsersModel();

        // Default user data
        $defaultData = [
            'username' => 'testuser_' . uniqid(),
            'email' => 'test_' . uniqid() . '@example.com',
            'password' => 'TestPassword123!',
            'active' => 1,
        ];

        $userData = array_merge($defaultData, $userData);

        // Create user using Shield's createUser method
        $user = $userModel->createUser($userData);

        if ($roleName) {
            $this->assignRoleToUser($user, $roleName, $permissions);
        }

        $this->testUser = $user;

        return $user;
    }

    /**
     * Assign a role with permissions to a user
     *
     * @param User $user
     * @param string $roleName
     * @param array $permissions
     * @return object Role object
     */
    protected function assignRoleToUser(User $user, string $roleName, array $permissions = []): object
    {
        $rolesModel = new RolesModel();
        $permissionsModel = new PermissionsModel();
        $rolePermissionsModel = new RolePermissionsModel();

        // Create or get role
        $role = $rolesModel->where('name', $roleName)->first();

        if (!$role) {
            $roleId = $rolesModel->insert([
                'name' => $roleName,
                'description' => "Test role: {$roleName}",
            ]);
            $role = $rolesModel->find($roleId);
        }

        // Add permissions to role
        foreach ($permissions as $permissionName) {
            $permission = $permissionsModel->where('name', $permissionName)->first();

            if (!$permission) {
                $permissionId = $permissionsModel->insert([
                    'name' => $permissionName,
                    'description' => "Test permission: {$permissionName}",
                ]);
                $permission = $permissionsModel->find($permissionId);
            }

            // Assign permission to role if not already assigned
            $existing = $rolePermissionsModel
                ->where('role_id', $role->id)
                ->where('permission_id', $permission->id)
                ->first();

            if (!$existing) {
                $rolePermissionsModel->insert([
                    'role_id' => $role->id,
                    'permission_id' => $permission->id,
                ]);
            }
        }

        // Assign role to user
        $user->addGroup($roleName);

        $this->testRole = $role;

        return $role;
    }

    /**
     * Generate JWT token for a user
     *
     * @param User|null $user User to generate token for (uses test user if null)
     * @return string JWT token
     */
    protected function generateToken(?User $user = null): string
    {
        $user = $user ?? $this->testUser;

        if (!$user) {
            throw new \RuntimeException('No user available to generate token. Create a test user first.');
        }

        // Generate token using Shield's JWT functionality
        $token = $user->generateAccessToken('test_token_' . uniqid());
        $this->testToken = $token->raw_token;

        return $this->testToken;
    }

    /**
     * Make an authenticated API request
     *
     * @param string $method HTTP method
     * @param string $url Endpoint URL (without base URL)
     * @param array $data Request data
     * @param array $headers Additional headers
     * @return \CodeIgniter\HTTP\ResponseInterface
     */
    protected function apiRequest(
        string $method,
        string $url,
        array $data = [],
        array $headers = []
    ) {
        if ($this->testToken) {
            $headers['Authorization'] = 'Bearer ' . $this->testToken;
        }

        $headers['Accept'] = $headers['Accept'] ?? 'application/json';
        $headers['Content-Type'] = $headers['Content-Type'] ?? 'application/json';

        $url = $this->apiPrefix . $url;

        $method = strtolower($method);

        switch ($method) {
            case 'get':
                return $this->withHeaders($headers)->get($url, $data);
            case 'post':
                return $this->withHeaders($headers)->post($url, $data);
            case 'put':
                return $this->withHeaders($headers)->put($url, $data);
            case 'patch':
                return $this->withHeaders($headers)->patch($url, $data);
            case 'delete':
                return $this->withHeaders($headers)->delete($url, $data);
            default:
                throw new \InvalidArgumentException("Unsupported HTTP method: {$method}");
        }
    }

    /**
     * Make an authenticated GET request
     */
    protected function apiGet(string $url, array $data = [], array $headers = [])
    {
        return $this->apiRequest('GET', $url, $data, $headers);
    }

    /**
     * Make an authenticated POST request
     */
    protected function apiPost(string $url, array $data = [], array $headers = [])
    {
        return $this->apiRequest('POST', $url, $data, $headers);
    }

    /**
     * Make an authenticated PUT request
     */
    protected function apiPut(string $url, array $data = [], array $headers = [])
    {
        return $this->apiRequest('PUT', $url, $data, $headers);
    }

    /**
     * Make an authenticated DELETE request
     */
    protected function apiDelete(string $url, array $data = [], array $headers = [])
    {
        return $this->apiRequest('DELETE', $url, $data, $headers);
    }

    /**
     * Assert response is successful (2xx status code)
     */
    protected function assertResponseSuccess($message = '')
    {
        $this->assertTrue(
            $this->response->isOK() || $this->response->getStatusCode() >= 200 && $this->response->getStatusCode() < 300,
            $message ?: 'Response status code is not successful: ' . $this->response->getStatusCode()
        );
    }

    /**
     * Assert response has specific status code
     */
    protected function assertResponseStatus(int $expectedStatus, $message = '')
    {
        $actualStatus = $this->response->getStatusCode();
        $this->assertEquals(
            $expectedStatus,
            $actualStatus,
            $message ?: "Expected status {$expectedStatus} but got {$actualStatus}"
        );
    }

    /**
     * Assert response JSON contains specific data
     */
    protected function assertResponseHasJson(array $expectedData, $message = '')
    {
        $responseData = json_decode($this->response->getBody(), true);

        foreach ($expectedData as $key => $value) {
            $this->assertArrayHasKey($key, $responseData, $message ?: "Response JSON missing key: {$key}");
            $this->assertEquals($value, $responseData[$key], $message ?: "Response JSON key {$key} has unexpected value");
        }
    }

    /**
     * Assert response JSON structure matches expected structure
     */
    protected function assertResponseJsonStructure(array $structure, $message = '')
    {
        $responseData = json_decode($this->response->getBody(), true);
        $this->assertArrayStructure($structure, $responseData, $message);
    }

    /**
     * Recursively assert array has expected structure
     */
    protected function assertArrayStructure(array $structure, array $data, $message = '')
    {
        foreach ($structure as $key => $value) {
            if (is_array($value)) {
                $this->assertArrayHasKey($key, $data, $message ?: "Missing key: {$key}");
                $this->assertArrayStructure($value, $data[$key], $message);
            } else {
                $this->assertArrayHasKey($value, $data, $message ?: "Missing key: {$value}");
            }
        }
    }

    /**
     * Get response as array
     */
    protected function getResponseArray(): array
    {
        return json_decode($this->response->getBody(), true) ?? [];
    }

    /**
     * Assert response is unauthorized (401)
     */
    protected function assertUnauthorized($message = '')
    {
        $this->assertResponseStatus(401, $message ?: 'Expected unauthorized response');
    }

    /**
     * Assert response is forbidden (403)
     */
    protected function assertForbidden($message = '')
    {
        $this->assertResponseStatus(403, $message ?: 'Expected forbidden response');
    }

    /**
     * Assert response is not found (404)
     */
    protected function assertNotFound($message = '')
    {
        $this->assertResponseStatus(404, $message ?: 'Expected not found response');
    }

    /**
     * Assert response is validation error (422 or 400)
     */
    protected function assertValidationError($message = '')
    {
        $status = $this->response->getStatusCode();
        $this->assertTrue(
            in_array($status, [400, 422]),
            $message ?: "Expected validation error (400 or 422) but got {$status}"
        );
    }
}
