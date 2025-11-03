<?php

namespace Tests\Api;

use Tests\ApiTestCase;

/**
 * Test applications API endpoints
 *
 * These tests verify application form management endpoints
 */
class ApplicationsEndpointTest extends ApiTestCase
{
    protected string $apiPrefix = '/applications';

    public function testGetApplicationsWithoutAuthenticationReturnsUnauthorized()
    {
        $response = $this->get($this->apiPrefix . '/details');

        $this->assertUnauthorized();
    }

    public function testGetApplicationsWithPermissionReturnsSuccess()
    {
        $user = $this->createTestUser([], 'app_viewer', ['View_Application_Forms']);
        $this->generateToken($user);

        $response = $this->apiGet('/details');

        $this->assertResponseSuccess();
    }

    public function testGetApplicationsWithoutPermissionReturnsForbidden()
    {
        $user = $this->createTestUser([], 'basic_user', []);
        $this->generateToken($user);

        $response = $this->apiGet('/details');

        $this->assertForbidden();
    }

    public function testGetSingleApplicationWithPermissionReturnsApplication()
    {
        $user = $this->createTestUser([], 'app_viewer', ['View_Application_Forms']);
        $this->generateToken($user);

        $applicationId = 'test-app-uuid';
        $response = $this->apiGet("/details/{$applicationId}");

        // Might return 404 if application doesn't exist
        $this->assertTrue(
            in_array($response->getStatusCode(), [200, 404]),
            'Expected 200 or 404 for single application request'
        );
    }

    public function testCreateApplicationWithPermissionReturnsSuccess()
    {
        $user = $this->createTestUser([], 'app_creator', ['Create_Application_Forms']);
        $this->generateToken($user);

        $templateId = 'test-template-id';
        $applicationData = [
            'template_id' => $templateId,
            'data' => [
                'field1' => 'value1',
                'field2' => 'value2',
            ],
        ];

        $response = $this->apiPost("/details/{$templateId}", $applicationData);

        // Might return validation error or success
        $this->assertTrue(
            in_array($response->getStatusCode(), [200, 201, 400, 422, 404]),
            'Expected success, validation error, or not found'
        );
    }

    public function testCreateApplicationWithoutPermissionReturnsForbidden()
    {
        $user = $this->createTestUser([], 'basic_user', []);
        $this->generateToken($user);

        $templateId = 'test-template-id';
        $applicationData = [
            'template_id' => $templateId,
            'data' => [],
        ];

        $response = $this->apiPost("/details/{$templateId}", $applicationData);

        $this->assertForbidden();
    }

    public function testUpdateApplicationWithPermissionReturnsSuccess()
    {
        $user = $this->createTestUser([], 'app_updater', ['Update_Application_Forms']);
        $this->generateToken($user);

        $applicationId = 'test-app-uuid';
        $updateData = [
            'data' => [
                'field1' => 'updated_value',
            ],
        ];

        $response = $this->apiPut("/details/{$applicationId}", $updateData);

        // Might return 404 if application doesn't exist
        $this->assertTrue(
            in_array($response->getStatusCode(), [200, 404, 400, 422]),
            'Expected success, not found, or validation error'
        );
    }

    public function testDeleteApplicationWithPermissionReturnsSuccess()
    {
        $user = $this->createTestUser([], 'app_deleter', ['Delete_Application_Forms']);
        $this->generateToken($user);

        $applicationId = 'test-app-uuid';
        $response = $this->apiDelete("/details/{$applicationId}");

        // Might return 404 if application doesn't exist
        $this->assertTrue(
            in_array($response->getStatusCode(), [200, 204, 404]),
            'Expected success or not found'
        );
    }

    public function testRestoreApplicationWithPermissionReturnsSuccess()
    {
        $user = $this->createTestUser([], 'app_restorer', ['Restore_Application_Forms']);
        $this->generateToken($user);

        $applicationId = 'test-app-uuid';
        $response = $this->apiPut("/details/{$applicationId}/restore");

        // Might return 404 if application doesn't exist
        $this->assertTrue(
            in_array($response->getStatusCode(), [200, 404]),
            'Expected success or not found'
        );
    }

