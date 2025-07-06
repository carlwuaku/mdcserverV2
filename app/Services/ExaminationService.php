<?php
namespace App\Services;

use App\Helpers\ExaminationsUtils;
use App\Helpers\LicenseUtils;
use App\Helpers\Utils;

use App\Models\Examinations\ExaminationApplicationsModel;
use App\Models\Examinations\ExaminationRegistrationsModel;
use App\Models\Examinations\ExaminationsModel;
use App\Models\Examinations\ExaminationLetterTemplatesModel;
use App\Models\Examinations\ExaminationLetterTemplateCriteriaModel;
use CodeIgniter\Database\BaseBuilder;
use App\Models\ActivitiesModel;
use App\Helpers\Types\ExaminationLetterCriteriaType;
use App\Helpers\Types\ExaminationLetterType;
use App\Models\Licenses\LicensesModel;


/**
 * ExaminationService class
 * This service handles operations related to examinations, creating exams, applications, registration, and managing letter templates and criteria.
 */
class ExaminationService
{

    //create exam
    //update exam
    //delete exam
    //get exam by id
    //get all exams
    private ActivitiesModel $activitiesModel;
    private ExaminationsModel $examinationsModel;
    private ExaminationLetterTemplatesModel $examinationLetterTemplatesModel;
    private ExaminationLetterTemplateCriteriaModel $examinationLetterTemplateCriteriaModel;

    private ExaminationApplicationsModel $examinationApplicationsModel;

    private ExaminationRegistrationsModel $examinationRegistrationsModel;

    public function __construct()
    {
        $this->activitiesModel = new ActivitiesModel();
        $this->examinationsModel = new ExaminationsModel();
        $this->examinationLetterTemplatesModel = new ExaminationLetterTemplatesModel();
        $this->examinationLetterTemplateCriteriaModel = new ExaminationLetterTemplateCriteriaModel();
        $this->examinationApplicationsModel = new ExaminationApplicationsModel();
        $this->examinationRegistrationsModel = new ExaminationRegistrationsModel();
    }
    /**
     * Create a new exam with the provided data.
     *
     * @param array $data The data for the exam.
     * @param ExaminationLetterType[] $letters An array of letter templates associated with the exam.
     * @return int Returns true on success, false on failure.
     */
    public function createExam(array $data, array $letters): int
    {
        // Validate and process the data
        $rules = [
            "title" => "required|is_unique[examinations.title]",
            "exam_type" => "required",
            "open_from" => "required|valid_date",
            "open_to" => "required|valid_date",
            "type" => "required",
            "scores_names" => "required|array",
        ];

        $validator = \Config\Services::validation();
        $validator->setRules($rules);
        if (!$validator->run($data)) {
            $message = implode(" ", array_values($validator->getErrors()));
            throw new \InvalidArgumentException("Validation failed: " . $message);
        }
        // Insert into the database
        $examData = $this->examinationsModel->createArrayFromAllowedFields($data);
        //get the metadata fields from the settings and get the values from the data
        $metadataFields = Utils::getAppSettings('examinations')['metadataFields'] ?? [];
        $metadata = [];
        foreach ($metadataFields as $field) {
            if (isset($data[$field['name']])) {
                $metadata[$field['name']] = $data[$field['name']] ?? null; // Use null if not set
            }
        }
        $examData['metadata'] = json_encode($metadata); // Store metadata as JSON
        $examData['scores_names'] = json_encode($data['scores_names']);
        $this->examinationsModel->db->transException(true)->transStart();
        $examId = $this->examinationsModel->insert($examData);
        if (!$examId) {
            $this->examinationsModel->db->transRollback();
            throw new \RuntimeException("Failed to create exam.");
        }
        // Create letter templates for the exam
        foreach ($letters as $letter) {
            if (!$letter instanceof ExaminationLetterType) {
                throw new \InvalidArgumentException('All letters must be instances of ExaminationLetterType');
            }
            $letter->examId = $examId; // Set the exam ID for the letter
            $this->createLetterTemplate($examId, $letter);
        }
        $this->examinationsModel->db->transComplete();

        // Return the exam ID
        return $examId;
    }

    /**
     * Create a letter template with criteria for an exam.
     * 
     * @param int $examId The ID of the exam for which the letter template is being created.
     * @param ExaminationLetterType $data The data for the letter template, including name, type, content, and criteria.
     * @return int Returns true on success, false on failure.
     * @throws \InvalidArgumentException If validation fails.
     * @throws \RuntimeException If the database operation fails.
     */
    public function createLetterTemplate(int $examId, ExaminationLetterType $letter): int
    {
        // Validate and process the data
        $rules = [
            "exam_id" => "required|integer|is_not_unique[examinations.id]",
            "type" => "required|in_list[pass,fail,registration]",
            "content" => "required"
        ];

        $validator = \Config\Services::validation();
        $validator->setRules($rules);
        if (!$validator->run($letter->toArray())) {
            $message = implode(" ", array_values($validator->getErrors()));
            throw new \InvalidArgumentException("Validation failed: " . $message);
        }

        // For each letter in $data, create a letter template and its criteria
        $this->examinationLetterTemplatesModel->db->transException(true)->transStart();
        if (!$letter instanceof ExaminationLetterType) {
            throw new \InvalidArgumentException('All letters must be instances of ExaminationLetterType');
        }
        // Create the letter template
        $templateData = [
            'name' => $letter->name,
            'exam_id' => $examId,
            'type' => $letter->type,
            'content' => $letter->content,
            'created_at' => date('Y-m-d H:i:s')
        ];
        $templateId = $this->examinationLetterTemplatesModel->insert($templateData, true);

        // Create criteria for the letter template
        foreach ($letter->criteria as $criterion) {
            if (!$criterion instanceof ExaminationLetterCriteriaType) {
                throw new \InvalidArgumentException('All criteria must be instances of ExaminationLetterCriteriaType');
            }
            $criteriaData = [
                'letter_id' => $templateId,
                'field' => $criterion->field,
                'value' => json_encode($criterion->value)
            ];
            $this->examinationLetterTemplateCriteriaModel->insert($criteriaData);
        }

        $this->examinationLetterTemplatesModel->db->transComplete();

        return $templateId;
    }

