<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Helpers\PractitionerUtils;
use App\Models\Applications\ApplicationsModel;
use App\Models\Applications\ApplicationTemplateModel;
use App\Models\Practitioners\PractitionerModel;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;
use App\Models\ActivitiesModel;
use App\Helpers\Utils;
use \Exception;
use App\Helpers\EmailHelper;
use App\Helpers\EmailConfig;
use App\Models\Practitioners\PractitionerRenewalModel;

class ApplicationsController extends ResourceController
{

    private function createFormMetaFromPayload(array $payload, string $form_type): array
    {
        try {
            if (empty($payload)) {
                throw new Exception("Payload cannot be empty");
            }
            if (empty($form_type)) {
                throw new Exception("Form type cannot be empty");
            }

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
            $meta['form_type'] = $form_type;

            return $meta;
        } catch (\Throwable $th) {
            throw $th;
        }
    }
    public function createApplication($form_type)
    {
        try {
            //in case of a new application, the payload is what goes into the form_data field
            //all the other fields, first_ame, etc are generated from the form_data
            $data = $this->createFormMetaFromPayload((array) $this->request->getPost(), $form_type);
            $data['application_code'] = Utils::generateApplicationCode($form_type);
            $data['status'] = "Pending approval";

            $model = new ApplicationsModel();
            $model->insert((object) $data);
            $id = $model->getInsertID();
            /** @var ActivitiesModel $activitiesModel */
            $activitiesModel = new ActivitiesModel();

            $activitiesModel->logActivity("Created application {$data['form_type']} for {$data['first_name']} {$data['last_name']}");
            //if registered this year, retain the person
            return $this->respond(['message' => 'Application created successfully', 'data' => null], ResponseInterface::HTTP_OK);
        } catch (\Throwable $th) {
            log_message('error', $th->getMessage());
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function updateApplication($uuid)
    {
        try {
            $model = new ApplicationsModel();
            //get the form_type from the database
            $application = $model->where(['uuid' => $uuid])->first();
            if (!$application) {
                return $this->respond(['message' => 'Application not found'], ResponseInterface::HTTP_NOT_FOUND);
            }

            $form_type = $application['form_type'];
            $data = (object) ["form_data" => json_encode($this->request->getVar())];
            $data->uuid = $uuid;
            if (property_exists($data, "id")) {
                unset($data->id);
            }
            $oldData = $application;
            $changes = implode(", ", Utils::compareObjects($oldData, $data));
            log_message('info', print_r($data, true));
            if (!$model->builder()->where(['uuid' => $uuid])->update($data)) {
                return $this->respond(['message' => $model->errors()], ResponseInterface::HTTP_BAD_REQUEST);
            }
            /** @var ActivitiesModel $activitiesModel */
            $activitiesModel = new ActivitiesModel();
            $activitiesModel->logActivity("Updated application {$form_type}  {$application['application_code']}. Changes: $changes");

            return $this->respond(['message' => 'Application updated successfully'], ResponseInterface::HTTP_OK);
        } catch (\Throwable $th) {
            log_message('error', $th->getMessage());
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_BAD_REQUEST);
        }
    }

    public function deleteApplication($uuid)
    {
        try {
            $model = new ApplicationsModel();
            $data = $model->where(["uuid" => $uuid])->first();

            if (!$model->where('uuid', $uuid)->delete()) {
                return $this->respond(['message' => $model->errors()], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
            }
            /** @var ActivitiesModel $activitiesModel */
            $activitiesModel = new ActivitiesModel();
            $activitiesModel->logActivity("Deleted application {$data['form_type']}  for {$data['email']}  ");

            return $this->respond(['message' => 'Application deleted successfully'], ResponseInterface::HTTP_OK);
        } catch (\Throwable $th) {
            log_message('error', $th->getMessage());
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function restoreApplication($uuid)
    {
        $model = new ApplicationsModel();
        if (!$model->builder()->where(['uuid' => $uuid])->update(['deleted_at' => null])) {
            return $this->respond(['message' => $model->errors()], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
        $data = $model->where(["uuid" => $uuid])->first();
        /** @var ActivitiesModel $activitiesModel */
        $activitiesModel = new ActivitiesModel();
        $activitiesModel->logActivity("Restored application {$data['form_type']} for {$data['email']} from recycle bin");

        return $this->respond(['message' => 'Application restored successfully'], ResponseInterface::HTTP_OK);
    }

    /**
     * Get Application details by UUID.
     *
     * @param string $uuid The UUID of the Application
     * @return ApplicationsModel|null The Application data if found, null otherwise
     * @throws Exception If Application is not found
     */
    private function getApplicationDetails(string $uuid): array|object|null
    {
        $model = new ApplicationsModel();
        $builder = $model->builder();
        $builder->where('uuid', $uuid);
        $data = $model->first();
        if (!$data) {
            throw new Exception("Application not found");
        }
        $data['form_data'] = json_decode($data['form_data'], true);
        return $data;
    }

    public function getApplication($uuid)
    {
        $model = new ApplicationsModel();
        $data = $this->getApplicationDetails($uuid);
        if (!$data) {
            return $this->respond(['message' => "Application not found"], ResponseInterface::HTTP_NOT_FOUND);
        }
        return $this->respond(['data' => $data, 'displayColumns' => $model->getDisplayColumns()], ResponseInterface::HTTP_OK);
    }

    public function getApplications()
    {
        try {
            $per_page = $this->request->getVar('limit') ? (int) $this->request->getVar('limit') : 100;
            $page = $this->request->getVar('page') ? (int) $this->request->getVar('page') : 0;
            $withDeleted = $this->request->getVar('withDeleted') && $this->request->getVar('withDeleted') === "yes";
            $param = $this->request->getVar('param');
            $sortBy = $this->request->getVar('sortBy') ?? "id";
            $sortOrder = $this->request->getVar('sortOrder') ?? "asc";
            $application_code = $this->request->getGet('application_code');
            $status = $this->request->getGet('status');
            $start_date = $this->request->getGet('start_date');
            $end_date = $this->request->getGet('end_date');
            $practitioner_type = $this->request->getGet('practitioner_type');
            $form_type = $this->request->getGet('form_type');

            $model = new ApplicationsModel();

            $builder = $param ? $model->search($param) : $model->builder();
            $builder = $model->addCustomFields($builder);
            $builder->orderBy("$sortBy", $sortOrder);
            if ($application_code !== null) {
                $builder->where('application_code', $application_code);
            }
            if ($status !== null) {
                $builder->where('status', $status);
            }
            if ($start_date !== null) {
                $builder->where('created_on >=', $start_date);
            }
            if ($end_date !== null) {
                $builder->where('created_on <=', $end_date);
            }
            if ($practitioner_type !== null) {
                $builder->where('practitioner_type', $practitioner_type);
            }
            if ($form_type !== null) {
                $builder->where('form_type', $form_type);
            }

            // if ($withDeleted) {
            //     $model->withDeleted();
            // }
            $totalBuilder = clone $builder;
            $total = $totalBuilder->countAllResults();
            $result = $builder->get($per_page, $page)->getResult();
            $final = [];
            $displayColumns = [];//try to get the display columns from the form data if available. else fallback to models display columns
            foreach ($result as $value) {
                //convert json string in form_data to json object
                $form_data = json_decode($value->form_data, true);
                $form_data['picture'] = $value->picture;

                $form_data['uuid'] = $value->uuid;
                $form_data['status'] = $value->status;
                $form_data['created_on'] = $value->created_on;
                $form_data['form_type'] = $value->form_type;
                $form_data['practitioner_type'] = $value->practitioner_type;
                if (empty($displayColumns)) {
                    $displayColumns = array_keys($form_data);
                    $primaryColumns = ['picture', 'practitioner_type', 'status', 'form_type', 'created_on'];
                    foreach ($primaryColumns as $col) {
                        if (array_key_exists($col, $form_data)) {
                            //if the picture key exists, move it to the first position
                            //move it to the first position
                            $pictureIndex = array_search($col, $displayColumns);
                            unset($displayColumns[$pictureIndex]);
                            array_unshift($displayColumns, $col);
                        }
                    }

                }
                // $value = array_replace((array) $value, $form_data);
                //convert json object in form_data to array
                $final[] = $form_data;
            }


            return $this->respond([
                'data' => $final,
                'total' => $total,
                'displayColumns' => $displayColumns ?? $model->getDisplayColumns(),
                'columnFilters' => $model->getDisplayColumnFilters()
            ], ResponseInterface::HTTP_OK);
        } catch (\Throwable $th) {
            log_message('error', $th->getMessage());
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getApplicationFormTypes($field)
    {
        $model = new ApplicationsModel();
        $formTypes = $model->getDistinctValuesAsKeyValuePairs($field);
        return $this->respond(['data' => $formTypes], ResponseInterface::HTTP_OK);
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
            $param = $this->request->getVar('param');
            $model = new ApplicationsModel();
            $application_code = $this->request->getGet('application_code');
            $status = $this->request->getGet('status');
            $start_date = $this->request->getGet('start_date');
            $end_date = $this->request->getGet('end_date');
            $practitioner_type = $this->request->getGet('practitioner_type');
            $form_type = $this->request->getGet('form_type');
            // Validate inputs here

            $builder = $param ? $model->search($param) : $model->builder();
            if ($application_code !== null) {
                $builder->where('application_code', $application_code);
            }
            if ($status !== null) {
                $builder->where('status', $status);
            }
            if ($start_date !== null) {
                $builder->where('created_on >=', $start_date);
            }
            if ($end_date !== null) {
                $builder->where('created_on <=', $end_date);
            }
            if ($practitioner_type !== null) {
                $builder->where('practitioner_type', $practitioner_type);
            }
            if ($form_type !== null) {
                $builder->where('form_type', $form_type);
            }

            $total = $builder->countAllResults();
            return $this->respond([
                'data' => $total
            ], ResponseInterface::HTTP_OK);
        } catch (\Throwable $th) {
            log_message("error", $th->getMessage());
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Approve a permanent application.
     *
     * @param array $applicationDetails The details of the application
     * @return array{message: string, status: int} The response message and status
     */
    private function approvePermanentApplication(array $applicationDetails)
    {
        try {
            //start a transaction
            $today = date("Y-m-d");
            $model = new ApplicationsModel();
            $practitionerModel = new PractitionerModel();
            $renewalModel = new PractitionerRenewalModel();
            $registration_number = $this->request->getVar('registration_number');

            $model->builder()->where(['uuid' => $applicationDetails['uuid']])->update(['status' => "approved"]);
            $formData = json_decode($applicationDetails['form_data'], true);

            $practitionerData = $practitionerModel->createArrayFromAllowedFields($formData);
            $practitionerData['register_type'] = "Permanent";
            $practitionerData['practitioner_type'] = $formData['type'];
            $practitionerData['year_of_permanent'] = $today;
            $practitionerData['year_of_provisional'] = $formData["date_of_provisional"];
            $practitionerData['registration_date'] = $today;
            $practitionerData['registration_number'] = $registration_number;
            $practitionerData['qualification_at_registration'] = $formData["qualification"];
            $practitionerData['qualification_date'] = $formData["date_of_graduation"];
            $practitionerData['status'] = 1;
            $practitionerModel->db->transException(true)->transStart();
            $existingPractitionerBuilder = $practitionerModel->builder()->where(['registration_number' => $formData["provisional_registration_number"]]);

            if ($existingPractitionerBuilder->countAllResults() > 0) {
                $existingPractitionerBuilder->update($practitionerData);
                $practitionerId = $practitionerModel->first()['id'];
            } else {


            }
            $practitioner = $practitionerModel->find($practitionerId);

            $model->delete($applicationDetails['id']);


            $retentionYear = date("Y");
            //save the documents to the documents model
            //create a retention record with the qr code
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
            $practitionerModel->db->transComplete();

            return ['message' => 'Application approved successfully', 'status' => ResponseInterface::HTTP_OK];
        } catch (\Throwable $th) {
            log_message("error", $th->getMessage());
            return ['message' => 'Server error: ' . $th->getMessage(), 'status' => ResponseInterface::HTTP_INTERNAL_SERVER_ERROR];
        }
    }

    /**
     * Approve a temporary application.
     *
     * @param array $applicationDetails The details of the application
     * @return array{message: string, status: int} The response message and status
     */
    private function approveTemporaryApplication(array $applicationDetails)
    {
        try {
            //start a transaction
            $today = date("Y-m-d");
            $model = new ApplicationsModel();
            $practitionerModel = new PractitionerModel();
            $renewalModel = new PractitionerRenewalModel();
            $registration_number = $this->request->getVar('registration_number');

            $model->builder()->where(['uuid' => $applicationDetails['uuid']])->update(['status' => "approved"]);
            $formData = json_decode($applicationDetails['form_data'], true);

            $practitionerData = $practitionerModel->createArrayFromAllowedFields($formData);
            $practitionerData['register_type'] = "Temporary";
            $practitionerData['practitioner_type'] = $formData['type'];
            $practitionerData['year_of_provisional'] = $today;
            $practitionerData['registration_date'] = $today;
            $practitionerData['registration_number'] = $registration_number;
            $practitionerData['qualification_at_registration'] = $formData["qualification"];
            $practitionerData['qualification_date'] = $formData["date_of_graduation"];
            $practitionerData['status'] = 1;
            $practitionerModel->db->transException(true)->transStart();

            $practitionerId = $practitionerModel->insert((object) $practitionerData);

            $practitioner = $practitionerModel->find($practitionerId);

            $model->delete($applicationDetails['id']);


            $retentionYear = date("Y");
            //save the documents to the documents model
            //create a retention record with the qr code
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
            $practitionerModel->db->transComplete();

            return ['message' => 'Application updated successfully', 'status' => ResponseInterface::HTTP_OK];
        } catch (\Throwable $th) {
            log_message("error", $th->getMessage());
            return ['message' => 'Server error: ' . $th->getMessage(), 'status' => ResponseInterface::HTTP_INTERNAL_SERVER_ERROR];
        }
    }

    private function approveProvisionalApplication(array $applicationDetails)
    {
        try {
            //start a transaction
            $today = date("Y-m-d");
            $model = new ApplicationsModel();
            $practitionerModel = new PractitionerModel();
            $renewalModel = new PractitionerRenewalModel();
            $registration_number = $this->request->getVar('registration_number');

            $model->builder()->where(['uuid' => $applicationDetails['uuid']])->update(['status' => "approved"]);
            $formData = json_decode($applicationDetails['form_data'], true);

            $practitionerData = $practitionerModel->createArrayFromAllowedFields($formData);
            $practitionerData['register_type'] = "Provisional";
            $practitionerData['practitioner_type'] = $formData['type'];
            $practitionerData['year_of_provisional'] = $today;
            $practitionerData['registration_date'] = $today;
            $practitionerData['registration_number'] = $registration_number;
            $practitionerData['qualification_at_registration'] = $formData["qualification"];
            $practitionerData['qualification_date'] = $formData["date_of_graduation"];
            $practitionerData['status'] = 1;
            $practitionerModel->db->transException(true)->transStart();

            $practitionerId = $practitionerModel->insert((object) $practitionerData);

            $practitioner = $practitionerModel->find($practitionerId);

            $model->delete($applicationDetails['id']);


            $retentionYear = date("Y");
            //save the documents to the documents model
            //create a retention record with the qr code
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
            $practitionerModel->db->transComplete();

            return ['message' => 'Application updated successfully', 'status' => ResponseInterface::HTTP_OK];
        } catch (\Throwable $th) {
            log_message("error", $th->getMessage());
            return ['message' => 'Server error: ' . $th->getMessage(), 'status' => ResponseInterface::HTTP_INTERNAL_SERVER_ERROR];
        }
    }




    private function approvePortalEdit(array $applicationDetails)
    {
        /**
         * data: field, value, reg_num, action, attachments, revalidate
         * update based on field and action
         */
        try {
            //start a transaction
            $today = date("Y-m-d");
            $model = new ApplicationsModel();
            $practitionerModel = new PractitionerModel();
            $field = $applicationDetails['field'];
            $value = $applicationDetails['value'];
            $registration_number = $applicationDetails['reg_num'];
            $action = $applicationDetails['action'];
            $attachments = $applicationDetails['attachments'];
            $revalidate = $applicationDetails['revalidate'];
            $updateData = [];
            $act = "";
            switch ($field) {
                case 'picture':
                    //check if the picture exists in application attachments folder in writable path
                    //if it does, move it to the practitioner folder
                    //update the practitioner picture field with the new path
                    try {
                        $registration_number_no_spaces = str_replace(" ", "_", $registration_number);
                        $origin = WRITEPATH . UPLOADS_FOLDER . DIRECTORY_SEPARATOR . APPLICATIONS_ASSETS_FOLDER . DIRECTORY_SEPARATOR . $value;
                        $file = new \CodeIgniter\Files\File($origin, true);
                        $destination_file_name = microtime() . $registration_number_no_spaces . $file->guessExtension();

                        $destination = WRITEPATH . UPLOADS_FOLDER . DIRECTORY_SEPARATOR . PRACTITIONERS_ASSETS_FOLDER . DIRECTORY_SEPARATOR . $destination_file_name;
                        copy($origin, $destination);
                        $updateData['picture'] = $destination_file_name;
                        $act = "updated picture of $registration_number in response to web request ";
                        //TODO: save the previous value to archive
                        //TODO: save all the attachments to the documents model
                    } catch (\Throwable $th) {
                        throw $th;
                    }
                    break;
                case "qualification":
                    $additionalQualificationModel = new \App\Models\Practitioners\PractitionerAdditionalQualificationsModel();
                    $qualification = $additionalQualificationModel->find($value);
                    $additionalQualificationModel->delete($value);
                    $act = "deleted certificate: {$qualification->qualification} ({$qualification->institution}) from profile of $registration_number in response to web request ";

                    break;


                //in case of work_history, it can only be dropped from here
                case "work_history":
                    $workHistoryModel = new \App\Models\Practitioners\PractitionerWorkHistoryModel();
                    $details = $workHistoryModel->find($value);
                    $workHistoryModel->delete($value);
                    $act = "deleted work history: {$details->position} at ({$details->institution}) from profile of $registration_number in response to web request ";
                    break;

                default:
                    $updateData[$field] = $value;
                    break;
            }
            if ($revalidate) {
                $updateData['require_revalidation'] = "no";
                $updateData['last_revalidation_date'] = date("Y-m-d");
            }
            if (!empty($updateData)) {
                $practitionerModel->where(['registration_number' => $registration_number])->update($updateData);
            }
            /** @var ActivitiesModel $activitiesModel */
            $activitiesModel = new ActivitiesModel();
            $activitiesModel->logActivity($act);



            return ['message' => 'Application updated successfully', 'status' => ResponseInterface::HTTP_OK];
        } catch (\Throwable $th) {
            log_message("error", $th->getMessage());
            return ['message' => 'Server error: ' . $th->getMessage(), 'status' => ResponseInterface::HTTP_INTERNAL_SERVER_ERROR];
        }

    }


    public function finishApplication(string $uuid, string $decision)
    {
        try {
            $comments = $this->request->getVar('comments');
            //the decision is either approve or deny
            if ($decision !== "approve" && $decision !== "deny") {
                return $this->respond(['message' => 'Invalid decision'], ResponseInterface::HTTP_BAD_REQUEST);
            }
            $data = $this->getApplicationDetails($uuid);
            $emailTemplate = $this->request->getVar("email_template");
            if (!$data) {
                return $this->respond(['message' => "Application not found"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
            }
            $result = [];
            if ($decision === "approve") {

                switch ($data['form_type']) {
                    case 'Practitioners Permanent Registration Application':
                        $result = $this->approvePermanentApplication($data);

                        break;
                    case 'Practitioners Temporary Registration Application':
                        $result = $this->approveTemporaryApplication($data);

                        break;
                    case 'Practitioners Provisional Registration Application':
                        $result = $this->approveProvisionalApplication($data);

                        break;

                    case 'Practitioners Portal Edit':
                        $result = $this->approvePortalEdit($data);

                        break;
                    default:

                        //update the status and send an email if one was provided
                        $model = new ApplicationsModel();
                        $model->builder()->where(['uuid' => $uuid])->update(['status' => "approved"]);
                        $result = ['message' => 'Application updated successfully', 'status' => ResponseInterface::HTTP_OK];
                }
            } else {
                $model = new ApplicationsModel();
                $model->builder()->where(['uuid' => $uuid])->update(['status' => "denied"]);
                $result = ['message' => 'Application denied successfully', 'status' => ResponseInterface::HTTP_OK];
            }
            /** @var ActivitiesModel $activitiesModel */
            $activitiesModel = new ActivitiesModel();
            $activitiesModel->logActivity("Completed application {$data['form_type']} for {$data['email']} with decision: {$decision}, comments: $comments");


            if (trim($emailTemplate)) {
                $formName = $data['form_type'];
                $subject = $formName;
                $receiver = $data['email'];
                //TODO: : implement fill template in utils
                $message = $emailTemplate;
                $emailConfig = new EmailConfig($message, $subject, $receiver);
                EmailHelper::sendEmail($emailConfig);
            }
            return $this->respond(['message' => $result['message']], $result['status']);
        } catch (\Throwable $th) {
            log_message('error', $th->getMessage());
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }



    public function getApplicationConfig(string $form_name, string $type = null)
    {
        try {
            //get the form-settings.json file and get the config for the specified form
            $form = str_replace(" ", "-", $form_name);
            $configContents = file_get_contents(WRITEPATH . 'config_files/form-settings.json');
            $config = json_decode($configContents, true);
            $formConfig = !empty($type) ? $config[$form][$type] : $config[$form];
            return $this->respond(['data' => $formConfig], ResponseInterface::HTTP_OK);
        } catch (\Throwable $th) {
            log_message('error', $th->getMessage());
            return $this->respond(['message' => "Server error:" . $th->getMessage()], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }


    }

    public function getApplicationTemplates()
    {
        try {
            $per_page = $this->request->getVar('limit') ? (int) $this->request->getVar('limit') : 100;
            $page = $this->request->getVar('page') ? (int) $this->request->getVar('page') : 0;
            $withDeleted = $this->request->getVar('withDeleted') && $this->request->getVar('withDeleted') === "yes";
            $param = $this->request->getVar('param');
            $sortBy = $this->request->getVar('sortBy') ?? "id";
            $sortOrder = $this->request->getVar('sortOrder') ?? "asc";

            $model = new ApplicationTemplateModel();

            $builder = $param ? $model->search($param) : $model->builder();
            $builder = $model->addCustomFields($builder);
            $builder->orderBy("$sortBy", $sortOrder);

            if ($withDeleted) {
                $model->withDeleted();
            }
            $totalBuilder = clone $builder;
            $total = $totalBuilder->countAllResults();
            $result = $builder->get($per_page, $page)->getResult();
            foreach ($result as $value) {
                //convert json string in form_data to json object
                $value->data = json_decode($value->data, true);

            }

            return $this->respond([
                'data' => $result,
                'total' => $total,
                'displayColumns' => $model->getDisplayColumns()
            ], ResponseInterface::HTTP_OK);
        } catch (\Throwable $th) {
            log_message('error', $th->getMessage());
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    private function getApplicationTemplateDetails(string $uuid): array|object|null
    {
        $model = new ApplicationTemplateModel();
        $builder = $model->builder();
        $builder->where('uuid', $uuid)->orWhere('form_name', $uuid);
        $data = $model->first();
        if (!$data) {
            throw new Exception("Application template not found");
        }
        $data['data'] = json_decode($data['data'], true);
        return $data;
    }

    public function getApplicationTemplate($uuid)
    {
        try {
            $data = $this->getApplicationTemplateDetails($uuid);
            return $this->respond(['data' => $data], ResponseInterface::HTTP_OK);
        } catch (\Throwable $th) {
            log_message('error', $th->getMessage());
            return $this->respond(['message' => "Application not found"], ResponseInterface::HTTP_NOT_FOUND);
        }
    }

    public function createApplicationTemplate()
    {
        try {
            $rules = [
                "form_name" => "required",
                "data" => "required",
                "open_date" => "permit_empty|valid_date",
                'close_date' => "permit_empty|valid_date",

            ];

            if (!$this->validate($rules)) {
                return $this->respond($this->validator->getErrors(), ResponseInterface::HTTP_BAD_REQUEST);
            }
            $data = $this->request->getPost();
            $model = new ApplicationTemplateModel();
            if (!$model->insert($data)) {
                log_message('error', $model->errors()["message"]);
                return $this->respond(['message' => 'Server error. Please try again'], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
            }
            $id = $model->getInsertID();
            /** @var ActivitiesModel $activitiesModel */
            $activitiesModel = new ActivitiesModel();

            $activitiesModel->logActivity("Created application template {$data['form_name']} ");
            //if registered this year, retain the person
            return $this->respond(['message' => 'Application template created successfully', 'data' => $id], ResponseInterface::HTTP_OK);
        } catch (\Throwable $th) {
            log_message('error', $th->getMessage());
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function updateApplicationTemplate($uuid)
    {
        try {
            $rules = [
                "id" => "required",
            ];

            if (!$this->validate($rules)) {
                return $this->respond($this->validator->getErrors(), ResponseInterface::HTTP_BAD_REQUEST);
            }
            $data = $this->request->getVar();
            $data->uuid = $uuid;
            if (property_exists($data, "id")) {
                unset($data->id);
            }
            $model = new ApplicationTemplateModel();
            $oldData = $model->where(["uuid" => $uuid])->first();
            $changes = implode(", ", Utils::compareObjects($oldData, $data));
            if (!$model->builder()->where(['uuid' => $uuid])->update($data)) {
                return $this->respond(['message' => $model->errors()], ResponseInterface::HTTP_BAD_REQUEST);
            }
            /** @var ActivitiesModel $activitiesModel */
            $activitiesModel = new ActivitiesModel();
            $activitiesModel->logActivity("Updated application template. Changes: $changes");

            return $this->respond(['message' => 'Application template updated successfully'], ResponseInterface::HTTP_OK);
        } catch (\Throwable $th) {
            log_message('error', $th->getMessage());
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function deleteApplicationTemplate($uuid)
    {
        try {
            $model = new ApplicationTemplateModel();
            $data = $model->where(["uuid" => $uuid])->first();

            if (!$model->where('uuid', $uuid)->delete()) {
                return $this->respond(['message' => $model->errors()], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
            }
            /** @var ActivitiesModel $activitiesModel */
            $activitiesModel = new ActivitiesModel();
            $activitiesModel->logActivity("Deleted application template {$data['form_name']}  ");

            return $this->respond(['message' => 'Application template deleted successfully'], ResponseInterface::HTTP_OK);
        } catch (\Throwable $th) {
            log_message('error', $th->getMessage());
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
