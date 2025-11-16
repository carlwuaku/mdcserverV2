<?php

// 1. Application Service

namespace App\Services;

use App\Helpers\ApplicationFormActionHelper;
use App\Helpers\BaseBuilderJSONQueryUtil;
use App\Helpers\CacheHelper;
use App\Helpers\PractitionerUtils;
use App\Helpers\Utils;
use App\Models\ActivitiesModel;
use App\Models\Applications\ApplicationsModel;
use App\Models\Applications\ApplicationTemplateModel;
use App\Models\Applications\ApplicationTemplateStage;
use App\Models\Applications\ApplicationTimelineModel;
use App\Models\Practitioners\PractitionerModel;
use App\Models\Practitioners\PractitionerRenewalModel;
use CodeIgniter\Database\BaseBuilder;
use Exception;

/**
 * Application Service - Handles all application-related business logic
 */
class ApplicationService
{
    private ActivitiesModel $activitiesModel;
    private ApplicationTimelineModel $timelineModel;
    private array $primaryColumns = ['first_name', 'picture', 'last_name', 'middle_name', 'email', 'phone'];

    public function __construct()
    {
        $this->activitiesModel = new ActivitiesModel();
        $this->timelineModel = new ApplicationTimelineModel();
    }

    /**
     * Create a new application
     */
    public function createApplication(string $formType, array $payload): array
    {
        if (empty($payload)) {
            throw new \InvalidArgumentException("Payload cannot be empty");
        }
        if (empty($formType)) {
            throw new \InvalidArgumentException("Form type cannot be empty");
        }

        $data = $this->createFormMetaFromPayload($payload, $formType);
        $applicationCode = $data['application_code'] = Utils::generateApplicationCode($formType);

        // Get the form template
        $template = ApplicationFormActionHelper::getApplicationTemplate($formType);
        if (!$template) {
            throw new \InvalidArgumentException("Form template not found");
        }
        $data['template'] = is_string($template->data) ? $template->data : json_encode($template->data);
        // Set initial status
        if (empty($template->initialStage)) {
            $data['status'] = "Pending approval";
        } else {
            $data['status'] = $template->initialStage;
        }

        // Process initial stage actions
        $this->processInitialStageActions($template, $data);

        // Save application
        $model = new ApplicationsModel();
        $insertId = $model->insert((object) $data);

        // Get the created application to retrieve its UUID
        $createdApplication = $model->find($insertId);
        $applicationUuid = is_array($createdApplication) ? $createdApplication['uuid'] : $createdApplication->uuid;

        // Create initial timeline entry for application creation
        $this->timelineModel->createTimelineEntry(
            $applicationUuid,
            $data['status'],
            [
                'fromStatus' => null,
                'notes' => 'Application created',
                'submittedData' => $payload,
                'userId' => auth("tokens")->id() ?? null,
            ]
        );

        // Log activity
        $this->activitiesModel->logActivity("Created application {$data['form_type']} with code $applicationCode");

        return [
            'success' => true,
            'message' => 'Application created successfully',
            'data' => [
                'applicationCode' => $applicationCode,
                'onSubmitMessage' => $template->on_submit_message
            ]
        ];
    }

    /**
     * Update an existing application
     */
    public function updateApplication(string $uuid, array $requestData): array
    {
        $model = new ApplicationsModel();
        $application = $model->where(['uuid' => $uuid])->first();

        if (!$application) {
            throw new \RuntimeException('Application not found');
        }

        $formType = $application['form_type'];
        $data = (object) ["form_data" => json_encode($requestData)];
        $data->uuid = $uuid;

        // Remove id if present
        if (property_exists($data, "id")) {
            unset($data->id);
        }

        // Update primary columns from form data
        $formData = json_decode($data->form_data, true);
        foreach ($this->primaryColumns as $column) {
            if (array_key_exists($column, $formData)) {
                $data->$column = $formData[$column];
            }
        }

        $oldData = $application;
        $changes = implode(", ", Utils::compareObjects($oldData, $data));

        if (!$model->builder()->where(['uuid' => $uuid])->update($data)) {
            throw new \RuntimeException('Failed to update application: ' . json_encode($model->errors()));
        }

        // Log activity
        $this->activitiesModel->logActivity("Updated application {$formType} {$application['application_code']}. Changes: $changes");

        return [
            'success' => true,
            'message' => 'Application updated successfully'
        ];
    }