    /**
     * Updates an existing exam
     *
     * @param string $uuid The UUID of the exam
     * @param array $data The data to update the exam with
     * @param array|null $letters An array of letter templates associated with the exam. if null the letters will not be updated. if set to any array, even if empty, then the letters will be deleted and replaced with the new ones
     * @return bool Whether the exam was updated successfully
     * @throws \InvalidArgumentException If validation fails
     */
    public function updateExam(string $uuid, array $data, array $letters = null): bool
    {
        // Validate and process the data
        $rules = [
            "title" => "permit_empty|is_unique[examinations.title,uuid,{$uuid}]",
            "open_from" => "permit_empty|valid_date",
            "open_to" => "permit_empty|valid_date",
        ];

        $validator = \Config\Services::validation();
        $validator->setRules($rules);
        if (!$validator->run($data)) {
            $message = implode(" ", array_values($validator->getErrors()));
            throw new \InvalidArgumentException("Validation failed: " . $message);
        }

        // Get the old data first (before any updates)
        $oldData = $this->examinationsModel->where(['uuid' => $uuid])->first();
        if (!$oldData) {
            throw new \InvalidArgumentException("Exam with UUID {$uuid} not found");
        }

        $examId = $oldData['id'];

        // Update the exam in the database
        $examData = $this->examinationsModel->createArrayFromAllowedFields($data);

        // Get the metadata fields from the settings and prepare metadata updates
        $metadataFields = Utils::getAppSettings('examinations')['metadataFields'] ?? [];

        $metadataUpdates = [];

        // Only collect metadata that actually exists in the incoming data
        foreach ($metadataFields as $field) {
            $fieldName = $field['name'] ?? $field; // Handle both array and string field definitions
            log_message("debug", $data[$fieldName]);
            if (isset($data[$fieldName])) {
                $metadataUpdates[$fieldName] = $data[$fieldName];
            }
        }
        unset($data['id']);
        $changes = implode(", ", Utils::compareObjects($oldData, $data));

        $this->examinationsModel->db->transException(true)->transStart();
        //if the score names are updated, encode them
        if (array_key_exists('scores_names', $data)) {
            $examData['scores_names'] = json_encode($data['scores_names']);
        }
        // Update main exam data
        $this->examinationsModel->builder()->where(['uuid' => $uuid])->update($examData);

        // Update JSON metadata if there are metadata updates
        if (!empty($metadataUpdates)) {
            // Get current metadata
            $currentMetadata = [];
            if (!empty($oldData['metadata'])) {
                $currentMetadata = json_decode($oldData['metadata'], true) ?? [];
            }

            // Merge the updates with existing metadata (this preserves existing fields)
            $updatedMetadata = array_merge($currentMetadata, $metadataUpdates);

            // Simple approach: Update the entire metadata column
            $this->examinationsModel->builder()
                ->where(['uuid' => $uuid])
                ->update(['metadata' => json_encode($updatedMetadata)]);
        }


        if ($letters !== null) {
            // Delete the letters and re-insert them
            $this->examinationLetterTemplatesModel->where(['exam_id' => $examId])->delete();

            // Create letter templates for the exam
            foreach ($letters as $letter) {
                if (!$letter instanceof ExaminationLetterType) {
                    throw new \InvalidArgumentException('All letters must be instances of ExaminationLetterType');
                }
                $letter->examId = $examId; // Set the exam ID for the letter
                $this->createLetterTemplate($examId, $letter);
            }
        }

        $this->examinationsModel->db->transComplete();
        $this->activitiesModel->logActivity("Updated exam {$oldData['title']}. Changes: $changes");
        return true;
    }


    /**
     * Deletes an exam by its UUID.
     *
     * @param int $uuid The UUID of the exam to delete.
     * @return bool Returns true on success, false on failure.
     */
    public function deleteExam(int $uuid): bool
    {
        // Validate the UUID
        $data = $this->examinationsModel->where(["uuid" => $uuid])->first();

        if (!$data) {
            throw new \RuntimeException("Exam not found");
        }

        if (!$this->examinationsModel->where('uuid', $uuid)->delete()) {
            throw new \RuntimeException('Failed to delete exam: ' . json_encode($this->examinationsModel->errors()));
        }

        // Log activity
        $this->activitiesModel->logActivity("Deleted exam {$data['title']}.");

        return true;
    }

