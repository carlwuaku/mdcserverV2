<?php

namespace App\Services;

use App\Helpers\LicenseUtils;
use App\Helpers\Types\LicenseRenewalEligibilityCriteriaType;
use App\Helpers\Types\RenewalStageType;
use App\Helpers\Utils;
use App\Models\ActivitiesModel;
use App\Models\Licenses\LicenseRenewalModel;
use App\Models\Licenses\LicensesModel;
use App\Models\PrintTemplateModel;
use App\Models\UsersModel;
use CodeIgniter\Database\BaseBuilder;
use CodeIgniter\Exceptions\ConfigException;
use Exception;
use App\Helpers\ApplicationFormActionHelper;
use App\Helpers\AuthHelper;
use App\Helpers\Types\PractitionerPortalRenewalViewModelType;
use DateTime;
use App\Helpers\TemplateEngineHelper;

/**
 * License Renewal Service - Handles all license renewal-related business logic
 */
class LicenseRenewalService
{
    private ActivitiesModel $activitiesModel;

    public function __construct()
    {
        $this->activitiesModel = new ActivitiesModel();
    }


    /**
     * Create a new license renewal.
     *
     * @param array $data An array containing the license number, license UUID and license type.
     * @return array An array containing the success status, message and data.
     * @throws ConfigException If no valid stages are found for the license type.
     * @throws Exception If the renewal is not found with the given id.
     * @throws \Throwable If an error occurs during the creation of the renewal.
     */
    public function createRenewal(array $data): array
    {
        $rules = [
            "license_number" => "required",
            "license_uuid" => "required",
            "license_type" => "required"
        ];

        $license_uuid = $data['license_uuid'];
        $licenseType = $data['license_type'];
        // Validate data
        $validation = \Config\Services::validation();
        if (!$validation->setRules($rules)->run($data)) {
            throw new \InvalidArgumentException('Validation failed: ' . json_encode($validation->getErrors()));
        }
        //get the valid stages for the license type
        $validStages = LicenseUtils::getLicenseRenewalStagesValues($licenseType);
        if (count($validStages) == 0) {
            throw new ConfigException("No valid stages found for license type $licenseType");
        }
        //get the first stage. in this case there's no need for the user to have permission to activate that stage since it's the default
        $stage = $validStages[0];
        $this->validateRenewalStageActivation($data, $stage, $licenseType, false);

        $data['status'] = $stage->label;



        $model = new LicenseRenewalModel(licenseType: $licenseType);

        try {
            $model->db->transException(true)->transStart();
            $renewalId = LicenseUtils::retainLicense($license_uuid, $data);
            $model->db->transComplete();
            try {
                //get the details of the renewal
                $builder = $model->builder();
                $builder->where($model->table . '.id', $renewalId);
                $builder = $model->addLicenseDetails($builder, $licenseType, true, true, '', '', true);
                $renewalDetails = $builder->get()->getFirstRow('array');
                if (!$renewalDetails) {
                    throw new Exception("Renewal not found with id $renewalId");
                }
                //add the data_snapshot to the renewal details
                //TODO: REFACTOR
                $dataSnapshot = array_key_exists("data_snapshot", $renewalDetails) && $renewalDetails['data_snapshot'] ? json_decode($renewalDetails['data_snapshot'], true) : [];
                $fieldsToRemove = ['id', 'uuid', 'created_on', 'modified_on', 'deleted_at', 'status'];
                foreach ($fieldsToRemove as $field) {
                    if (array_key_exists($field, $dataSnapshot)) {
                        unset($dataSnapshot[$field]);
                    }
                }
                $renewalDetails = array_merge($renewalDetails, $dataSnapshot);
                //run the actions for that stage
                $this->runRenewalActions($renewalDetails, $stage);
            } catch (\Throwable $th) {
                log_message('error', "Error getting renewal details: " . $license_uuid . "<br>" . $th);
            }

            return [
                'success' => true,
                'message' => "Renewal created successfully",
                'data' => ""
            ];

        } catch (\Throwable $e) {
            $model->db->transRollback();
            throw $e;
        }
    }

    /**
     * Validates the renewal stage activation by checking if the provided data complies with the rules
     * for the specified license type and stage. Also checks if the user has the required permission for the stage.
     *
     * @param array $data The data to be validated.
     * @param RenewalStageType $stage The stage to validate against.
     * @param string $licenseType The license type.
     * @throws \InvalidArgumentException If the data validation fails.
     * @throws \CodeIgniter\Shield\Exceptions\PermissionException If the user does not have the required permission.
     */
    private function validateRenewalStageActivation(array $data, RenewalStageType $stage, string $licenseType, bool $requirePermission = true)
    {
        $rules = Utils::getLicenseRenewalStageValidation($licenseType, $stage->label);
        $validation = \Config\Services::validation();
        if (count($rules) > 0 && !$validation->setRules($rules)->run($data)) {
            log_message('error', json_encode($validation->getErrors()));
            throw new \InvalidArgumentException('Validation failed: ' . json_encode($validation->getErrors()));
        }
        // Check if the user has the required permission for the stage
        if ($requirePermission) {
            $permission = $stage->permission;
            $rpModel = new \App\Models\RolePermissionsModel();
            //if an admin, check the permission. if not, check if the user is the owner of the renewal

            if (!$rpModel->hasPermission(auth()->getUser()->role_name, $permission)) {
                log_message("error", "User " . auth()->getUser()->username . " attempted to activate renewal stage {$stage->label} without the required permission: $permission");
                throw new \CodeIgniter\Shield\Exceptions\PermissionException("You do not have permission to perform this action");
            }
        }

    }