    /**
     * Update application status with bulk operations
     */
    public function updateApplicationStatus(string $applicationType, string $status, array $applicationIds, int $userId, array $submittedData = []): array
    {
        if (!$applicationType || !$status || !$applicationIds) {
            throw new \InvalidArgumentException("Application type, status, and application IDs are required");
        }

        // Get template and validate stage
        $template = ApplicationFormActionHelper::getApplicationTemplate($applicationType);
        if (!$template) {
            throw new \RuntimeException("Application template not found");
        }
        // log_message('info', "Template: " . print_r($template, true));
        $stages = is_string($template->stages) ? json_decode($template->stages, true) : $template->stages;
        if (empty($stages)) {
            throw new \RuntimeException("Application stages not found");
        }

        $stage = $this->findStageByName($stages, $status);
        if (!$stage) {
            throw new \RuntimeException("Stage not found");
        }

        // Validate user permissions
        $this->validateUserPermissions($userId, $stage);

        // Get request metadata for timeline
        $request = \Config\Services::request();
        $ipAddress = $request->getIPAddress();
        $userAgent = $request->getUserAgent()->getAgentString();

        // Process applications
        $model = new ApplicationsModel();
        $applications = $model->builder()->whereIn('uuid', $applicationIds)->get()->getResult('array');

        $applicationIdsToUpdate = [];
        $applicationCodesArray = [];
        $timelineEntries = [];

        foreach ($applications as $application) {
            $applicationCodesArray[] = $application['application_code'];
            $actionsResults = [];

            if (!empty($stage['actions'])) {
                try {
                    $actionsResults = $this->processStageActionsWithResults($stage['actions'], $application, $model);
                    $applicationIdsToUpdate[] = $application['uuid'];
                } catch (\Throwable $e) {
                    log_message('error', 'Stage action failed: ' . $e);

                    // Log failed timeline entry
                    $this->timelineModel->createTimelineEntry(
                        $application['uuid'],
                        $status,
                        [
                            'fromStatus' => $application['status'],
                            'userId' => $userId,
                            'stageData' => $stage,
                            'actionsExecuted' => $stage['actions'],
                            'actionsResults' => [
                                'success' => false,
                                'error' => $e->getMessage(),
                                'timestamp' => date('Y-m-d H:i:s'),
                            ],
                            'submittedData' => $submittedData,
                            'notes' => 'Status update failed: ' . $e->getMessage(),
                            'ipAddress' => $ipAddress,
                            'userAgent' => $userAgent,
                        ]
                    );

                    throw new \RuntimeException("Failed to process application {$application['application_code']}: " . $e->getMessage());
                }
            } else {
                $applicationIdsToUpdate[] = $application['uuid'];
                $actionsResults = ['success' => true, 'message' => 'No actions to execute'];
            }

            // Prepare timeline entry
            $timelineEntries[] = [
                'application_uuid' => $application['uuid'],
                'from_status' => $application['status'],
                'to_status' => $status,
                'stage_data' => $stage,
                'actions_executed' => $stage['actions'] ?? null,
                'actions_results' => $actionsResults,
                'submitted_data' => $submittedData,
                'user_id' => $userId,
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
            ];
        }

        if (empty($applicationIdsToUpdate)) {
            throw new \RuntimeException("No applications were updated");
        }

        // Update status for successful applications
        $model->builder()->whereIn('uuid', $applicationIdsToUpdate)->update(['status' => $status]);

        // Insert timeline entries for successful updates
        foreach ($timelineEntries as $entry) {
            $this->timelineModel->createTimelineEntry(
                $entry['application_uuid'],
                $entry['to_status'],
                [
                    'fromStatus' => $entry['from_status'],
                    'userId' => $entry['user_id'],
                    'stageData' => $entry['stage_data'],
                    'actionsExecuted' => $entry['actions_executed'],
                    'actionsResults' => $entry['actions_results'],
                    'submittedData' => $entry['submitted_data'],
                    'notes' => 'Status updated successfully',
                    'ipAddress' => $entry['ip_address'],
                    'userAgent' => $entry['user_agent'],
                ]
            );
        }

        $applicationCodes = implode(", ", $applicationCodesArray);
        $this->activitiesModel->logActivity("Updated applications {$applicationCodes} status to $status. See the logs for more details");

        return [
            'success' => true,
            'message' => 'Applications updated successfully. See logs for more details'
        ];
    }

