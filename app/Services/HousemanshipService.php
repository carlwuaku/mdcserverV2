<?php
namespace App\Services;

use App\Helpers\LicenseUtils;
use App\Helpers\TemplateEngineHelper;
use App\Helpers\Utils;
use App\Helpers\Enums\HousemanshipSetting;
use App\Helpers\HousemanshipUtils;
use App\Helpers\Types\HousemanshipPostingType;
use App\Models\Housemanship\HousemanshipApplicationDetailsModel;
use App\Models\Housemanship\HousemanshipApplicationModel;
use App\Models\Housemanship\HousemanshipDisciplinesModel;
use App\Models\Housemanship\HousemanshipFacilitiesModel;
use App\Models\Housemanship\HousemanshipFacilityAvailabilityModel;
use App\Models\Housemanship\HousemanshipFacilityCapacitiesModel;
use App\Models\Housemanship\HousemanshipPostingDetailsModel;
use App\Models\Housemanship\HousemanshipPostingsModel;
use App\Models\ActivitiesModel;
use App\Models\PrintTemplateModel;
use Exception;


class HousemanshipService
{
    private $activityModule = "housemanship";

    // Facility Methods
    public function createFacility(array $data): array
    {
        $model = new HousemanshipFacilitiesModel();
        if (!$model->insert($data)) {
            throw new Exception(json_encode($model->errors()));
        }

        $id = $model->getInsertID();
        $this->logActivity("Created housemanship facility {$data['name']}.");

        return ['id' => $id, 'message' => 'Housemanship facility created successfully'];
    }

    public function updateFacility(string $uuid, array $data): array
    {
        $model = new HousemanshipFacilitiesModel();
        $oldData = $model->where(["uuid" => $uuid])->first();

        if (!$oldData) {
            throw new Exception("Housemanship facility not found");
        }

        $changes = implode(", ", Utils::compareObjects($oldData, $data));

        if (!$model->builder()->where(['uuid' => $uuid])->update($data)) {
            throw new Exception(json_encode($model->errors()));
        }

        $this->logActivity("Updated housemanship facility {$oldData['name']}. Changes: $changes", null, "cpd");

        return ['message' => 'Housemanship facility updated successfully'];
    }

    public function deleteFacility(string $uuid): array
    {
        $model = new HousemanshipFacilitiesModel();
        $data = $model->where(["uuid" => $uuid])->first();

        if (!$data) {
            throw new Exception("Housemanship facility not found");
        }

        if (!$model->where('uuid', $uuid)->delete()) {
            throw new Exception(json_encode($model->errors()));
        }

        $this->logActivity("Deleted Housemanship facility {$data['name']}.", null, "cpd");

        return ['message' => 'Housemanship facility deleted successfully'];
    }

    public function getFacility(string $uuid): array
    {
        $model = new HousemanshipFacilitiesModel();
        $data = $model->where(["uuid" => $uuid])->first();

        if (!$data) {
            throw new Exception("Housemanship facility not found");
        }

        return [
            'data' => $data,
            'displayColumns' => $model->getDisplayColumns()
        ];
    }

    public function getFacilities(array $params): array
    {
        $per_page = $params['limit'] ?? 100;
        $page = $params['page'] ?? 0;
        $withDeleted = ($params['withDeleted'] ?? '') === "yes";
        $param = $params['param'] ?? null;
        $sortBy = $params['sortBy'] ?? "name";
        $sortOrder = $params['sortOrder'] ?? "asc";

        $model = new HousemanshipFacilitiesModel();
        $builder = $param ? $model->search($param) : $model->builder();

        $tableName = $model->table;
        $builder->orderBy("$tableName.$sortBy", $sortOrder);

        if ($withDeleted) {
            $model->withDeleted();
        }

        $totalBuilder = clone $builder;
        $total = $totalBuilder->countAllResults();
        $result = $builder->get($per_page, $page)->getResult();

        return [
            'data' => $result,
            'total' => $total,
            'displayColumns' => $model->getDisplayColumns(),
            'columnFilters' => $model->getDisplayColumnFilters()
        ];
    }

    public function countFacilities(array $params): int
    {
        $param = $params['param'] ?? null;
        $model = new HousemanshipFacilitiesModel();
        $filterArray = $model->createArrayFromAllowedFields($params);

        $builder = $param ? $model->search($param) : $model->builder();
        array_map(function ($value, $key) use ($builder) {
            $builder->where($key, $value);
        }, $filterArray, array_keys($filterArray));

        return $builder->countAllResults();
    }