    /**
     * Runs the actions for the given renewal stage.
     *
     * @param array $renewalDetails The details of the renewal.
     * @param RenewalStageType $stage The stage to run the actions for.
     * @throws \Throwable If an error occurs while running the actions.
     */
    private function runRenewalActions(array $renewalDetails, RenewalStageType $stage)
    {
        try {

            //run the actions for that stage
            if (isset($stage->actions)) {
                foreach ($stage->actions as $action) {
                    try {
                        ApplicationFormActionHelper::runAction($action, (array) $renewalDetails);
                    } catch (\Throwable $th) {
                        //let it through for now. 
                        //TODO: notify the admin to retry
                        log_message('error', "Error running action for renewal: " . json_encode($action) . json_encode($renewalDetails) . "<br>" . $th);
                    }

                }
            }
        } catch (\Throwable $th) {
            log_message('error', "Error getting renewal details: " . json_encode($renewalDetails) . "<br>" . $th);
        }
    }




    public function updateRenewal(string $uuid, array $data): array
    {
        $rules = [
            "license_number" => "required",
            "license_uuid" => "required",
            "id" => "required",
        ];

        // Validate data
        $validation = \Config\Services::validation();
        if (!$validation->setRules($rules)->run($data)) {
            throw new \InvalidArgumentException('Validation failed: ' . json_encode($validation->getErrors()));
        }

        $renewalUuid = $data['id'];
        $licenseType = $data['license_type'];

        $data['uuid'] = $uuid;
        unset($data['id']);
        //the status is not allowed to be updated. do that through updateBulkRenewals
        unset($data['status']);

        $model = new LicenseRenewalModel($licenseType);

        // Use database transaction
        $model->db->transException(true)->transStart();

        try {
            LicenseUtils::updateRenewal($renewalUuid, $data);
            $model->db->transComplete();

            return [
                'success' => true,
                'message' => 'Renewal updated successfully'
            ];

        } catch (\Throwable $e) {
            $model->db->transRollback();
            throw $e;
        }
    }


    /**
     * Update multiple license renewals. this is used to update the status of the renewals
     *
     * @param object[] $renewalsData An array of renewals data
     * @param string|null $status The new status for the renewals. If provided, it will be
     *          validated against the valid stages for the license type.
     * @return array An array containing the results of the bulk renewal update.
     * @throws Exception If validation fails or if the renewal status is invalid.
     */
    public function updateBulkRenewals(array $renewalsData, ?string $status = null, bool $requireStageValidationPermission = true): array
    {
        $results = [];
        $failed = 0;
        $success = 0;
        foreach ($renewalsData as $renewal) {
            $renewal = (array) $renewal;
            try {

                $renewalUuid = $renewal['uuid'];

                $model = new LicenseRenewalModel();
                $existingRenewal = $model->builder()->where('uuid', $renewalUuid)->get()->getFirstRow('array');


                if (!$existingRenewal) {
                    $results[] = [
                        'id' => $renewalUuid,
                        'successful' => false,
                        'message' => 'Renewal not found'
                    ];
                    continue;
                }
                $licenseType = $existingRenewal['license_type'];
                //get the details of the renewal
                $builder = $model->builder();
                $builder->where($model->table . '.uuid', $renewalUuid);
                $builder = $model->addLicenseDetails($builder, $licenseType, true, true, '', '', true);
                $renewalDetails = $builder->get()->getFirstRow('array');

                /**
                 * @var RenewalStageType
                 */
                $renewalStage = null;


                // Validate if status is provided
                if (!empty($status)) {
                    //get the valid stages for the license type
                    $validStages = LicenseUtils::getLicenseRenewalStagesValues($licenseType);

                    if (count($validStages) == 0) {
                        throw new ConfigException("No valid stages found for license type $licenseType");
                    }
                    $validStageNames = array_map(function ($stage) {
                        return $stage->label;
                    }, $validStages);

                    if (!in_array($status, $validStageNames)) {
                        throw new Exception("Invalid renewal status: $status");
                    }


                    $renewalStage = $validStages[array_search($status, $validStageNames)];
                    $this->validateRenewalStageActivation($renewal, $renewalStage, $licenseType, $requireStageValidationPermission);
                    $renewal['status'] = $status;

                }
                unset($renewal['uuid']);
                $model = new LicenseRenewalModel($licenseType);
                // Use database transaction
                $model->db->transException(true)->transStart();

                LicenseUtils::updateRenewal($renewalUuid, $renewal);

                $model->db->transComplete();
                $success++;
                $results[] = [
                    'id' => $renewalUuid,
                    'successful' => true,
                    'message' => 'Renewal updated successfully'
                ];
                if ($renewalStage) {
                    $this->runRenewalActions($renewalDetails, $renewalStage);
                }



            } catch (\Throwable $e) {
                log_message('error', 'Bulk renewal update failed: ' . $e);
                $failed++;
                $results[] = [
                    'id' => $renewal['uuid'] ?? 'unknown',
                    'successful' => false,
                    'message' => $e->getMessage()
                ];
            }
        }

        return [
            'success' => true,
            'message' => "Bulk renewal update complete. Success: $success, Failed: $failed",
            'data' => $results
        ];
    }