    /**
     * Delete an application
     */
    public function deleteApplication(string $uuid): array
    {
        $model = new ApplicationsModel();
        $data = $model->where(["uuid" => $uuid])->first();

        if (!$data) {
            throw new \RuntimeException("Application not found");
        }

        if (!$model->where('uuid', $uuid)->delete()) {
            throw new \RuntimeException('Failed to delete application: ' . json_encode($model->errors()));
        }

        $this->activitiesModel->logActivity("Deleted application {$data['form_type']} for {$data['email']}");

        return [
            'success' => true,
            'message' => 'Application deleted successfully'
        ];
    }

    /**
     * Restore an application
     */
    public function restoreApplication(string $uuid): array
    {
        $model = new ApplicationsModel();

        if (!$model->builder()->where(['uuid' => $uuid])->update(['deleted_at' => null])) {
            throw new \RuntimeException('Failed to restore application: ' . json_encode($model->errors()));
        }

        $data = $model->where(["uuid" => $uuid])->first();
        $this->activitiesModel->logActivity("Restored application {$data['form_type']} for {$data['email']} from recycle bin");

        return [
            'success' => true,
            'message' => 'Application restored successfully'
        ];
    }

    /**
     * Get application details
     */
    public function getApplicationDetails(string $uuid): ?array
    {
        $model = new ApplicationsModel();
        $data = $model->where('uuid', $uuid)->orWhere('application_code', $uuid)->first();

        if (!$data) {
            return null;
        }

        $data['form_data'] = json_decode($data['form_data'], true);
        $data['template'] = empty($data['template']) ? [] : json_decode($data['template'], true);

        return [
            'data' => $data,
            'displayColumns' => $model->getDisplayColumns()
        ];
    }

    /**
     * Get applications with filtering and pagination
     */
    public function getApplications(array $filters = [], array $exclusionFilters = []): array
    {
        $per_page = $filters['limit'] ?? 100;
        $page = $filters['page'] ?? 0;
        $withDeleted = ($filters['withDeleted'] ?? '') === "yes";
        $param = $filters['param'] ?? null;
        $sortBy = $filters['sortBy'] ?? "id";
        $sortOrder = $filters['sortOrder'] ?? "desc";

        $model = new ApplicationsModel();
        $builder = $param ? $model->search($param) : $model->builder();
        $builder = $model->addCustomFields($builder);
        $builder->orderBy($sortBy, $sortOrder);

        // Apply filters
        $this->applyApplicationFilters($builder, $filters, $exclusionFilters);

        // Apply child parameters (JSON field filters)
        $this->applyChildParameters($builder, $filters);

        // Get total count
        $totalBuilder = clone $builder;
        $total = $totalBuilder->countAllResults();

        // Get results
        $result = $builder->get($per_page, $page)->getResult();

        // Process results and build dynamic columns
        $processedData = $this->processApplicationResults($result, $model);

        return [
            'data' => $processedData['data'],
            'total' => $total,
            'displayColumns' => $processedData['displayColumns'],
            'columnFilters' => $model->getDisplayColumnFilters()
        ];
    }