    // Facility Capacity Methods
    public function createFacilityCapacity(array $data): array
    {
        $model = new HousemanshipFacilityCapacitiesModel();

        // Delete existing record and insert a new one
        $model->where("facility_name", $data['facility_name'])
            ->where("year", $data['year'])
            ->where("discipline", $data['discipline'])
            ->delete();

        if (!$model->insert($data)) {
            throw new Exception(json_encode($model->errors()));
        }

        $id = $model->getInsertID();
        $this->logActivity("Created housemanship facility capacity for {$data['facility_name']} {$data['year']} - {$data['discipline']} {$data['capacity']}.");

        return ['id' => $id, 'message' => 'Housemanship facility capacity created successfully'];
    }

    public function updateFacilityCapacity(int $id, array $data): array
    {
        $model = new HousemanshipFacilityCapacitiesModel();
        $oldData = $model->where(["id" => $id])->first();

        if (!$oldData) {
            throw new Exception("Record not found");
        }

        if ($oldData['facility_name'] !== $data['facility_name']) {
            throw new Exception("Record facility does not match");
        }

        $changes = implode(", ", Utils::compareObjects($oldData, $data));

        if (!$model->builder()->where(['id' => $id])->update($data)) {
            throw new Exception(json_encode($model->errors()));
        }

        $this->logActivity("Updated housemanship facility capacity {$oldData['facility_name']}. Changes: $changes");

        return ['message' => 'Housemanship facility capacity updated successfully'];
    }

    public function deleteFacilityCapacity(int $id): array
    {
        $model = new HousemanshipFacilityCapacitiesModel();
        $data = $model->where(["id" => $id])->first();

        if (!$data) {
            throw new Exception("Record not found");
        }

        if (!$model->where('id', $id)->delete()) {
            throw new Exception(json_encode($model->errors()));
        }

        $this->logActivity("Deleted Housemanship facility capacity {$data['facility_name']} {$data['year']} - {$data['discipline']} {$data['capacity']}.");

        return ['message' => 'Housemanship facility capacity deleted successfully'];
    }

    public function getFacilityCapacities(array $params): array
    {
        $per_page = $params['limit'] ?? 100;
        $page = $params['page'] ?? 0;
        $withDeleted = ($params['withDeleted'] ?? '') === "yes";
        $param = $params['param'] ?? null;
        $sortBy = $params['sortBy'] ?? "facility_name, year, discipline";
        $sortOrder = $params['sortOrder'] ?? "desc";

        $model = new HousemanshipFacilityCapacitiesModel();
        $filterArray = $model->createArrayFromAllowedFields($params);

        $builder = $param ? $model->search($param) : $model->builder();
        array_map(function ($value, $key) use ($builder) {
            $builder->where($key, $value);
        }, $filterArray, array_keys($filterArray));

        $tableName = $model->table;
        $builder->orderBy("$tableName.$sortBy", $sortOrder);

        if ($withDeleted) {
            $model->withDeleted();
        }

        $totalBuilder = clone $builder;
        $total = $totalBuilder->countAllResults();
        $result = $builder->get($per_page, $page)->getResult();

        return [
            'data' => $result,
            'total' => $total,
            'displayColumns' => $model->getDisplayColumns(),
            'columnFilters' => $model->getDisplayColumnFilters()
        ];
    }

    // Facility Availability Methods
    public function createFacilityAvailability(array $data): array
    {
        $model = new HousemanshipFacilityAvailabilityModel();

        // Delete existing record and insert a new one
        $model->where("facility_name", $data['facility_name'])
            ->where("year", $data['year'])
            ->where("category", $data['category'])
            ->delete();

        if (!$model->insert($data)) {
            throw new Exception(json_encode($model->errors()));
        }

        $id = $model->getInsertID();
        $this->logActivity("Created housemanship facility availability for {$data['facility_name']} {$data['year']} - {$data['category']} {$data['available']}.");

        return ['id' => $id, 'message' => 'Housemanship facility availability created successfully'];
    }

    public function updateFacilityAvailability(int $id, array $data): array
    {
        $model = new HousemanshipFacilityAvailabilityModel();
        $oldData = $model->where(["id" => $id])->first();

        if (!$oldData) {
            throw new Exception("Record not found");
        }

        if ($oldData['facility_name'] !== $data['facility_name']) {
            throw new Exception("Record facility does not match");
        }

        $changes = implode(", ", Utils::compareObjects($oldData, $data));

        if (!$model->builder()->where(['id' => $id])->update($data)) {
            throw new Exception(json_encode($model->errors()));
        }

        $this->logActivity("Updated housemanship facility availability {$oldData['name']}. Changes: $changes");

        return ['message' => 'Housemanship facility availability updated successfully'];
    }