    /**
     * Delete a license renewal.
     *
     * @param string $uuid The uuid of the renewal
     * @param string $userUuid The uuid of the user performing the deletion, used for permission checks
     * @return array A response with a success message
     * @throws \RuntimeException If the renewal does not exist or the delete fails
     */
    public function deleteRenewal(string $uuid, ?string $userUuid = null): array
    {
        $model = new LicenseRenewalModel();
        $data = $model->where(["uuid" => $uuid])->first();

        if (!$data) {
            throw new \RuntimeException("License renewal not found");
        }
        //if a userUuid is provided, make sure the renewal record matches that user's uuid
        if ($userUuid) {
            if ($data['license_uuid'] != $userUuid) {
                log_message('error', "User with uuid $userUuid does not have permission to delete renewal with uuid $uuid");
                throw new \RuntimeException("You do not have permission to delete this renewal");
            }
            //make sure the status of the renewal is deletable
            if (!LicenseUtils::isRenewalStageDeletable($data['license_type'], $data['status'])) {
                log_message('error', "Renewal with uuid $uuid cannot be deleted by user with uuid $userUuid because its status is {$data['status']}");
                throw new \RuntimeException("This renewal cannot be deleted");
            }
        }
        if (!$model->where('uuid', $uuid)->delete()) {
            throw new \RuntimeException('Failed to delete renewal: ' . json_encode($model->errors()));
        }

        // Log activity
        $this->activitiesModel->logActivity("Deleted renewal for license number {$data['license_number']}.");

        return [
            'success' => true,
            'message' => 'License renewal deleted successfully'
        ];
    }

    public function runRenewalStageActions()
    {
    }

    /**
     * Get renewal details
     */
    public function getRenewalDetails(string $uuid): ?array
    {
        $model = new LicenseRenewalModel();
        $finalData = Utils::getLicenseRenewalDetails($uuid);

        return [
            'data' => $finalData,
            'displayColumns' => $model->getDisplayColumns()
        ];
    }


    /**
     * Retrieves a list of license renewals
     * @param string|null $license_uuid the uuid of the license to get the renewals for
     * @param array $filters an array of filters. The following filters are supported:
     * - limit: the number of records to return
     * - page: the page of records to return
     * - param: the search parameter
     * - sortBy: the field to sort by
     * - sortOrder: the order to sort in
     * - isGazette: whether to return results in gazette mode
     * - license_type: the license type
     * - child_param: a search parameter for the license child table
     * @return array{data: object[], total: int, displayColumns: array, columnLabels: array} an array containing the following:
     * - data: the list of renewals
     * - total: the total number of records
     * - displayColumns: an array of column names to display
     * - columnLabels: an array of column labels
     * - columnFilters: an array of column filters
     */
    public function getRenewals(?string $license_uuid = null, array $filters = []): array
    {
        try {
            $per_page = $filters['limit'] ?? 100;
            $page = $filters['page'] ?? 0;
            $param = $filters['param'] ?? $filters['child_param'] ?? $filters['renewal_param'] ?? null;
            $sortBy = $filters['sortBy'] ?? "id";
            $sortOrder = $filters['sortOrder'] ?? "desc";
            $isGazette = $filters['isGazette'] ?? null;
            $licenseType = $filters['license_type'] ?? null;

            //if the lisense_uuid was provided, we use it to determine the license type
            if ($license_uuid !== null && $licenseType === null) {
                $licenseModel = new LicensesModel();
                $licenseData = $licenseModel->builder()->select("type")->where("uuid", $license_uuid)->get()->getRow();
                if ($licenseData) {
                    $licenseType = $licenseData->type;
                }
            }

            if (empty($licenseType)) {
                throw new \InvalidArgumentException("License type is required");
            }

            $model = new LicenseRenewalModel($licenseType);
            $renewalTable = $model->getTableName();

            $licenseSettings = Utils::getLicenseSetting($licenseType);
            $renewalSubTable = $licenseSettings->renewalTable;
            $renewalSubTableJsonFields = $licenseSettings->renewalJsonFields;

            // Configure search fields
            $searchFields = $licenseSettings->renewalSearchFields;
            $searchFields['table'] = $renewalSubTable;
            $model->joinSearchFields = $searchFields;

            $builder = $param ? $model->search($param) : $model->builder();
            //remove the param from the filters
            unset($filters['param']);
            unset($filters['child_param']);
            unset($filters['renewal_param']);

            $addSelectClause = true;

            // Handle gazette mode
            if ($isGazette) {
                $builder = $this->configureGazetteQuery($builder, $licenseSettings, $renewalTable, $renewalSubTable);
                $addSelectClause = false;
            }

            // Apply filters
            $builder = $this->applyRenewalFilters($builder, $filters, $renewalTable, $license_uuid, $licenseType);

            // Apply child parameters
            $builder = $this->applyRenewalChildParameters($builder, $filters, $licenseType);

            // Add license details
            $addJoin = !$param; // If param exists, join might already be added
            $builder = $model->addLicenseDetails($builder, $licenseType, true, $addJoin, '', '', $addSelectClause);

            // Handle JSON fields
            if (!empty($renewalSubTableJsonFields) && !empty($renewalSubTable)) {
                foreach ($renewalSubTableJsonFields as $jsonField) {
                    $builder->select("JSON_UNQUOTE($renewalSubTable.$jsonField) as $jsonField");
                }
            }

            $builder->orderBy($model->getTableName() . ".$sortBy", $sortOrder);
            // Get total count
            $total = $builder->countAllResults(false);
            $builder->limit($per_page, $page);

            $result = $builder->get()->getResult();

            // Process results
            $data = $this->processRenewalResults($result, $renewalSubTableJsonFields);

            return [
                'data' => $data,
                'total' => $total,
                'displayColumns' => $model->getDisplayColumns(),
                'columnLabels' => $model->getDisplayColumnLabels(),
                'columnFilters' => $model->getDisplayColumnFilters()
            ];
        } catch (\CodeIgniter\Database\Exceptions\DatabaseException $th) {
            log_message('error', "Database error: " . $th);
            throw new \RuntimeException("Database error: " . $th->getMessage());
        } catch (\Throwable $th) {
            throw $th;
        }

    }

