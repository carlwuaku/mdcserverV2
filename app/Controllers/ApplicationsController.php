<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\Applications\ApplicationsModel;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;
use App\Models\ActivitiesModel;
use App\Helpers\Utils;
use \Exception;

class ApplicationsController extends ResourceController
{
    public function createApplication()
    {
        try {
            $rules = [
                "first_name" => "required",
                "last_name" => "required",
                "form_data" => "required",
                'practitioner_type' => "required",
                'form_type' => "required",
                'email' => "required",
                'phone' => "required"

            ];

            if (!$this->validate($rules)) {
                return $this->respond($this->validator->getErrors(), ResponseInterface::HTTP_BAD_REQUEST);
            }
            $data = $this->request->getPost();
            $model = new ApplicationsModel();
            if (!$model->insert($data)) {
                log_message('error', $model->errors());
                return $this->respond(['message' => 'Server error. Please try again'], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
            }
            $id = $model->getInsertID();
            /** @var ActivitiesModel $activitiesModel */
            $activitiesModel = new ActivitiesModel();

            $activitiesModel->logActivity("Created application {$data['application_type']} for {$data['first_name']} {$data['last_name']}");
            //if registered this year, retain the person
            return $this->respond(['message' => 'Application created successfully', 'data' => $id], ResponseInterface::HTTP_OK);
        } catch (\Throwable $th) {
            log_message('error', $th->getMessage());
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function updateApplication($uuid)
    {
        try{
        $rules = [
            "uuid" => "required",
        ];

        if (!$this->validate($rules)) {
            return $this->respond($this->validator->getErrors(), ResponseInterface::HTTP_BAD_REQUEST);
        }
        $data = $this->request->getVar();
        $data->uuid = $uuid;
        if (property_exists($data, "id")) {
            unset($data->id);
        }
        $model = new ApplicationsModel();
        $oldData = $model->where(["uuid" => $uuid])->first();
        $changes = implode(", ", Utils::compareObjects($oldData, $data));
        if (!$model->builder()->where(['uuid' => $uuid])->update($data)) {
            return $this->respond(['message' => $model->errors()], ResponseInterface::HTTP_BAD_REQUEST);
        }
        /** @var ActivitiesModel $activitiesModel */
        $activitiesModel = new ActivitiesModel();
        $activitiesModel->logActivity("Updated application {$data['application_type']}  {$oldData['email']}. Changes: $changes");

        return $this->respond(['message' => 'Application updated successfully'], ResponseInterface::HTTP_OK);
    } catch (\Throwable $th) {
        log_message('error', $th->getMessage());
        return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
    }
    }

    public function deleteApplication($uuid)
    {
        try{
        $model = new ApplicationsModel();
        $data = $model->where(["uuid" => $uuid])->first();

        if (!$model->where('uuid', $uuid)->delete()) {
            return $this->respond(['message' => $model->errors()], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
        /** @var ActivitiesModel $activitiesModel */
        $activitiesModel = new ActivitiesModel();
        $activitiesModel->logActivity("Deleted application {$data['application_type']}  for {$data['email']}  ");

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
        $activitiesModel->logActivity("Restored application {$data['application_type']} for {$data['email']} from recycle bin");

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
        $builder->where( '.uuid', $uuid);
        $data = $model->first();
        if (!$data) {
            throw new Exception("Application not found");
        }
        return $data;
    }

    public function getApplication($uuid)
    {
        $model = new ApplicationsModel();
        $data = $this->getApplicationDetails($uuid);
        if (!$data) {
            return $this->respond("Application not found", ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
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

            if ($withDeleted) {
                $model->withDeleted();
            }
            $totalBuilder = clone $builder;
            $total = $totalBuilder->countAllResults();
            $result = $builder->get($per_page, $page)->getResult();
            foreach ($result as $value) {
                //convert json string in form_data to json object
                $value->form_data = json_decode($value->form_data);
                $value->picture = $value->form_data->picture;
            }
            return $this->respond([
                'data' => $result,
                'total' => $total,
                'displayColumns' => $model->getDisplayColumns()
            ], ResponseInterface::HTTP_OK);
        } catch (\Throwable $th) {
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
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
            log_message("debug", $total);

            return $this->respond([
                'data' => $total
            ], ResponseInterface::HTTP_OK);
        } catch (\Throwable $th) {
            log_message("error", $th->getMessage());
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