    public function deleteFacilityAvailability(int $id): array
    {
        $model = new HousemanshipFacilityAvailabilityModel();
        $data = $model->where(["id" => $id])->first();

        if (!$data) {
            throw new Exception("Record not found");
        }

        if (!$model->where('id', $id)->delete()) {
            throw new Exception(json_encode($model->errors()));
        }

        $this->logActivity("Deleted Housemanship facility availability {$data['facility_name']} {$data['year']} - {$data['category']} {$data['availability']}.");

        return ['message' => 'Housemanship facility availability deleted successfully'];
    }

    public function getFacilityAvailabilities(array $params): array
    {
        $per_page = $params['limit'] ?? 100;
        $page = $params['page'] ?? 0;
        $withDeleted = ($params['withDeleted'] ?? '') === "yes";
        $param = $params['param'] ?? null;
        $sortBy = $params['sortBy'] ?? "facility_name, year, category";
        $sortOrder = $params['sortOrder'] ?? "desc";

        $model = new HousemanshipFacilityAvailabilityModel();
        $filterArray = $model->createArrayFromAllowedFields($params);

        $builder = $param ? $model->search($param) : $model->builder();
        array_map(function ($value, $key) use ($builder) {
            $builder->where($key, $value);
        }, $filterArray, array_keys($filterArray));

        $tableName = $model->table;
        $builder->orderBy("$tableName.$sortBy", $sortOrder);

        if ($withDeleted) {
            $model->withDeleted();
        }

        $totalBuilder = clone $builder;
        $total = $totalBuilder->countAllResults();
        $result = $builder->get($per_page, $page)->getResult();

        return [
            'data' => $result,
            'total' => $total,
            'displayColumns' => $model->getDisplayColumns(),
            'columnFilters' => $model->getDisplayColumnFilters()
        ];
    }

    // Discipline Methods
    public function createDiscipline(array $data): array
    {
        $model = new HousemanshipDisciplinesModel();

        if (!$model->insert($data)) {
            throw new Exception(json_encode($model->errors()));
        }

        $id = $model->getInsertID();
        $this->logActivity("Created housemanship discipline");

        return ['id' => $id, 'message' => 'Housemanship discipline created successfully'];
    }

    public function updateDiscipline(int $id, array $data): array
    {
        $model = new HousemanshipDisciplinesModel();
        $oldData = $model->where(["id" => $id])->first();

        if (!$oldData) {
            throw new Exception("Record not found");
        }

        $changes = implode(", ", Utils::compareObjects($oldData, $data));

        if (!$model->builder()->where(['id' => $id])->update($data)) {
            throw new Exception(json_encode($model->errors()));
        }

        $this->logActivity("Updated housemanship discipline {$oldData['name']}. Changes: $changes");

        return ['message' => 'Housemanship discipline updated successfully'];
    }

    public function deleteDiscipline(int $id): array
    {
        $model = new HousemanshipDisciplinesModel();
        $data = $model->where(["id" => $id])->first();

        if (!$data) {
            throw new Exception("Record not found");
        }

        if (!$model->where('id', $id)->delete()) {
            throw new Exception(json_encode($model->errors()));
        }

        $this->logActivity("Deleted Housemanship discipline {$data['name']}.");

        return ['message' => 'Housemanship discipline deleted successfully'];
    }

    public function restoreDiscipline(int $id): array
    {
        $model = new HousemanshipDisciplinesModel();

        if (!$model->builder()->where(['id' => $id])->update(['deleted_at' => null])) {
            throw new Exception(json_encode($model->errors()));
        }

        $data = $model->where(["id" => $id])->first();
        $this->logActivity("Restored license {$data['name']} from recycle bin");

        return ['message' => 'Discipline restored successfully'];
    }

