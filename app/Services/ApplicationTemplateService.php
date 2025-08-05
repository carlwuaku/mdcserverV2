<?php
namespace App\Services;

use App\Helpers\ApplicationFormActionHelper;
use App\Helpers\CacheHelper;
use App\Helpers\Utils;
use App\Models\Applications\ApplicationTemplateModel;
use App\Traits\CacheInvalidatorTrait;
use Exception;

/**
 * Application Template Service - Handles application template management
 */
class ApplicationTemplateService
{
    use CacheInvalidatorTrait;

    /**
     * Get application templates with filtering and pagination
     */
    public function getApplicationTemplates(array $filters = []): array
    {
        $per_page = $filters['limit'] ?? 100;
        $page = $filters['page'] ?? 0;
        $withDeleted = ($filters['withDeleted'] ?? '') === "yes";
        $param = $filters['param'] ?? null;
        $sortBy = $filters['sortBy'] ?? "id";
        $sortOrder = $filters['sortOrder'] ?? "asc";

        // Generate cache key
        $cacheKey = "app_templates_" . md5(json_encode([
            $per_page,
            $page,
            $withDeleted,
            $param,
            $sortBy,
            $sortOrder
        ]));

        return CacheHelper::remember($cacheKey, function () use ($per_page, $page, $withDeleted, $param, $sortBy, $sortOrder) {
            $model = new ApplicationTemplateModel();
            $builder = $param ? $model->search($param) : $model->builder();

            if ($withDeleted) {
                $model->withDeleted();
            }

            $builder->orderBy($sortBy, $sortOrder);
            $totalBuilder = clone $builder;
            $total = $totalBuilder->countAllResults();
            $result = $builder->get($per_page, $page)->getResult();

            return [
                'data' => $result,
                'total' => $total,
                'displayColumns' => $model->getDisplayColumns()
            ];
        }, 3600);
    }

    /**
     * Get application template details
     */
    public function getApplicationTemplateDetails(string $uuid): ?array
    {
        $model = new ApplicationTemplateModel();
        $builder = $model->builder();
        $builder->where('uuid', $uuid)->orWhere('form_name', $uuid);
        $data = $model->first();

        if (!$data) {
            return null;
        }

        $data['data'] = json_decode($data['data'], true);

        return $data;
    }

    /**
     * Create application template
     */
    public function createApplicationTemplate(array $data): array
    {
        $rules = [
            "form_name" => "required|is_unique[application_form_templates.form_name]",
            "data" => "required"
        ];

        $validation = \Config\Services::validation();
        if (!$validation->setRules($rules)->run($data)) {
            throw new \InvalidArgumentException('Validation failed: ' . json_encode($validation->getErrors()));
        }

        $model = new ApplicationTemplateModel();
        if (!$model->insert($data)) {
            throw new \RuntimeException('Failed to create template: ' . json_encode($model->errors()));
        }

        // Invalidate cache
        $this->invalidateCache('app_templates_');
        $this->invalidateCache('app_config_');

        return [
            'success' => true,
            'message' => 'Application template created successfully'
        ];
    }

    /**
     * Update application template
     */
    public function updateApplicationTemplate(string $uuid, array $data): array
    {
        $rules = [
            "form_name" => "required",
            "data" => "required"
        ];

        $validation = \Config\Services::validation();
        if (!$validation->setRules($rules)->run($data)) {
            throw new \InvalidArgumentException('Validation failed: ' . json_encode($validation->getErrors()));
        }

        $model = new ApplicationTemplateModel();
        if (!$model->builder()->where(['uuid' => $uuid])->update($data)) {
            throw new \RuntimeException('Failed to update template: ' . json_encode($model->errors()));
        }

        // Invalidate cache
        $this->invalidateCache('app_templates_');
        $this->invalidateCache('app_config_');

        return [
            'success' => true,
            'message' => 'Application template updated successfully'
        ];
    }

    /**
     * Delete application template
     */
    public function deleteApplicationTemplate(string $uuid): array
    {
        $model = new ApplicationTemplateModel();
        if (!$model->delete($uuid)) {
            throw new \RuntimeException('Failed to delete template: ' . json_encode($model->errors()));
        }

        // Invalidate cache
        $this->invalidateCache('app_templates_');
        $this->invalidateCache('app_config_');

        return [
            'success' => true,
            'message' => 'Application template deleted successfully'
        ];
    }

    /**
     * Get application config
     */
    public function getApplicationConfig(string $formName, ?string $type = null): array
    {
        $cacheKey = "app_config_" . md5($formName . '_' . $type);

        return CacheHelper::remember($cacheKey, function () use ($formName, $type) {
            $form = str_replace(" ", "-", $formName);
            $configContents = file_get_contents(WRITEPATH . 'config_files/form-settings.json');
            $config = json_decode($configContents, true);
            $formConfig = !empty($type) ? $config[$form][$type] : $config[$form];

            return $formConfig;
        }, 3600);
    }

