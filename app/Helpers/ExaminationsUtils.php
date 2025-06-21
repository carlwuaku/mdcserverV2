<?php
namespace App\Helpers;

class ExaminationsUtils extends Utils
{
    /**
     * get from the exam_candidates table the state of the intern code
     *if it is 'Apply for examination', then proceed to get the exams which are applicable.
     *applicable exams should have open_from date in the past, and open_to date in the future, the same 'type' as the intern code's practitioner type, and from app settings, the exam type
     * should be one whose required_previous_exam_result[] conditions are met
     * @param mixed $internCode
     * @param bool $useApplicationDates
     * @return array{message: string, exams: array{id:string, uuid:string, exam_type:string, open_from: string, open_to: string, type: string, publish_scores: bool, publish_score_date: string, title: string}[]}
     */
    public static function getValidExaminationsForApplication($internCode, $useApplicationDates = true)
    {
        try {
            $candidate = LicenseUtils::getLicenseDetails($internCode);
            if (array_key_exists('state', $candidate) && $candidate['state'] === 'Apply for examination') {


                //get the exam registrations for the intern code
                $examRegistrations = self::getExaminationRegistrations(['intern_code' => $internCode]);
                //if the person has passed any exam_type, then they cannot apply for the same exam_type again. So we need to filter out those exam types from the applicable exams.
                //also, if there is currently a registration for an exam_type (the result has not been set), then they cannot apply for the same exam_type again. So we need to filter out those exam types from the applicable exams.
                //so basically they can only apply for exam types that they have taken before and failed, or have never taken before.
                /**
                 * @var string[]
                 */
                $excludedExamTypes = [];
                /**
                 * @var string[]
                 */
                $passedExamTypes = [];
                /**
                 * @var string[]
                 */
                $failedExamTypes = [];

                //if there is a registration with no result, then it is pending
                $hasPendingRegistration = false;

                foreach ($examRegistrations as $registration) {
                    if (strtolower($registration['result']) === 'pass' || !$registration['result']) {
                        $excludedExamTypes[] = $registration['exam_type'];
                    }
                    if (strtolower($registration['result']) === 'pass') {
                        $passedExamTypes[] = $registration['exam_type'];
                    } else if (strtolower($registration['result']) === 'fail') {
                        $failedExamTypes[] = $registration['exam_type'];
                    }
                    if (!$registration['result']) {
                        $hasPendingRegistration = true;
                    }
                }
                //if there is a pending registration, then they cannot apply for any exam
                if ($hasPendingRegistration) {
                    $message = "Intern code $internCode has a pending registration. Cannot apply for examination.";
                    log_message('error', $message);
                    throw new \InvalidArgumentException($message);
                }

                //get valid exam types from app settings
                $examSettings = Utils::getAppSettings('examinations');
                $examTypes = $examSettings['examination_types'] ?? [];
                //filter out the excluded exam types
                $allowedExamTypes = self::filterOutArrayByKeys($examTypes, $excludedExamTypes);

                //at this point, we have the allowed exam types. Now we need to check the required_previous_exam_result conditions. we can only allow exam types whose conditions are met.
                $permittedExamTypes = [];
                foreach ($allowedExamTypes as $type => $examType) {

                    /**
                     * @var array{exam_type: string, result: string}[]
                     */
                    $requiredPreviousExamResults = $examType['required_previous_exam_result'] ?? [];
                    //if no requirement, then it is permitted
                    if (empty($requiredPreviousExamResults) || !is_array($requiredPreviousExamResults)) {
                        $permittedExamTypes[] = $type;
                        continue;
                    }
                    //for each required previous exam result, if the required_previous_exam_result is "Pass", then the exam type must be in the passedExamTypes array. If it is "Fail", then the exam type must be in the failedExamTypes array.
                    foreach ($requiredPreviousExamResults as $requirement) {
                        if (strtolower($requirement['result']) === 'pass' && in_array($requirement['exam_type'], $passedExamTypes)) {
                            $permittedExamTypes[] = $type;
                        } elseif (strtolower($requirement['result']) === 'fail' && in_array($requirement['exam_type'], $failedExamTypes)) {
                            $permittedExamTypes[] = $type;
                        }
                    }
                }
                //get exams that are open, of the same practitioner type as the candidate, and are in the permitted exam types
                if (empty($permittedExamTypes)) {
                    $message = "Intern code $internCode has no permitted exam types to apply for examination";
                    log_message('error', $message);
                    throw new \InvalidArgumentException($message);
                }
                $examModel = new \App\Models\Examinations\ExaminationsModel();
                //if useApplicationDates is true, then we filter by the open_from and open_to dates
                if ($useApplicationDates) {
                    $examModel->where('open_from <=', date('Y-m-d'))
                        ->where('open_to >=', date('Y-m-d'));
                }
                $exams = $examModel->where('type', $candidate['practitioner_type'])
                    ->whereIn('exam_type', $permittedExamTypes)
                    ->findAll();
                //get the last query
                $lastQuery = $examModel->getLastQuery();

                return [
                    'message' => 'Apply for examination',
                    'exams' => $exams
                ];
            } else {
                $message = "Intern code $internCode is not in a valid state to apply for examination";
                log_message('error', $message);
                throw new \InvalidArgumentException("Intern code is not in a valid state to apply for examination");
            }
        } catch (\Throwable $th) {
            throw $th;
        }



    }