    public function getDisciplines(array $params): array
    {
        $per_page = $params['limit'] ?? 100;
        $page = $params['page'] ?? 0;
        $withDeleted = ($params['withDeleted'] ?? '') === "yes";
        $param = $params['param'] ?? null;
        $sortBy = $params['sortBy'] ?? "name";
        $sortOrder = $params['sortOrder'] ?? "asc";

        $model = new HousemanshipDisciplinesModel();
        $filterArray = $model->createArrayFromAllowedFields($params);

        $builder = $param ? $model->search($param) : $model->builder();
        array_map(function ($value, $key) use ($builder) {
            $builder->where($key, $value);
        }, $filterArray, array_keys($filterArray));

        $tableName = $model->table;
        $builder->orderBy("$tableName.$sortBy", $sortOrder);

        if ($withDeleted) {
            $model->withDeleted();
        }

        $totalBuilder = clone $builder;
        $total = $totalBuilder->countAllResults();
        $result = $builder->get($per_page, $page)->getResult();

        return [
            'data' => $result,
            'total' => $total,
            'displayColumns' => $model->getDisplayColumns(),
            'columnFilters' => $model->getDisplayColumnFilters()
        ];
    }

    // Posting Methods
    public function createPosting(array $data): array
    {
        $license = LicenseUtils::getLicenseDetails($data['license_number']);
        if (!$license) {
            throw new Exception("License not found");
        }

        $data['category'] = $license['category'];
        $data['type'] = $license['practitioner_type'];
        $data['practitioner_details'] = json_encode($license);

        $model = new HousemanshipPostingsModel();
        $model->db->transException(true)->transStart();

        $postingId = $model->insert($data);
        $posting = $model->where(['id' => $postingId])->first();

        if (!$posting) {
            throw new Exception("Failed to create posting");
        }

        $postingUuid = $posting['uuid'];
        $details = $data['details'];

        $this->insertPostingDetails($postingUuid, $details);
        $this->logActivity("Added housemanship session {$data['session']} posting for {$data['license_number']}");

        $model->db->transComplete();

        return ['message' => "Housemanship posting created successfully for {$data['license_number']}", 'data' => ""];
    }

    public function updatePosting(string $uuid, array $data): array
    {
        $license = LicenseUtils::getLicenseDetails($data['license_number']);
        if (!$license) {
            throw new Exception("License not found");
        }

        $data['category'] = $license['category'];
        $data['type'] = $license['practitioner_type'];
        $data['practitioner_details'] = json_encode($license);

        $model = new HousemanshipPostingsModel();
        $model->db->transException(true)->transStart();

        $details = $data['details'];
        unset($data['details']);

        $model->builder()->where(['uuid' => $uuid])->update($data);

        $postingDetailsModel = new HousemanshipPostingDetailsModel();
        $postingDetailsModel->builder()->where(['posting_uuid' => $uuid])->delete();

        $this->insertPostingDetails($uuid, $details);
        $this->logActivity("Updated housemanship session {$data['session']} posting for {$data['license_number']}");

        $model->db->transComplete();

        return ['message' => "Housemanship posting updated successfully for {$data['license_number']}", 'data' => ""];
    }

    public function deletePosting(string $uuid): array
    {
        $model = new HousemanshipPostingsModel();
        $data = $model->where(["uuid" => $uuid])->first();

        if (!$model->where('uuid', $uuid)->delete()) {
            throw new Exception(json_encode($model->errors()));
        }

        $this->logActivity("Deleted housemanship posting for {$data['license_number']}.");

        return ['message' => 'Housemanship posting deleted successfully'];
    }

    public function countPostings(array $params): int
    {
        $param = $params['param'] ?? null;
        $model = new HousemanshipPostingsModel();
        $filterArray = $model->createArrayFromAllowedFields($params);

        $builder = $param ? $model->search($param) : $model->builder();
        array_map(function ($value, $key) use ($builder) {
            $builder->where($key, $value);
        }, $filterArray, array_keys($filterArray));

        return $builder->countAllResults();
    }

    /**
     * Get a single housemanship posting with its details flattened.
     * This will mostly be used for filling the form to edit the posting.
     *
     * @param string $uuid
     * @throws \Exception
     * @return array
     */
    public function getPosting(string $uuid): array
    {
        $model = new HousemanshipPostingsModel();
        $detailsModel = new HousemanshipPostingDetailsModel();
        $data = $model->where(["uuid" => $uuid])->first();

        if (!$data) {
            throw new Exception("Housemanship posting not found");
        }

        $details = $detailsModel->where(["posting_uuid" => $uuid])->findAll();

        for ($i = 0; $i < count($details); $i++) {
            $data["posting_detail-facility_name-$i"] = $details[$i]['facility_name'];
            $data["posting_detail-discipline-$i"] = $details[$i]['discipline'];
            $data["posting_detail-start_date-$i"] = $details[$i]['start_date'];
            $data["posting_detail-end_date-$i"] = $details[$i]['end_date'];
            $data["posting_detail-facility_region-$i"] = $details[$i]['facility_region'];
        }

        return $data;
    }