    /**
     * Retrieves an exam by its UUID.
     *
     * @param string $uuid The UUID of the exam to retrieve.
     * @return object Returns the exam data as an associative array, or null if not found.
     */
    public function getExamByUuid(string $uuid): object
    {
        $builder = $this->examinationsModel->builder();
        $builder = $this->examinationsModel->addCustomFields($builder);
        $data = $builder->where(["examinations.uuid" => $uuid])->get()->getRow();
        $data->scores_names = $data->scores_names ? json_decode($data->scores_names) : [];
        $metadata = json_decode($data->metadata);

        //merge the metadata fields with the data
        $data = (object) array_merge((array) $data, (array) $metadata);

        if (!$data) {
            throw new \RuntimeException("Exam not found");
        }

        // Return the exam data
        return $data;
    }
    /**
     * Retrieves all exams.
     *
     * @return array Returns an array of all exams.
     */
    public function getAllExams(array $filters = []): array
    {
        $per_page = $filters['limit'] ?? 100;
        $page = $filters['page'] ?? 0;
        $withDeleted = ($filters['withDeleted'] ?? '') === "yes";
        $param = $filters['param'] ?? $filters['child_param'] ?? null;
        $sortBy = $filters['sortBy'] ?? "id";
        $sortOrder = $filters['sortOrder'] ?? "desc";
        // Build query
        $builder = $param ? $this->examinationsModel->search($param) : $this->examinationsModel->builder();
        $builder = $this->examinationsModel->addCustomFields($builder);
        $tableName = $this->examinationsModel->table;
        $builder->orderBy("$tableName.$sortBy", $sortOrder);
        // Apply filters
        if (isset($filters['type']) && $filters['type'] !== '') {
            $builder->where("$tableName.type", $filters['type']);
        }
        if (isset($filters['exam_type']) && $filters['exam_type'] !== '') {
            $builder->where("$tableName.exam_type", $filters['exam_type']);
        }

        if ($withDeleted) {
            $this->examinationsModel->withDeleted();
        }
        $total = $builder->countAllResults(false);
        $result = $builder->get($per_page, $page)->getResult();
        foreach ($result as &$row) {
            $row->metadata = $row->metadata ? json_decode($row->metadata) : [];
            $row->scores_names = $row->scores_names ? json_decode($row->scores_names) : [];
            //merge the metadata fields with the data
            $row = array_merge((array) $row, (array) $row->metadata);
        }
        $displayColumns = $this->examinationsModel->getDisplayColumns();
        $metadataFields = Utils::getAppSettings('examinations')['metadataFields'] ?? [];
        foreach ($metadataFields as $field) {
            $displayColumns[] = $field['name'];
        }
        //add the metadata fields to the display columns
        return [
            'data' => $result,
            'total' => $total,
            'displayColumns' => $displayColumns,
            'columnFilters' => $this->examinationsModel->getDisplayColumnFilters()
        ];
    }
    /**
     * Retrieves all letter templates for a given exam.
     *
     * @param int $examId The ID of the exam.
     * @return array Returns an array of letter templates associated with the exam.
     */
    public function getLetterTemplatesByExamId(int $examId): array
    {
        // Validate the exam ID
        $exam = $this->examinationsModel->find($examId);
        if (!$exam) {
            throw new \RuntimeException("Exam not found");
        }

        // Retrieve letter templates for the exam
        $templates = $this->examinationLetterTemplatesModel->where('exam_id', $examId)->findAll();

        // Return the letter templates
        return $templates;
    }
    /**
     * Retrieves a letter template by its ID.
     *
     * @param int $templateId The ID of the letter template.
     * @return array|null Returns the letter template data as an associative array, or null if not found.
     */
    public function getLetterTemplateById(int $templateId): ?array
    {
        // Validate the template ID
        $template = $this->examinationLetterTemplatesModel->find($templateId)->get();
        if (!$template) {
            return null; // Template not found
        }

        // Retrieve criteria for the letter template
        $criteria = $this->examinationLetterTemplateCriteriaModel->where('letter_id', $templateId)->findAll();
        $template['criteria'] = $criteria;

        // Return the letter template data
        return $template;
    }

    /**
     * Get all letter templates for an exam Id
     * @param int $examId
     * @return array
     * @throws \RuntimeException If the exam is not found or if there is an error retrieving the letter templates.
     * @throws \InvalidArgumentException If the exam ID is invalid.
     * @throws \Exception If there is a database error.
     */
    public function getAllLetterTemplatesForExam(int $examId): array
    {
        // Validate the exam ID
        if (!$examId || !is_int($examId)) {
            throw new \InvalidArgumentException("Invalid exam ID");
        }

        // Retrieve letter templates for the exam
        $templates = $this->examinationLetterTemplatesModel->where('exam_id', $examId)->findAll();

        // Return the letter templates
        return $templates;
    }

    /**
     * Deletes a letter template by its ID.
     *
     * @param int $templateId The ID of the letter template to delete.
     * @return bool Returns true on success, false on failure.
     */
    public function deleteLetterTemplate(int $templateId): bool
    {
        // Validate the template ID
        $template = $this->examinationLetterTemplatesModel->find($templateId)->get();
        if (!$template) {
            throw new \RuntimeException("Letter template not found");
        }

        // Delete the letter template
        if (!$this->examinationLetterTemplatesModel->delete($templateId)) {
            throw new \RuntimeException('Failed to delete letter template: ' . json_encode($this->examinationLetterTemplatesModel->errors()));
        }

        // Log activity
        $this->activitiesModel->logActivity("Deleted letter template {$template['name']}.");

        return true;
    }

    public function getExaminationForm()
    {

        return $this->examinationsModel->getFormFields();
    }

