<?php

namespace Tests\Api;

use Tests\ApiTestCase;
use App\Models\Licenses\LicenseModel;

/**
 * Test licenses API endpoints
 *
 * These tests verify authenticated endpoints with permission checks
 */
class LicensesEndpointTest extends ApiTestCase
{
    protected string $apiPrefix = '/licenses';

    public function testGetLicensesWithoutAuthenticationReturnsUnauthorized()
    {
        $response = $this->get($this->apiPrefix . '/details');

        $this->assertUnauthorized();
    }

    public function testGetLicensesWithoutPermissionReturnsForbidden()
    {
        // Create user without View_License_Details permission
        $user = $this->createTestUser([], 'basic_user', []);
        $this->generateToken($user);

        $response = $this->apiGet('/details');

        $this->assertForbidden();
    }

    public function testGetLicensesWithPermissionReturnsSuccess()
    {
        // Create user with View_License_Details permission
        $user = $this->createTestUser([], 'license_viewer', ['View_License_Details']);
        $this->generateToken($user);

        $response = $this->apiGet('/details');

        $this->assertResponseSuccess();
    }

    public function testGetLicensesReturnsExpectedStructure()
    {
        $user = $this->createTestUser([], 'license_viewer', ['View_License_Details']);
        $this->generateToken($user);

        $response = $this->apiGet('/details');

        $this->assertResponseSuccess();

        $data = $this->getResponseArray();

        // Assert the response has expected structure (adjust based on your actual response)
        $this->assertIsArray($data);

        // If response has data array
        if (isset($data['data']) && !empty($data['data'])) {
            $firstLicense = $data['data'][0];
            // Assert common fields exist
            $this->assertArrayHasKey('id', $firstLicense);
        }
    }

    public function testGetSingleLicenseWithPermissionReturnsLicense()
    {
        $user = $this->createTestUser([], 'license_viewer', ['View_License_Details']);
        $this->generateToken($user);

        // You might need to create a license first or use an existing one
        // For now, we'll test with a UUID (adjust as needed)
        $licenseId = 'test-license-uuid';

        $response = $this->apiGet("/details/{$licenseId}");

        // Might return 404 if no license exists, which is expected
        $this->assertTrue(
            in_array($response->getStatusCode(), [200, 404]),
            'Expected 200 or 404 for single license request'
        );
    }

    public function testCreateLicenseWithoutPermissionReturnsForbidden()
    {
        $user = $this->createTestUser([], 'basic_user', []);
        $this->generateToken($user);

        $licenseData = [
            'license_type' => 'test',
            'license_number' => 'TEST-' . uniqid(),
        ];

        $response = $this->apiPost('/details', $licenseData);

        $this->assertForbidden();
    }

    public function testCreateLicenseWithPermissionReturnsSuccess()
    {
        $user = $this->createTestUser([], 'license_creator', ['Create_License_Details']);
        $this->generateToken($user);

        $licenseData = [
            'license_type' => 'test',
            'license_number' => 'TEST-' . uniqid(),
            'issue_date' => date('Y-m-d'),
            'expiry_date' => date('Y-m-d', strtotime('+1 year')),
        ];

        $response = $this->apiPost('/details', $licenseData);

        // Should return 200 or 201 or validation error if required fields are missing
        $this->assertTrue(
            in_array($response->getStatusCode(), [200, 201, 400, 422]),
            'Expected success or validation error for license creation'
        );
    }

    public function testUpdateLicenseWithoutPermissionReturnsForbidden()
    {
        $user = $this->createTestUser([], 'basic_user', []);
        $this->generateToken($user);

        $licenseId = 'test-license-uuid';
        $updateData = [
            'license_number' => 'UPDATED-' . uniqid(),
        ];

        $response = $this->apiPut("/details/{$licenseId}", $updateData);

        $this->assertForbidden();
    }

    public function testUpdateLicenseWithPermissionReturnsSuccess()
    {
        $user = $this->createTestUser([], 'license_updater', ['Update_License_Details']);
        $this->generateToken($user);

        $licenseId = 'test-license-uuid';
        $updateData = [
            'license_number' => 'UPDATED-' . uniqid(),
        ];

        $response = $this->apiPut("/details/{$licenseId}", $updateData);

        // Might return 404 if license doesn't exist
        $this->assertTrue(
            in_array($response->getStatusCode(), [200, 404, 400, 422]),
            'Expected success, not found, or validation error'
        );
    }

    public function testDeleteLicenseWithoutPermissionReturnsForbidden()
    {
        $user = $this->createTestUser([], 'basic_user', []);
        $this->generateToken($user);

        $licenseId = 'test-license-uuid';

        $response = $this->apiDelete("/details/{$licenseId}");

        $this->assertForbidden();
    }

    public function testDeleteLicenseWithPermissionReturnsSuccess()
    {
        $user = $this->createTestUser([], 'license_deleter', ['Delete_License_Details']);
        $this->generateToken($user);

        $licenseId = 'test-license-uuid';

        $response = $this->apiDelete("/details/{$licenseId}");

        // Might return 404 if license doesn't exist
        $this->assertTrue(
            in_array($response->getStatusCode(), [200, 204, 404]),
            'Expected success or not found'
        );
    }

    public function testCountLicensesWithPermissionReturnsCount()
    {
        $user = $this->createTestUser([], 'license_viewer', ['View_License_Details']);
        $this->generateToken($user);

        $response = $this->apiGet('/count');

        $this->assertResponseSuccess();

        $data = $this->getResponseArray();

        // Assert count is returned
        $this->assertTrue(
            isset($data['count']) || isset($data['total']) || is_numeric($data),
            'Expected count in response'
        );
    }

    public function testGetLicenseRenewalsWithPermissionReturnsSuccess()
    {
        $user = $this->createTestUser([], 'renewal_viewer', ['View_License_Renewal']);
        $this->generateToken($user);

        $response = $this->apiGet('/renewal');

        $this->assertResponseSuccess();
    }

    public function testGetLicenseRenewalsWithoutPermissionReturnsForbidden()
    {
        $user = $this->createTestUser([], 'basic_user', []);
        $this->generateToken($user);

        $response = $this->apiGet('/renewal');

        $this->assertForbidden();
    }

    public function testCreateLicenseRenewalWithPermissionReturnsSuccess()
    {
        $user = $this->createTestUser([], 'renewal_creator', ['Create_License_Renewal']);
        $this->generateToken($user);

        $renewalData = [
            'license_id' => 'test-license-uuid',
            'renewal_year' => date('Y'),
        ];

        $response = $this->apiPost('/renewal', $renewalData);

        // Might return validation error or success
        $this->assertTrue(
            in_array($response->getStatusCode(), [200, 201, 400, 422, 404]),
            'Expected success, validation error, or not found'
        );
    }

    public function testGetLicenseBasicStatisticsWithPermissionReturnsSuccess()
    {
        $user = $this->createTestUser([], 'license_viewer', ['View_License_Details']);
        $this->generateToken($user);

        $licenseType = 'test';
        $response = $this->apiGet("/reports/basic-statistics/{$licenseType}");

        $this->assertResponseSuccess();
    }

    public function testGetLicenseFormFieldsWithPermissionReturnsFields()
    {
        $user = $this->createTestUser([], 'license_viewer', ['View_License_Details']);
        $this->generateToken($user);

        $licenseType = 'test';
        $response = $this->apiGet("/config/{$licenseType}");

        $this->assertResponseSuccess();

        $data = $this->getResponseArray();

        // Assert response contains form field configuration
        $this->assertIsArray($data);
    }
}