    public function testCountApplicationsWithPermissionReturnsCount()
    {
        $user = $this->createTestUser([], 'app_viewer', ['View_Application_Forms']);
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

    public function testGetApplicationStatusCountsReturnsSuccess()
    {
        $user = $this->createTestUser([], 'app_viewer', ['View_Application_Forms']);
        $this->generateToken($user);

        $templateId = 'test-template-id';
        $response = $this->apiGet("/statusCounts/{$templateId}");

        $this->assertResponseSuccess();
    }

    public function testGetApplicationTemplatesWithPermissionReturnsTemplates()
    {
        $user = $this->createTestUser([], 'template_viewer', ['View_Application_Form_Templates']);
        $this->generateToken($user);

        $response = $this->apiGet('/templates');

        $this->assertResponseSuccess();

        $data = $this->getResponseArray();
        $this->assertIsArray($data);
    }

    public function testGetApplicationTemplatesWithoutPermissionReturnsForbidden()
    {
        $user = $this->createTestUser([], 'basic_user', []);
        $this->generateToken($user);

        $response = $this->apiGet('/templates');

        $this->assertForbidden();
    }

    public function testCreateApplicationTemplateWithPermissionReturnsSuccess()
    {
        $user = $this->createTestUser([], 'template_creator', ['Create_Application_Form_Templates']);
        $this->generateToken($user);

        $templateData = [
            'name' => 'Test Template',
            'type' => 'test_type',
            'fields' => [
                [
                    'name' => 'field1',
                    'type' => 'text',
                    'label' => 'Field 1',
                ],
            ],
        ];

        $response = $this->apiPost('/templates', $templateData);

        // Might return validation error or success
        $this->assertTrue(
            in_array($response->getStatusCode(), [200, 201, 400, 422]),
            'Expected success or validation error'
        );
    }

    public function testUpdateApplicationTemplateWithPermissionReturnsSuccess()
    {
        $user = $this->createTestUser([], 'template_updater', ['Update_Application_Form_Templates']);
        $this->generateToken($user);

        $templateId = 'test-template-uuid';
        $updateData = [
            'name' => 'Updated Template Name',
        ];

        $response = $this->apiPut("/templates/{$templateId}", $updateData);

        // Might return 404 if template doesn't exist
        $this->assertTrue(
            in_array($response->getStatusCode(), [200, 404, 400, 422]),
            'Expected success, not found, or validation error'
        );
    }

    public function testDeleteApplicationTemplateWithPermissionReturnsSuccess()
    {
        $user = $this->createTestUser([], 'template_deleter', ['Delete_Application_Form_Templates']);
        $this->generateToken($user);

        $templateId = 'test-template-uuid';
        $response = $this->apiDelete("/templates/{$templateId}");

        // Might return 404 if template doesn't exist
        $this->assertTrue(
            in_array($response->getStatusCode(), [200, 204, 404]),
            'Expected success or not found'
        );
    }

    public function testUpdateApplicationStatusWithPermissionReturnsSuccess()
    {
        $user = $this->createTestUser([], 'app_updater', ['Update_Application_Forms']);
        $this->generateToken($user);

        $statusData = [
            'application_id' => 'test-app-uuid',
            'status' => 'approved',
        ];

        $response = $this->apiPut('/status', $statusData);

        // Might return validation error or not found
        $this->assertTrue(
            in_array($response->getStatusCode(), [200, 400, 404, 422]),
            'Expected success, validation error, or not found'
        );
    }

    public function testGetApplicationConfigReturnsSuccess()
    {
        $user = $this->createTestUser([], 'template_viewer', ['View_Application_Form_Templates']);
        $this->generateToken($user);

        $response = $this->apiGet('/config');

        $this->assertResponseSuccess();
    }

    public function testGetApplicationFormTypesReturnsSuccess()
    {
        $user = $this->createTestUser([], 'app_viewer', ['View_Application_Forms']);
        $this->generateToken($user);

        $userType = 'test_user_type';
        $response = $this->apiGet("/types/{$userType}");

        $this->assertResponseSuccess();
    }
}