    /**
     * Get application status transitions
     */
    public function getApplicationStatusTransitions(string $form): array
    {
        if (empty(trim($form))) {
            throw new \InvalidArgumentException("Please provide a form type");
        }

        $applicationTemplateModel = new ApplicationTemplateModel();
        $template = $applicationTemplateModel->builder()
            ->select(['form_name', 'stages', 'initialStage', 'finalStage'])
            ->where('form_name', $form)
            ->get()
            ->getFirstRow();

        if (!$template) {
            throw new \RuntimeException("The selected form is not configured properly");
        }

        return json_decode($template->stages, true);
    }

    /**
     * Get application template action types
     */
    public function getApplicationTemplateActionTypes(): array
    {
        //UI controls: 
        // template: [''],
        // subject: [''],
        // admin_email: [''],

        // // API call configs
        // endpoint: [''],
        // method: ['GET'],
        // auth_token: [''],
        // headers: this.fb.array([]),
        // body_mapping: this.fb.array([]),
        // query_params
        // the config_type is used to determine how the action should be processed. it is used by the ui to determine how to render the action type. api_call would have controls for endpoint, method, auth_token, headers, body_mapping, query_params, etc.
        //internal_api_call would have controls for body_mapping only.
        $licenseTypes = [];
        $licenseSettings = Utils::getAppSettings("licenseTypes");
        if (!empty($licenseSettings)) {
            foreach ($licenseSettings as $key => $value) {
                if (is_array($value) && isset($value['fields'])) {
                    $actionType = [
                        'type' => "create_" . $key,
                        'config_type' => 'internal_api_call',
                        'label' => "Create " . ucfirst(str_replace('_', ' ', $key)) . " Instance",
                        'config' => [

                            'body_mapping' => [
                                "type" => $key,
                                "license_number" => "@{$value['uniqueKeyField']}",
                                "registration_date" => date('Y-m-d'),// Default to current date. TODO: review this
                            ]
                        ]
                    ];
                    //add the license fields to the body_mapping
                    $licenseModel = new \App\Models\Licenses\LicensesModel();
                    $licenseFormFields = $licenseModel->getFormFields();
                    // Merge default mapping with license fields
                    foreach ($licenseFormFields as $field) {
                        if (isset($field['name']) && !isset($actionType['config']['body_mapping'][$field['name']])) {
                            // Only add if not already set
                            // This ensures we don't overwrite any existing mappings
                            $actionType['config']['body_mapping'][$field['name']] = '@' . $field['name'];
                        }
                    }
                    // Add license-specific fields to body_mapping
                    foreach ($value['fields'] as $field) {
                        if (isset($field['name'])) {
                            $actionType['config']['body_mapping'][$field['name']] = '@' . $field['name'];
                        }
                    }
                    $licenseTypes[] = $actionType;
                }
            }
        }
        $defaults = [
            ['type' => 'email', 'config_type' => 'email', 'label' => 'Send Email', 'config' => ['template' => '', 'subject' => '']],
            ['type' => 'admin_email', 'config_type' => 'admin_email', 'label' => 'Send Admin Email', 'config' => ['template' => '', 'subject' => '', 'admin_email' => '']],
            [
                'type' => 'api_call',
                'config_type' => 'api_call',
                'label' => 'API Call',
                'config' => [
                    'endpoint' => '',
                    'method' => 'GET',
                    'auth_token' => '__self__',
                    'headers' => [],
                    'body_mapping' => [],
                    'query_params' => []
                ]
            ]

        ];
        return array_merge($defaults, $licenseTypes);


    }

    /**
     * Get application templates API default configs
     */
    public function getApplicationTemplatesApiDefaultConfigs(): array
    {
        $licenseSettings = Utils::getAppSettings("licenseTypes");
        $defaultConfigs = [];
        $licenseModel = new \App\Models\Licenses\LicensesModel();
        $licenseFormFields = $licenseModel->getFormFields();

        // Create default mapping from license form fields
        $defaultMapping = [];
        foreach ($licenseFormFields as $field) {
            $fieldName = $field['name'];
            $defaultMapping[$fieldName] = '@' . $fieldName;
        }

        // Loop through each license type and create default configuration
        foreach ($licenseSettings as $key => $value) {
            if (is_array($value)) {
                $bodyMapping = [];

                foreach ($value['fields'] as $field) {
                    $fieldName = $field['name'];
                    $bodyMapping[$fieldName] = '@' . $fieldName;
                }

                $label = ucfirst(str_replace('_', ' ', $key));
                $defaultConfigs[] = [
                    'name' => $key,
                    'label' => "Create {$label} Instance",
                    'type' => "create_{$key}",
                    'config' => [
                        'endpoint' => base_url("licenses/details"),
                        'method' => 'POST',
                        'headers' => [
                            'Content-Type' => 'application/json'
                        ],
                        'auth_token' => '__self__',
                        'body_mapping' => array_merge($bodyMapping, $defaultMapping)
                    ]
                ];
            }
        }

        return $defaultConfigs;
    }