    /**
     * Count applications with filters
     */
    public function countApplications(array $filters = []): int
    {
        $param = $filters['param'] ?? null;
        $model = new ApplicationsModel();
        $builder = $param ? $model->search($param) : $model->builder();

        // Apply filters
        $this->applyApplicationFilters($builder, $filters);
        $this->applyChildParameters($builder, $filters);

        return $builder->countAllResults();
    }

    /**
     * Get application form types
     */
    public function getApplicationFormTypes(string $field): array
    {
        $model = new ApplicationsModel();
        $formTypes = $model->getDistinctValuesAsKeyValuePairs($field);

        $applicationTemplateModel = new ApplicationTemplateModel();
        $templates = $applicationTemplateModel->builder()->select(['form_name'])->distinct()->get()->getResult();

        // Merge form types with templates
        foreach ($formTypes as $formType) {
            if (!in_array($formType['key'], array_column($templates, 'form_name'))) {
                $templates[] = (object) ['form_name' => $formType['key']];
            }
        }

        return $templates;
    }

    /**
     * Get application statuses for a form
     */
    public function getApplicationStatuses(string $form): array
    {
        if (empty(trim($form))) {
            throw new \InvalidArgumentException("Please provide a form type");
        }

        $model = new ApplicationsModel();
        $builder = $model->builder();
        $builder->select(["form_type", "status", "count(*) as count"]);
        $builder->where("form_type", $form);
        $builder->groupBy(["form_type", "status"]);
        $statuses = $builder->get()->getResultArray();

        // Get template stages
        $template = ApplicationFormActionHelper::getApplicationTemplate($form);
        if (!$template) {
            return $statuses;
        }

        $stages = is_string($template->stages) ? json_decode($template->stages, true) : $template->stages;
        return $this->mergeStatusesWithStages($statuses, $stages, $template, $form);
    }

    /**
     * Approve permanent application
     */
    public function approvePermanentApplication(array $applicationDetails, string $registrationNumber): array
    {
        $today = date("Y-m-d");
        $model = new ApplicationsModel();
        $practitionerModel = new PractitionerModel();
        $renewalModel = new PractitionerRenewalModel();

        $practitionerModel->db->transException(true)->transStart();

        try {
            // Update application status
            $model->builder()->where(['uuid' => $applicationDetails['uuid']])->update(['status' => "approved"]);

            $formData = json_decode($applicationDetails['form_data'], true);

            // Prepare practitioner data
            $practitionerData = $this->preparePractitionerData($practitionerModel, $formData, $registrationNumber, $today);

            // Handle existing practitioner or create new
            $practitionerId = $this->handlePractitionerCreation($practitionerModel, $practitionerData, $formData);

            $practitioner = $practitionerModel->find($practitionerId);

            // Delete application
            $model->delete($applicationDetails['id']);

            // Create retention record
            $this->createRetentionRecord($renewalModel, (array) $practitioner, $practitionerData);

            $practitionerModel->db->transComplete();

            return [
                'success' => true,
                'message' => 'Application approved successfully'
            ];

        } catch (\Throwable $e) {
            $practitionerModel->db->transRollback();
            throw $e;
        }
    }

    /**
     * Approve portal edit
     */
    public function approvePortalEdit(array $applicationDetails): array
    {
        $today = date("Y-m-d");
        $practitionerModel = new PractitionerModel();

        $field = $applicationDetails['field'];
        $value = $applicationDetails['value'];
        $registrationNumber = $applicationDetails['reg_num'];
        $action = $applicationDetails['action'];
        $attachments = $applicationDetails['attachments'];
        $revalidate = $applicationDetails['revalidate'];

        $updateData = [];
        $activityLog = "";

        switch ($field) {
            case 'picture':
                $result = $this->handlePictureUpdate($value, $registrationNumber);
                $updateData['picture'] = $result['filename'];
                $activityLog = $result['activity'];
                break;

            case "qualification":
                $activityLog = $this->handleQualificationDeletion($value, $registrationNumber);
                break;

            case "work_history":
                $activityLog = $this->handleWorkHistoryDeletion($value, $registrationNumber);
                break;

            default:
                $updateData[$field] = $value;
                break;
        }

        if ($revalidate) {
            $updateData['require_revalidation'] = "no";
            $updateData['last_revalidation_date'] = $today;
        }

        if (!empty($updateData)) {
            $practitionerModel->where(['registration_number' => $registrationNumber])->update($updateData);
        }

        if ($activityLog) {
            $this->activitiesModel->logActivity($activityLog);
        }

        return [
            'success' => true,
            'message' => 'Application updated successfully'
        ];
    }