    /**
     * Retrieves all exam applications.
     * may be filtered by intern code, exam ID
     * @return array Returns an array of all exam applications.
     */
    public function getExamApplications(array $filters = []): array
    {
        $per_page = $filters['limit'] ?? 100;
        $page = $filters['page'] ?? 0;
        $withDeleted = ($filters['withDeleted'] ?? '') === "yes";
        $param = $filters['param'] ?? $filters['child_param'] ?? null;
        $sortBy = $filters['sortBy'] ?? "created_at";
        $sortOrder = $filters['sortOrder'] ?? "asc";
        // Build query
        $builder = $param ? $this->examinationApplicationsModel->search($param) : $this->examinationApplicationsModel->builder();
        $builder = $this->examinationApplicationsModel->addCustomFields($builder);
        $tableName = $this->examinationApplicationsModel->table;
        $builder->orderBy("$tableName.$sortBy", $sortOrder);
        // Apply filters
        if (isset($filters['intern_code']) && $filters['intern_code'] !== '') {
            $builder->where("$tableName.intern_code", $filters['intern_code']);
        }
        if (isset($filters['exam_id']) && $filters['exam_id'] !== '') {
            $builder->where("$tableName.exam_id", $filters['exam_id']);
        }
        $childParams = $this->extractChildParams($filters);
        if (!empty($childParams)) {
            $examCandidatesTable = "exam_candidates";
            foreach ($childParams as $key => $value) {
                if ($key === "child_param") {
                    continue;
                }

                $value = Utils::parseParam($value);
                $columnName = $examCandidatesTable . "." . str_replace('child_', '', $key);
                $builder = Utils::parseWhereClause($builder, $columnName, $value);
            }
        }



        if ($withDeleted) {
            $this->examinationApplicationsModel->withDeleted();
        }
        $total = $builder->countAllResults(false);
        $result = $builder->get($per_page, $page)->getResult();
        return [
            'data' => $result,
            'total' => $total,
            'displayColumns' => $this->examinationApplicationsModel->getDisplayColumns(),
            'columnFilters' => $this->examinationApplicationsModel->getDisplayColumnFilters()
        ];
    }

    /**
     * Counts all exam applications.
     * may be filtered by intern code, exam ID
     * @param array $filters An associative array of filters.
     * @return int Returns the total number of exam applications.
     */
    public function countExamApplications(array $filters = []): int
    {

        $param = $filters['param'] ?? $filters['child_param'] ?? null;

        // Build query
        $builder = $param ? $this->examinationApplicationsModel->search($param) : $this->examinationApplicationsModel->builder();
        $builder = $this->examinationApplicationsModel->addCustomFields($builder);
        $tableName = $this->examinationApplicationsModel->table;

        $filterArray = $this->examinationApplicationsModel->createArrayFromAllowedFields($filters);
        array_map(function ($value, $key) use ($builder, $tableName) {
            $builder->where($tableName . "." . $key, $value);
        }, $filterArray, array_keys($filterArray));
        // Apply other filters
        foreach ($filterArray as $key => $value) {
            if (strpos($key, 'child_') !== 0) {
                $value = Utils::parseParam($value);
                $builder = Utils::parseWhereClause($builder, $key, $value);
            }
        }
        // Apply filters
        if (isset($filters['intern_code']) && $filters['intern_code'] !== '') {
            $builder->where("$tableName.intern_code", $filters['intern_code']);
        }
        if (isset($filters['exam_id']) && $filters['exam_id'] !== '') {
            $builder->where("$tableName.exam_id", $filters['exam_id']);
        }
        $childParams = $this->extractChildParams($filters);
        if (!empty($childParams)) {
            $examCandidatesTable = "exam_candidates";
            foreach ($childParams as $key => $value) {
                if ($key === "child_param") {
                    continue;
                }

                $value = Utils::parseParam($value);
                $columnName = $examCandidatesTable . "." . str_replace('child_', '', $key);
                $builder = Utils::parseWhereClause($builder, $columnName, $value);
            }
        }
        $total = $builder->countAllResults(false);
        return $total;
    }

    /**
     * Retrieves all exam registrations.
     * may be filtered by intern code, exam ID
     * @return array Returns an array of all exam registrations.
     */
    public function getExamRegistrations(array $filters = []): array
    {
        $per_page = $filters['limit'] ?? 100;
        $page = $filters['page'] ?? 0;
        $withDeleted = ($filters['withDeleted'] ?? '') === "yes";
        $param = $filters['param'] ?? $filters['child_param'] ?? null;
        $sortBy = $filters['sortBy'] ?? "created_at";
        $sortOrder = $filters['sortOrder'] ?? "asc";
        $mode = "exam";
        // Build query
        $builder = $param ? $this->examinationRegistrationsModel->search($param) : $this->examinationRegistrationsModel->builder();
        $builder = $this->examinationRegistrationsModel->addCustomFields($builder);
        $tableName = $this->examinationRegistrationsModel->table;
        $builder->orderBy("$tableName.$sortBy", $sortOrder);
        // Apply filters
        if (isset($filters['intern_code']) && $filters['intern_code'] !== '') {
            $builder->where("$tableName.intern_code", $filters['intern_code']);
            $mode = "candidate";
        }
        if (isset($filters['exam_id']) && $filters['exam_id'] !== '') {
            $builder->where("$tableName.exam_id", $filters['exam_id']);
        }
        if (isset($filters['result'])) {
            $value = Utils::parseParam($filters['result']);
            $builder = Utils::parseWhereClause($builder, "result", $value);

        }
        $childParams = $this->extractChildParams($filters);
        if (!empty($childParams)) {
            $examCandidatesTable = "exam_candidates";
            foreach ($childParams as $key => $value) {
                if ($key === "child_param") {
                    continue;
                }

                $value = Utils::parseParam($value);
                $columnName = $examCandidatesTable . "." . str_replace('child_', '', $key);
                $builder = Utils::parseWhereClause($builder, $columnName, $value);
            }
        }

        if ($withDeleted) {
            $this->examinationRegistrationsModel->withDeleted();
        }
        $total = $builder->countAllResults(false);
        $result = $builder->get($per_page, $page)->getResult();
        foreach ($result as &$resultItem) {
            $scoreString = [];
            $scores = $resultItem->scores ? json_decode($resultItem->scores) : [];
            foreach ($scores as $score) {
                $scoreString[] = $score->title . ": " . $score->score;
            }
            $resultItem->scores = implode(", ", $scoreString);
        }
        return [
            'data' => $result,
            'total' => $total,
            'displayColumns' => $this->examinationRegistrationsModel->getDisplayColumns($mode),
            'columnFilters' => $this->examinationRegistrationsModel->getDisplayColumnFilters()
        ];
    }

