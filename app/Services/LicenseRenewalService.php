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
            "start_date" => "required",
            "license_uuid" => "required",
            "status" => "required",
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
        $model->db->transException(true)->transStart();

        try {
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
     * Update multiple renewals at once
     */
    public function updateBulkRenewals(array $renewalsData, ?string $status = null): array
    {
        $results = [];

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
                    $rules = Utils::getLicenseRenewalStageValidation($licenseType, $status);
                    $validation = \Config\Services::validation();

                    if (!$validation->setRules($rules)->run($renewal)) {
                        throw new Exception("Validation failed for renewal $renewalUuid");
                    }
                    $renewal['status'] = $status;
                }

                $model = new LicenseRenewalModel($licenseType);

                // Use database transaction
                $model->db->transException(true)->transStart();

                LicenseUtils::updateRenewal($renewalUuid, $renewal);

                $model->db->transComplete();

                $results[] = [
                    'id' => $renewalUuid,
                    'successful' => true,
                    'message' => 'Renewal updated successfully'
                ];

            } catch (\Throwable $e) {
                log_message('error', 'Bulk renewal update failed: ' . $e->getMessage());
                $results[] = [
                    'id' => $renewal['uuid'] ?? 'unknown',
                    'successful' => false,
                    'message' => $e->getMessage()
                ];
            }
        }

        return [
            'success' => true,
            'message' => 'Bulk renewal update completed',
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
        $builder = $model->builder();
        $builder->where($model->getTableName() . '.uuid', $uuid);
        $data = $model->first();

        if (!$data) {
            return null;
        }

        $model2 = new LicenseRenewalModel();
        $builder2 = $model2->builder();
        $builder2->where($model2->getTableName() . '.uuid', $uuid);
        $builder2 = $model->addLicenseDetails($builder2, $data['license_type']);
        $finalData = $model2->first();

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

        $model = new LicenseRenewalModel();
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
        $builder = $model->addLicenseDetails($builder, $licenseType, $addJoin, $addJoin, '', '', $addSelectClause);

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

    // Private helper methods

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
            'in_print_queue' => "$renewalTable.in_print_queue"
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