    public function getPostings(array $params, ?array $userData = null): array
    {
        $per_page = $params['limit'] ?? 100;
        $page = $params['page'] ?? 0;
        $withDeleted = ($params['withDeleted'] ?? '') === "yes";
        $param = $params['param'] ?? null;
        $sortBy = $params['sortBy'] ?? "id";
        $sortOrder = $params['sortOrder'] ?? "asc";
        $facilityName = $params['facility_name'] ?? null;

        $model = new HousemanshipPostingsModel();
        $detailsModel = new HousemanshipPostingDetailsModel();

        $filterArray = $model->createArrayFromAllowedFields($params);

        if ($userData && !$userData['isAdmin']) {
            $filterArray['license_number'] = $userData['license_number'];
        }

        $tableName = $model->table;
        $builder = $param ? $model->search($param)->select("$tableName.*") : $model->builder()->select("$tableName.*");
        $builder = $model->addPractitionerDetailsFields($builder);

        if ($facilityName) {
            $builder->join($detailsModel->table, "{$detailsModel->table}.posting_uuid = " . $tableName . '.uuid');
            $builder->where("{$detailsModel->table}.facility_name", $facilityName);
        }

        array_map(function ($value, $key) use ($builder, $tableName) {
            $builder->where($tableName . "." . $key, $value);
        }, $filterArray, array_keys($filterArray));

        $builder->orderBy("$tableName.$sortBy", $sortOrder);

        if ($withDeleted) {
            $model->withDeleted();
        }

        $totalBuilder = clone $builder;
        $total = $totalBuilder->countAllResults();
        $displayColumns = $model->getDisplayColumns();

        $parentRecords = $builder->get($per_page, $page)->getResult();
        $parentIds = array_map(fn($record) => $record->uuid, $parentRecords);

        $childRecords = [];
        if (!empty($parentIds)) {
            $childRecords = $detailsModel->whereIn('posting_uuid', $parentIds)->findAll();
        }

        $childrenByParentId = [];
        foreach ($childRecords as $child) {
            $childrenByParentId[$child['posting_uuid']][] = $child;
        }

        foreach ($parentRecords as $parent) {
            $children = $childrenByParentId[$parent->uuid] ?? [];
            foreach ($children as $index => $child) {
                $childArray = (array) $child;
                foreach ($childArray as $key => $value) {
                    if (!in_array($key, ['posting_uuid', 'id', 'facility_details'])) {
                        $fieldName = $key . "_" . ($index + 1);
                        if (!in_array($fieldName, $displayColumns)) {
                            $displayColumns[] = $fieldName;
                        }
                        $parent->$fieldName = $value;
                    }
                }
            }
        }

        return [
            'data' => $parentRecords,
            'total' => $total,
            'displayColumns' => $displayColumns,
            'columnFilters' => $model->getDisplayColumnFilters()
        ];
    }

    public function getPostingFormFields(string $session): array
    {
        $model = new HousemanshipPostingsModel();
        $detailsModel = new HousemanshipPostingDetailsModel();

        $sessionSetting = Utils::getHousemanshipSetting(HousemanshipSetting::SESSIONS);
        if (!$sessionSetting) {
            throw new Exception("Session setting not found");
        }

        if (!array_key_exists($session, $sessionSetting)) {
            throw new Exception("Session not found");
        }

        $numberOfRequiredFacilities = (int) $sessionSetting[$session]['number_of_facilities'];
        $mainFields = $model->getFormFields();
        $detailsFields = $detailsModel->getFormFields();

        for ($i = 0; $i < $numberOfRequiredFacilities; $i++) {
            $mainFields[] = [
                "label" => "Discipline " . ($i + 1),
                "name" => "",
                "type" => "label",
                "hint" => "",
                "options" => [],
                "value" => "",
                "required" => false
            ];

            $detail = [];
            foreach ($detailsFields as $detailsField) {
                $detailsField['name'] = "posting_detail-{$detailsField['name']}-$i";
                $detail[] = $detailsField;
            }

            $mainFields[] = $detail;
        }

        return ['data' => $mainFields];
    }