    /**
     * Creates a new exam registration.
     *
     * @param array{intern_code:string,exam_id:string,index_number:string,result:string,registration_letter:string,result_letter:string,publish_result_date:string, scores:[]}[] $data The list of data required to create an exam registration.
     * @throws \InvalidArgumentException If the provided data is invalid.
     * @throws \Exception If there is a database error during the creation process.
     * @return int Returns the number of rows inserted .
     */

    public function createExamRegistration(array $data, $userId)
    {
        // Validate and process the data
        $rules = [
            "intern_code" => "required|is_not_unique[exam_candidates.intern_code]",
            "exam_id" => "required|is_not_unique[examinations.id]",
            "index_number" => "required",
            "result" => "permit_empty|in_list[Pass,Fail,Absent]",
            "registration_letter" => "permit_empty",
            "result_letter" => "permit_empty",
            "publish_result_date" => "permit_empty|valid_date",
            "scores" => "permit_empty|array"
        ];
        $insertData = [];
        $activityLogMessages = [];
        $validator = \Config\Services::validation();
        $validator->setRules($rules);
        for ($i = 0; $i < count($data); $i++) {
            $registration = (array) $data[$i];
            if (!$validator->run($registration)) {
                $message = implode(" ", array_values($validator->getErrors()));
                throw new \InvalidArgumentException("Validation failed for " . $registration['index_number'] . ": . $message");
            }
            //check if the candidate is eligible for the exam

            ExaminationsUtils::candidateIsEligibleForExamination($registration['intern_code'], $registration['exam_id'], false);

            $registrationData = $this->examinationRegistrationsModel->createArrayFromAllowedFields($registration);
            $registrationData['scores'] = json_encode($data['scores'] ?? []);
            $insertData[] = $registrationData;
            $activityLogMessages[] = "Created exam registration for intern code {$registration['intern_code']} index number {$registration['index_number']} for exam ID {$registration['exam_id']}.";
        }
        $this->examinationRegistrationsModel->db->transException(true)->transStart();
        $numRows = $this->examinationRegistrationsModel->insertBatch($insertData);
        $this->examinationRegistrationsModel->db->transComplete();
        try {
            $this->activitiesModel->logActivity($activityLogMessages, $userId, "Examination");
        } catch (\Throwable $th) {
            log_message("error", $th);
        }


        return $numRows;

    }

    public function updateExamRegistration(string $id, array $data, $userId)
    {
        // Update the exam registration in the database. createArrayFromAllowedFields removes fields that are null
        $registrationData = $this->examinationRegistrationsModel->createArrayFromAllowedFields($data);
        //some fields are not allowed to be updated, or need to be updated specially, so we need to unset them
        $unchangeableFields = ['intern_code', 'exam_id', 'index_number', 'result', 'publish_result_date', 'scores'];

        foreach ($unchangeableFields as $field) {
            unset($registrationData[$field]);
        }
        //some fields are nullable. we may want to set these to null if they're present and have a null value
        $nullableFields = ['registration_letter', 'result_letter'];
        foreach ($nullableFields as $field) {

            if (array_key_exists($field, $data) && empty($data[$field])) {
                $registrationData[$field] = null;
            }
        }
        if (isset($data['scores'])) {
            $registrationData['scores'] = json_encode($data['scores']);
        }
        return $this->examinationRegistrationsModel->builder()->where(['id' => $id])->update($registrationData);
    }

    /**
     * Delete a registration by its UUID.
     */
    public function deleteExaminationRegistration(string $uuid, $userId): array
    {
        $model = $this->examinationRegistrationsModel;
        $data = $model->where(["uuid" => $uuid])->first();

        if (!$data) {
            throw new \RuntimeException("Registration not found");
        }
        $this->examinationRegistrationsModel->db->transException(true)->transStart();
        //update the candidate state in the exam_candidates table
        $candidateStateData = [
            'state' => ExaminationsUtils::determineCandidateState($data['intern_code']),
            'intern_code' => $data['intern_code']
        ];
        $licenseModel = new LicensesModel('exam_candidates');
        $licenseModel->createOrUpdateLicenseDetails("exam_candidates", $candidateStateData);
        $model->where('uuid', $uuid)->delete();

        $this->examinationRegistrationsModel->db->transComplete();

        // Log activity
        $this->activitiesModel->logActivity("Deleted registration {$data['index_number']} for {$data['intern_code']}.", $userId, "Examination");

        return [
            'success' => true,
            'message' => 'Registration deleted successfully'
        ];
    }

    public function deleteExaminationApplication(string $id): array
    {
        $model = $this->examinationApplicationsModel;
        $data = $model->where(["id" => $id])->first();

        if (!$data) {
            throw new \RuntimeException("Application not found");
        }

        if (!$model->where('id', $id)->delete()) {
            throw new \RuntimeException('Failed to delete application: ' . json_encode($model->errors()));
        }

        // Log activity
        $this->activitiesModel->logActivity("Deleted application for {$data['intern_code']}.", null, "Examination");

        return [
            'success' => true,
            'message' => 'Application deleted successfully'
        ];
    }