    public function getRenewalFilters(UsersModel $userModel, $licenseType): array
    {
        $model = new LicenseRenewalModel($licenseType);
        return $model->getDisplayColumnFilters();
    }

    /**
     * Count renewals with filters
     */
    public function countRenewals(array $filters = []): int
    {
        $param = $filters['param'] ?? $filters['child_param'] ?? $filters['renewal_param'] ?? null;
        $licenseType = $filters['license_type'] ?? null;

        $model = new LicenseRenewalModel();
        $renewalTable = $model->getTableName();
        $builder = $param ? $model->search($param) : $model->builder();
        //remove the param from the filters
        unset($filters['param']);
        unset($filters['child_param']);
        unset($filters['renewal_param']);
        // Apply filters
        $builder = $this->applyRenewalFilters($builder, $filters, $renewalTable, null, $licenseType);

        // Apply child parameters
        $builder = $this->applyRenewalChildParameters($builder, $filters, $licenseType);

        $builder = $model->addLicenseDetails($builder, $licenseType);

        return $builder->countAllResults();
    }

    /**
     * Get license renewal form fields
     */
    public function getLicenseRenewalFormFields(string $licenseType): array
    {
        $licenseModel = new LicenseRenewalModel();
        $licenseDef = Utils::getLicenseSetting($licenseType);
        $modelFields = $licenseModel->getFormFields();
        return array_merge($modelFields, $licenseDef->renewalFields);
    }

    /**
     * Get the form fields for the license renewal form for the given license type, but for the portal.
     * @param string $licenseType the license type
     * @param array $existingDetails the existing details
     * @return array the form fields
     */
    public function getPortalLicenseRenewalFormFields(string $licenseType, array $existingDetails): array
    {
        $licenseDef = Utils::getLicenseSetting($licenseType);
        $fields = $licenseDef->portalRenewalFields;
        //if a field has a value in the format --somefield-- then replace it with the existing value
        foreach ($fields as $field) {
            if (in_array($field->name, $licenseDef->portalRenewalFieldsPrePopulate)) {
                $field->value = $existingDetails[$field->name] ?? $field->value;
            }
        }
        return $licenseDef->portalRenewalFields;
    }

    /**
     * Get printable renewal statuses
     */
    public function getPrintableRenewalStatuses(string $licenseType): array
    {
        return LicenseUtils::getPrintableRenewalStatuses($licenseType);
    }

    /**
     * Get renewal basic statistics
     */
    public function getRenewalBasicStatistics(string $licenseType, array $filters = []): array
    {
        $model = new LicenseRenewalModel();
        $renewalTable = $model->getTableName();
        $renewalSubTable = $model->getChildRenewalTable($licenseType);
        $selectedFields = $filters['fields'] ?? [];

        $fields = $model->getBasicStatisticsFields($licenseType);
        $allFields = array_merge($fields['default'], $fields['custom']);

        // Filter fields based on selection
        $allFields = array_filter($allFields, function ($field) use ($selectedFields) {
            return in_array($field->name, $selectedFields);
        });

        $results = [];
        $renewalChildParams = $this->extractRenewalChildParams($filters);

        foreach ($allFields as $field) {
            $builder = $this->buildRenewalStatisticsQuery(
                $model,
                $field,
                $filters,
                $renewalTable,
                $renewalSubTable,
                $licenseType,
                $renewalChildParams
            );
            $result = $builder->get()->getResult();
            $results[$field->name] = $this->formatStatisticsResult($field, $result);
        }

        return $results;
    }


