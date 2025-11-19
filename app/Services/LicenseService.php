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
 * License Service - Handles all license-related business logic
 */
class LicenseService
{
    private LicenseUtils $licenseUtils;
    private ActivitiesModel $activitiesModel;

    public function __construct()
    {
        $this->licenseUtils = new LicenseUtils();
        $this->activitiesModel = new ActivitiesModel();
    }

    /**
     * Create a new license
     */
    public function createLicense(array $data): array
    {
        $type = $data['type'] ?? null;

        if (!$type) {
            throw new \InvalidArgumentException('License type is required');
        }

        $licenseDef = Utils::getLicenseSetting($type);
        $uniqueKeyField = $licenseDef->uniqueKeyField;
        $data[$uniqueKeyField] = $data[$uniqueKeyField] ?? null;
        if (!$data[$uniqueKeyField]) {
            $data[$uniqueKeyField] = $this->licenseUtils->generateLicenseNumber($type);
        }

        // Get validation rules
        $rules = $this->getLicenseValidationRules($type, 'create');

        // Validate data
        $validation = \Config\Services::validation();
        if (!$validation->setRules($rules)->run($data)) {
            throw new \InvalidArgumentException('Validation failed: ' . json_encode($validation->getErrors()));
        }

        $model = new LicensesModel($type);

        // Use database transaction
        $model->db->transException(true)->transStart();

        try {
            $model->insert($data);
            $model->createOrUpdateLicenseDetails($type, $data);

            $model->db->transComplete();

            // Log activity
            $this->activitiesModel->logActivity("Created license {$data['license_number']}");

            return [
                'success' => true,
                'message' => 'License created successfully',
                'data' => null
            ];

        } catch (\Throwable $e) {
            $model->db->transRollback();
            throw $e;
        }
    }

    /**
     * Update an existing license
     */
    public function updateLicense(string $uuid, array $data): array
    {
        $model = new LicensesModel();
        $oldData = $model->where(["uuid" => $uuid])->orWhere('license_number', $uuid)->first();

        if (!$oldData) {
            throw new \RuntimeException("License not found");
        }

        $type = $oldData['type'];
        $data['uuid'] = $oldData['uuid'];
        $data['license_number'] = $oldData['license_number']; // Preserve license number

        // Get validation rules
        $rules = $this->getLicenseValidationRules($type, 'update', $uuid);

        // Validate data
        $validation = \Config\Services::validation();
        if (!$validation->setRules($rules)->run($data)) {
            throw new \InvalidArgumentException('Validation failed: ' . json_encode($validation->getErrors()));
        }

        // Remove ID if present
        unset($data['id']);

        $changes = implode(", ", Utils::compareObjects($oldData, $data));
        $licenseUpdateData = $model->createArrayFromAllowedFields($data);
        //make sure the district is not an empty string
        $licenseUpdateData['district'] = isset($licenseUpdateData['district']) && $licenseUpdateData['district'] ? $licenseUpdateData['district'] : null;

        $model->db->transException(true)->transStart();

        try {
            $model->builder()->where(['uuid' => $oldData['uuid']])->update($licenseUpdateData);
            $model->createOrUpdateLicenseDetails($type, $data);

            $model->db->transComplete();

            // Log activity
            $this->activitiesModel->logActivity("Updated license {$oldData['license_number']}. Changes: $changes");

            return [
                'success' => true,
                'message' => 'License updated successfully'
            ];

        } catch (\Throwable $e) {
            $model->db->transRollback();
            throw $e;
        }
    }

    /**
     * Delete a license
     */
    public function deleteLicense(string $uuid): array
    {
        $model = new LicensesModel();
        $data = $model->where(["uuid" => $uuid])->first();

        if (!$data) {
            throw new \RuntimeException("License not found");
        }

        if (!$model->where('uuid', $uuid)->delete()) {
            throw new \RuntimeException('Failed to delete license: ' . json_encode($model->errors()));
        }

        // Log activity
        $this->activitiesModel->logActivity("Deleted license {$data['license_number']}.");

        return [
            'success' => true,
            'message' => 'License deleted successfully'
        ];
    }

    /**
     * Restore a deleted license
     */
    public function restoreLicense(string $uuid): array
    {
        $model = new LicensesModel();

        if (!$model->builder()->where(['uuid' => $uuid])->update(['deleted_at' => null])) {
            throw new \RuntimeException('Failed to restore license: ' . json_encode($model->errors()));
        }

        $data = $model->where(["uuid" => $uuid])->first();

        // Log activity
        $this->activitiesModel->logActivity("Restored license {$data['license_number']} from recycle bin");

        return [
            'success' => true,
            'message' => 'License restored successfully'
        ];
    }

