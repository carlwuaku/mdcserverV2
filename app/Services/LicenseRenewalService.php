<?php

namespace App\Services;

use App\Helpers\LicenseUtils;
use App\Helpers\Utils;
use App\Models\ActivitiesModel;
use App\Models\Licenses\LicenseRenewalModel;
use App\Models\Licenses\LicensesModel;
use CodeIgniter\Database\BaseBuilder;
use Exception;

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
     * Create a new license renewal
     */
    public function createRenewal(array $data): array
    {
        $rules = [
            "license_number" => "required",
            "license_uuid" => "required",
            "license_type" => "required"
        ];

        // Validate data
        $validation = \Config\Services::validation();
        if (!$validation->setRules($rules)->run($data)) {
            throw new \InvalidArgumentException('Validation failed: ' . json_encode($validation->getErrors()));
        }

        $license_uuid = $data['license_uuid'];
        $licenseType = $data['license_type'];

        $model = new LicenseRenewalModel($licenseType);

        // Use database transaction


        try {
            $model->db->transException(true)->transStart();
            LicenseUtils::retainLicense($license_uuid, $data);
            $model->db->transComplete();

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
     * Update a license renewal
     */
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
     * Update multiple license renewals.
     *
     * @param object[] $renewalsData An array of renewals data
     * @param string|null $status The new status for the renewals. If provided, it will be
     *          validated against the valid stages for the license type.
     * @return array An array containing the results of the bulk renewal update.
     * @throws Exception If validation fails or if the renewal status is invalid.
     */
    public function updateBulkRenewals(array $renewalsData, ?string $status = null): array
    {
        $results = [];
        $failed = 0;
        $success = 0;
        foreach ($renewalsData as $renewal) {
            try {
                $renewal = (array) $renewal;
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
                unset($renewal['uuid']);

                // Validate if status is provided
                if (!empty($status)) {
                    //get the valid stages for the license type
                    $validStages = LicenseUtils::getLicenseRenewalStages($licenseType);
                    if (!in_array($status, $validStages)) {
                        throw new Exception("Invalid renewal status: $status");
                    }
                    $rules = Utils::getLicenseRenewalStageValidation($licenseType, $status);
                    $validation = \Config\Services::validation();
                    if (count($rules) > 0 && !$validation->setRules($rules)->run($renewal)) {
                        log_message('error', json_encode($validation->getErrors()));
                        throw new Exception("Validation failed for renewal $renewalUuid");
                    }
                    $renewal['status'] = $status;
                }

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
     * Delete a license renewal
     */
    public function deleteRenewal(string $uuid): array
    {
        $model = new LicenseRenewalModel();
        $data = $model->where(["uuid" => $uuid])->first();

        if (!$data) {
            throw new \RuntimeException("License renewal not found");
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
     * Get renewals with filtering and pagination
     */
    public function getRenewals(?string $license_uuid = null, array $filters = []): array
    {
        $per_page = $filters['limit'] ?? 100;
        $page = $filters['page'] ?? 0;
        $param = $filters['param'] ?? $filters['child_param'] ?? null;
        $sortBy = $filters['sortBy'] ?? "id";
        $sortOrder = $filters['sortOrder'] ?? "asc";
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
    }

    /**
     * Count renewals with filters
     */
    public function countRenewals(array $filters = []): int
    {
        $param = $filters['param'] ?? $filters['child_param'] ?? null;
        $licenseType = $filters['license_type'] ?? null;

        $model = new LicenseRenewalModel();
        $renewalTable = $model->getTableName();
        $builder = $param ? $model->search($param) : $model->builder();

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
        $renewalStages = (array) $licenseDef->renewalStages;

        // Create status field with renewal stages
        $status = [
            "label" => "Status",
            "name" => "status",
            "type" => "select",
            "hint" => "",
            "options" => [],
            "value" => "",
            "required" => true
        ];

        foreach (array_keys($renewalStages) as $key) {
            $status["options"][] = [
                "key" => $key,
                "value" => $key
            ];
        }

        $modelFields = $licenseModel->getFormFields();
        $modelFields[] = $status;

        return array_merge($modelFields, $licenseDef->renewalFields);
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

        // Apply license child parameters
        if (!empty($childParams)) {
            $licenseDef = Utils::getLicenseSetting($licenseType);
            $licenseTypeTable = $licenseDef->table;

            foreach ($childParams as $key => $value) {
                if ($key === "child_param") {
                    continue;
                }

                $value = Utils::parseParam($value);
                $columnName = $licenseTypeTable . "." . str_replace('child_', '', $key);
                $builder = Utils::parseWhereClause($builder, $columnName, $value);
            }
        }

        // Apply renewal child parameters
        if (!empty($renewalChildParams)) {
            $licenseDef = Utils::getLicenseSetting($licenseType);
            $renewalSubTable = $licenseDef->renewalTable;

            foreach ($renewalChildParams as $key => $value) {
                $value = Utils::parseParam($value);
                $columnName = $renewalSubTable . "." . str_replace('renewal_', '', $key);
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
                $item = (object) array_merge($item->data_snapshot, (array) $item);
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
}