    // Private helper methods

    private function createFormMetaFromPayload(array $payload, string $formType): array
    {
        $model = new ApplicationsModel();
        $meta = $model->createArrayFromAllowedFields($payload);

        if (array_key_exists('last_name', $meta)) {
            if (empty($meta['last_name']) && array_key_exists('registration_number', $payload)) {
                $meta['last_name'] = $payload['registration_number'];
            }
        } else if (array_key_exists('registration_number', $payload)) {
            $meta['last_name'] = $payload['registration_number'];
        } else {
            $meta['last_name'] = "";
        }

        $meta['form_data'] = json_encode($payload);
        $meta['form_type'] = $formType;

        return $meta;
    }



    private function processInitialStageActions(object $template, array &$data): void
    {
        $stages = is_string($template->stages) ? json_decode($template->stages, true) : $template->stages;
        if (empty($stages)) {
            return;
        }

        $initialStage = array_filter($stages, function ($stage) use ($template) {
            return $stage['name'] == $template->initialStage;
        });

        if (empty($initialStage)) {
            throw new \RuntimeException("Initial stage not found");
        }

        $initialStage = array_values($initialStage)[0];

        foreach ($initialStage['actions'] as $action) {
            try {
                $action = \App\Helpers\Types\ApplicationStageType::fromArray($action);
                ApplicationFormActionHelper::runAction($action, $data);
            } catch (\Throwable $e) {
                log_message('error', 'Initial stage action failed: ' . $e);
            }
        }
    }

    private function findStageByName(array $stages, string $status): ?array
    {
        $filtered = array_filter($stages, function ($stage) use ($status) {
            return $stage['name'] == $status;
        });

        return !empty($filtered) ? current($filtered) : null;
    }

    private function validateUserPermissions(int $userId, array $stage): void
    {
        $userObject = new \App\Models\UsersModel();
        $userData = $userObject->findById($userId);

        if (!in_array($userData->role_name, $stage['allowedUserRoles'])) {
            throw new \RuntimeException("You are not allowed to update applications to this stage");
        }
    }

    private function processStageActions(array $actions, array $application, ApplicationsModel $model): void
    {
        $model->db->transException(transException: true)->transStart();
        try {
            // log_message('info', "Processing application: " . print_r($application['form_data'], true));
            $formData = json_decode($application['form_data'], true);
            $applicationData = array_merge($application, $formData);

            foreach ($actions as $action) {
                // Merge form_data with application data

                // unset($application['form_data']);
                $action = \App\Helpers\Types\ApplicationStageType::fromArray($action);
                ApplicationFormActionHelper::runAction($action, $applicationData);
            }

            $model->db->transComplete();
        } catch (\Throwable $e) {
            $model->db->transRollback();
            throw $e;
        }
    }