    // Application Methods
    public function createApplication(array $data): array
    {
        $license = LicenseUtils::getLicenseDetails($data['license_number']);
        if (!$license) {
            throw new Exception("License not found");
        }

        $data['category'] = $license['category'];
        $data['type'] = $license['practitioner_type'];

        $model = new HousemanshipApplicationModel();
        $model->db->transException(true)->transStart();

        $applicationId = $model->insert($data);
        $application = $model->where(['id' => $applicationId])->first();

        if (!$application) {
            throw new Exception("Failed to create application");
        }

        $applicationUuid = $application['uuid'];
        $details = $data['details'];

        $this->insertApplicationDetails($applicationUuid, $details);
        $this->logActivity("Added housemanship session {$data['session']} application for {$data['license_number']}");

        $model->db->transComplete();

        return ['message' => "Housemanship application created successfully for {$data['license_number']}", 'data' => ""];
    }

    public function updateApplication(string $uuid, array $data): array
    {
        $license = LicenseUtils::getLicenseDetails($data['license_number']);
        if (!$license) {
            throw new Exception("License not found");
        }

        $data['category'] = $license['category'];
        $data['type'] = $license['practitioner_type'];

        $model = new HousemanshipApplicationModel();
        $model->db->transException(true)->transStart();

        $details = $data['details'];
        unset($data['details']);

        $model->builder()->where(['uuid' => $uuid])->update($data);

        $applicationDetailsModel = new HousemanshipApplicationDetailsModel();
        $applicationDetailsModel->builder()->where(['application_uuid' => $uuid])->delete();

        $this->insertApplicationDetails($uuid, $details);
        $this->logActivity("Updated housemanship session {$data['session']} application for {$data['license_number']}");

        $model->db->transComplete();

        return ['message' => "Housemanship application updated successfully for {$data['license_number']}", 'data' => ""];
    }

    public function deleteApplication(string $uuid): array
    {
        $model = new HousemanshipApplicationModel();
        $data = $model->where(["uuid" => $uuid])->first();

        if (!$model->where('uuid', $uuid)->delete()) {
            throw new Exception(json_encode($model->errors()));
        }

        $this->logActivity("Deleted housemanship posting application for {$data['license_number']}.");

        return ['message' => 'Housemanship posting application deleted successfully'];
    }

    public function countApplications(array $params): int
    {
        $param = $params['param'] ?? null;
        $model = new HousemanshipApplicationModel();
        $filterArray = $model->createArrayFromAllowedFields($params);

        $builder = $param ? $model->search($param) : $model->builder();
        array_map(function ($value, $key) use ($builder) {
            $builder->where($key, $value);
        }, $filterArray, array_keys($filterArray));

        return $builder->countAllResults();
    }

    public function getApplication(string $uuid): array
    {
        $model = new HousemanshipApplicationModel();
        $detailsModel = new HousemanshipApplicationDetailsModel();
        $data = $model->where(["uuid" => $uuid])->first();

        if (!$data) {
            throw new Exception("Housemanship application not found");
        }

        $details = $detailsModel->where(["application_uuid" => $uuid])->findAll();

        for ($i = 0; $i < count($details); $i++) {
            $data["posting_application_detail-first_choice-$i"] = $details[$i]['first_choice'];
            $data["posting_application_detail-discipline-$i"] = $details[$i]['discipline'];
            $data["posting_application_detail-second_choice-$i"] = $details[$i]['second_choice'];
        }

        return ['data' => $data, 'displayColumns' => $model->getDisplayColumns()];
    }

    public function getApplications(array $params, ?array $userData = null): array
    {
        $per_page = $params['limit'] ?? 100;
        $page = $params['page'] ?? 0;
        $withDeleted = ($params['withDeleted'] ?? '') === "yes";
        $param = $params['param'] ?? null;
        $sortBy = $params['sortBy'] ?? "id";
        $sortOrder = $params['sortOrder'] ?? "asc";

        $model = new HousemanshipApplicationModel();
        $detailsModel = new HousemanshipApplicationDetailsModel();

        $filterArray = $model->createArrayFromAllowedFields($params);

        if ($userData && !$userData['isAdmin']) {
            $filterArray['license_number'] = $userData['license_number'];
        }

        $tableName = $model->table;
        $builder = !empty($param) ? $model->search($param)->select("$tableName.*") : $model->builder()->select("$tableName.*");
        $builder = $model->addPractitionerDetailsFields($builder, empty($param));

        array_map(function ($value, $key) use ($builder, $tableName) {
            $builder->where($tableName . "." . $key, $value);
        }, $filterArray, array_keys($filterArray));

        $builder->orderBy("$tableName.$sortBy", $sortOrder);

        if ($withDeleted) {
            $model->withDeleted();
        }

        $totalBuilder = clone $builder;
        $total = $totalBuilder->countAllResults();
        $displayColumns = $model->getDisplayColumns();

        $parentRecords = $builder->get($per_page, $page)->getResult();
        $parentIds = array_map(fn($record) => $record->uuid, $parentRecords);

        $childRecords = [];
        if (!empty($parentIds)) {
            $childRecords = $detailsModel->whereIn('application_uuid', $parentIds)->findAll();
        }

        $childrenByParentId = [];
        foreach ($childRecords as $child) {
            $childrenByParentId[$child['application_uuid']][] = $child;
        }

        foreach ($parentRecords as $parent) {
            $children = $childrenByParentId[$parent->uuid] ?? [];
            foreach ($children as $index => $child) {
                $childArray = (array) $child;
                foreach ($childArray as $key => $value) {
                    if (!in_array($key, ['application_uuid', 'id'])) {
                        $fieldName = $key . "_" . ($index + 1);
                        if (!in_array($fieldName, $displayColumns)) {
                            $displayColumns[] = $fieldName;
                        }
                        $parent->$fieldName = $value;
                    }
                }
            }
        }

        return [
            'data' => $parentRecords,
            'total' => $total,
            'displayColumns' => $displayColumns,
            'columnFilters' => $model->getDisplayColumnFilters()
        ];
    }