    /**
     * Retrieves the candidate letter for a specific examination registration.
     *
     * @param string $uuid The UUID of the examination registration.
     * @param string $letterType The type of letter to generate (e.g., registration, result).
     * @return string Returns the generated letter content.
     * @throws \RuntimeException If the letter cannot be generated.
     */

    public function getCandidateLetter(string $uuid, string $letterType): string
    {
        // Generate the letter content using the template and registration data
        return ExaminationsUtils::generateCandidateLetter($uuid, $letterType);
    }

    public function getExaminationRegistrationResultsCounts(string $examId)
    {
        $builder = $this->examinationRegistrationsModel->builder();
        $builder->select('result, COUNT(*) as count');
        $builder->where('exam_id', $examId);
        $builder->groupBy('result');

        $results = $builder->get()->getResultArray();
        // Convert the results to a more usable format
        $counts = [];
        $total = 0;
        foreach ($results as $result) {
            $key = !empty($result['result']) ? strtolower($result['result']) : 'not_set'; // Handle null or empty results
            $counts[$key] = (int) $result['count'];
            $total += $result['count'];
        }

        $counts['total'] = $total;
        return $counts;
    }

    public function getExaminationLetters($examId)
    {
        return ExaminationsUtils::getExaminationLettersWithCriteria($examId, "");
    }

    /**
     * Set the scores and results for a set of examination registrations.
     * @param object{uuid:string, index_number:string, result:string, scores:[]}[] $data The list of data required to set the results
     * @return int
     */
    public function setExaminationResults(array $data): int
    {
        // Validate and process the data
        $rules = [
            "uuid" => "required|is_not_unique[examination_registrations.uuid]",
            "index_number" => "required|is_not_unique[examination_registrations.index_number]",
            "intern_code" => "required|is_not_unique[examination_registrations.intern_code]",
            "result" => "required|in_list[Pass,Fail,Absent]",
            "scores" => "required"
        ];
        $updateData = [];
        $candidateStateData = [];
        $activityLogMessages = [];
        $uuids = [];
        $validator = \Config\Services::validation();
        $validator->setRules($rules);
        for ($i = 0; $i < count($data); $i++) {
            $registration = (array) $data[$i];
            if (!$validator->run($registration)) {
                $message = implode(" ", array_values($validator->getErrors()));
                throw new \InvalidArgumentException("Validation failed for " . $registration['index_number'] . ": . $message");
            }

            $registrationData = [
                'uuid' => $registration['uuid'],
                'result' => $registration['result'],
                'scores' => json_encode($registration['scores'])
            ];

            $updateData[] = $registrationData;
            $uuids[] = "'{$registration['uuid']}'";
            $activityLogMessages[] = "Set result for  exam registration for intern code {$registration['intern_code']} index number {$registration['index_number']}";
        }
        $examinationRegistrations = $this->examinationRegistrationsModel->select("{$this->examinationRegistrationsModel->table}.*, {$this->examinationsModel->table}.exam_type")->join($this->examinationsModel->table, "{$this->examinationsModel->table}.id = {$this->examinationRegistrationsModel->table}.exam_id")->whereIn("{$this->examinationRegistrationsModel->table}.uuid", $uuids)->findAll();
        $this->examinationRegistrationsModel->db->transException(true)->transStart();
        $numRows = $this->examinationRegistrationsModel->updateBatch($updateData, 'uuid', count($updateData));
        //get all the examination registrations for the uuids
        foreach ($examinationRegistrations as $examinationRegistration) {
            $candidateStateData = [
                'intern_code' => $examinationRegistration['intern_code'],
                'state' => ExaminationsUtils::getExamCandidateStateFromExamResult($examinationRegistration['exam_type'], $examinationRegistration['result'])
            ];
            $licenseModel = new LicensesModel('exam_candidates');
            $licenseModel->createOrUpdateLicenseDetails("exam_candidates", $candidateStateData);
        }
        //update the exam_candidates table with the state
        $this->examinationRegistrationsModel->db->transComplete();
        try {
            $this->activitiesModel->logActivity($activityLogMessages, null, "Examination");
        } catch (\Throwable $th) {
            log_message("error", $th);
        }


        return $numRows;
    }

    /**
     * Set the scores and results for a set of examination registrations to null.
     * @param object{uuid:string, index_number:string, intern_code:string}[] $data The list of data required to set the results
     * @return int
     */
    public function removeExaminationResults($uuid)
    {
        $oldData = $this->examinationRegistrationsModel->where(['uuid' => $uuid])->first();
        if (!$oldData) {
            throw new \InvalidArgumentException("Registration not found");
        }
        // Update the exam registration in the database. 
        $registrationData = [
            'result' => null,
            'scores' => null
        ];
        $this->examinationRegistrationsModel->db->transException(true)->transStart();
        $update = $this->examinationRegistrationsModel->builder()->where(['uuid' => $uuid])->update($registrationData);
        //update the candidate state in the exam_candidates table
        $candidateStateData = [
            'state' => ExaminationsUtils::determineCandidateState($oldData['intern_code']),
            'intern_code' => $oldData['intern_code']
        ];
        $licenseModel = new LicensesModel('exam_candidates');
        $licenseModel->createOrUpdateLicenseDetails("exam_candidates", $candidateStateData);
        $this->examinationRegistrationsModel->db->transComplete();
        try {
            $this->activitiesModel->logActivity("Removed result for  exam registration for intern code {$oldData['intern_code']} index number {$oldData['index_number']}", null, "Examination");
        } catch (\Throwable $th) {
            log_message("error", $th);
        }
        return $update;

    }