    /**
     * Check if a practitioner is eligible to be a pharmacy superintendent.
     *
     * This method verifies if the given practitioner, identified by their license number,
     * meets all the criteria required to be eligible as a pharmacy superintendent. It ensures
     * that the practitioner is a registered pharmacist, is on the permanent register, is active,
     * is in good standing with an approved status in the license renewal table, and is not
     * already a superintendent of any facility.
     *
     * @param string $practitionerLicenseNumber The license number of the practitioner.
     * @return bool True if the practitioner is eligible to be a pharmacy superintendent, otherwise false.
     * @throws \Exception If any of the eligibility criteria are not met.
     */

    public function isEligiblePharmacySuperintendent(string $practitionerLicenseNumber): array
    {
        try {
            //for a superintendent, these properties must be met:
            // [type]=practitioner
            // [register_type]=permanent
            // [status]=active
            // must be in good standing (a row in the license_renewal table not expired and status Approved)
            // must not have a row in the facility_renewal table which is not expired
            try {
                $practitionerDetails = LicenseUtils::getLicenseDetails($practitionerLicenseNumber, null, 'practitioners');
            } catch (Exception $e) {
                throw new \InvalidArgumentException("Practitioner not found");
            }
            if (!array_key_exists('practitioner_type', $practitionerDetails) || strtolower($practitionerDetails['practitioner_type']) !== 'pharmacist') {
                throw new \InvalidArgumentException("Practitioner is not on a registered pharmacist");
            }
            if (!array_key_exists('register_type', $practitionerDetails) || strtolower($practitionerDetails['register_type']) !== 'permanent') {
                throw new \InvalidArgumentException("Practitioner is not on the permanent register");
            }
            if (!array_key_exists('status', $practitionerDetails) || strtolower($practitionerDetails['status']) !== 'active') {
                throw new \InvalidArgumentException("Practitioner is not active");
            }
            $licenseType = $practitionerDetails['type'];
            $licenseNumber = $practitionerDetails['license_number'];
            $today = date('Y-m-d');
            if (!LicenseUtils::licenseIsInGoodStanding($licenseNumber, $today)) {
                throw new \InvalidArgumentException("Practitioner is not in good standing");
            }
            if ($this->isFacilitySuperintendent($licenseNumber, $licenseType)) {
                throw new \InvalidArgumentException("Practitioner is already a facility superintendent");
            }
            return $practitionerDetails;


        } catch (\Throwable $th) {
            throw $th;
        }
    }