    /**
     * Get license details
     */
    public function getLicenseDetails(string $uuid): ?array
    {
        $data = $this->licenseUtils->getLicenseDetails($uuid);

        if (!$data) {
            return null;
        }

        $model = new LicensesModel();
        $model->licenseType = $data['type'];

        return [
            'data' => $data,
            'displayColumns' => $model->getDisplayColumns()
        ];
    }

    /**
     * Get licenses with filtering and pagination
     */
    public function getLicenses(array $filters = []): array
    {
        $per_page = $filters['limit'] ?? 100;
        $page = $filters['page'] ?? 0;
        $withDeleted = ($filters['withDeleted'] ?? '') === "yes";
        $param = $filters['param'] ?? $filters['child_param'] ?? null;
        $sortBy = $filters['sortBy'] ?? "id";
        $sortOrder = $filters['sortOrder'] ?? "asc";
        $licenseType = $filters['licenseType'] ?? null;
        $renewalDate = $filters['renewalDate'] ?? null;

        $model = new LicensesModel();

        if ($licenseType) {
            $model->licenseType = $licenseType;
            $this->configureLicenseTypeModel($model, $licenseType);
        }

        if ($renewalDate) {
            $model->renewalDate = date("Y-m-d", strtotime($renewalDate));
        }

        // Build query
        $builder = $param ? $model->search($param) : $model->builder();
        $tableName = $model->table;
        $filterArray = $model->createArrayFromAllowedFields($filters);

        array_map(function ($value, $key) use ($builder, $tableName) {
            $value = Utils::parseParam($value);
            $columnName = $tableName . "." . $key;
            $builder = Utils::parseWhereClause($builder, $columnName, $value);
        }, $filterArray, array_keys($filterArray));

        // Apply child parameters if license type is specified
        if ($licenseType) {
            $builder = $this->applyChildParameters($builder, $filters, $licenseType);
        }

        $builder = $model->addCustomFields($builder);

        if ($renewalDate) {
            $builder = $model->addLastRenewalField($builder);
        }

        // Apply sorting
        $builder = $this->applySorting($builder, $model, $sortBy, $sortOrder, $licenseType);

        // Apply license type filter
        if ($licenseType) {
            $tableName = $model->getTableName();
            $builder->where("$tableName.type", $licenseType);

            $addJoin = !$param; // If param exists, join might already be added
            $builder = $model->addLicenseDetails($builder, $licenseType, $addJoin);
        }

        if ($withDeleted) {
            $model->withDeleted();
        }
        // Get total count
        $totalBuilder = clone $builder;
        $total = $totalBuilder->countAllResults();
        // Get paginated results
        $builder->limit($per_page)->offset($page);
        $result = $builder->get()->getResult();

        return [
            'data' => $result,
            'total' => $total,
            'displayColumns' => $model->getDisplayColumns(),
            'columnFilters' => $model->getDisplayColumnFilters()
        ];
    }

    /**
     * Count licenses with filters
     */
    public function countLicenses(array $filters = []): int
    {
        $param = $filters['param'] ?? $filters['child_param'] ?? null;
        $licenseType = $filters['licenseType'] ?? null;

        $model = new LicensesModel();
        $filterArray = $model->createArrayFromAllowedFields($filters);
        $builder = $param ? $model->search($param) : $model->builder();

        // Apply child parameters
        if ($licenseType) {
            $builder = $this->applyChildParameters($builder, $filters, $licenseType);
        }

        // Apply other filters
        foreach ($filterArray as $key => $value) {
            if (strpos($key, 'child_') !== 0) {
                $value = Utils::parseParam($value);
                $builder = Utils::parseWhereClause($builder, $key, $value);
            }
        }

        if ($licenseType) {
            $builder = $model->addLicenseDetails($builder, $licenseType, true);
        }

        return $builder->countAllResults();
    }

    /**
     * Get license form fields for a specific type
     */
    public function getLicenseFormFields(string $licenseType): array
    {
        $licenseModel = new LicensesModel();
        $licenseDef = Utils::getLicenseSetting($licenseType);

        return array_merge($licenseModel->getFormFields(), $licenseDef->fields);
    }