    /**
     * Set the scores and results for a set of examination registrations.
     * @param object{uuid:string, index_number:string, publish_result_date:string}[] $data The list of data required to set the publish date
     * @return int
     */
    public function publishResults(array $data): int
    {
        // Validate and process the data
        $rules = [
            "uuid" => "required|is_not_unique[examination_registrations.uuid]",
            "index_number" => "required",
            "intern_code" => "required",
            "publish_result_date" => "required|valid_date"
        ];
        $updateData = [];
        $activityLogMessages = [];
        $validator = \Config\Services::validation();
        $validator->setRules($rules);
        for ($i = 0; $i < count($data); $i++) {
            $registration = (array) $data[$i];
            if (!$validator->run($registration)) {
                $message = implode(" ", array_values($validator->getErrors()));
                throw new \InvalidArgumentException("Validation failed for " . $registration['index_number'] . ": . $message");
            }

            $registrationData = [
                'uuid' => $registration['uuid'],
                'publish_result_date' => $registration['publish_result_date']
            ];

            $updateData[] = $registrationData;
            $activityLogMessages[] = "Published result for  exam registration for intern code {$registration['intern_code']} index number {$registration['index_number']}";
        }
        $this->examinationRegistrationsModel->db->transException(true)->transStart();
        $numRows = $this->examinationRegistrationsModel->updateBatch($updateData, 'uuid', count($updateData));
        $this->examinationRegistrationsModel->db->transComplete();
        try {
            $this->activitiesModel->logActivity($activityLogMessages, null, "Examination");
        } catch (\Throwable $th) {
            log_message("error", $th);
        }


        return $numRows;
    }

    /**
     * Set the publish result date  set of examination registrations to null.
     * @param object{uuid:string, index_number:string, intern_code:string}[] $data The list of data required to set the results
     * @return int
     */
    public function unpublishResults($data)
    {
        // Validate and process the data
        $rules = [
            "uuid" => "required|is_not_unique[examination_registrations.uuid]",
            "index_number" => "required",
            "intern_code" => "required"
        ];
        $updateData = [];
        $activityLogMessages = [];
        $validator = \Config\Services::validation();
        $validator->setRules($rules);
        for ($i = 0; $i < count($data); $i++) {
            $registration = (array) $data[$i];
            if (!$validator->run($registration)) {
                $message = implode(" ", array_values($validator->getErrors()));
                throw new \InvalidArgumentException("Validation failed for " . $registration['index_number'] . ": . $message");
            }

            $registrationData = [
                'uuid' => $registration['uuid'],
                'publish_result_date' => null
            ];

            $updateData[] = $registrationData;
            $activityLogMessages[] = "Unpublished result for  exam registration for intern code {$registration['intern_code']} index number {$registration['index_number']}";
        }
        $this->examinationRegistrationsModel->db->transException(true)->transStart();
        $numRows = $this->examinationRegistrationsModel->updateBatch($updateData, 'uuid', count($updateData));
        $this->examinationRegistrationsModel->db->transComplete();
        try {
            $this->activitiesModel->logActivity($activityLogMessages, null, "Examination");
        } catch (\Throwable $th) {
            log_message("error", $th);
        }


        return $numRows;

    }

    /**
     * Updates the status of a set of examination applications.
     * @param array{id:string, intern_code:string, status:string} $data The list of data required to set the status
     * @return array an array containing a message
     * @throws \InvalidArgumentException If the validation fails
     * @throws \Throwable
     */
    public function bulkUpdateExaminationApplications(array $data): array
    {
        // Validate and process the data
        $rules = [
            "id" => "numeric|required|is_not_unique[examination_applications.id]",
            "intern_code" => "required|is_not_unique[examination_applications.intern_code]",
            "status" => "required"
        ];
        $updateData = [];
        $activityLogMessages = [];
        $validator = \Config\Services::validation();
        $validator->setRules($rules);
        for ($i = 0; $i < count($data); $i++) {
            $application = (array) $data[$i];
            if (!$validator->run($application)) {
                $message = implode(" ", array_values($validator->getErrors()));
                throw new \InvalidArgumentException("Validation failed : . $message");
            }

            $applicationData = [
                'id' => $application['id'],
                'application_status' => $application['status']
            ];

            $updateData[] = $applicationData;
            $activityLogMessages[] = "Set status for  exam registration for intern code {$application['intern_code']}";
        }
        $this->examinationApplicationsModel->db->transException(true)->transStart();
        log_message("debug", print_r($updateData, true));
        $numRows = $this->examinationApplicationsModel->updateBatch($updateData, 'id', count($updateData));
        $this->examinationApplicationsModel->db->transComplete();
        try {
            $this->activitiesModel->logActivity($activityLogMessages, null, "Examination");
        } catch (\Throwable $th) {
            log_message("error", $th);
        }


        return ["message" => "Updated successfully"];
    }

    /**
     * Deletes a batch of examination applications by their IDs.
     *
     * @param array $data An array of application IDs to be deleted.
     * @return array An array containing a message indicating successful deletion.
     * @throws \RuntimeException If an error occurs during the deletion process.
     */