    /**
     * @param array $filters
     * @return array
     */
    /* 
     * This function retrieves all registrations for an intern code.
     * The function can take an array of filters. The filters should be an associative array with the following keys:
     * index_number, intern_code, exam_id.
     * The function first filters the array of filters by the above keys.
     * It then joins the examination registrations table with the examinations table on the exam_id field.
     * It selects all fields from the examination registrations table and the fields exam_type, open_from, open_to, type, publish_scores, publish_score_date, title from the examinations table.
     * The function then applies the filters and retrieves the records.
     * Finally, it returns the result array.
     */
    public static function getExaminationRegistrations(array $filters = [])
    {
        $filterFields = ["index_number", "intern_code", "exam_id"];
        $filters = self::filterArrayByKeys($filters, $filterFields);
        $model = new \App\Models\Examinations\ExaminationRegistrationsModel();
        $examModel = new \App\Models\Examinations\ExaminationsModel();
        $builder = $model->builder();
        $builder->select("$examModel->table.exam_type, $examModel->table.open_from, $examModel->table.open_to, $examModel->table.type as exam_practitioner_type, $examModel->table.publish_scores, $examModel->table.publish_score_date, $examModel->table.title, $model->table.*");
        foreach ($filters as $key => $value) {
            if (is_array($value)) {
                $builder->whereIn("$model->table.$key", $value);
            } else {
                $builder->where("$model->table.$key", $value);
            }
        }
        $builder->join($examModel->table, "$examModel->table.id = $model->table.exam_id", 'left');
        $result = $builder->get()->getResultArray();
        return $result;
    }

    public static function candidateIsEligibleForExamination(string $internCode, string $examId, bool $useApplicationDates = true): bool
    {
        try {
            $validExams = self::getValidExaminationsForApplication($internCode, $useApplicationDates);
            if ($validExams['message'] !== 'Apply for examination') {
                return false;
            }
            $exams = $validExams['exams'] ?? [];
            //check if the examId is in the exams array
            foreach ($exams as $exam) {
                if ($exam['id'] === $examId || $exam['uuid'] === $examId) {
                    return true;
                }
            }
            return false;
        } catch (\Throwable $th) {
            log_message('error', $th);
            return false;
        }
    }

    /**
     * Generate a registration letter for the candidate based on the UUID.
     * 
     * This function will check if the registration has a letter template, and if so, it will use it.
     * If not, it will use the default letter template for the exam.
     * 
     * @param string $uuid The UUID of the registration
     * @param string $letterType The type of letter to generate. Can be either "registration" or "result".
     * @return string The generated letter
     * @throws \InvalidArgumentException If the registration has no letter template and no exam ID
     * @throws \RuntimeException If there is an error generating the letter
     */
    public static function generateCandidateLetter($uuid, $letterType = "registration")
    {
        // This function should generate a registration letter for the candidate based on the UUID.
        // each exam has different templates for the registration letter. one that applies to all, and others that apply to specific values of the candidate's details. e.g. there may be one set for those who have category set to Dental, specialty set to 1 (any specialty) etc.
        // additionally, each registration also has a registration letter template that is used to generate the letter. if this is null, then we use the exam's letter template. for the exam templates, 
        // we check if there's a letter that's specific to the candidate's details, and if not, we use the default letter template for the exam.

        $templateEngine = new TemplateEngineHelper();
        $registration = self::getExaminationRegistrationDetails($uuid);

        //check if the registration has a letter template
        $candidateLetterName = $letterType === "registration" ? "registration_letter" : "result_letter";
        if (!array_key_exists($candidateLetterName, $registration)) {
            throw new \InvalidArgumentException("No letter template found for registration with UUID: $uuid");
        }
        //if the registration has a letter template, then we use it
        $letterTemplate = $registration[$candidateLetterName] ?? null;
        if (!empty($letterTemplate)) {
            return $templateEngine->process(
                $letterTemplate,
                $registration
            );
        }
        //no registration letter template, so we need to get the exam's letter template
        $examId = $registration['exam_id'] ?? null;
        if (empty($examId)) {
            throw new \InvalidArgumentException("No exam ID found for registration with UUID: $uuid");
        }
        $examLetterTemplates = self::getExaminationLettersWithCriteria($examId, $letterType);
        $selectedLetterTemplate = "No letter template found for this exam. Please contact support for assistance.";
        //if there is a default letter template it will overwrite the above. if there is a more specific one that matches the criteria, then it will be returned.
        log_message('info', print_r($registration, true));
        foreach ($examLetterTemplates as $row) {
            $criteria = $row['criteria'];// json_decode($row['criteria'], true);
            //for each exam letter template type there's one with no criteria, which is the default one.
            //we use it if the others with criteria do not match the registration details.

            if (empty($criteria)) {
                $selectedLetterTemplate = $row['content'];
                continue;
            }

            // Check if the criteria match the registration details
            $criteriaMatch = true;
            foreach ($criteria as $criterion) {

                $field = $criterion['field'] ?? '';
                $values = $criterion['values'] ?? [];
                // Check if the field exists in the registration and if its value is in the allowed values
                log_message('info', print_r($values, true));
                if (!array_key_exists($field, $registration)) {
                    $criteriaMatch = false;
                    break;
                }
                if (array_key_exists($field, $registration) && !in_array($registration[$field], $values)) {
                    $criteriaMatch = false;
                    break;
                }
            }
            // If all criteria match, process the letter template
            if ($criteriaMatch) {
                $selectedLetterTemplate = $row['content'];
                break; // Exit the loop once we find a matching template

            }
        }

        return $templateEngine->process(
            $selectedLetterTemplate,
            $registration
        );
    }