    /**
     * Get basic statistics for licenses
     */
    public function getBasicStatistics(?string $licenseType = null, array $filters = []): array
    {
        $model = new LicensesModel();
        $licenseTable = $model->getTableName();
        $selectedFields = $filters['fields'] ?? [];

        $fields = $model->getBasicStatisticsFields($licenseType);
        $allFields = array_merge($fields['default'], $fields['custom']);

        // Filter fields based on selection
        $allFields = array_filter($allFields, function ($field) use ($selectedFields) {
            return in_array($field->name, $selectedFields);
        });

        $parentParams = $model->createArrayFromAllowedFields($filters);
        $results = [];

        if ($licenseType) {
            $licenseDef = Utils::getLicenseSetting($licenseType);
            $licenseTypeTable = $licenseDef->table;
            $childParams = $this->extractChildParams($filters);

            foreach ($allFields as $field) {
                $builder = $this->buildStatisticsQuery($model, $field, $parentParams, $licenseType, $licenseTypeTable, $childParams);
                $result = $builder->get()->getResult();

                $results[$field->name] = $this->formatStatisticsResult($field, $result);
            }
        }

        return $results;
    }

    // Private helper methods

    private function getLicenseValidationRules(string $type, string $operation, ?string $uuid = null): array
    {
        $baseRules = [];
        if ($operation === 'create') {
            //use the unique field of the license type instead of license number
            $licenseDef = Utils::getLicenseSetting($type);
            $uniqueKeyField = $licenseDef->uniqueKeyField;

            $baseRules = [
                "$uniqueKeyField" => $operation === 'create'
                    ? "required|is_unique[licenses.license_number]"
                    : "if_exist|is_unique[licenses.license_number,uuid,$uuid]",
                "registration_date" => "required|valid_date",
                "email" => "required|valid_email",
                "phone" => "required",
                "type" => "required"
            ];

        } else if ($operation === 'update') {
            $baseRules = [
                "uuid" => "required",
                "registration_date" => "if_exist|valid_date",
                "email" => "if_exist|valid_email"
            ];
        }


        try {
            $licenseValidation = $operation === 'create'
                ? Utils::getLicenseOnCreateValidation($type)
                : Utils::getLicenseOnUpdateValidation($type);

            return array_merge($baseRules, $licenseValidation);
        } catch (\Throwable $th) {
            log_message("error", $th);
            return $baseRules;
        }
    }

    private function configureLicenseTypeModel(LicensesModel $model, string $licenseType): void
    {
        $searchFields = Utils::getLicenseSearchFields($licenseType);
        $searchFields['table'] = Utils::getLicenseTable($licenseType);
        $model->joinSearchFields = $searchFields;
    }

    private function applyChildParameters(BaseBuilder $builder, array $filters, string $licenseType): BaseBuilder
    {
        $childParams = $this->extractChildParams($filters);

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

        return $builder;
    }

    private function extractChildParams(array $filters): array
    {
        return array_filter($filters, function ($key) {
            return strpos($key, 'child_') === 0;
        }, ARRAY_FILTER_USE_KEY);
    }

    private function applySorting(BaseBuilder $builder, LicensesModel $model, string $sortBy, string $sortOrder, ?string $licenseType): BaseBuilder
    {
        $tableName = $model->getTableName();

        if ($sortBy === "id" || in_array($sortBy, $model->allowedFields)) {
            $sortField = $tableName . "." . $sortBy;
        } else if ($licenseType) {
            $licenseDef = Utils::getLicenseSetting($licenseType);
            $sortField = $licenseDef->table . "." . $sortBy;
        } else {
            $sortField = $tableName . ".id"; // Default fallback
        }

        return $builder->orderBy($sortField, $sortOrder);
    }

    private function buildStatisticsQuery(LicensesModel $model, object $field, array $parentParams, string $licenseType, string $licenseTypeTable, array $childParams): BaseBuilder
    {
        $builder = $model->builder();
        $licenseTable = $model->getTableName();
        $licenseDef = Utils::getLicenseSetting($licenseType);
        $uniqueKeyField = $licenseDef->uniqueKeyField;

        // Apply parent parameters
        foreach ($parentParams as $key => $value) {
            if (strpos($key, 'child_') !== 0) {
                $value = Utils::parseParam($value);
                $builder = Utils::parseWhereClause($builder, $licenseTable . "." . $key, $value);
            }
        }

        $builder->join($licenseTypeTable, cond: "$licenseTypeTable.$uniqueKeyField = licenses.license_number");
        $builder->select([$field->name, "COUNT(*) as count"]);
        $builder->where("type", $licenseType);

        // Apply child parameters
        foreach ($childParams as $key => $value) {
            $value = Utils::parseParam($value);
            $columnName = $licenseTypeTable . "." . str_replace('child_', '', $key);
            $builder = Utils::parseWhereClause($builder, $columnName, $value);
        }

        // Handle field aliases
        $fieldName = $field->name;
        if (strpos($fieldName, " as ") !== false) {
            $fieldName = explode(" as ", $fieldName)[1];
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