    /**
     * Process stage actions and capture results for timeline
     */
    private function processStageActionsWithResults(array $actions, array $application, ApplicationsModel $model): array
    {
        $results = [];

        $model->db->transException(true)->transStart();

        try {
            $formData = json_decode($application['form_data'], true);
            $applicationData = array_merge($application, $formData);

            foreach ($actions as $index => $action) {
                $action = \App\Helpers\Types\ApplicationStageType::fromArray($action);

                try {
                    // Run the action
                    $actionResult = ApplicationFormActionHelper::runAction($action, $applicationData);

                    // Capture result
                    $results[] = [
                        'action_type' => $action->type ?? 'unknown',
                        'action_config' => $action->config ?? null,
                        'success' => true,
                        'result' => $actionResult,
                        'timestamp' => date('Y-m-d H:i:s'),
                    ];
                } catch (\Throwable $e) {
                    $results[] = [
                        'action_type' => $action->type ?? 'unknown',
                        'action_config' => $action->config ?? null,
                        'success' => false,
                        'error' => $e->getMessage(),
                        'timestamp' => date('Y-m-d H:i:s'),
                    ];
                    throw $e; // Re-throw to trigger rollback
                }
            }

            $model->db->transComplete();

            return [
                'success' => true,
                'actions' => $results,
            ];
        } catch (\Throwable $e) {
            $model->db->transRollback();

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'actions' => $results,
            ];
        }
    }

    private function applyApplicationFilters(BaseBuilder $builder, array $filters, array $exclusionFilters = []): void
    {
        $filterMappings = [
            'application_code' => 'application_code',
            'status' => 'status',
            'practitioner_type' => 'practitioner_type',
            'form_type' => 'form_type',
            'applicant_unique_id' => 'applicant_unique_id'
        ];

        foreach ($filterMappings as $filterKey => $column) {
            if (isset($filters[$filterKey]) && $filters[$filterKey] !== null) {
                $builder->where($column, $filters[$filterKey]);
            }
        }

        foreach ($filterMappings as $filterKey => $column) {
            if (isset($exclusionFilters[$filterKey]) && $exclusionFilters[$filterKey] !== null) {
                $builder->where($column . ' != ', $exclusionFilters[$filterKey]);
            }
        }

        // Date filters
        if (isset($filters['start_date']) && $filters['start_date'] !== null) {
            $builder->where('created_on >=', $filters['start_date']);
        }
        if (isset($filters['end_date']) && $filters['end_date'] !== null) {
            $builder->where('created_on <=', $filters['end_date']);
        }
    }

    private function applyChildParameters(BaseBuilder $builder, array $filters): void
    {
        $childParams = array_filter($filters, function ($key) {
            return strpos($key, 'child_') === 0;
        }, ARRAY_FILTER_USE_KEY);

        foreach ($childParams as $key => $value) {
            $field = str_replace('child_', '', $key);
            $builder = BaseBuilderJSONQueryUtil::whereJson($builder, 'form_data', $field, $value);
        }
    }

    private function processApplicationResults(array $result, ApplicationsModel $model): array
    {
        $final = [];
        $displayColumns = $model->getDisplayColumns();
        $excludedDisplayColumns = ['id', 'uuid'];
        $allFormColumns = [];

        foreach ($result as $value) {
            $formData = json_decode($value->form_data, true);

            // Add system fields
            $formData['uuid'] = $value->uuid;
            $formData['status'] = $value->status;
            $formData['created_on'] = $value->created_on;
            $formData['form_type'] = $value->form_type;
            $formData['application_code'] = $value->application_code;
            //for these field if they're not in formData, use whatever value is in the $value
            $defaultFields = ["first_name", "last_name", "middle_name", "email", "phone"];
            foreach ($defaultFields as $field) {
                if (!array_key_exists($field, $formData)) {
                    $formData[$field] = $value->$field;
                }
            }

            // Collect unique form columns
            $formColumns = array_keys($formData);
            foreach ($formColumns as $col) {
                if (
                    !in_array($col, $allFormColumns) &&
                    !in_array($col, $displayColumns) &&
                    !in_array($col, $excludedDisplayColumns)
                ) {
                    $allFormColumns[] = $col;
                }
            }
            $final[] = $formData;
        }

        // Insert form columns into display columns
        $formDataIndex = array_search('form_data', $displayColumns) ?? count($displayColumns);
        array_splice($displayColumns, $formDataIndex, 1, $allFormColumns);

        return [
            'data' => $final,
            'displayColumns' => $displayColumns
        ];
    }

    private function mergeStatusesWithStages(array $statuses, array $stages, object $template, string $form): array
    {
        $statusesArray = array_column($statuses, null, 'status');
        $stagesArray = array_column($stages, null, 'name');

        foreach ($stagesArray as $stage => $stageData) {
            if (!array_key_exists($stage, $statusesArray)) {
                $statusesArray[$stage] = [
                    "form_type" => $template->form_name,
                    "status" => $stage,
                    "count" => 0
                ];
            }
        }

        // Reorder with initial and final stages
        $data = [];
        $initialStageData = null;
        $finalStageData = null;

        if (array_key_exists($template->initialStage, $statusesArray)) {
            $initialStageData = $statusesArray[$template->initialStage];
            unset($statusesArray[$template->initialStage]);
        }

        if (array_key_exists($template->finalStage, $statusesArray)) {
            $finalStageData = $statusesArray[$template->finalStage];
            unset($statusesArray[$template->finalStage]);
        }

        if ($initialStageData) {
            $data[] = $initialStageData;
        }

        $data = array_merge($data, array_values($statusesArray));

        if ($finalStageData) {
            $data[] = $finalStageData;
        }

        return $data;
    }

    private function preparePractitionerData(PractitionerModel $model, array $formData, string $registrationNumber, string $today): array
    {
        $practitionerData = $model->createArrayFromAllowedFields($formData);
        $practitionerData['register_type'] = "Permanent";
        $practitionerData['practitioner_type'] = $formData['type'];
        $practitionerData['year_of_permanent'] = $today;
        $practitionerData['year_of_provisional'] = $formData["date_of_provisional"];
        $practitionerData['registration_date'] = $today;
        $practitionerData['registration_number'] = $registrationNumber;
        $practitionerData['qualification_at_registration'] = $formData["qualification"];
        $practitionerData['qualification_date'] = $formData["date_of_graduation"];
        $practitionerData['status'] = 1;

        return $practitionerData;
    }

    private function handlePractitionerCreation(PractitionerModel $model, array $practitionerData, array $formData): int
    {
        $existingPractitionerBuilder = $model->builder()->where(['registration_number' => $formData["provisional_registration_number"]]);

        if ($existingPractitionerBuilder->countAllResults() > 0) {
            $existingPractitionerBuilder->update($practitionerData);
            return $model->first()['id'];
        } else {
            // Handle new practitioner creation
            $model->insert($practitionerData);
            return $model->getInsertID();
        }
    }

    private function createRetentionRecord(PractitionerRenewalModel $renewalModel, array $practitioner, array $practitionerData): void
    {
        $retentionYear = date("Y");
        $retentionData = $renewalModel->createArrayFromAllowedFields($practitioner);
        $retentionData = array_merge($retentionData, [
            "practitioner_uuid" => $practitioner['uuid'],
            "status" => "Approved",
            "practitioner_type" => $practitioner['practitioner_type'],
        ]);

        PractitionerUtils::retainPractitioner(
            $practitioner['uuid'],
            "",
            $retentionData,
            $retentionYear,
            null,
            null,
            null,
            null,
            $practitionerData['specialty']
        );
    }

    private function handlePictureUpdate(string $value, string $registrationNumber): array
    {
        $registrationNumberNoSpaces = str_replace(" ", "_", $registrationNumber);
        $origin = WRITEPATH . UPLOADS_FOLDER . DIRECTORY_SEPARATOR . APPLICATIONS_ASSETS_FOLDER . DIRECTORY_SEPARATOR . $value;
        $file = new \CodeIgniter\Files\File($origin, true);
        $destinationFileName = microtime() . $registrationNumberNoSpaces . $file->guessExtension();
        $destination = WRITEPATH . UPLOADS_FOLDER . DIRECTORY_SEPARATOR . PRACTITIONERS_ASSETS_FOLDER . DIRECTORY_SEPARATOR . $destinationFileName;

        copy($origin, $destination);

        return [
            'filename' => $destinationFileName,
            'activity' => "updated picture of $registrationNumber in response to web request"
        ];
    }

    private function handleQualificationDeletion(string $value, string $registrationNumber): string
    {
        $additionalQualificationModel = new \App\Models\Practitioners\PractitionerAdditionalQualificationsModel();
        $qualification = $additionalQualificationModel->find($value);
        $additionalQualificationModel->delete($value);

        return "deleted certificate: {$qualification->qualification} ({$qualification->institution}) from profile of $registrationNumber in response to web request";
    }

    private function handleWorkHistoryDeletion(string $value, string $registrationNumber): string
    {
        $workHistoryModel = new \App\Models\Practitioners\PractitionerWorkHistoryModel();
        $details = $workHistoryModel->find($value);
        $workHistoryModel->delete($value);

        return "deleted work history: {$details->position} at ({$details->institution}) from profile of $registrationNumber in response to web request";
    }

    /**
     * Get basic statistics for application forms
     *
     * @param array $filters Array of filter parameters
     * @return array Array of statistics results
     */
    public function getBasicStatistics(array $filters = []): array
    {
        $model = new ApplicationsModel();
        $selectedFields = $filters['fields'] ?? [];

        $fields = $model->getBasicStatisticsFields();

        // Filter fields based on selection
        $filteredFields = array_filter($fields, function ($field) use ($selectedFields) {
            return in_array($field['name'], $selectedFields);
        });

        $parentParams = $model->createArrayFromAllowedFields($filters);
        $results = [];

        foreach ($filteredFields as $field) {
            $builder = $this->buildStatisticsQuery($model, $field, $parentParams, $filters);
            $result = $builder->get()->getResult();

            $results[$field['name']] = $this->formatStatisticsResult($field, $result);
        }

        return $results;
    }

    /**
     * Build query for statistics data
     *
     * @param ApplicationsModel $model The application model
     * @param array $field Field configuration
     * @param array $parentParams Filter parameters
     * @param array $filters All filters including date ranges
     * @return \CodeIgniter\Database\BaseBuilder
     */
    private function buildStatisticsQuery(ApplicationsModel $model, array $field, array $parentParams, array $filters = []): \CodeIgniter\Database\BaseBuilder
    {
        $builder = $model->builder();
        $tableName = $model->getTableName();

        // Apply filter parameters
        foreach ($parentParams as $key => $value) {
            $value = Utils::parseParam($value);
            $builder = Utils::parseWhereClause($builder, $tableName . "." . $key, $value);
        }

        // Handle date range filters if provided
        if (isset($filters['start_date'])) {
            $builder->where("$tableName.created_on >=", $filters['start_date']);
        }
        if (isset($filters['end_date'])) {
            $builder->where("$tableName.created_on <=", $filters['end_date']);
        }

        $builder->select([$field['name'], "COUNT(*) as count"]);

        // Handle field aliases (e.g., "YEAR(created_on) as year")
        $fieldName = $field['name'];
        if (strpos($fieldName, " as ") !== false) {
            $fieldName = explode(" as ", $fieldName)[1];
        }

        $builder->groupBy($fieldName);
        return $builder;
    }

    /**
     * Format statistics result for frontend consumption
     *
     * @param array $field Field configuration
     * @param array $result Query result
     * @return array Formatted result
     */
    private function formatStatisticsResult(array $field, array $result): array
    {
        $fieldName = $field['name'];
        if (strpos($fieldName, " as ") !== false) {
            $fieldName = explode(" as ", $fieldName)[1];
        }

        // Replace null values with 'Null'
        $result = array_map(function ($item) use ($fieldName) {
            $item->$fieldName = empty($item->$fieldName) ? 'Null' : $item->$fieldName;
            return $item;
        }, $result);

        return [
            "label" => $field['label'],
            "type" => $field['type'],
            "data" => $result,
            "labelProperty" => $fieldName,
            "valueProperty" => "count",
            "name" => $fieldName,
            "xAxisLabel" => $field['xAxisLabel'],
            "yAxisLabel" => $field['yAxisLabel'],
        ];
    }

}