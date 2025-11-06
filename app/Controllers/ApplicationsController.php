<?php

namespace App\Controllers;

use App\Helpers\AuthHelper;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;
use App\Traits\CacheInvalidatorTrait;
use App\Services\ApplicationService;
use App\Services\ApplicationTemplateService;


class ApplicationsController extends ResourceController
{
    use CacheInvalidatorTrait;
    protected $baseBuilderJsonQuery;
    private ApplicationService $applicationService;
    private ApplicationTemplateService $templateService;
    public function __construct()
    {
        $this->applicationService = \Config\Services::applicationService();
        $this->templateService = \Config\Services::applicationTemplateService();
    }

    public function createApplication($formType)
    {
        try {
            $payload = (array) $this->request->getVar();
            $result = $this->applicationService->createApplication($formType, $payload);

            return $this->respond($result, ResponseInterface::HTTP_OK);

        } catch (\InvalidArgumentException $e) {
            return $this->respond(['message' => $e->getMessage()], ResponseInterface::HTTP_BAD_REQUEST);
        } catch (\RuntimeException $e) {
            return $this->respond(['message' => $e->getMessage()], ResponseInterface::HTTP_NOT_FOUND);
        } catch (\Throwable $e) {
            log_message('error', $e);
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Creates an application form from the portal. overwrites the applicant_unique_id
     * @param string $formType type of application form to create
     * @return \CodeIgniter\HTTP\Response
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     * @throws \Throwable
     */
    public function createApplicationFromPortal($formType)
    {
        try {
            $payload = (array) $this->request->getVar();
            $userId = auth("tokens")->id();
            $uniqueId = AuthHelper::getAuthUserUniqueId($userId);
            $payload['applicant_unique_id'] = $uniqueId;
            $result = $this->applicationService->createApplication($formType, $payload);

            return $this->respond($result, ResponseInterface::HTTP_OK);

        } catch (\InvalidArgumentException $e) {
            return $this->respond(['message' => $e->getMessage()], ResponseInterface::HTTP_BAD_REQUEST);
        } catch (\RuntimeException $e) {
            return $this->respond(['message' => $e->getMessage()], ResponseInterface::HTTP_NOT_FOUND);
        } catch (\Throwable $e) {
            log_message('error', $e);
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // Legacy approval methods (can be moved to separate service if needed)

    public function approvePermanentApplication()
    {
        try {
            $applicationUuid = $this->request->getVar('application_uuid');
            $registrationNumber = $this->request->getVar('registration_number');

            if (!$applicationUuid || !$registrationNumber) {
                return $this->respond(['message' => 'Application UUID and registration number are required'], ResponseInterface::HTTP_BAD_REQUEST);
            }

            // Get application details
            $applicationDetails = $this->applicationService->getApplicationDetails($applicationUuid);
            if (!$applicationDetails) {
                return $this->respond(['message' => 'Application not found'], ResponseInterface::HTTP_NOT_FOUND);
            }

            $result = $this->applicationService->approvePermanentApplication($applicationDetails['data'], $registrationNumber);
            return $this->respond($result, ResponseInterface::HTTP_OK);

        } catch (\Throwable $e) {
            log_message("error", $e);
            return $this->respond(['message' => 'Server error: ' . $e], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function approvePortalEdit()
    {
        try {
            $applicationDetails = $this->request->getVar();

            $result = $this->applicationService->approvePortalEdit($applicationDetails);
            return $this->respond($result, ResponseInterface::HTTP_OK);

        } catch (\Throwable $e) {
            log_message("error", $e);
            return $this->respond(['message' => 'Server error: ' . $e], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // Private helper methods

    /**
     * Extract request filters from various request methods
     */
    private function extractRequestFilters(): array
    {
        $filters = [];

        // Get common parameters
        $commonParams = [
            'limit',
            'page',
            'withDeleted',
            'param',
            'sortBy',
            'sortOrder',
            'application_code',
            'status',
            'start_date',
            'end_date',
            'practitioner_type',
            'form_type'
        ];

        foreach ($commonParams as $param) {
            $value = $this->request->getVar($param);
            if ($value !== null) {
                $filters[$param] = $value;
            }
        }

        // Get all child_ parameters for JSON field filtering
        $allParams = $this->request->getGet();
        if (is_array($allParams)) {
            foreach ($allParams as $key => $value) {
                if (strpos($key, 'child_') === 0) {
                    $filters[$key] = $value;
                }
            }
        }

        return $filters;
    }

    public function updateApplication($uuid)
    {
        try {
            $requestData = $this->request->getVar();
            $result = $this->applicationService->updateApplication($uuid, (array) $requestData);

            return $this->respond($result, ResponseInterface::HTTP_OK);

        } catch (\RuntimeException $e) {
            return $this->respond(['message' => $e], ResponseInterface::HTTP_NOT_FOUND);
        } catch (\Throwable $e) {
            log_message('error', $e);
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_BAD_REQUEST);
        }
    }

    public function updateApplicationStatus()
    {
        try {
            $status = $this->request->getVar('status');
            $applicationType = $this->request->getVar('form_type');
            $applicationIds = $this->request->getVar('applicationIds');
            $userId = auth("tokens")->id();

            // Capture any additional submitted data for timeline
            $submittedData = $this->request->getVar('statusData');

            // Remove null values
            $submittedData = array_filter($submittedData, fn($val) => $val !== null);

            $result = $this->applicationService->updateApplicationStatus(
                $applicationType,
                $status,
                $applicationIds,
                $userId,
                $submittedData
            );

            return $this->respond($result, ResponseInterface::HTTP_OK);

        } catch (\InvalidArgumentException $e) {
            return $this->respond(['message' => $e], ResponseInterface::HTTP_BAD_REQUEST);
        } catch (\RuntimeException $e) {
            $statusCode = str_contains($e, 'not found')
                ? ResponseInterface::HTTP_NOT_FOUND
                : ResponseInterface::HTTP_FORBIDDEN;
            return $this->respond(['message' => $e], $statusCode);
        } catch (\Throwable $e) {
            log_message('error', $e);
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function deleteApplication($uuid)
    {
        try {
            $result = $this->applicationService->deleteApplication($uuid);
            return $this->respond($result, ResponseInterface::HTTP_OK);

        } catch (\RuntimeException $e) {
            return $this->respond(['message' => $e], ResponseInterface::HTTP_NOT_FOUND);
        } catch (\Throwable $e) {
            log_message('error', $e);
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function restoreApplication($uuid)
    {
        try {
            $result = $this->applicationService->restoreApplication($uuid);
            return $this->respond($result, ResponseInterface::HTTP_OK);

        } catch (\RuntimeException $e) {
            return $this->respond(['message' => $e], ResponseInterface::HTTP_BAD_REQUEST);
        } catch (\Throwable $e) {
            log_message('error', $e);
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getApplication($uuid)
    {
        try {
            $result = $this->applicationService->getApplicationDetails($uuid);

            if (!$result) {
                return $this->respond(['message' => "Application not found"], ResponseInterface::HTTP_NOT_FOUND);
            }

            return $this->respond($result, ResponseInterface::HTTP_OK);

        } catch (\Throwable $e) {
            log_message('error', $e);
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getApplications()
    {
        try {
            $filters = $this->extractRequestFilters();
            $result = $this->applicationService->getApplications($filters);

            return $this->respond($result, ResponseInterface::HTTP_OK);

        } catch (\Throwable $e) {
            log_message('error', $e);
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getApplicationsByUser()
    {
        try {
            $userId = auth("tokens")->id();
            $uniqueId = AuthHelper::getAuthUserUniqueId($userId);
            $status = $this->request->getGet('status');
            $filters = [
                "applicant_unique_id" => $uniqueId,
            ];
            $exclusionFilters = [];
            $formType = $this->request->getGet('form_type');
            if ($formType) {
                $filters["form_type"] = $formType;
            } else {
                //Portal Edits are a special kind of application. if not specified, exclude it

                $exclusionFilters["form_type"] = PORTAL_EDIT_FORM_TYPE;
            }

            if ($status) {
                $filters["status"] = $status;
            }
            $result = $this->applicationService->getApplications($filters, $exclusionFilters);

            return $this->respond($result, ResponseInterface::HTTP_OK);

        } catch (\Throwable $e) {
            log_message('error', $e);
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getApplicationFormTypes($field)
    {
        try {
            $templates = $this->applicationService->getApplicationFormTypes($field);
            return $this->respond(['data' => $templates], ResponseInterface::HTTP_OK);

        } catch (\Throwable $e) {
            log_message('error', $e);
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getApplicationStatuses($form)
    {
        try {
            $data = $this->applicationService->getApplicationStatuses($form);
            return $this->respond([
                'data' => $data,
                'displayColumns' => ["form_type", "status", "count"]
            ], ResponseInterface::HTTP_OK);

        } catch (\InvalidArgumentException $e) {
            log_message('error', $e);
            return $this->respond([
                'message' => $e,
                'displayColumns' => ["form_type", "status", "count"]
            ], ResponseInterface::HTTP_BAD_REQUEST);
        } catch (\Throwable $e) {
            log_message('error', $e);
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function countApplications()
    {
        try {
            $rules = [
                "start_date" => "if_exist|valid_date",
                "end_date" => "if_exist|valid_date",
            ];

            if (!$this->validate($rules)) {
                return $this->respond($this->validator->getErrors(), ResponseInterface::HTTP_BAD_REQUEST);
            }

            $filters = $this->extractRequestFilters();
            $total = $this->applicationService->countApplications($filters);

            return $this->respond(['data' => $total], ResponseInterface::HTTP_OK);

        } catch (\Throwable $e) {
            log_message("error", $e);
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // Application Template Operations

    public function getApplicationConfig(string $formName, ?string $type = null)
    {
        try {
            $formConfig = $this->templateService->getApplicationConfig($formName, $type);
            return $this->respond(['data' => $formConfig], ResponseInterface::HTTP_OK);

        } catch (\Throwable $e) {
            log_message('error', $e);
            return $this->respond(['message' => "Server error:" . $e], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getApplicationTemplates()
    {
        try {
            $userId = auth("tokens")->id();
            $userData = AuthHelper::getAuthUser($userId);
            $filters = $this->extractRequestFilters();
            $result = $this->templateService->getApplicationTemplates($userData, $filters);

            return $this->respond($result, ResponseInterface::HTTP_OK);

        } catch (\Throwable $e) {
            log_message('error', $e);
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getApplicationTemplateForFilling(string $uuid)
    {
        try {
            $result = $this->templateService->getApplicationTemplateForFilling($uuid);
            return $this->respond(["data" => $result], ResponseInterface::HTTP_OK);

        } catch (\RuntimeException $e) {
            return $this->respond(['message' => $e], ResponseInterface::HTTP_NOT_FOUND);
        } catch (\Throwable $e) {
            log_message('error', $e);
            return $this->respond(['message' => "Application not found at this time. Please try again later"], ResponseInterface::HTTP_NOT_FOUND);
        }
    }

    public function getApplicationTemplate($uuid)
    {
        try {
            $data = $this->templateService->getApplicationTemplateDetails($uuid);

            if (!$data) {
                return $this->respond(['message' => "Application not found"], ResponseInterface::HTTP_NOT_FOUND);
            }

            return $this->respond(['data' => $data], ResponseInterface::HTTP_OK);

        } catch (\Throwable $e) {
            log_message('error', $e);
            return $this->respond(['message' => "Application not found"], ResponseInterface::HTTP_NOT_FOUND);
        }
    }

    public function createApplicationTemplate()
    {
        try {
            $data = $this->request->getPost();
            $result = $this->templateService->createApplicationTemplate($data);

            return $this->respond($result, ResponseInterface::HTTP_OK);

        } catch (\InvalidArgumentException $e) {
            return $this->respond(['message' => $e], ResponseInterface::HTTP_BAD_REQUEST);
        } catch (\RuntimeException $e) {
            return $this->respond(['message' => $e], ResponseInterface::HTTP_BAD_REQUEST);
        } catch (\Throwable $e) {
            log_message('error', $e);
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function updateApplicationTemplate($uuid)
    {
        try {
            $data = (array) $this->request->getVar();
            $result = $this->templateService->updateApplicationTemplate($uuid, $data);

            return $this->respond($result, ResponseInterface::HTTP_OK);

        } catch (\InvalidArgumentException $e) {
            return $this->respond(['message' => $e], ResponseInterface::HTTP_BAD_REQUEST);
        } catch (\RuntimeException $e) {
            return $this->respond(['message' => $e], ResponseInterface::HTTP_BAD_REQUEST);
        } catch (\Throwable $e) {
            log_message('error', $e);
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function deleteApplicationTemplate($uuid)
    {
        try {
            $result = $this->templateService->deleteApplicationTemplate($uuid);
            return $this->respond($result, ResponseInterface::HTTP_OK);

        } catch (\RuntimeException $e) {
            return $this->respond(['message' => $e], ResponseInterface::HTTP_BAD_REQUEST);
        } catch (\Throwable $e) {
            log_message('error', $e);
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getApplicationStatusTransitions($form)
    {
        try {
            $stages = $this->templateService->getApplicationStatusTransitions($form);
            return $this->respond([
                'data' => $stages,
                'displayColumns' => ["form_type", "status", "count"]
            ], ResponseInterface::HTTP_OK);

        } catch (\InvalidArgumentException $e) {
            return $this->respond([
                'message' => $e,
                'displayColumns' => ["form_type", "status", "count"]
            ], ResponseInterface::HTTP_BAD_REQUEST);
        } catch (\RuntimeException $e) {
            return $this->respond([
                'message' => $e,
                'displayColumns' => []
            ], ResponseInterface::HTTP_BAD_REQUEST);
        } catch (\Throwable $e) {
            log_message('error', $e);
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getApplicationTemplateActionTypes()
    {
        try {
            $actionTypes = $this->templateService->getApplicationTemplateActionTypes();
            return $this->respond(['data' => $actionTypes], ResponseInterface::HTTP_OK);

        } catch (\Throwable $e) {
            log_message('error', $e);
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getApplicationTemplatesApiDefaultConfigs()
    {
        try {
            $defaultConfigs = $this->templateService->getApplicationTemplatesApiDefaultConfigs();
            return $this->respond([
                'data' => $defaultConfigs,
                'message' => "Default configurations for application templates actions"
            ], ResponseInterface::HTTP_OK);

        } catch (\Throwable $e) {
            log_message("error", $e);
            return $this->respond(['message' => "An error occurred. Please try again"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function testAction()
    {
        try {
            $json = $this->request->getJSON(true);

            if (!$json) {
                return $this->respond([
                    'success' => false,
                    'message' => 'Invalid JSON payload'
                ], ResponseInterface::HTTP_BAD_REQUEST);
            }

            $action = $json['action'] ?? null;
            $sampleData = $json['sample_data'] ?? [];

            $result = $this->templateService->testAction($action, $sampleData);
            return $this->respond($result);

        } catch (\InvalidArgumentException $e) {
            return $this->respond([
                'success' => false,
                'message' => $e
            ], ResponseInterface::HTTP_BAD_REQUEST);
        } catch (\Throwable $e) {
            log_message('error', 'Action test failed: ' . $e);
            return $this->respond([
                'success' => false,
                'message' => 'Action test failed: ' . $e,
                'error_details' => [
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]
            ], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function testEndpoint()
    {
        $method = $this->request->getMethod();
        $headers = $this->request->getHeaders();
        $body = $this->request->getJSON(true) ?: $this->request->getRawInput();
        $queryParams = $this->request->getGet();

        // Log the test request
        log_message('info', "Test endpoint called with method: $method");
        log_message('info', "Test endpoint headers: " . json_encode($headers));
        log_message('info', "Test endpoint body: " . json_encode($body));
        log_message('info', "Test endpoint query params: " . json_encode($queryParams));

        // Simulate some processing time
        usleep(100000); // 100ms delay

        $response = [
            'success' => true,
            'message' => 'Test endpoint called successfully',
            'received_data' => [
                'method' => $method,
                'headers' => $headers,
                'body' => $body,
                'query_params' => $queryParams,
                'timestamp' => date('Y-m-d H:i:s')
            ],
            'simulated_response' => [
                'id' => rand(1000, 9999),
                'status' => 'processed',
                'created_at' => date('Y-m-d H:i:s')
            ]
        ];

        // Simulate different responses based on method
        switch (strtoupper($method)) {
            case 'POST':
                $response['simulated_response']['action'] = 'created';
                return $this->respond($response, ResponseInterface::HTTP_CREATED);

            case 'PUT':
                $response['simulated_response']['action'] = 'updated';
                return $this->respond($response);

            case 'DELETE':
                $response['simulated_response']['action'] = 'deleted';
                return $this->respond($response);

            case 'GET':
            default:
                $response['simulated_response']['action'] = 'retrieved';
                return $this->respond($response);
        }
    }

    public function getCommonApplicationTemplates()
    {
        try {
            $data = $this->templateService->getCommonApplicationTemplates();
            return $this->respond(['data' => $data], ResponseInterface::HTTP_OK);

        } catch (\RuntimeException $e) {
            return $this->respond(['message' => $e], ResponseInterface::HTTP_NOT_FOUND);
        } catch (\Throwable $e) {
            log_message('error', $e);
            return $this->respond(['message' => $e], ResponseInterface::HTTP_BAD_REQUEST);
        }
    }


    // private $primaryColumns = ['first_name', 'picture', 'last_name', 'middle_name', 'email', 'phone'];
    // private function createFormMetaFromPayload(array $payload, string $form_type): array
    // {
    //     try {
    //         if (empty($payload)) {
    //             throw new Exception("Payload cannot be empty");
    //         }
    //         if (empty($form_type)) {
    //             throw new Exception("Form type cannot be empty");
    //         }

    //         $model = new ApplicationsModel();
    //         $meta = $model->createArrayFromAllowedFields($payload);
    //         if (array_key_exists('last_name', $meta)) {
    //             if (empty($meta['last_name']) && array_key_exists('registration_number', $payload)) {
    //                 $meta['last_name'] = $payload['registration_number'];
    //             }
    //         } else if (array_key_exists('registration_number', $payload)) {
    //             $meta['last_name'] = $payload['registration_number'];
    //         } else {
    //             $meta['last_name'] = "";
    //         }

    //         $meta['form_data'] = json_encode($payload);
    //         $meta['form_type'] = $form_type;

    //         return $meta;
    //     } catch (\Throwable $th) {
    //         throw $th;
    //     }
    // }
    // public function createApplication($form_type)
    // {
    //     try {
    //         //in case of a new application, the payload is what goes into the form_data field
    //         //all the other fields, first_name, etc are generated from the form_data
    //         $data = $this->createFormMetaFromPayload((array) $this->request->getPost(), $form_type);
    //         $applicationCode = $data['application_code'] = Utils::generateApplicationCode($form_type);
    //         //get the form actions and initial stage
    //         $applicationTemplateModel = new ApplicationTemplateModel();
    //         $template = $applicationTemplateModel->builder()->select(['form_name', 'stages', 'initialStage', 'finalStage', 'on_submit_message'])->where('form_name', $form_type)->get()->getFirstRow();
    //         if (!$template) {
    //             throw new Exception("Form template not found");
    //         }
    //         if (empty($template->initialStage)) {
    //             //set the status to Pending approval
    //             $data['status'] = "Pending approval";
    //         } else {
    //             $data['status'] = $template->initialStage;
    //         }


    //         $stages = json_decode($template->stages, true);
    //         if (!empty($stages)) {
    //             //find the one with the id of the initial stage
    //             try {
    //                 /**
    //                  * @var ApplicationTemplateStage[]  $initialStage
    //                  */
    //                 $initialStage = array_filter($stages, function ($stage) use ($template) {
    //                     return $stage['name'] == $template->initialStage;
    //                 });
    //                 if (empty($initialStage)) {
    //                     throw new Exception("Initial stage not found");
    //                 }
    //                 $initialStage = array_values($initialStage)[0];
    //                 //run the actions for the initial stage
    //                 foreach ($initialStage['actions'] as $action) {
    //                     ApplicationFormActionHelper::runAction($action, $data);
    //                 }

    //             } catch (\Throwable $th) {
    //                 log_message('error', $th->getMessage());
    //             }
    //         }



    //         $model = new ApplicationsModel();
    //         $model->insert(row: (object) $data);
    //         /** @var ActivitiesModel $activitiesModel */
    //         $activitiesModel = new ActivitiesModel();

    //         $activitiesModel->logActivity("Created application {$data['form_type']} with code $applicationCode");
    //         return $this->respond(['message' => 'Application created successfully', 'data' => ['applicationCode' => $applicationCode, 'onSubmitMessage' => $template->on_submit_message]], ResponseInterface::HTTP_OK);
    //     } catch (\Throwable $th) {
    //         log_message('error', $th->getMessage());
    //         return $this->respond(['message' => $th->getMessage()], ResponseInterface::HTTP_BAD_REQUEST);
    //     }
    // }

    // public function updateApplication($uuid)
    // {
    //     try {
    //         $model = new ApplicationsModel();
    //         //get the form_type from the database
    //         $application = $model->where(['uuid' => $uuid])->first();
    //         if (!$application) {
    //             return $this->respond(['message' => 'Application not found'], ResponseInterface::HTTP_NOT_FOUND);
    //         }


    //         $form_type = $application['form_type'];
    //         $data = (object) ["form_data" => json_encode($this->request->getVar())];
    //         $data->uuid = $uuid;
    //         if (property_exists($data, "id")) {
    //             unset($data->id);
    //         }
    //         $formData = json_decode($data->form_data, true);
    //         //the first_name, picture, last_name, middle_name, email, phone,  are generated from the form_data and saved in their own columns. update these if needed
    //         foreach ($this->primaryColumns as $column) {
    //             if (array_key_exists($column, $formData)) {
    //                 $data->$column = $formData[$column];
    //             }
    //         }
    //         $oldData = $application;
    //         $changes = implode(", ", Utils::compareObjects($oldData, $data));
    //         if (!$model->builder()->where(key: ['uuid' => $uuid])->update($data)) {
    //             return $this->respond(['message' => $model->errors()], ResponseInterface::HTTP_BAD_REQUEST);
    //         }
    //         /** @var ActivitiesModel $activitiesModel */
    //         $activitiesModel = new ActivitiesModel();
    //         $activitiesModel->logActivity("Updated application {$form_type}  {$application['application_code']}. Changes: $changes");

    //         return $this->respond(['message' => 'Application updated successfully'], ResponseInterface::HTTP_OK);
    //     } catch (\Throwable $th) {
    //         log_message('error', $th->getMessage());
    //         return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_BAD_REQUEST);
    //     }
    // }

    // public function updateApplicationStatus()
    // {
    //     try {
    //         $model = new ApplicationsModel();
    //         $status = $this->request->getVar('status');
    //         $applicationType = $this->request->getVar('form_type');
    //         $applicationIds = $this->request->getVar('applicationIds');
    //         if (!$applicationType) {
    //             return $this->respond(['message' => "Please provide an application type"], ResponseInterface::HTTP_BAD_REQUEST);
    //         }
    //         if (!$status) {
    //             return $this->respond(['message' => "Please provide a status"], ResponseInterface::HTTP_BAD_REQUEST);
    //         }
    //         if (!$applicationIds) {
    //             return $this->respond(['message' => "Please provide applications to update"], ResponseInterface::HTTP_BAD_REQUEST);
    //         }
    //         //get the application template details to get the stages
    //         $applicationTemplateModel = new ApplicationTemplateModel();
    //         $template = $applicationTemplateModel->builder()->select(['form_name', 'stages', 'initialStage', 'finalStage'])->where('form_name', $applicationType)->get()->getFirstRow();
    //         if (!$template) {
    //             return $this->respond(['message' => "Application template not found"], ResponseInterface::HTTP_NOT_FOUND);
    //         }
    //         $stages = json_decode($template->stages, true);
    //         if (empty($stages)) {
    //             return $this->respond(['message' => "Application stages not found"], ResponseInterface::HTTP_NOT_FOUND);
    //         }

    //         //get the stage matching the status
    //         /**
    //          * @var array {"id":string,"name":string,"description":string,"allowedTransitions":string[], "allowedUserRoles":string[],"actions":array{"type":string,"config":object{"template":string,"subject":string,"endpoint":string,"method":string,"recipient_field":string}}} 
    //          */
    //         $stage = current(array_filter($stages, function ($stage) use ($status) {
    //             return $stage['name'] == $status;
    //         }));
    //         if (!$stage) {
    //             return $this->respond(['message' => "Stage not found"], ResponseInterface::HTTP_NOT_FOUND);
    //         }
    //         $userObject = new \App\Models\UsersModel();
    //         $userData = $userObject->findById(auth("tokens")->id());
    //         if (!in_array($userData->role_name, $stage['allowedUserRoles'])) {
    //             return $this->respond(['message' => "You are not allowed to update applications to this stage"], ResponseInterface::HTTP_FORBIDDEN);
    //         }
    //         $applications = $model->builder()->whereIn('uuid', $applicationIds)->get()->getResult('array');
    //         /**
    //          * @var string[]
    //          */
    //         $applicationIdsToUpdate = [];
    //         $applicationCodesArray = [];
    //         foreach ($applications as $application) {
    //             $applicationCodesArray[] = $application['application_code'];
    //             //if there are actions only update the stage if the actions are successful
    //             if (!empty($stage['actions'])) {
    //                 //run the actions for the stage.
    //                 //make it a transaction so that if any action fails, the whole update is rolled back. for emails, they should be queued
    //                 $model->db->transException(true)->transStart();
    //                 for ($i = 0; $i < count($stage['actions']); $i++) {
    //                     $action = $stage['actions'][$i];
    //                     try {
    //                         //merge the form_data with the application data
    //                         $formData = json_decode($application['form_data'], true);
    //                         //unset the form_data field from the application data
    //                         unset($application['form_data']);
    //                         $applicationData = array_merge($application, $formData);
    //                         ApplicationFormActionHelper::runAction((object) $action, $applicationData);
    //                         $applicationIdsToUpdate[] = $application['uuid'];
    //                     } catch (\Throwable $th) {
    //                         //possibly log the error for a retry

    //                         log_message('error', $th);
    //                         //if the action fails, rollback the transaction and return an error
    //                         $model->db->transRollback();
    //                         //skip the remaining actions and continue with the next application
    //                         break;
    //                     }
    //                 }
    //                 $model->db->transComplete();
    //             } else {
    //                 $applicationIdsToUpdate[] = "'{$application['uuid']}'";
    //             }
    //         }
    //         if (empty($applicationIdsToUpdate)) {
    //             return $this->respond(['message' => "No applications updated"], ResponseInterface::HTTP_NOT_IMPLEMENTED);
    //         }
    //         $model->builder()->whereIn('uuid', $applicationIdsToUpdate)->update(['status' => $status]);
    //         $applicationCodes = implode(", ", $applicationCodesArray);


    //         /** @var ActivitiesModel $activitiesModel */
    //         $activitiesModel = new ActivitiesModel();
    //         $activitiesModel->logActivity("Updated applications {$applicationCodes} status to $status. See the logs for more details");

    //         return $this->respond(['message' => 'Applications updated successfully. See logs for more details'], ResponseInterface::HTTP_OK);
    //     } catch (\Throwable $th) {
    //         log_message('error', $th->getMessage());
    //         return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
    //     }
    // }

    // public function deleteApplication($uuid)
    // {
    //     try {
    //         $model = new ApplicationsModel();
    //         $data = $model->where(["uuid" => $uuid])->first();

    //         if (!$model->where('uuid', $uuid)->delete()) {
    //             return $this->respond(['message' => $model->errors()], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
    //         }
    //         /** @var ActivitiesModel $activitiesModel */
    //         $activitiesModel = new ActivitiesModel();
    //         $activitiesModel->logActivity("Deleted application {$data['form_type']}  for {$data['email']}  ");

    //         return $this->respond(['message' => 'Application deleted successfully'], ResponseInterface::HTTP_OK);
    //     } catch (\Throwable $th) {
    //         log_message('error', $th->getMessage());
    //         return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
    //     }
    // }

    // public function restoreApplication($uuid)
    // {
    //     $model = new ApplicationsModel();
    //     if (!$model->builder()->where(['uuid' => $uuid])->update(['deleted_at' => null])) {
    //         return $this->respond(['message' => $model->errors()], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
    //     }
    //     $data = $model->where(["uuid" => $uuid])->first();
    //     /** @var ActivitiesModel $activitiesModel */
    //     $activitiesModel = new ActivitiesModel();
    //     $activitiesModel->logActivity("Restored application {$data['form_type']} for {$data['email']} from recycle bin");

    //     return $this->respond(['message' => 'Application restored successfully'], ResponseInterface::HTTP_OK);
    // }

    // /**
    //  * Get Application details by UUID.
    //  *
    //  * @param string $uuid The UUID of the Application
    //  * @return ApplicationsModel|null The Application data if found, null otherwise
    //  * @throws Exception If Application is not found
    //  */
    // private function getApplicationDetails(string $uuid): array|object|null
    // {
    //     $model = new ApplicationsModel();
    //     $builder = $model->builder();
    //     $builder->where('uuid', $uuid);
    //     $data = $model->first();
    //     if (!$data) {
    //         throw new Exception("Application not found");
    //     }
    //     $data['form_data'] = json_decode($data['form_data'], true);
    //     return $data;
    // }

    // public function getApplication($uuid)
    // {
    //     $model = new ApplicationsModel();
    //     $data = $this->getApplicationDetails($uuid);
    //     if (!$data) {
    //         return $this->respond(['message' => "Application not found"], ResponseInterface::HTTP_NOT_FOUND);
    //     }
    //     return $this->respond(['data' => $data, 'displayColumns' => $model->getDisplayColumns()], ResponseInterface::HTTP_OK);
    // }

    // public function getApplications()
    // {

    //     try {
    //         $per_page = $this->request->getVar('limit') ? (int) $this->request->getVar('limit') : 100;
    //         $page = $this->request->getVar('page') ? (int) $this->request->getVar('page') : 0;
    //         $withDeleted = $this->request->getVar('withDeleted') && $this->request->getVar('withDeleted') === "yes";
    //         $param = $this->request->getVar('param');
    //         $sortBy = $this->request->getVar('sortBy') ?? "id";
    //         $sortOrder = $this->request->getVar('sortOrder') ?? "asc";
    //         $application_code = $this->request->getGet('application_code');
    //         $status = $this->request->getGet('status');
    //         $start_date = $this->request->getGet('start_date');
    //         $end_date = $this->request->getGet('end_date');
    //         $practitioner_type = $this->request->getGet('practitioner_type');
    //         $form_type = $this->request->getGet('form_type');

    //         $model = new ApplicationsModel();

    //         $builder = $param ? $model->search($param) : $model->builder();
    //         $builder = $model->addCustomFields($builder);
    //         $builder->orderBy("$sortBy", $sortOrder);
    //         if ($application_code !== null) {
    //             $builder->where('application_code', $application_code);
    //         }
    //         if ($status !== null) {
    //             $builder->where('status', $status);
    //         }
    //         if ($start_date !== null) {
    //             $builder->where('created_on >=', $start_date);
    //         }
    //         if ($end_date !== null) {
    //             $builder->where('created_on <=', $end_date);
    //         }
    //         if ($practitioner_type !== null) {
    //             $builder->where('practitioner_type', $practitioner_type);
    //         }
    //         if ($form_type !== null) {
    //             $builder->where('form_type', $form_type);
    //         }
    //         //add a filter for fields from form_data. these fields will be prefixed with child_
    //         /**
    //          * @var array
    //          */
    //         $childParams = array_filter($this->request->getGet(), function ($key) {
    //             return strpos($key, 'child_') === 0;
    //         }, ARRAY_FILTER_USE_KEY);
    //         // if childParams is not empty, search for the fields in form_data. this is a json field
    //         if (!empty($childParams)) {
    //             foreach ($childParams as $key => $value) {
    //                 $field = str_replace('child_', '', $key);
    //                 $builder = BaseBuilderJSONQueryUtil::whereJson(
    //                     $builder,
    //                     'form_data',
    //                     $field,
    //                     $value
    //                 );
    //             }
    //         }

    //         // if ($withDeleted) {
    //         //     $model->withDeleted();
    //         // }
    //         $totalBuilder = clone $builder;
    //         $total = $totalBuilder->countAllResults();
    //         $result = $builder->get($per_page, $page)->getResult();
    //         $final = [];
    //         $displayColumns = $model->getDisplayColumns();
    //         $excludedDisplayColumns = ['id', 'uuid'];
    //         //try to get the display columns from the form data if available. 
    //         /**
    //          * @var string[]
    //          */
    //         $allFormColumns = [];//a unique list of columns from the form data
    //         foreach ($result as $value) {
    //             //convert json string in form_data to json object
    //             $form_data = json_decode($value->form_data, true);

    //             $form_data['uuid'] = $value->uuid;
    //             $form_data['status'] = $value->status;
    //             $form_data['created_on'] = $value->created_on;
    //             $form_data['form_type'] = $value->form_type;
    //             $form_data['application_code'] = $value->application_code;

    //             $formColumns = array_keys($form_data);
    //             foreach ($formColumns as $col) {
    //                 if (
    //                     !in_array($col, $allFormColumns) &&
    //                     !in_array($col, $displayColumns) && !in_array($col, $excludedDisplayColumns)
    //                 ) {
    //                     $allFormColumns[] = $col;
    //                 }
    //             }
    //             $final[] = $form_data;
    //         }
    //         //insert the form columns into the display columns where we have the form_data column. if for some reason it's not there, add it to the end of the display columns
    //         $formDataIndex = array_search('form_data', $displayColumns) ?? count($displayColumns);
    //         array_splice($displayColumns, $formDataIndex, 1, $allFormColumns);
    //         return $this->respond([
    //             'data' => $final,
    //             'total' => $total,
    //             'displayColumns' => $displayColumns,
    //             'columnFilters' => $model->getDisplayColumnFilters()
    //         ], ResponseInterface::HTTP_OK);
    //     } catch (\Throwable $th) {
    //         log_message('error', $th->getMessage());
    //         return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
    //     }
    // }

    // public function getApplicationFormTypes($field)
    // {
    //     $model = new ApplicationsModel();
    //     $formTypes = $model->getDistinctValuesAsKeyValuePairs($field);
    //     $applicationTemplateModel = new ApplicationTemplateModel();
    //     $templates = $applicationTemplateModel->builder()->select(['form_name'])->distinct()->get()->getResult();
    //     //for each formTypes, check if it is in the templates
    //     foreach ($formTypes as $formType) {
    //         if (!in_array($formType['key'], array_column($templates, 'form_name'))) {
    //             $templates[] = (object) ['form_name' => $formType['key']];
    //         }
    //     }
    //     return $this->respond(['data' => $templates], ResponseInterface::HTTP_OK);
    // }

    // public function getApplicationStatuses($form)
    // {

    //     if (empty(trim($form))) {
    //         return $this->respond(['message' => "Please provide a form type", 'displayColumns' => ["form_type", "status", "count"]], ResponseInterface::HTTP_BAD_REQUEST);
    //     }


    //     $model = new ApplicationsModel();
    //     $builder = $model->builder();
    //     $builder->select(["form_type", "status", "count(*) as count"]);
    //     $builder->where("form_type", $form);
    //     $builder->groupBy(["form_type", "status"]);
    //     $statuses = $builder->get()->getResultArray();

    //     //make the statuses an associative array with the status as the key
    //     $statusesArray = array_column($statuses, null, 'status');


    //     //if a form type is provided, get all its stages and add them to the statuses

    //     $applicationTemplateModel = new ApplicationTemplateModel();
    //     /**
    //      * @var object|null $template
    //      */
    //     $template = $applicationTemplateModel->builder()->select(['form_name', 'stages', 'initialStage', 'finalStage'])->where('form_name', $form)->get()->getFirstRow();

    //     /**
    //      * @var array{id: int, name:string, description:string, allowedTransitions: array} $stages
    //      */
    //     if (!$template) {
    //         $data = $statuses;
    //     } else {
    //         $stages = json_decode($template->stages, true);
    //         //TODO: are we using the names or ids of the stages to save application status?
    //         $stagesArray = array_column($stages, null, 'name');
    //         $initialStage = $template->initialStage;
    //         $finalStage = $template->finalStage;
    //         //get the count of each stage from the statuses

    //         foreach ($stagesArray as $stage => $stageData) {
    //             if (!array_key_exists($stage, $statusesArray)) {
    //                 $statusesArray[$stage] = [
    //                     "form_type" => $template->form_name,
    //                     "status" => $stage,
    //                     "count" => 0
    //                 ];
    //             }
    //         }

    //         //move the initial and final stages to the beginning and end of the array
    //         $initialStageData = null;
    //         $finalStageData = null;
    //         if (array_key_exists($initialStage, $statusesArray)) {
    //             $initialStageData = $statusesArray[$initialStage];
    //             unset($statusesArray[$initialStage]);
    //         }
    //         if (array_key_exists($finalStage, $statusesArray)) {
    //             $finalStageData = $statusesArray[$finalStage];
    //             unset($statusesArray[$finalStage]);
    //         }
    //         $data = [];
    //         if ($initialStageData) {
    //             $data[] = $initialStageData;
    //         }
    //         $data = array_merge($data, array_values($statusesArray));


    //         if ($finalStageData) {
    //             $data[] = $finalStageData;
    //         }
    //     }
    //     return $this->respond(['data' => $data, 'displayColumns' => ["form_type", "status", "count"]], ResponseInterface::HTTP_OK);
    // }

    // public function countApplications()
    // {
    //     try {
    //         $rules = [
    //             "start_date" => "if_exist|valid_date",
    //             "end_date" => "if_exist|valid_date",
    //         ];
    //         if (!$this->validate($rules)) {
    //             return $this->respond($this->validator->getErrors(), ResponseInterface::HTTP_BAD_REQUEST);
    //         }
    //         $param = $this->request->getVar('param');
    //         $model = new ApplicationsModel();
    //         $application_code = $this->request->getGet('application_code');
    //         $status = $this->request->getGet('status');
    //         $start_date = $this->request->getGet('start_date');
    //         $end_date = $this->request->getGet('end_date');
    //         $practitioner_type = $this->request->getGet('practitioner_type');
    //         $form_type = $this->request->getGet('form_type');
    //         // Validate inputs here

    //         $builder = $param ? $model->search($param) : $model->builder();
    //         if ($application_code !== null) {
    //             $builder->where('application_code', $application_code);
    //         }
    //         if ($status !== null) {
    //             $builder->where('status', $status);
    //         }
    //         if ($start_date !== null) {
    //             $builder->where('created_on >=', $start_date);
    //         }
    //         if ($end_date !== null) {
    //             $builder->where('created_on <=', $end_date);
    //         }
    //         if ($practitioner_type !== null) {
    //             $builder->where('practitioner_type', $practitioner_type);
    //         }
    //         if ($form_type !== null) {
    //             $builder->where('form_type', $form_type);
    //         }
    //         //add a filter for fields from form_data. these fields will be prefixed with child_
    //         /**
    //          * @var array
    //          */
    //         $childParams = array_filter($this->request->getGet(), function ($key) {
    //             return strpos($key, 'child_') === 0;
    //         }, ARRAY_FILTER_USE_KEY);
    //         // if childParams is not empty, search for the fields in form_data. this is a json field
    //         if (!empty($childParams)) {
    //             foreach ($childParams as $key => $value) {
    //                 $field = str_replace('child_', '', $key);
    //                 $builder = BaseBuilderJSONQueryUtil::whereJson(
    //                     $builder,
    //                     'form_data',
    //                     $field,
    //                     $value
    //                 );
    //             }
    //         }

    //         $total = $builder->countAllResults();
    //         return $this->respond([
    //             'data' => $total
    //         ], ResponseInterface::HTTP_OK);
    //     } catch (\Throwable $th) {
    //         log_message("error", $th->getMessage());
    //         return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
    //     }
    // }

    // /**
    //  * Approve a permanent application.
    //  *
    //  * @param array $applicationDetails The details of the application
    //  * @return array{message: string, status: int} The response message and status
    //  */
    // private function approvePermanentApplication(array $applicationDetails)
    // {
    //     try {
    //         //start a transaction
    //         $today = date("Y-m-d");
    //         $model = new ApplicationsModel();
    //         $practitionerModel = new PractitionerModel();
    //         $renewalModel = new PractitionerRenewalModel();
    //         $registration_number = $this->request->getVar('registration_number');

    //         $model->builder()->where(['uuid' => $applicationDetails['uuid']])->update(['status' => "approved"]);
    //         $formData = json_decode($applicationDetails['form_data'], true);

    //         $practitionerData = $practitionerModel->createArrayFromAllowedFields($formData);
    //         $practitionerData['register_type'] = "Permanent";
    //         $practitionerData['practitioner_type'] = $formData['type'];
    //         $practitionerData['year_of_permanent'] = $today;
    //         $practitionerData['year_of_provisional'] = $formData["date_of_provisional"];
    //         $practitionerData['registration_date'] = $today;
    //         $practitionerData['registration_number'] = $registration_number;
    //         $practitionerData['qualification_at_registration'] = $formData["qualification"];
    //         $practitionerData['qualification_date'] = $formData["date_of_graduation"];
    //         $practitionerData['status'] = 1;
    //         $practitionerModel->db->transException(true)->transStart();
    //         $existingPractitionerBuilder = $practitionerModel->builder()->where(['registration_number' => $formData["provisional_registration_number"]]);

    //         if ($existingPractitionerBuilder->countAllResults() > 0) {
    //             $existingPractitionerBuilder->update($practitionerData);
    //             $practitionerId = $practitionerModel->first()['id'];
    //         } else {


    //         }
    //         $practitioner = $practitionerModel->find($practitionerId);

    //         $model->delete($applicationDetails['id']);


    //         $retentionYear = date("Y");
    //         //save the documents to the documents model
    //         //create a retention record with the qr code
    //         $retentionData = $renewalModel->createArrayFromAllowedFields($practitioner);
    //         $retentionData = array_merge($retentionData, [
    //             "practitioner_uuid" => $practitioner['uuid'],
    //             "status" => "Approved",
    //             "practitioner_type" => $practitioner['practitioner_type'],
    //         ]);

    //         PractitionerUtils::retainPractitioner(
    //             $practitioner['uuid'],
    //             "",
    //             $retentionData,
    //             $retentionYear,
    //             null,
    //             null,
    //             null,
    //             null,
    //             $practitionerData['specialty']
    //         );
    //         $practitionerModel->db->transComplete();

    //         return ['message' => 'Application approved successfully', 'status' => ResponseInterface::HTTP_OK];
    //     } catch (\Throwable $th) {
    //         log_message("error", $th->getMessage());
    //         return ['message' => 'Server error: ' . $th->getMessage(), 'status' => ResponseInterface::HTTP_INTERNAL_SERVER_ERROR];
    //     }
    // }




    // private function approvePortalEdit(array $applicationDetails)
    // {
    //     /**
    //      * data: field, value, reg_num, action, attachments, revalidate
    //      * update based on field and action
    //      */
    //     try {
    //         //start a transaction
    //         $today = date("Y-m-d");
    //         $model = new ApplicationsModel();
    //         $practitionerModel = new PractitionerModel();
    //         $field = $applicationDetails['field'];
    //         $value = $applicationDetails['value'];
    //         $registration_number = $applicationDetails['reg_num'];
    //         $action = $applicationDetails['action'];
    //         $attachments = $applicationDetails['attachments'];
    //         $revalidate = $applicationDetails['revalidate'];
    //         $updateData = [];
    //         $act = "";
    //         switch ($field) {
    //             case 'picture':
    //                 //check if the picture exists in application attachments folder in writable path
    //                 //if it does, move it to the practitioner folder
    //                 //update the practitioner picture field with the new path
    //                 try {
    //                     $registration_number_no_spaces = str_replace(" ", "_", $registration_number);
    //                     $origin = WRITEPATH . UPLOADS_FOLDER . DIRECTORY_SEPARATOR . APPLICATIONS_ASSETS_FOLDER . DIRECTORY_SEPARATOR . $value;
    //                     $file = new \CodeIgniter\Files\File($origin, true);
    //                     $destination_file_name = microtime() . $registration_number_no_spaces . $file->guessExtension();

    //                     $destination = WRITEPATH . UPLOADS_FOLDER . DIRECTORY_SEPARATOR . PRACTITIONERS_ASSETS_FOLDER . DIRECTORY_SEPARATOR . $destination_file_name;
    //                     copy($origin, $destination);
    //                     $updateData['picture'] = $destination_file_name;
    //                     $act = "updated picture of $registration_number in response to web request ";
    //                     //TODO: save the previous value to archive
    //                     //TODO: save all the attachments to the documents model
    //                 } catch (\Throwable $th) {
    //                     throw $th;
    //                 }
    //                 break;
    //             case "qualification":
    //                 $additionalQualificationModel = new \App\Models\Practitioners\PractitionerAdditionalQualificationsModel();
    //                 $qualification = $additionalQualificationModel->find($value);
    //                 $additionalQualificationModel->delete($value);
    //                 $act = "deleted certificate: {$qualification->qualification} ({$qualification->institution}) from profile of $registration_number in response to web request ";

    //                 break;


    //             //in case of work_history, it can only be dropped from here
    //             case "work_history":
    //                 $workHistoryModel = new \App\Models\Practitioners\PractitionerWorkHistoryModel();
    //                 $details = $workHistoryModel->find($value);
    //                 $workHistoryModel->delete($value);
    //                 $act = "deleted work history: {$details->position} at ({$details->institution}) from profile of $registration_number in response to web request ";
    //                 break;

    //             default:
    //                 $updateData[$field] = $value;
    //                 break;
    //         }
    //         if ($revalidate) {
    //             $updateData['require_revalidation'] = "no";
    //             $updateData['last_revalidation_date'] = date("Y-m-d");
    //         }
    //         if (!empty($updateData)) {
    //             $practitionerModel->where(['registration_number' => $registration_number])->update($updateData);
    //         }
    //         /** @var ActivitiesModel $activitiesModel */
    //         $activitiesModel = new ActivitiesModel();
    //         $activitiesModel->logActivity($act);



    //         return ['message' => 'Application updated successfully', 'status' => ResponseInterface::HTTP_OK];
    //     } catch (\Throwable $th) {
    //         log_message("error", $th->getMessage());
    //         return ['message' => 'Server error: ' . $th->getMessage(), 'status' => ResponseInterface::HTTP_INTERNAL_SERVER_ERROR];
    //     }

    // }



    // public function getApplicationConfig(string $form_name, string $type = null)
    // {
    //     try {
    //         $cacheKey = "app_config_" . md5($form_name . '_' . $type);

    //         return CacheHelper::remember($cacheKey, function () use ($form_name, $type) {
    //             //get the form-settings.json file and get the config for the specified form
    //             $form = str_replace(" ", "-", $form_name);
    //             $configContents = file_get_contents(WRITEPATH . 'config_files/form-settings.json');
    //             $config = json_decode($configContents, true);
    //             $formConfig = !empty($type) ? $config[$form][$type] : $config[$form];
    //             return $this->respond(['data' => $formConfig], ResponseInterface::HTTP_OK);
    //         }, 3600); // Cache for 1 hour
    //     } catch (\Throwable $th) {
    //         log_message('error', $th->getMessage());
    //         return $this->respond(['message' => "Server error:" . $th->getMessage()], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
    //     }
    // }

    // public function getApplicationTemplates()
    // {
    //     try {
    //         $per_page = $this->request->getVar('limit') ? (int) $this->request->getVar('limit') : 100;
    //         $page = $this->request->getVar('page') ? (int) $this->request->getVar('page') : 0;
    //         $withDeleted = $this->request->getVar('withDeleted') && $this->request->getVar('withDeleted') === "yes";
    //         $param = $this->request->getVar('param');
    //         $sortBy = $this->request->getVar('sortBy') ?? "id";
    //         $sortOrder = $this->request->getVar('sortOrder') ?? "asc";

    //         // Generate cache key based on query parameters
    //         $cacheKey = "app_templates_" . md5(json_encode([
    //             $per_page,
    //             $page,
    //             $withDeleted,
    //             $param,
    //             $sortBy,
    //             $sortOrder
    //         ]));

    //         return CacheHelper::remember($cacheKey, function () use ($per_page, $page, $withDeleted, $param, $sortBy, $sortOrder) {
    //             $model = new ApplicationTemplateModel();
    //             $builder = $param ? $model->search($param) : $model->builder();

    //             if ($withDeleted) {
    //                 $model->withDeleted();
    //             }

    //             $builder->orderBy($sortBy, $sortOrder);
    //             $totalBuilder = clone $builder;
    //             $total = $totalBuilder->countAllResults();
    //             $result = $builder->get($per_page, $page)->getResult();
    //             return $this->respond([
    //                 'data' => $result,
    //                 'total' => $total,
    //                 'displayColumns' => $model->getDisplayColumns()
    //             ], ResponseInterface::HTTP_OK);
    //         }, 3600); // Cache for 1 hour
    //     } catch (\Throwable $th) {
    //         log_message('error', $th->getMessage());
    //         return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
    //     }
    // }


    // private function getApplicationTemplateDetails(string $uuid): array|object|null
    // {
    //     $model = new ApplicationTemplateModel();
    //     $builder = $model->builder();
    //     $builder->where('uuid', $uuid)->orWhere('form_name', $uuid);
    //     $data = $model->first();
    //     if (!$data) {
    //         throw new Exception("Application template not found");
    //     }
    //     $data['data'] = json_decode($data['data'], true);
    //     return $data;
    // }

    // public function getApplicationTemplateForFilling(string $uuid): array|object|null
    // {
    //     try {
    //         
    //         return $this->respond([
    //             'data' => $data,
    //             'message' => ""
    //         ], ResponseInterface::HTTP_OK);
    //     } catch (\Throwable $th) {
    //         log_message('error', $th->getMessage());
    //         return $this->respond(['message' => "Application not found at this time. Please try again later"], ResponseInterface::HTTP_NOT_FOUND);
    //     }
    // }

    // public function getApplicationTemplate($uuid)
    // {
    //     try {
    //         $data = $this->getApplicationTemplateDetails($uuid);
    //         return $this->respond(['data' => $data], ResponseInterface::HTTP_OK);
    //     } catch (\Throwable $th) {
    //         log_message('error', $th->getMessage());
    //         return $this->respond(['message' => "Application not found"], ResponseInterface::HTTP_NOT_FOUND);
    //     }
    // }

    // public function createApplicationTemplate()
    // {
    //     try {
    //         $rules = [
    //             "form_name" => "required|is_unique[application_form_templates.form_name]",
    //             "data" => "required"
    //         ];

    //         if (!$this->validate($rules)) {
    //             return $this->respond($this->validator->getErrors(), ResponseInterface::HTTP_BAD_REQUEST);
    //         }

    //         $model = new ApplicationTemplateModel();
    //         $data = $this->request->getPost();

    //         if (!$model->insert($data)) {
    //             return $this->respond(['message' => $model->errors()], ResponseInterface::HTTP_BAD_REQUEST);
    //         }

    //         // Invalidate application templates and config cache
    //         $this->invalidateCache('app_templates_');
    //         $this->invalidateCache('app_config_');

    //         return $this->respond(['message' => 'Application template created successfully'], ResponseInterface::HTTP_OK);
    //     } catch (\Throwable $th) {
    //         log_message('error', $th->getMessage());
    //         return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
    //     }
    // }

    // public function updateApplicationTemplate($uuid)
    // {
    //     try {
    //         $rules = [
    //             "form_name" => "required",
    //             "data" => "required"
    //         ];

    //         if (!$this->validate($rules)) {
    //             return $this->respond($this->validator->getErrors(), ResponseInterface::HTTP_BAD_REQUEST);
    //         }

    //         $model = new ApplicationTemplateModel();
    //         $data = $this->request->getVar();
    //         if (!$model->builder()->where(key: ['uuid' => $uuid])->update($data)) {
    //             return $this->respond(['message' => $model->errors()], ResponseInterface::HTTP_BAD_REQUEST);
    //         }

    //         // Invalidate application templates and config cache
    //         $this->invalidateCache('app_templates_');
    //         $this->invalidateCache('app_config_');

    //         return $this->respond(['message' => 'Application template updated successfully'], ResponseInterface::HTTP_OK);
    //     } catch (\Throwable $th) {
    //         log_message('error', $th->getMessage());
    //         return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
    //     }
    // }

    // public function deleteApplicationTemplate($uuid)
    // {
    //     try {
    //         $model = new ApplicationTemplateModel();

    //         if (!$model->delete($uuid)) {
    //             return $this->respond(['message' => $model->errors()], ResponseInterface::HTTP_BAD_REQUEST);
    //         }

    //         // Invalidate application templates and config cache
    //         $this->invalidateCache('app_templates_');
    //         $this->invalidateCache('app_config_');

    //         return $this->respond(['message' => 'Application template deleted successfully'], ResponseInterface::HTTP_OK);
    //     } catch (\Throwable $th) {
    //         log_message('error', $th->getMessage());
    //         return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
    //     }
    // }

    // public function getApplicationStatusTransitions($form)
    // {
    //     if (empty(trim($form))) {
    //         return $this->respond(['message' => "Please provide a form type", 'displayColumns' => ["form_type", "status", "count"]], ResponseInterface::HTTP_BAD_REQUEST);
    //     }


    //     $applicationTemplateModel = new ApplicationTemplateModel();
    //     /**
    //      * @var object|null $template
    //      */
    //     $template = $applicationTemplateModel->builder()->select(['form_name', 'stages', 'initialStage', 'finalStage'])->where('form_name', $form)->get()->getFirstRow();

    //     /**
    //      * @var array{id: int, name:string, description:string, allowedTransitions: array} $stages
    //      */
    //     if (!$template) {
    //         return $this->respond(['message' => "The selected form is not configured properly", 'displayColumns' => []], ResponseInterface::HTTP_BAD_REQUEST);
    //     } else {
    //         $stages = json_decode($template->stages, true);
    //     }
    //     return $this->respond(['data' => $stages, 'displayColumns' => ["form_type", "status", "count"]], ResponseInterface::HTTP_OK);
    // }

    // public function getApplicationTemplateActionTypes()
    // {
    //     try {
    //         $actionTypes = [
    //             'email' => 'Send Email',
    //             'admin_email' => 'Send Admin Email',
    //             'api_call' => 'API Call',
    //             'create_invoice' => 'Create Invoice',
    //             'create_document' => 'Create Document'
    //         ];
    //         return $this->respond(['data' => $actionTypes], ResponseInterface::HTTP_OK);
    //     } catch (\Throwable $th) {
    //         log_message('error', $th->getMessage());
    //         return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
    //     }
    // }

    // /**
    //  * return a list of default configurations for application templates actions
    //  * there will be one option  to create an instance of each license type. these will return the form fields for the license type and general license fields
    //  * create invoice
    //  * create document
    //  * @return ResponseInterface
    //  */
    // public function getApplicationTemplatesApiDefaultConfigs()
    // {
    //     try {
    //         //get the license types from app-settings.json
    //         $licenseSettings = Utils::getAppSettings("licenseTypes");
    //         //for each one, get the names from the form fields

    //         //{"type":"api_call","config":{"endpoint":"/licenses/details","method":"POST","headers":{"Content-Type":"application/json"},"body_mapping":{"first_name":"@first_name","last_name":"@last_name","email":"@email","certificate":"@certificate"}}}
    //         /**
    //          * @var array 
    //          */
    //         $defaultMapping = [];
    //         /**
    //          * @var array
    //          */
    //         $defaultConfigs = [];
    //         $licenseModel = new \App\Models\Licenses\LicensesModel();
    //         $licenseFormFields = $licenseModel->getFormFields();
    //         // map
    //         foreach ($licenseFormFields as $field) {
    //             $fieldName = $field['name'];
    //             // Map the field to the body mapping
    //             $defaultMapping[$fieldName] = '@' . $fieldName; // field names are prefixed with '@'
    //         }

    //         // Loop through each license type and create a default configuration
    //         foreach ($licenseSettings as $key => $value) {
    //             if (is_array($value)) {
    //                 $bodyMapping = [];

    //                 foreach ($value['fields'] as $field) {
    //                     $fieldName = $field['name'];
    //                     // Map the field to the body mapping
    //                     $bodyMapping[$fieldName] = '@' . $fieldName; // field names are prefixed with '@'
    //                 }
    //                 $label = ucfirst(str_replace('_', ' ', $key)); // Convert key to a more readable label
    //                 $defaultConfigs[] = [
    //                     'name' => $key,
    //                     'label' => "Create {$label} Instance",
    //                     'type' => "create_{$key}",
    //                     'config' => [
    //                         'endpoint' => base_url("licenses/details"),
    //                         'method' => 'POST',
    //                         'headers' => [
    //                             'Content-Type' => 'application/json'
    //                         ],
    //                         'auth_token' => '__self__', // Use __self__ to indicate the current user's token
    //                         'body_mapping' => array_merge($bodyMapping, $defaultMapping) // merge with default mapping
    //                     ]
    //                 ];
    //             }
    //         }
    //         return $this->respond([
    //             'data' => $defaultConfigs,
    //             'message' => "Default configurations for application templates actions"
    //         ], ResponseInterface::HTTP_OK);
    //     } catch (Exception $th) {
    //         log_message("error", $th);
    //         return $this->respond(['message' => "An error occurred. Please try again"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
    //     }
    // }

    // /**
    //  * Test an action configuration
    //  * This endpoint allows testing action configurations before saving them
    //  */
    // public function testAction()
    // {
    //     try {
    //         $json = $this->request->getJSON(true);

    //         if (!$json) {
    //             return $this->respond([
    //                 'success' => false,
    //                 'message' => 'Invalid JSON payload'
    //             ], ResponseInterface::HTTP_BAD_REQUEST);
    //         }

    //         $action = $json['action'] ?? null;
    //         $sampleData = $json['sample_data'] ?? [];

    //         if (!$action || !isset($action['type']) || !isset($action['config'])) {
    //             return $this->respond([
    //                 'success' => false,
    //                 'message' => 'Invalid action configuration. Must include type and config.'
    //             ], ResponseInterface::HTTP_BAD_REQUEST);
    //         }

    //         // Validate action type
    //         $allowedTypes = ['email', 'admin_email', 'api_call'];
    //         if (!in_array($action['type'], $allowedTypes)) {
    //             return $this->respond([
    //                 'success' => false,
    //                 'message' => 'Invalid action type. Allowed types: ' . implode(', ', $allowedTypes)
    //             ], ResponseInterface::HTTP_BAD_REQUEST);
    //         }

    //         // Additional validation for API calls
    //         if ($action['type'] === 'api_call') {
    //             $config = $action['config'];

    //             if (empty($config['endpoint'])) {
    //                 return $this->respond([
    //                     'success' => false,
    //                     'message' => 'API call endpoint is required'
    //                 ], ResponseInterface::HTTP_BAD_REQUEST);
    //             }

    //             if (empty($config['method'])) {
    //                 return $this->respond([
    //                     'success' => false,
    //                     'message' => 'API call method is required'
    //                 ], ResponseInterface::HTTP_BAD_REQUEST);
    //             }

    //             // Validate HTTP method
    //             $allowedMethods = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH'];
    //             if (!in_array(strtoupper($config['method']), $allowedMethods)) {
    //                 return $this->respond([
    //                     'success' => false,
    //                     'message' => 'Invalid HTTP method. Allowed methods: ' . implode(', ', $allowedMethods)
    //                 ], ResponseInterface::HTTP_BAD_REQUEST);
    //             }

    //             // Validate URL format
    //             if (!filter_var($config['endpoint'], FILTER_VALIDATE_URL) && !$this->isRelativeUrl($config['endpoint'])) {
    //                 return $this->respond([
    //                     'success' => false,
    //                     'message' => 'Invalid endpoint URL format'
    //                 ], ResponseInterface::HTTP_BAD_REQUEST);
    //             }
    //         }

    //         // Add some default test data if sample data is empty
    //         if (empty($sampleData)) {
    //             $sampleData = [
    //                 'first_name' => 'John',
    //                 'last_name' => 'Doe',
    //                 'email' => 'john.doe@example.com',
    //                 'phone' => '+1234567890',
    //                 'application_code' => 'TEST_' . uniqid(),
    //                 'status' => 'pending',
    //                 'created_at' => date('Y-m-d H:i:s'),
    //                 'uuid' => uniqid()
    //             ];
    //         }

    //         // Convert action array to object for the helper
    //         $actionObject = (object) [
    //             'type' => $action['type'],
    //             'config' => (object) $action['config']
    //         ];

    //         // Test the action in a controlled environment
    //         $testResult = $this->runTestAction($actionObject, $sampleData);

    //         return $this->respond([
    //             'success' => true,
    //             'message' => 'Action test completed',
    //             'test_result' => $testResult,
    //             'sample_data_used' => $sampleData
    //         ]);

    //     } catch (\Throwable $e) {
    //         log_message('error', 'Action test failed: ' . $e);
    //         log_message('error', 'Stack trace: ' . $e->getTraceAsString());

    //         return $this->respond([
    //             'success' => false,
    //             'message' => 'Action test failed: ' . $e,
    //             'error_details' => [
    //                 'file' => $e->getFile(),
    //                 'line' => $e->getLine()
    //             ]
    //         ], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
    //     }
    // }

    // /**
    //  * Run test action in a controlled environment
    //  */
    // private function runTestAction($action, $data)
    // {
    //     $startTime = microtime(true);

    //     try {
    //         // For API calls, we might want to add a test mode or use a sandbox endpoint
    //         if ($action->type === 'api_call') {
    //             $originalEndpoint = $action->config->endpoint;

    //             // If this is a relative URL, make it absolute for testing
    //             if ($this->isRelativeUrl($originalEndpoint)) {
    //                 $baseUrl = base_url();
    //                 $action->config->endpoint = rtrim($baseUrl, '/') . '/' . ltrim($originalEndpoint, '/');
    //             }

    //             log_message('info', 'Testing API call to: ' . $action->config->endpoint);
    //         }

    //         // Run the action
    //         $result = ApplicationFormActionHelper::runAction($action, $data);

    //         $endTime = microtime(true);
    //         $executionTime = round(($endTime - $startTime) * 1000, 2); // milliseconds

    //         return [
    //             'status' => 'success',
    //             'execution_time_ms' => $executionTime,
    //             'action_type' => $action->type,
    //             'endpoint_called' => $action->type === 'api_call' ? $action->config->endpoint : null,
    //             'result' => $result
    //         ];

    //     } catch (\Throwable $e) {
    //         $endTime = microtime(true);
    //         $executionTime = round(($endTime - $startTime) * 1000, 2);

    //         return [
    //             'status' => 'error',
    //             'execution_time_ms' => $executionTime,
    //             'action_type' => $action->type,
    //             'error' => $e,
    //             'endpoint_called' => $action->type === 'api_call' ? $action->config->endpoint : null
    //         ];
    //     }
    // }

    // /**
    //  * Check if URL is relative
    //  */
    // private function isRelativeUrl($url)
    // {
    //     return !preg_match('/^https?:\/\//', $url);
    // }

    // /**
    //  * Example endpoint that can be used for testing API calls
    //  * This creates a simple endpoint that accepts various HTTP methods
    //  */
    // public function testEndpoint()
    // {
    //     $method = $this->request->getMethod();
    //     $headers = $this->request->getHeaders();
    //     $body = $this->request->getJSON(true) ?: $this->request->getRawInput();
    //     $queryParams = $this->request->getGet();

    //     // Log the test request
    //     log_message('info', "Test endpoint called with method: $method");
    //     log_message('info', "Test endpoint headers: " . json_encode($headers));
    //     log_message('info', "Test endpoint body: " . json_encode($body));
    //     log_message('info', "Test endpoint query params: " . json_encode($queryParams));

    //     // Simulate some processing time
    //     usleep(100000); // 100ms delay

    //     $response = [
    //         'success' => true,
    //         'message' => 'Test endpoint called successfully',
    //         'received_data' => [
    //             'method' => $method,
    //             'headers' => $headers,
    //             'body' => $body,
    //             'query_params' => $queryParams,
    //             'timestamp' => date('Y-m-d H:i:s')
    //         ],
    //         'simulated_response' => [
    //             'id' => rand(1000, 9999),
    //             'status' => 'processed',
    //             'created_at' => date('Y-m-d H:i:s')
    //         ]
    //     ];

    //     // Simulate different responses based on method
    //     switch (strtoupper($method)) {
    //         case 'POST':
    //             $response['simulated_response']['action'] = 'created';
    //             return $this->respond($response, ResponseInterface::HTTP_CREATED);

    //         case 'PUT':
    //             $response['simulated_response']['action'] = 'updated';
    //             return $this->respond($response);

    //         case 'DELETE':
    //             $response['simulated_response']['action'] = 'deleted';
    //             return $this->respond($response);

    //         case 'GET':
    //         default:
    //             $response['simulated_response']['action'] = 'retrieved';
    //             return $this->respond($response);
    //     }
    // }

    // /**
    //  * Retrieves common application templates from the application settings.
    //  *
    //  * This function fetches the common application templates configured in the 
    //  * application settings and returns them in the response. If no templates 
    //  * are found, it returns a 404 response. In case of an error, it logs the 
    //  * error and returns a 500 response.
    //  *
    //  * @return \CodeIgniter\HTTP\Response
    //  */

    // public function getCommonApplicationTemplates()
    // {
    //     try {
    //         $settings = Utils::getAppSettings("commonApplicationTemplates");
    //         if (empty($settings)) {
    //             return $this->respond(['message' => "No common application templates found"], ResponseInterface::HTTP_NOT_FOUND);
    //         }
    //         $data = [];
    //         foreach ($settings as $key => $value) {
    //             $data[] = $value;
    //         }
    //         return $this->respond(['data' => $data], ResponseInterface::HTTP_OK);
    //     } catch (\Throwable $th) {
    //         log_message('error', $th);
    //         return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
    //     }
    // }

    /**
     * Get application timeline
     *
     * Retrieves the complete history of status changes and actions for a specific application
     *
     * @param string $uuid Application UUID
     * @return \CodeIgniter\HTTP\Response
     */
    public function getApplicationTimeline(string $uuid)
    {
        try {
            $timelineModel = new \App\Models\Applications\ApplicationTimelineModel();

            // Get query parameters for pagination and sorting
            $limit = $this->request->getGet('limit') ?? 50;
            $offset = $this->request->getGet('offset') ?? 0;
            $orderDir = $this->request->getGet('orderDir') ?? 'DESC';

            // Validate limit and offset
            $limit = min(max((int) $limit, 1), 200); // Between 1 and 200
            $offset = max((int) $offset, 0);
            $userId = auth("tokens")->id();
            $userData = AuthHelper::getAuthUser($userId);
            $timeline = $timelineModel->getApplicationTimeline($uuid, [
                'limit' => $limit,
                'offset' => $offset,
                'orderDir' => $orderDir,
            ], $userData);

            $total = $timelineModel->getTimelineCount($uuid);

            return $this->respond([
                'success' => true,
                'data' => $timeline,
                'total' => $total,
                'limit' => $limit,
                'offset' => $offset,
            ], ResponseInterface::HTTP_OK);

        } catch (\Throwable $e) {
            log_message('error', 'Timeline fetch error: ' . $e->getMessage());
            return $this->respond([
                'success' => false,
                'message' => 'Failed to fetch application timeline'
            ], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get simplified status history for an application
     *
     * @param string $uuid Application UUID
     * @return \CodeIgniter\HTTP\Response
     */
    public function getApplicationStatusHistory(string $uuid)
    {
        try {
            $timelineModel = new \App\Models\Applications\ApplicationTimelineModel();
            $statusHistory = $timelineModel->getStatusHistory($uuid);

            return $this->respond([
                'success' => true,
                'data' => $statusHistory,
            ], ResponseInterface::HTTP_OK);

        } catch (\Throwable $e) {
            log_message('error', 'Status history fetch error: ' . $e->getMessage());
            return $this->respond([
                'success' => false,
                'message' => 'Failed to fetch status history'
            ], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
