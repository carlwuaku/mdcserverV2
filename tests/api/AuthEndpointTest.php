<?php

namespace Tests\Api;

use Tests\ApiTestCase;

/**
 * Test public authentication endpoints
 *
 * These tests verify unauthenticated endpoints like login, register, and app settings
 */
class AuthEndpointTest extends ApiTestCase
{
    protected string $apiPrefix = '/api';

    public function testAppSettingsEndpointReturnsSuccess()
    {
        $response = $this->get($this->apiPrefix . '/app-settings');

        $response->assertStatus(200);
        $response->assertJSONFragment(['appName' => true], true); // Assert appName key exists
    }

    public function testAppSettingsReturnsExpectedStructure()
    {
        $response = $this->get($this->apiPrefix . '/app-settings');

        $response->assertStatus(200);

        $data = $this->getResponseArray();

        // Assert expected keys are present
        $this->assertArrayHasKey('appName', $data);
        $this->assertArrayHasKey('appVersion', $data);
        $this->assertArrayHasKey('recaptchaSiteKey', $data);
    }

    public function testLoginWithValidCredentialsReturnsToken()
    {
        // Create a test user first
        $userData = [
            'username' => 'logintest',
            'email' => 'logintest@example.com',
            'password' => 'SecurePassword123!',
            'active' => 1,
        ];

        $user = $this->createTestUser($userData);

        // Attempt login
        $response = $this->post($this->apiPrefix . '/login', [
            'email' => $userData['email'],
            'password' => $userData['password'],
        ]);

        $response->assertStatus(200);

        $data = $this->getResponseArray();

        // Assert token is returned
        $this->assertArrayHasKey('token', $data);
        $this->assertNotEmpty($data['token']);
    }

    public function testLoginWithInvalidCredentialsReturnsError()
    {
        $response = $this->post($this->apiPrefix . '/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'WrongPassword123!',
        ]);

        // Should return error status (401 or 400)
        $this->assertTrue(
            in_array($response->getStatusCode(), [400, 401]),
            'Expected 400 or 401 status for invalid login'
        );
    }

    public function testLoginWithMissingEmailReturnsValidationError()
    {
        $response = $this->post($this->apiPrefix . '/login', [
            'password' => 'SomePassword123!',
        ]);

        $this->assertValidationError();
    }

    public function testLoginWithMissingPasswordReturnsValidationError()
    {
        $response = $this->post($this->apiPrefix . '/login', [
            'email' => 'test@example.com',
        ]);

        $this->assertValidationError();
    }

    public function testRegisterWithValidDataCreatesUser()
    {
        $userData = [
            'username' => 'newuser_' . uniqid(),
            'email' => 'newuser_' . uniqid() . '@example.com',
            'password' => 'SecurePassword123!',
            'password_confirm' => 'SecurePassword123!',
        ];

        $response = $this->post($this->apiPrefix . '/register', $userData);

        // Depending on your implementation, this might return 200 or 201
        $this->assertTrue(
            in_array($response->getStatusCode(), [200, 201]),
            'Expected 200 or 201 status for successful registration'
        );
    }

    public function testRegisterWithDuplicateEmailReturnsError()
    {
        // Create a user first
        $userData = [
            'username' => 'existing',
            'email' => 'existing@example.com',
            'password' => 'SecurePassword123!',
        ];

        $this->createTestUser($userData);

        // Try to register with the same email
        $response = $this->post($this->apiPrefix . '/register', [
            'username' => 'different',
            'email' => 'existing@example.com',
            'password' => 'SecurePassword123!',
            'password_confirm' => 'SecurePassword123!',
        ]);

        $this->assertValidationError();
    }

    public function testRegisterWithMismatchedPasswordsReturnsError()
    {
        $response = $this->post($this->apiPrefix . '/register', [
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => 'Password123!',
            'password_confirm' => 'DifferentPassword123!',
        ]);

        $this->assertValidationError();
    }

    public function testRegisterWithWeakPasswordReturnsError()
    {
        $response = $this->post($this->apiPrefix . '/register', [
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => '123',
            'password_confirm' => '123',
        ]);

        $this->assertValidationError();
    }

    public function testMobileLoginWithValidCredentialsReturnsToken()
    {
        $userData = [
            'username' => 'mobileuser',
            'email' => 'mobile@example.com',
            'password' => 'SecurePassword123!',
            'active' => 1,
        ];

        $user = $this->createTestUser($userData);

        $response = $this->post($this->apiPrefix . '/mobile-login', [
            'email' => $userData['email'],
            'password' => $userData['password'],
        ]);

        $response->assertStatus(200);

        $data = $this->getResponseArray();
        $this->assertArrayHasKey('token', $data);
    }

    public function testVerifyRecaptchaWithInvalidTokenReturnsError()
    {
        $response = $this->post($this->apiPrefix . '/verify-recaptcha', [
            'token' => 'invalid_recaptcha_token',
        ]);

        // Should return error or false
        $this->assertTrue(
            in_array($response->getStatusCode(), [400, 401, 403]),
            'Expected error status for invalid recaptcha'
        );
    }
}