    /**
     * Gets the portal renewal model for the given user.
     *
     * This will return whether the user is eligible for renewal and what actions they can take.
     *
     * The response will contain the following properties:
     * - actions: an array of actions that the user can perform. This can be "fill_form" if the user is eligible to apply for renewal, or an empty array if not.
     * - data: the form data that the user needs to fill out if they are eligible for renewal. This will be empty if the user is not eligible.
     * - message: a message that will be displayed to the user explaining what they can do next.
     *
     * @param string $userId the id of the user
     * @return PractitionerPortalRenewalViewModelType the response containing the actions, data and message for the user
     */
    public function getPractitionerPortalRenewal(string $userId)
    {

        $userData = AuthHelper::getAuthUser($userId);
        //for some institutions practitioners have to apply to be in good standing while they're still in good standing. others require that they apply after it's expired

        $licenseType = $userData->profile_data['type'];
        $licenseNumber = $userData->profile_data['license_number'];

        if (empty($licenseType)) {
            log_message('error', "License type not found for user: $userId");
            throw new \InvalidArgumentException("License type not found");
        }
        /**
         * @var PractitionerPortalRenewalViewModelType
         */
        $response = new PractitionerPortalRenewalViewModelType("", null, '', []);


        $renewalModel = new LicenseRenewalModel($licenseType);
        $lastRenewal = $renewalModel->where('license_number', $licenseNumber)->orderBy('id', 'desc')->first();
        /** @var string */
        $isInGoodStanding = NOT_IN_GOOD_STANDING;//this will be 'In Good Standing' if the last renewal was approved and in the validity period, 'Not In Good Standing' if expired or not available, or some other status if the renewal is in progress 
        $lastRenewalId = null;
        if (!$lastRenewal) {
            $isInGoodStanding = NOT_IN_GOOD_STANDING;
        } else {
            $lastRenewalId = $lastRenewal['uuid'];
            $startDate = new DateTime($lastRenewal['start_date']);
            $expiry = $lastRenewal['expiry']
                ? new DateTime($lastRenewal['expiry'])
                : (new DateTime($startDate->format('Y') . '-12-31'));

            $today = new DateTime();
            $inDateRange = $today >= $startDate && $today <= $expiry;
            if (!$inDateRange) {
                $isInGoodStanding = NOT_IN_GOOD_STANDING;
            } else {
                $isInGoodStanding = $lastRenewal['status'] === APPROVED
                    ? IN_GOOD_STANDING
                    : $lastRenewal['status'];
            }

        }
        //get the following settings: revalidationPeriod, cpd_category_1_cutoff, cpd_category_2_cutoff, cpd_category_3_cutoff, cpd_cutoff, revalidation_period
        $licenseDef = Utils::getLicenseSetting($licenseType);
        $permitRenewal = false;//TODO: check if  the user has been allowed to bypass eligibility criteria
        //get the year for the renewal
        $eligibilityCriteria = new LicenseRenewalEligibilityCriteriaType(
            $licenseDef->mustBeInGoodStandingToRenew,
            '',
            $licenseDef->renewalCpdTotalCutoff,
            $licenseDef->renewalCpdCategory1Cutoff,
            $licenseDef->renewalCpdCategory2Cutoff,
            $licenseDef->renewalCpdCategory3Cutoff,
            $userData->profile_data['register_type'],
            $licenseDef->revalidationPeriodInYears,
            $licenseDef->revalidationMessage,
            '',
            $permitRenewal
        );
        /** @var string */
        $isInGoodStanding = $userData->profile_data['in_good_standing'];//this will be 'In Good Standing' if the last renewal was approved and in the validity period, 'Not In Good Standing' if expired or not available, or some other status if the renewal is in progress 

        $onlineApplicationsOpen = LicenseUtils::portalRenewalApplicationOpen($userData->profile_data['type'], $userData->profile_data);
        log_message('info', 'onlineApplicationsOpen: ' . json_encode($onlineApplicationsOpen));
        //check if the person is eligible for renewal. the cpd year is the year after the last_renewal_start if it's available and is not more than a year ago
        $cpdYear = date("Y", strtotime("-1 year"));

        $isEligibleForRenewal = LicenseUtils::isEligibleForRenewal($licenseNumber, $eligibilityCriteria, $cpdYear);
        $eligible = $isEligibleForRenewal->isEligible;
        //and within the validity period
        if ($isInGoodStanding === IN_GOOD_STANDING || $isInGoodStanding === NOT_IN_GOOD_STANDING) {
            if ($eligible) {
                //check if online applications are open
                if ($onlineApplicationsOpen->result) {
                    $response = new PractitionerPortalRenewalViewModelType(
                        "fill_form",
                        null,
                        "Please fill the application form to apply for renewal",
                        $this->getPortalLicenseRenewalFormFields($userData->profile_data['type'], $userData->profile_data),
                        false,
                        $lastRenewalId
                    );

                } else {
                    $response = new PractitionerPortalRenewalViewModelType("", null, $onlineApplicationsOpen->message, [], false, $lastRenewalId);

                }
            } else {
                $response = new PractitionerPortalRenewalViewModelType("", null, "You are not eligible for renewal - " . $isEligibleForRenewal->reason, [], false, $lastRenewalId);

            }

        } else {
            //there's a renewal in progress. get possible actions from the licenseDef and guide the user as to what to do next
            $actions = LicenseUtils::getRenewalStageActions($licenseDef, $isInGoodStanding);
            $withdrawable = LicenseUtils::isRenewalStageDeletable($licenseType, $isInGoodStanding);
            $response = new PractitionerPortalRenewalViewModelType($actions, null, "Your application for renewal is in progress. Status: $isInGoodStanding", [], $withdrawable, $lastRenewalId);
        }


        return $response;
    }

    // Private helper methods

    /**
     * Determines if the practitioner is currently a superintendent for a facility.
     *
     * This function checks the facility_renewal table for a non-expired entry where
     * the practitioner is listed as the practitioner in charge. If the license type
     * is not provided, it retrieves the license type based on the practitioner's license
     * number. Returns true if such a record is found, indicating the practitioner is a
     * facility superintendent, otherwise false.
     *
     * @param string $practitionerLicenseNumber The practitioner's license number.
     * @param string|null $licenseType Optional. The type of license. If not provided,
     *                                 it will be determined based on the practitioner's
     *                                 details.
     * @return bool True if the practitioner is a facility superintendent, false otherwise.
     * @throws \Throwable If an error occurs while retrieving the practitioner's details.
     */

    private function isFacilitySuperintendent(string $practitionerLicenseNumber, $licenseType = null): bool
    {
        if ($licenseType == null) {
            try {
                $practitionerDetails = LicenseUtils::getLicenseDetails($practitionerLicenseNumber, null, 'practitioners');
                $licenseType = $practitionerDetails['license_type'];
            } catch (\Throwable $th) {
                throw $th;
            }

        }
        //look in the facility_renewal table for a row which is not expired and has practitioner_in_charge = practitionerLicenseNumber
        $renewalModel = new LicenseRenewalModel($licenseType);
        $builder = $renewalModel->builder();
        $subTable = "facility_renewal";
        $today = date('Y-m-d');
        $builder->where('start_date <=', $today);
        $builder->where('expiry >=', $today);
        $builder->where($subTable . '.practitioner_in_charge', $practitionerLicenseNumber);
        $builder->join($subTable, $subTable . '.renewal_id = ' . $renewalModel->getTableName() . '.id');
        $result = $builder->get()->getResult();
        return count($result) > 0;

    }

