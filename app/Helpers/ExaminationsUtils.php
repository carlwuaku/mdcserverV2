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
    public static function getValidExaminationsForApplication(string $internCode, bool $useApplicationDates = true, bool $restrictIfApplications = true)
    {
        try {
            $candidate = LicenseUtils::getLicenseDetails($internCode);
            if (array_key_exists('state', $candidate) && $candidate['state'] === APPLY_FOR_EXAMINATION) {


                //get the exam registrations for the intern code
                $examRegistrations = self::getExaminationRegistrations(['intern_code' => $internCode]);
                $examApplications = self::getExaminationApplications(['intern_code' => $internCode]);
                //only one application can be accepted at a time
                if (count($examApplications) > 0 && $restrictIfApplications) {
                    $message = "Applicant $internCode has an application under review. Cannot apply for any more examination.";
                    log_message('error', $message);
                    throw new \InvalidArgumentException($message);
                }
                //if the person has passed any exam_type, then they cannot apply for the same exam_type again. So we need to filter out those exam types from the applicable exams.
                //also, if there is currently a registration for an exam_type (the result has not been set), then they cannot apply for the same exam_type again. So we need to filter out those exam types from the applicable exams.
                //so basically they can only apply for exam types that they have taken before and failed or been absent for, or have never taken before.
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
                    } else if (strtolower($registration['result']) === 'fail' || strtolower($registration['result']) === 'absent') {
                        $failedExamTypes[] = $registration['exam_type'];
                    }
                    if (!$registration['result']) {
                        $hasPendingRegistration = true;
                    }
                }
                //if there is a pending registration, then they cannot apply for any exam
                if ($hasPendingRegistration) {
                    $message = "Applicant $internCode has a pending registration. Cannot apply for examination.";
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

                return [
                    'message' => APPLY_FOR_EXAMINATION,
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
        $builder->orderBy('created_at', 'asc');
        $result = $builder->get()->getResultArray();
        return $result;
    }

    /**
     * Retrieves all exam applications.
     * The function takes an array of filters as an argument.
     * The filters should be an associative array with the following keys:
     * intern_code, exam_id.
     * The function first filters the array of filters by the above keys.
     * It then joins the examination applications table with the examinations table on the exam_id field.
     * It selects all fields from the examination applications table and the fields exam_type, open_from, open_to, type, publish_scores, publish_score_date, title from the examinations table.
     * The function then applies the filters and retrieves the records.
     * Finally, it returns the result array.
     * @param array $filters
     * @return array
     */
    public static function getExaminationApplications(array $filters = [])
    {
        $filterFields = ["intern_code", "exam_id"];
        $filters = self::filterArrayByKeys($filters, $filterFields);
        $model = new \App\Models\Examinations\ExaminationApplicationsModel();
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
        $builder->orderBy('created_at', 'asc');
        $result = $builder->get()->getResultArray();
        return $result;
    }

    /**
     * This function checks if a candidate is eligible to apply for a given examination.
     * It takes the intern code and exam ID as parameters and an optional boolean parameter to indicate whether to use the application dates.
     * It first calls the function getValidExaminationsForApplication to get the valid examinations for the given intern code and parameters.
     * It then checks if the examId is in the array of valid examinations.
     * If it is, it returns true, otherwise it throws an InvalidArgumentException.
     * @param string $internCode the intern code of the candidate
     * @param string $examId the ID of the examination
     * @param bool $useApplicationDates whether to use the application dates when checking eligibility
     * @return bool whether the candidate is eligible for the given examination
     * @throws \InvalidArgumentException if the candidate is not eligible for the given examination
     */
    public static function candidateIsEligibleForExamination(string $internCode, string $examId, bool $useApplicationDates = true, bool $restrictIfApplications = true): bool
    {
        try {
            $validExams = self::getValidExaminationsForApplication($internCode, $useApplicationDates, $restrictIfApplications);
            if ($validExams['message'] !== APPLY_FOR_EXAMINATION) {
                throw new \InvalidArgumentException("Intern code is not in a valid state to apply for examination");
            }
            $exams = $validExams['exams'] ?? [];
            //check if the examId is in the exams array
            foreach ($exams as $exam) {
                if ($exam['id'] === $examId || $exam['uuid'] === $examId) {
                    return true;
                }
            }
            throw new \InvalidArgumentException("The candidate is not eligible for this examination. They may be in a different category or have not met the requirements.");
        } catch (\InvalidArgumentException $th) {
            throw $th;
        } catch (\Throwable $th) {
            throw $th;
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
        // This function should generate a registration/result letter for the candidate based on the UUID.
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
        //for result letters, it has to be either a pass or fail. so we need to check if the result is pass or fail. if there's no result, or it's absent, no letter can be generated.
        $examinationLetterType = $letterType === "registration" ? "registration" : "";
        if ($letterType === "result") {
            if ($registration['result'] === "Absent") {
                throw new \InvalidArgumentException("Candidate was marked absent for this examination");
            }
            if ($registration['result'] !== "Pass" && $registration['result'] !== "Fail") {
                throw new \InvalidArgumentException("Result not set for candidate");
            }
            $examinationLetterType = strtolower($registration['result']);
        }
        $examLetterTemplates = self::getExaminationLettersWithCriteria($examId, $examinationLetterType);
        $selectedLetterTemplate = "No letter template found for this exam. Please contact support for assistance.";
        //if there is a default letter template it will overwrite the above. if there is a more specific one that matches the criteria, then it will be returned.
        foreach ($examLetterTemplates as $row) {
            $criteria = $row['criteria'];// json_decode($row['criteria'], true);
            //for each exam letter template type there's one with no criteria, which is the default one.
            //we use it if the others with criteria do not match the registration details.
            if (count($criteria) === 0) {
                $selectedLetterTemplate = $row['content'];
                continue;
            }

            // Check if the criteria match the registration details
            $criteriaMatch = true;
            foreach ($criteria as $criterion) {
                $field = $criterion['field'] ?? '';
                $values = $criterion['value'] ?? [];
                // Check if the field exists in the registration and if its value is in the allowed values.
                //the allowed values may be an integer 1 [1]. this means any value that's not empty should match. if it's [0], 
                //then only empty values should match i.e. empty strings or null. to keep things simple, if the first item in the 
                //values array is 1 or 0, anything else is ignored
                if (!array_key_exists($field, $registration)) {
                    $criteriaMatch = false;
                    break;
                }
                if (count($values) > 0) {
                    if (intval($values[0]) === 1) {
                        if ($registration[$field] === null || trim($registration[$field]) === "") {
                            $criteriaMatch = false;
                            break;
                        }
                        continue;
                    }
                    if (intval($values[0]) === 0) {
                        if (!($registration[$field] === null || trim($registration[$field]) === "")) {
                            $criteriaMatch = false;
                            break;
                        }
                        continue;
                    }
                    if (count($values) > 0 && !in_array($registration[$field], $values)) {
                        $criteriaMatch = false;
                        break;
                    }
                }
            }
            // If all criteria match, process the letter template
            if ($criteriaMatch) {
                $selectedLetterTemplate = $row['content'];
                break; // Exit the loop once we find a matching template

            }
        }
        $content = $templateEngine->process(
            $selectedLetterTemplate,
            $registration
        );


        return self::addLetterStyling($content, "$letterType Letter");
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

    /**
     * Returns the state that a candidate should be in after an examination result is set.
     *
     * The function takes an exam type and a result as parameters and returns the state that the candidate should be in.
     * The state is determined by the configuration setting 'candidate_state_after_result' in the examination settings.
     * The structure of the configuration setting is expected to be an associative array with the keys as the result and the values as the state.
     * The function throws an InvalidArgumentException if the exam type is invalid or the configuration setting is invalid.
     * The function throws a ConfigException if the state is invalid.
     *
     * @param string $examType The type of the exam
     * @param string $result The result of the exam
     * @return string The state that the candidate should be in
     * @throws \InvalidArgumentException If the exam type is invalid or the configuration setting is invalid
     * @throws \CodeIgniter\Exceptions\ConfigException If the state is invalid
     */
    public static function getExamCandidateStateFromExamResult(string $examType, string $result): string
    {
        //get the exam type from app settings.
        $examSettings = Utils::getAppSettings('examinations');
        $examTypes = array_keys($examSettings['examination_types']);
        if (!in_array($examType, $examTypes)) {
            throw new \InvalidArgumentException("Invalid exam type: $examType");
        }
        //get the candidate_state_after_result property
        $examSetting = $examSettings['examination_types'][$examType];
        if (!array_key_exists('candidate_state_after_result', $examSetting)) {
            throw new \InvalidArgumentException("Invalid exam type config for results: $examType");
        }
        $examStateSettings = $examSetting['candidate_state_after_result'];
        /** expected structure of examStateSettings
         * "candidate_state_after_result": {
         *          "Pass": "apply_for_migration",
         *         "Fail": "apply_for_an_examination"
         *    }
         *
         */
        if (!array_key_exists($result, $examStateSettings)) {
            throw new \CodeIgniter\Exceptions\ConfigException("No state defined for result: $result");
        }
        $state = $examStateSettings[$result];
        if (!in_array($state, EXAM_CANDIDATES_VALID_STATES)) {
            throw new \CodeIgniter\Exceptions\ConfigException("Invalid state: $state");
        }
        return $state;
    }

    /**
     * Determine the state of a candidate given their intern code.
     *
     * This function is mostly used when a registration is deleted so we can choose the appropriate state for the candidate.
     * The steps taken are as follows:
     * 1. Look in the practitioners table if the intern code is in there. if it is, set the state to 'migrated'. else proceed
     * 2. Get the exam registrations for the intern code
     * 3. If there's none, set the state to 'apply_for_examination'.
     * 4. If there's a pass, look in the config for what state to set based on the exam type
     * 5. If there's no pass, set the state to 'apply_for_examination'
     *
     * @param string $internCode The intern code to determine the state for
     * @return string The state of the candidate
     * @throws \InvalidArgumentException If the exam type is invalid or the configuration setting is invalid
     * @throws \CodeIgniter\Exceptions\ConfigException If the state is invalid
     */
    public static function determineCandidateState(string $internCode)
    {
        //this would mostly be used when a registration is deleted so we can choose the appropriate state for the candidate

        //1. look in the practitioners table if the intern code is in there. if it is, set the state to 'migrated'. else proceed
        //2. get the exam registrations for the intern code
        //3. if there's none, set the state to 'apply_for_examination'.
        //4. if there's a pass, look in the config for what state to set based on the exam type
        //5. if there's no pass, set the state to 'apply_for_examination'
        try {
            self::getLicenseDetails($internCode, 'intern_code', 'practitioners');
            return MIGRATED;
        } catch (\Exception $th) {
            //not migrated, check for the results
            $examRegistrations = self::getExaminationRegistrations(["intern_code" => $internCode]);
            if (empty($registrations)) {
                return APPLY_FOR_EXAMINATION;
            }

            $lastExam = $examRegistrations[count($examRegistrations) - 1];
            $examType = $lastExam[0]['exam_type'];
            $result = $lastExam[0]['result'];
            $state = self::getExamCandidateStateFromExamResult($examType, $result);
            return $state;
        }



    }
}