    /**
     * Test an action configuration
     */
    public function testAction(array $actionData, array $sampleData = []): array
    {
        if (!isset($actionData['type']) || !isset($actionData['config'])) {
            throw new \InvalidArgumentException('Invalid action configuration. Must include type and config.');
        }

        // Validate action type
        $allowedTypes = ['email', 'admin_email', 'api_call'];
        if (!in_array($actionData['type'], $allowedTypes)) {
            throw new \InvalidArgumentException('Invalid action type. Allowed types: ' . implode(', ', $allowedTypes));
        }

        // Additional validation for API calls
        if ($actionData['type'] === 'api_call') {
            $this->validateApiCallConfig($actionData['config']);
        }

        // Add default test data if sample data is empty
        if (empty($sampleData)) {
            $sampleData = $this->getDefaultTestData();
        }

        // Convert action array to object for the helper
        $actionObject = (object) [
            'type' => $actionData['type'],
            'config' => (object) $actionData['config']
        ];

        // Test the action
        $testResult = $this->runTestAction($actionObject, $sampleData);

        return [
            'success' => true,
            'message' => 'Action test completed',
            'test_result' => $testResult,
            'sample_data_used' => $sampleData
        ];
    }

    /**
     * Get common application templates
     */
    public function getCommonApplicationTemplates(): array
    {
        $settings = Utils::getAppSettings("commonApplicationTemplates");
        if (empty($settings)) {
            throw new \RuntimeException("No common application templates found");
        }

        $data = [];
        foreach ($settings as $key => $value) {
            $data[] = $value;
        }

        return $data;
    }

    // Private helper methods

    private function validateApiCallConfig(array $config): void
    {
        if (empty($config['endpoint'])) {
            throw new \InvalidArgumentException('API call endpoint is required');
        }

        if (empty($config['method'])) {
            throw new \InvalidArgumentException('API call method is required');
        }

        // Validate HTTP method
        $allowedMethods = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH'];
        if (!in_array(strtoupper($config['method']), $allowedMethods)) {
            throw new \InvalidArgumentException('Invalid HTTP method. Allowed methods: ' . implode(', ', $allowedMethods));
        }

        // Validate URL format
        if (!filter_var($config['endpoint'], FILTER_VALIDATE_URL) && !$this->isRelativeUrl($config['endpoint'])) {
            throw new \InvalidArgumentException('Invalid endpoint URL format');
        }
    }

    private function getDefaultTestData(): array
    {
        return [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
            'phone' => '+1234567890',
            'application_code' => 'TEST_' . uniqid(),
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s'),
            'uuid' => uniqid()
        ];
    }

    private function runTestAction($action, $data): array
    {
        $startTime = microtime(true);

        try {
            // For API calls, handle relative URLs
            if ($action->type === 'api_call') {
                $originalEndpoint = $action->config['endpoint'];

                if ($this->isRelativeUrl($originalEndpoint)) {
                    $baseUrl = base_url();
                    $action->config['endpoint'] = rtrim($baseUrl, '/') . '/' . ltrim($originalEndpoint, '/');
                }

                log_message('info', 'Testing API call to: ' . $action->config['endpoint']);
            }

            // Run the action
            $result = ApplicationFormActionHelper::runAction($action, $data);

            $endTime = microtime(true);
            $executionTime = round(($endTime - $startTime) * 1000, 2);

            return [
                'status' => 'success',
                'execution_time_ms' => $executionTime,
                'action_type' => $action->type,
                'endpoint_called' => $action->type === 'api_call' ? $action->config['endpoint'] : null,
                'result' => $result
            ];

        } catch (\Throwable $e) {
            $endTime = microtime(true);
            $executionTime = round(($endTime - $startTime) * 1000, 2);

            return [
                'status' => 'error',
                'execution_time_ms' => $executionTime,
                'action_type' => $action->type,
                'error' => $e->getMessage(),
                'endpoint_called' => $action->type === 'api_call' ? $action->config['endpoint'] : null
            ];
        }
    }

    private function isRelativeUrl($url): bool
    {
        return !preg_match('/^https?:\/\//', $url);
    }

    public function getApplicationTemplateForFilling(string $uuid)
    {
        $model = new ApplicationTemplateModel();
        $builder = $model->builder();
        $builder->select("uuid, header,form_name, footer, data, open_date, close_date, on_submit_message, description, guidelines")->where('uuid', $uuid)->orWhere('form_name', $uuid);
        $data = $model->first();
        if (!$data) {
            throw new Exception("Application template not found");
        }
        if (!empty($data['open_date']) && !empty($data['close_date'])) {
            $currentDate = date("Y-m-d");
            if ($currentDate < $data['open_date'] || $currentDate > $data['close_date']) {
                throw new Exception("Application template is not available for filling");
            }
        }
        $data['data'] = json_decode($data['data'], true);
        $data['stages'] = json_decode($data['stages'], true);
        return $data;
    }
}