    private function configureGazetteQuery(BaseBuilder $builder, object $licenseSettings, string $renewalTable, string $renewalSubTable): BaseBuilder
    {
        $gazetteColumns = $licenseSettings->gazetteTableColumns;
        $builder->select(["data_snapshot", $renewalTable . ".license_number"]);

        $renewalSubFields = $licenseSettings->renewalFields;
        $renewalSubTableJsonFields = $licenseSettings->renewalJsonFields;

        foreach ($renewalSubFields as $renewalField) {
            if (!in_array($renewalField['name'], $renewalSubTableJsonFields)) {
                $builder->select("$renewalSubTable." . $renewalField['name']);
            }
        }

        return $builder;
    }

    private function applyRenewalFilters(BaseBuilder $builder, array $filters, string $renewalTable, ?string $license_uuid, ?string $licenseType): BaseBuilder
    {
        // Apply license UUID filter
        if ($license_uuid !== null) {
            $builder->where("$renewalTable.license_uuid", $license_uuid);

            // Get license type if not provided
            if ($licenseType === null) {
                $licenseModel = new LicensesModel();
                $licenseData = $licenseModel->builder()->select("type")->where("uuid", $license_uuid)->get()->getRow();
                if ($licenseData) {
                    $licenseType = $licenseData->type;
                }
            }
        }

        // Apply other filters
        $filterMappings = [
            'license_number' => "$renewalTable.license_number",
            'status' => "$renewalTable.status",
            'license_type' => "$renewalTable.license_type",
            'in_print_queue' => "$renewalTable.in_print_queue",
            'name' => "$renewalTable.name",
            'region' => "$renewalTable.region",
            'district' => "$renewalTable.district",
            'email' => "$renewalTable.email",
            'phone' => "$renewalTable.phone",
            'country_of_practice' => "$renewalTable.country_of_practice",
            'batch_number' => "$renewalTable.batch_number"
        ];

        foreach ($filterMappings as $filterKey => $column) {
            if (isset($filters[$filterKey]) && $filters[$filterKey] !== null) {
                $value = Utils::parseParam($filters[$filterKey]);
                $builder = Utils::parseWhereClause($builder, $column, $value);
            }
        }

        // Apply date range filters
        $dateFilters = [
            'start_date' => "$renewalTable.start_date",
            'expiry' => "$renewalTable.expiry",
            'created_on' => "$renewalTable.created_on"
        ];

        foreach ($dateFilters as $filterKey => $column) {
            if (isset($filters[$filterKey]) && $filters[$filterKey] !== null) {
                $dateRange = Utils::getDateRange($filters[$filterKey]);
                $builder->where("$column >=", $dateRange['start']);
                $builder->where("$column <=", $dateRange['end']);
            }
        }

        // Apply license type filter
        if ($licenseType !== null) {
            $builder->where("$renewalTable.license_type", $licenseType);
        }

        return $builder;
    }

    private function applyRenewalChildParameters(BaseBuilder $builder, array $filters, string $licenseType): BaseBuilder
    {
        $childParams = $this->extractChildParams($filters);
        $renewalChildParams = $this->extractRenewalChildParams($filters);

        $model = new LicenseRenewalModel();
        $renewalTable = $model->getTableName();
        $mainRenewalFilters = $model->mainRenewalFilters();

        // Apply license child parameters
        if (!empty($childParams)) {
            $licenseDef = Utils::getLicenseSetting($licenseType);
            $licenseTypeTable = $licenseDef->table;

            foreach ($childParams as $key => $value) {
                $fieldName = str_replace('child_', '', $key);
                if ($key === "param") {
                    continue;
                }

                //if the field has a value in the main renewal filters, use that value
                if (in_array($fieldName, $mainRenewalFilters)) {
                    $columnName = $renewalTable . "." . $fieldName;
                } else {
                    //THIS IS NOT BEING USED ANYMORE AS ALL DETAILS OF THE LICENSE ARE NOW STORED IN THE LICENSE_RENEWAL TABLE
                    $columnName = $licenseTypeTable . "." . $fieldName;
                }

                $value = Utils::parseParam($value);

                $builder = Utils::parseWhereClause($builder, $columnName, $value);
            }
        }

        // Apply renewal child parameters
        if (!empty($renewalChildParams)) {
            $licenseDef = Utils::getLicenseSetting($licenseType);
            $renewalSubTable = $licenseDef->renewalTable;

            foreach ($renewalChildParams as $key => $value) {
                $fieldName = str_replace('renewal_', '', $key);
                $value = Utils::parseParam($value);
                if ($key === "param") {
                    continue;
                }
                //if the field has a value in the main renewal filters, use that value
                if (in_array($fieldName, $mainRenewalFilters)) {
                    $columnName = $renewalTable . "." . $fieldName;
                } else {
                    $columnName = $renewalSubTable . "." . $fieldName;
                }
                $builder = Utils::parseWhereClause($builder, $columnName, $value);
            }
        }

        return $builder;
    }