    public function getApplicationsCount(array $params, ?array $userData = null): int
    {
        $withDeleted = ($params['withDeleted'] ?? '') === "yes";
        $param = $params['param'] ?? null;

        $model = new HousemanshipApplicationModel();
        $filterArray = $model->createArrayFromAllowedFields($params);

        if ($userData && !$userData['isAdmin']) {
            $filterArray['license_number'] = $userData['license_number'];
        }

        $tableName = $model->table;
        $builder = !empty($param) ? $model->search($param)->select("$tableName.*") : $model->builder()->select("$tableName.*");
        $builder = $model->addPractitionerDetailsFields($builder, empty($param));

        array_map(function ($value, $key) use ($builder, $tableName) {
            $builder->where($tableName . "." . $key, $value);
        }, $filterArray, array_keys($filterArray));

        if ($withDeleted) {
            $model->withDeleted();
        }

        return $builder->countAllResults();
    }

    public function getApplicationFormFields(string $session): array
    {
        $model = new HousemanshipApplicationModel();
        $detailsModel = new HousemanshipApplicationDetailsModel();

        $sessionSetting = Utils::getHousemanshipSetting(HousemanshipSetting::SESSIONS);
        if (!$sessionSetting) {
            throw new Exception("Session setting not found");
        }

        if (!array_key_exists($session, $sessionSetting)) {
            throw new Exception("Session not found");
        }

        $numberOfRequiredFacilities = (int) $sessionSetting[$session]['number_of_facilities'];
        $mainFields = $model->getFormFields();
        $detailsFields = $detailsModel->getFormFields();

        for ($i = 0; $i < $numberOfRequiredFacilities; $i++) {
            $mainFields[] = [
                "label" => "Discipline " . ($i + 1),
                "name" => "",
                "type" => "label",
                "hint" => "",
                "options" => [],
                "value" => "",
                "required" => false
            ];

            $detail = [];
            foreach ($detailsFields as $detailsField) {
                $detailsField['name'] = "posting_application_detail-{$detailsField['name']}-$i";
                $detail[] = $detailsField;
            }

            $mainFields[] = $detail;
        }

        return ['data' => $mainFields];
    }

    public function approveApplications(array $applicationsData, string $user): array
    {
        $results = [];

        foreach ($applicationsData as $application) {
            try {
                $applicationUuid = $application->application_uuid;

                $license = LicenseUtils::getLicenseDetails($application->license_number);
                if (!$license) {
                    throw new Exception("License not found");
                }

                $postingData = new HousemanshipPostingType(
                    $application->license_number,
                    $license['type'],
                    $license['category'],
                    $application->session,
                    $application->year,
                    $application->letter_template,
                    $application->tags ?? "",
                    json_encode($license),
                    $application->details
                );

                HousemanshipUtils::createPosting($postingData, $user);

                $results[] = [
                    'successful' => true,
                    'license_number' => $application->license_number
                ];

                $model = new HousemanshipApplicationModel();
                $existingApplication = $model->builder()->where('uuid', $applicationUuid)->get()->getFirstRow('array');

                $model->builder()->where('uuid', $applicationUuid)->delete();

                $this->logActivity(
                    "Approved housemanship posting application for year {$existingApplication['session']} posting for {$existingApplication['license_number']}",
                    $user
                );

            } catch (Exception $e) {
                log_message("error", $e->getMessage());
                $results[] = [
                    'successful' => false,
                    'license_number' => $application->license_number
                ];
            }
        }

        return ['message' => "Housemanship posting", 'data' => $results];
    }