    public static function getExaminationLettersWithCriteria($examId, $letterType = "registration")
    {
        $letterTemplateModel = new \App\Models\Examinations\ExaminationLetterTemplatesModel();
        $letterTemplateCriteriaModel = new \App\Models\Examinations\ExaminationLetterTemplateCriteriaModel();
        $query = $letterTemplateModel->select("
        {$letterTemplateModel->table}.id,
        name,
        exam_id,
        type,
        content,
        CASE 
        WHEN COUNT({$letterTemplateCriteriaModel->table}.letter_id) = 0 THEN NULL
        ELSE CONCAT('[', 
            GROUP_CONCAT(
                JSON_OBJECT('field', {$letterTemplateCriteriaModel->table}.field, 'value', {$letterTemplateCriteriaModel->table}.value)
                SEPARATOR ','
            ), 
        ']')
    END AS criteria
    ")
            ->join("{$letterTemplateCriteriaModel->table}", "{$letterTemplateModel->table}.id = {$letterTemplateCriteriaModel->table}.letter_id", 'left')
            ->where("{$letterTemplateModel->table}.exam_id", $examId);
        if (!empty($letterType)) {
            $query->where("{$letterTemplateModel->table}.type", "$letterType");
        }

        $query->groupBy("{$letterTemplateModel->table}.id");
        $examLetterTemplates = $query->findAll();
        foreach ($examLetterTemplates as &$row) {
            if (!empty($row['criteria'])) {
                $row['criteria'] = json_decode($row['criteria'], true);
                foreach ($row['criteria'] as &$criterion) {
                    if (isset($criterion['value']) && !is_array($criterion['value'])) {
                        $criterion['value'] = json_decode($criterion['value']);
                    }
                }
            } else {
                $row['criteria'] = [];
            }
        }
        return $examLetterTemplates;
    }

    /**
     * Retrieves the details of an examination registration using its UUID.
     *
     * This function queries the examination registrations, examinations, and licenses
     * tables to gather comprehensive information about the registration. It includes
     * details such as exam type, dates, practitioner type, and candidate details like
     * name, qualification, and specialty.
     *
     * @param string $uuid The UUID of the examination registration to retrieve.
     * @return array An associative array containing the registration and candidate details.
     * @throws \InvalidArgumentException If no registration is found for the provided UUID.
     */

    public static function getExaminationRegistrationDetails(string $uuid): array
    {
        // This function retrieves the details of an examination registration by its UUID.
        // It returns an array containing the registration details, including the exam details and candidate information.
        $model = new \App\Models\Examinations\ExaminationRegistrationsModel();
        $examModel = new \App\Models\Examinations\ExaminationsModel();
        $licenseModel = new \App\Models\Licenses\LicensesModel();
        $examCandidatesTable = "exam_candidates";
        $builder = $model->builder();
        $builder->select("$examModel->table.id as exam_id,$examModel->table.exam_type, $examModel->table.open_from, $examModel->table.open_to, $examModel->table.type as exam_practitioner_type, $examModel->table.publish_scores, $examModel->table.publish_score_date, $examModel->table.title, $model->table.*");
        $builder->select("$licenseModel->table.picture,  $licenseModel->table.postal_address, $examCandidatesTable.first_name,$examCandidatesTable.middle_name,$examCandidatesTable.last_name, $examCandidatesTable.qualification,$examCandidatesTable.training_institution, $examCandidatesTable.specialty, $examCandidatesTable.category");
        $builder->join($examModel->table, "$examModel->table.id = $model->table.exam_id", 'left');
        $builder->join($licenseModel->table, "$licenseModel->table.license_number = $model->table.intern_code", 'left');
        $builder->join($examCandidatesTable, "$examCandidatesTable.intern_code = $model->table.intern_code", 'left');
        $builder->where("$model->table.uuid", $uuid);

        $registration = $builder->get()->getRowArray();
        if (!$registration) {
            throw new \InvalidArgumentException("No registration found for UUID: $uuid");


        }
        return $registration;
    }
}