    private function extractChildParams(array $filters): array
    {
        return array_filter($filters, function ($key) {
            return strpos($key, 'child_') === 0;
        }, ARRAY_FILTER_USE_KEY);
    }

    private function extractRenewalChildParams(array $filters): array
    {
        return array_filter($filters, function ($key) {
            return strpos($key, 'renewal_') === 0;
        }, ARRAY_FILTER_USE_KEY);
    }

    private function processRenewalResults(array $result, array $renewalSubTableJsonFields): array
    {
        return array_map(function ($item) use ($renewalSubTableJsonFields) {
            // Process data_snapshot
            if (property_exists($item, 'data_snapshot')) {
                $item->data_snapshot = empty($item->data_snapshot) ? [] : json_decode($item->data_snapshot, true);
            }

            // Process JSON fields
            foreach ($renewalSubTableJsonFields as $jsonField) {
                if (property_exists($item, $jsonField)) {
                    $item->$jsonField = empty($item->$jsonField) ? [] : json_decode($item->$jsonField, true);
                }
            }

            // Merge data_snapshot with item data
            if (property_exists($item, 'data_snapshot')) {
                //remove these fields from data_snapshot as they apply to the license object and may conflict with the renewal object
                $fieldsToRemove = ['id', 'uuid', 'created_on', 'modified_on', 'deleted_at', 'status'];
                foreach ($fieldsToRemove as $field) {
                    if (array_key_exists($field, $item->data_snapshot)) {
                        unset($item->data_snapshot[$field]);
                    }
                }
                $item = (object) array_merge((array) $item, $item->data_snapshot);
                unset($item->data_snapshot);
            }

            // Format boolean fields
            if (property_exists($item, 'in_print_queue')) {
                $item->in_print_queue = $item->in_print_queue == 1 ? "Yes" : "No";
            }

            return $item;
        }, $result);
    }

    private function buildRenewalStatisticsQuery(
        LicenseRenewalModel $model,
        object $field,
        array $filters,
        string $renewalTable,
        string $renewalSubTable,
        string $licenseType,
        array $renewalChildParams
    ): BaseBuilder {
        $builder = $model->builder();

        // Apply filters
        $builder = $this->applyRenewalFilters($builder, $filters, $renewalTable, null, $licenseType);

        $builder->join($renewalSubTable, "$renewalSubTable.renewal_id = license_renewal.id");
        $builder->select([$field->name, "COUNT(*) as count"]);
        $builder->where("license_type", $licenseType);

        // Handle field aliases
        $fieldName = $field->name;
        if (strpos($fieldName, " as ") !== false) {
            $fieldName = explode(" as ", $fieldName)[1];
        }

        // Apply renewal child parameters
        foreach ($renewalChildParams as $key => $value) {
            $value = Utils::parseParam($value);
            $columnName = $renewalSubTable . "." . str_replace('renewal_', '', $key);
            $builder = Utils::parseWhereClause($builder, $columnName, $value);
        }

        $builder->groupBy($fieldName);
        return $builder;
    }

    private function formatStatisticsResult(object $field, array $result): array
    {
        $fieldName = $field->name;
        if (strpos($fieldName, " as ") !== false) {
            $fieldName = explode(" as ", $fieldName)[1];
        }

        // Replace null values with 'Null'
        $result = array_map(function ($item) use ($fieldName) {
            $item->$fieldName = empty($item->$fieldName) ? 'Null' : $item->$fieldName;
            return $item;
        }, $result);

        return [
            "label" => $field->label,
            "type" => $field->type,
            "data" => $result,
            "labelProperty" => $fieldName,
            "valueProperty" => "count",
            "name" => $fieldName,
            "xAxisLabel" => $field->xAxisLabel,
            "yAxisLabel" => $field->yAxisLabel,
        ];
    }

    public function getRenewalOnlinePrintTemplateForLicense(string $renewalUuid, string $licenseUuid)
    {
        //make sure the uuid belongs to the user
        $renewal = Utils::getLicenseRenewalDetails($renewalUuid);
        if (empty($renewal)) {
            throw new \InvalidArgumentException("Renewal not found");
        }
        if ($renewal['license_uuid'] != $licenseUuid) {
            throw new \InvalidArgumentException("You do not have permission to view this renewal");
        }
        $templateModel = new PrintTemplateModel();
        $template = $templateModel->where('template_name', $renewal['online_print_template'])->first();
        if (empty($template)) {
            throw new \InvalidArgumentException("Renewal online print template not found");
        }

        $templateEngine = new TemplateEngineHelper();
        return $templateEngine->process($template['template_content'], $renewal);
    }
}