    public function bulkDeleteExaminationApplications(array $data): array
    {
        // Validate and process the data
        $oldData = $this->examinationApplicationsModel->whereIn("id", $data)->findAll();
        for ($i = 0; $i < count($oldData); $i++) {
            $application = (array) $oldData[$i];
            $activityLogMessages[] = "Deleted examination application for intern code {$application['intern_code']}";
        }
        $this->examinationApplicationsModel->db->transException(true)->transStart();
        $numRows = $this->examinationApplicationsModel->whereIn("id", $data)->delete();
        $this->examinationApplicationsModel->db->transComplete();
        try {
            $this->activitiesModel->logActivity($activityLogMessages, null, "Examination");
        } catch (\Throwable $th) {
            log_message("error", $th);
        }


        return ["message" => "Updated successfully"];
    }

    /**
     * Retrieves all exam applications as a Word document.
     *
     * @param int $examId The ID of the exam whose applications are to be retrieved.
     * @return array An array containing the path and filename of the generated Word document.
     * @throws \InvalidArgumentException If the exam ID does not exist.
     */
    public function getExaminationApplicationsInWord($examId)
    {
        $data = $this->getExamApplications(['exam_id' => $examId, "limit" => 10000]);
        $examDetails = $this->examinationsModel->where("id", $examId)->first();
        if (!$examDetails) {
            throw new \InvalidArgumentException("No such exam exists");
        }
        $title = $examDetails['title'];

        $phpWord = new \PhpOffice\PhpWord\PhpWord();
        $headers = Utils::getAppSettings('examinations')['applicantsDownloadFields'] ?? [
            "last_name",
            "first_name",
            "middle_name",
            "qualification",
            "training_institution",
            "number_of_exams"
        ];
        Utils::createTableFromArray($phpWord, $data['data'], $headers);

        $filename = "$title-applicants.docx";
        $filepath = WRITEPATH . 'uploads/' . $filename;

        $writer = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($filepath);

        return ["path" => $filepath, "name" => $filename];
    }

    private function extractChildParams(array $filters): array
    {
        return array_filter($filters, function ($key) {
            return strpos($key, 'child_') === 0;
        }, ARRAY_FILTER_USE_KEY);
    }

    /**
     * Parses a CSV file and returns an array of examination results
     *
     * The CSV file should contain the index numbers of the candidates in the first column, and the scores in the subsequent columns.
     * The scores should be in the same order as the scores names in the exam settings.
     * The function checks that the index numbers are valid for the exam, and throws an InvalidArgumentException if they are not.
     * The function returns an array of arrays, each containing the exam ID, index number, intern code and scores.
     * @param string $examId The ID of the exam whose results are to be parsed.
     * @param string $filename The path to the CSV file.
     * @return array An array of arrays, each containing the exam ID, index number, intern code and scores.
     * @throws \InvalidArgumentException If any index number is invalid.
     * @throws \RuntimeException If there is an error opening the file.
     */
    public function parseResultsFromCsvFile(string $examId, string $filename)
    {
        $handle = fopen($filename, "r");
        $examDetails = $this->examinationsModel->where("id", $examId)->first();
        if (!$examDetails) {
            throw new \InvalidArgumentException("No such exam exists");
        }
        //get the valid index numbers for this exam, making sure all index numbers are in this list
        $registrations = $this->examinationRegistrationsModel->select("index_number, intern_code, uuid")->where("exam_id", $examId)->findAll();
        $validIndexNumbers = [];
        foreach ($registrations as $registration) {
            $validIndexNumbers[$registration['index_number']] = ["intern_code" => $registration['intern_code'], "uuid" => $registration['uuid']];
        }
        $data = [];
        $examScores = $examDetails['scores_names'] ? json_decode($examDetails['scores_names']) : [];
        if ($handle !== false) {
            while (($filesop = fgetcsv($handle, 1000, ",")) !== false) {

                //the first column should contain the index numbers
                $indexNumber = trim(strtoupper($filesop[0]));
                $internCode = $validIndexNumbers[$indexNumber]['intern_code'] ?? null;
                $uuid = $validIndexNumbers[$indexNumber]['uuid'] ?? null;
                if (!$internCode) {
                    throw new \InvalidArgumentException("Index number $indexNumber not found");
                }
                $scores = [];
                $result = "";
                //the other columns should contain the scores in the order of $examScores
                for ($i = 1; $i <= count($examScores); $i++) {
                    $score = trim($filesop[$i]);
                    $scores[] = ["title" => $examScores[$i - 1], "score" => $score];
                }
                //the last one should be the remarks. Pass, Fail or Absent
                try {
                    $resultField = trim($filesop[count($examScores) + 1]);
                    if (strtolower($resultField) === "pass" || strtolower($resultField) === "passed") {
                        $result = "Pass";
                    } elseif (strtolower($resultField) === "fail" || strtolower($resultField) === "failed") {
                        $result = "Fail";
                    } elseif (strtolower($resultField) === "absent") {
                        $result = "Absent";
                    }

                    if (!in_array($result, VALID_EXAMINATION_RESULTS)) {
                        throw new \InvalidArgumentException("Result/remark '$resultField' is not a valid result for $indexNumber. Results must be one of the following: " . implode(", ", VALID_EXAMINATION_RESULTS));
                    }
                } catch (\InvalidArgumentException $th) {
                    throw $th;
                } catch (\Throwable $th) {
                    throw new \InvalidArgumentException("Result/remarks field not found. Please make sure the result/remarks field is the last column in the CSV file");
                }

                $data[] = [
                    "exam_id" => $examId,
                    "index_number" => $indexNumber,
                    "intern_code" => $internCode,
                    "scores" => $scores,
                    "result" => $result,
                    "uuid" => $uuid
                ];
            }
            fclose($handle);
            return $data;
        } else {
            throw new \RuntimeException("Failed to open file");
        }
    }
}