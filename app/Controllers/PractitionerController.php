<?php

namespace App\Controllers;

use App\Models\PractitionerAdditionalQualificationsModel;
use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\HTTP\ResponseInterface;
use App\Models\PractitionerModel;
use App\Helpers\AppConstants;

class PractitionerController extends ResourceController
{

    public function createPractitioner()
    {
        $rules = [
            "registration_number" => "required|is_unique[practitioners.registration_number]",
            "last_name" => "required",
            "date_of_birth" => "required|valid_date",
            "sex" => "required",

        ];

        if (!$this->validate($rules)) {
            return $this->respond($this->validator->getErrors(), ResponseInterface::HTTP_BAD_REQUEST);
        }
        $data = $this->request->getPost();
        $model = new PractitionerModel();
        //get only the last part of the picture path
        if(property_exists($data, "picture")) {
            $splitPicturePath = explode("/", $data->picture);
            $data->picture =  array_pop($splitPicturePath);
        }
        if (!$model->insert($data)) {
            return $this->respond(['message' => $model->errors()], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
        $id = $model->getInsertID();
        //if registered this year, retain the person
        return $this->respond(['message' => 'Practitioner created successfully', 'data' => $id], ResponseInterface::HTTP_OK);
    }

    public function updatePractitioner($uuid)
    {
        $rules = [
            "registration_number" => "if_exist|is_unique[practitioners.registration_number,uuid,$uuid]",
            "uuid" => "required",
            "date_of_birth" => "if_exist|required|valid_date",

        ];

        if (!$this->validate($rules)) {
            return $this->respond($this->validator->getErrors(), ResponseInterface::HTTP_BAD_REQUEST);
        }
        $data = $this->request->getVar();
        $data->uuid = $uuid;
        if(property_exists($data, "id")) {unset($data->id);}
        //get only the last part of the picture path
        if(property_exists($data, "picture")) {
            $splitPicturePath = explode("/", $data->picture);
            $data->picture =  array_pop($splitPicturePath);
        }
        $model = new PractitionerModel();
        if (!$model->builder()->where(['uuid' => $uuid])->update($data)) {
            return $this->respond(['message' => $model->errors()], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
        return $this->respond(['message' => 'Practitioner updated successfully'], ResponseInterface::HTTP_OK);
    }

    public function deletePractitioner($uuid)
    {
        $model = new PractitionerModel();
        if (!$model->where('uuid', $uuid)->delete()) {
            return $this->respond(['message' => $model->errors()], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
        return $this->respond(['message' => 'Practitioner deleted successfully'], ResponseInterface::HTTP_OK);
    }

    public function restorePractitioner($uuid)
    {
        $model = new PractitionerModel();
        if (!$model->builder()->where(['uuid' => $uuid])->update(['deleted_at' => null])) {
            return $this->respond(['message' => $model->errors()], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
        return $this->respond(['message' => 'Practitioner restored successfully'], ResponseInterface::HTTP_OK);
    }

    public function getPractitioner($uuid)
    {
        $model = new PractitionerModel();
         $builder = $model->builder();
        $builder = $model->addCustomFields($builder);
        $builder->where($model->getTableName().'.uuid', $uuid);
        $data =$model->first();
        if (!$data) {
            return $this->respond("Practitioner not found", ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
        return $this->respond(['data' => $data, 'displayColumns' => $model->getDisplayColumns()], ResponseInterface::HTTP_OK);
    }

    public function getPractitioners()
    {
        try {
            $per_page = $this->request->getVar('limit') ? (int) $this->request->getVar('limit') : 100;
            $page = $this->request->getVar('page') ? (int) $this->request->getVar('page') : 0;
            $withDeleted = $this->request->getVar('withDeleted') && $this->request->getVar('withDeleted') === "yes";
            $param = $this->request->getVar('param');
            $model = new PractitionerModel();
            
            $builder = $param ? $model->search($param) : $model->builder();
            $builder = $model->addCustomFields($builder);
            
            if ($withDeleted) {
                $model->withDeleted();
            }
            $totalBuilder = clone $builder;
            $total = $totalBuilder->countAllResults();
            $result = $builder->get($per_page, $page)->getResult();
            return $this->respond(['data' => $result, 'total' => $total,
                'displayColumns' => $model->getDisplayColumns()
            ], ResponseInterface::HTTP_OK);
        } catch (\Throwable $th) {
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getPractitionerQualifications()
    {
        try {
            $per_page = $this->request->getVar('limit') ? (int) $this->request->getVar('limit') : 100;
            $page = $this->request->getVar('page') ? (int) $this->request->getVar('page') : 0;
            $withDeleted = $this->request->getVar('withDeleted') && $this->request->getVar('withDeleted') === "yes";
            $param = $this->request->getVar('param');

            $model = new PractitionerAdditionalQualificationsModel();
            $registration_number = $this->request->getGet('registration_number');
            $builder = $param ? $model->search($param) : $model->builder();
            if($registration_number !== null){
                $builder->where(["registration_number" => $registration_number]);
            }            
            if ($withDeleted) {
                $model->withDeleted();
            }
            $totalBuilder = clone $builder;
            $total = $totalBuilder->countAllResults();
            $result = $builder->get($per_page, $page)->getResult();
            return $this->respond(['data' => $result, 'total' => $total,
                'displayColumns' => $model->getDisplayColumns()
            ], ResponseInterface::HTTP_OK);
        } catch (\Throwable $th) {
            log_message("error", $th->getMessage());
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getPractitionerQualification($uuid)
    {
        $model = new PractitionerAdditionalQualificationsModel();
         $builder = $model->builder();
        $builder->where($model->getTableName().'.uuid', $uuid);
        $data =$model->first();
        if (!$data) {
            return $this->respond("Practitioner qualification not found", ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
        return $this->respond(['data' => $data, 'displayColumns' => $model->getDisplayColumns()], ResponseInterface::HTTP_OK);
    }

    public function createPractitionerQualification()
    {
        $rules = [
            "registration_number" => "required",
            "institution" => "required",
            "qualification" => "required"

        ];

        if (!$this->validate($rules)) {
            return $this->respond($this->validator->getErrors(), ResponseInterface::HTTP_BAD_REQUEST);
        }
        $data = $this->request->getPost();
        $model = new PractitionerAdditionalQualificationsModel();
        
        if (!$model->insert($data)) {
            return $this->respond(['message' => $model->errors()], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
        $id = $model->getInsertID();
        //if registered this year, retain the person
        return $this->respond(['message' => 'Additional qualification created successfully', 'data' => $id], ResponseInterface::HTTP_OK);
    }

    public function updatePractitionerQualification($uuid)
    {
        $rules = [
            "registration_number" => "required",
            "institution" => "required",
            "qualification" => "required"

        ];

        if (!$this->validate($rules)) {
            return $this->respond($this->validator->getErrors(), ResponseInterface::HTTP_BAD_REQUEST);
        }
        $data = $this->request->getVar();
        $data->uuid = $uuid;
        if(property_exists($data, "id")) {unset($data->id);}
        //get only the last part of the picture path
        if(property_exists($data, "picture")) {
            $splitPicturePath = explode("/", $data->picture);
            $data->picture =  array_pop($splitPicturePath);
        }
        $model = new PractitionerAdditionalQualificationsModel();
        if (!$model->builder()->where(['uuid' => $uuid])->update($data)) {
            return $this->respond(['message' => $model->errors()], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
        return $this->respond(['message' => 'Practitioner additional qualification updated successfully'], ResponseInterface::HTTP_OK);
    }

    public function deletePractitionerQualification($uuid)
    {
        $model = new PractitionerAdditionalQualificationsModel();
        if (!$model->where('uuid', $uuid)->delete()) {
            return $this->respond(['message' => $model->errors()], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
        return $this->respond(['message' => 'Practitioner additional qualification deleted successfully'], ResponseInterface::HTTP_OK);
    }

    public function restorePractitionerQualification($uuid)
    {
        $model = new PractitionerAdditionalQualificationsModel();
        if (!$model->builder()->where(['uuid' => $uuid])->update(['deleted_at' => null])) {
            return $this->respond(['message' => $model->errors()], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
        return $this->respond(['message' => 'Practitioner additional qualification restored successfully'], ResponseInterface::HTTP_OK);
    }

}