    public function generateHousemanshipLetter(string $uuid): string
    {
        try {
            $posting = $this->getPosting($uuid);
            $templateEngine = new TemplateEngineHelper();
            $templateModel = new PrintTemplateModel();
            $template = $templateModel->where('template_name', $posting['letter_template'])->first();
            if (empty($template)) {
                throw new Exception("Letter template not found");
            }
            $content = $templateEngine->process(
                $template['template_content'],
                $posting
            );


            return Utils::addLetterStyling($content);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    // Helper Methods
    private function insertPostingDetails(string $postingUuid, array $details): void
    {
        $detailsValidationRules = [
            "facility_name" => "required|is_not_unique[housemanship_facilities.name]",
            "discipline" => "required|is_not_unique[housemanship_disciplines.name]",
            "start_date" => "permit_empty|valid_date",
            "end_date" => "permit_empty|valid_date",
        ];

        foreach ($details as $postingDetail) {
            $postingDetail = (array) $postingDetail;
            $postingDetail['posting_uuid'] = $postingUuid;

            $validation = \Config\Services::validation();
            if (!$validation->setRules($detailsValidationRules)->run($postingDetail)) {
                throw new Exception("Validation failed");
            }

            $facilityModel = new HousemanshipFacilitiesModel();
            $facility = $facilityModel->where(['name' => $postingDetail['facility_name']])->first();

            if (!$facility) {
                throw new Exception("Facility not found");
            }

            $postingDetail['facility_region'] = $facility['region'];
            $postingDetail['facility_details'] = json_encode($facility);

            $postingDetailsModel = new HousemanshipPostingDetailsModel();
            $postingDetailsModel->insert($postingDetail);
        }
    }

    private function insertApplicationDetails(string $applicationUuid, array $details): void
    {
        $detailsValidationRules = [
            "first_choice" => "required|is_not_unique[housemanship_facilities.name]",
            "second_choice" => "required|is_not_unique[housemanship_facilities.name]|differs[first_choice]",
            "discipline" => "required|is_not_unique[housemanship_disciplines.name]"
        ];

        foreach ($details as $applicationDetail) {
            $applicationDetail = (array) $applicationDetail;
            $applicationDetail['application_uuid'] = $applicationUuid;

            $validation = \Config\Services::validation();
            if (!$validation->setRules($detailsValidationRules)->run($applicationDetail)) {
                $message = implode(" ", array_values($validation->getErrors()));
                throw new Exception($message);
            }

            $facilityModel = new HousemanshipFacilitiesModel();

            $firstChoice = $facilityModel->where(['name' => $applicationDetail['first_choice']])->first();
            if (!$firstChoice) {
                log_message("error", "First choice facility not found {$applicationDetail['first_choice']}");
                throw new Exception("First choice facility not found");
            }

            $secondChoice = $facilityModel->where(['name' => $applicationDetail['second_choice']])->first();
            if (!$secondChoice) {
                log_message("error", "Second choice facility not found {$applicationDetail['second_choice']}");
                throw new Exception("Second choice facility not found");
            }

            $applicationDetail['first_choice_region'] = $firstChoice['region'];
            $applicationDetail['second_choice_region'] = $secondChoice['region'];

            $applicationDetailsModel = new HousemanshipApplicationDetailsModel();
            $applicationDetailsModel->insert($applicationDetail);
        }
    }

    private function logActivity(string $message, ?string $user = null, ?string $module = null): void
    {
        $activitiesModel = new ActivitiesModel();
        $activitiesModel->logActivity($message, $user, $module ?? $this->activityModule);
    }

    // Form Fields Methods
    public function getFacilityFormFields(): array
    {
        $model = new HousemanshipFacilitiesModel();
        return ['data' => $model->getFormFields()];
    }

    public function getFacilityCapacityFormFields(): array
    {
        $model = new HousemanshipFacilityCapacitiesModel();
        return ['data' => $model->getFormFields()];
    }

    public function getFacilityAvailabilityFormFields(): array
    {
        $model = new HousemanshipFacilityAvailabilityModel();
        return ['data' => $model->getFormFields()];
    }

    public function getDisciplineFormFields(): array
    {
        $model = new HousemanshipDisciplinesModel();
        return ['data' => $model->getFormFields()];
    }

}