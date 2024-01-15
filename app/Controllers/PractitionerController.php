<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\HTTP\ResponseInterface;
use App\Models\PractitionerModel;
class PractitionerController extends ResourceController
{

    public function createPractitioner()
    {
        $rules = [
            "registration_number" => "required|is_unique[practitioners.registration_number]",
            "last_name"=> "required",
            "date_of_birth"=> "required|valid_date",
            "sex"=> "required",

        ];

        if (!$this->validate($rules)) {
            return $this->respond($this->validator->getErrors(), ResponseInterface::HTTP_BAD_REQUEST);
        }
        $data = $this->request->getPost();
        $model = new PractitionerModel();
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
            "registration_number" => "if_exist|is_unique[practitioners.registration_number, uuid, $uuid]",
            

        ];

        if (!$this->validate($rules)) {
            return $this->respond($this->validator->getErrors(), ResponseInterface::HTTP_BAD_REQUEST);
        }
        $data = $this->request->getVar();
        $data->uuid = $uuid;
        $model = new PractitionerModel();
        if (!$model->builder()->where(['uuid' => $uuid])->update($data)) {
            return $this->respond(['message' => $model->errors()], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
        return $this->respond(['message' => 'Practitioner updated successfully'], ResponseInterface::HTTP_OK);
    }

    public function deletePractitioner($uuid)
    {
        $model = new PractitionerModel();
        if (!$model->where('uuid', $uuid)->delete($uuid)) {
            return $this->respond(['message' => $model->errors()], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
        return $this->respond(['message' => 'Practitioner deleted successfully'], ResponseInterface::HTTP_OK);
    }

    public function restorePractitioner($uuid)
    {
        $model = new PractitionerModel();
        if (!$model->builder()->where(['uuid' => $uuid])->update(['deleted_at'=> null])) {
            return $this->respond(['message' => $model->errors()], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
        return $this->respond(['message' => 'Practitioner restored successfully'], ResponseInterface::HTTP_OK);
    }

    public function getPractitioner($uuid)
    {
        $model = new PractitionerModel();
        $data = $model->find($uuid);
        if (!$data) {
            return $this->respond("Practitioner not found", ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
        return $this->respond(['data' => $data], ResponseInterface::HTTP_OK);
    }

    public function getPractitioners()
    {
        $per_page = $this->request->getVar('limit') ? (int) $this->request->getVar('limit') : 100;
        $page = $this->request->getVar('page') ? (int) $this->request->getVar('page') : 0;
        $withDeleted = $this->request->getVar('withDeleted') && $this->request->getVar('withDeleted');
        $param = $this->request->getVar('param');
        $model = new PractitionerModel();
        $builder = $param ? $model->search($param) : $model->builder();
        $columns = $model->getDisplayColumns();
        $builder->join('practitioner_renewal', "practitioners.uuid = practitioner_renewal.practitioner_uuid","left")
        ->select( implode(', ', array_map(function($col) {
            return 'practitioners.' . $col;
          }, $columns)))
        ->select("(CASE 
        WHEN EXISTS (SELECT 1 FROM practitioner_renewal WHERE practitioner_renewal.practitioner_uuid = practitioners.uuid AND CURDATE() BETWEEN practitioner_renewal.year AND practitioner_renewal.expiry) THEN 'yes'
        ELSE 'no'
    END) AS in_good_standing")
        ->groupBy('practitioners.uuid');
        if($withDeleted){
            $model->withDeleted();
        }
        $totalBuilder = clone $builder;
        $total = $totalBuilder->countAllResults();
        $result = $builder->get($per_page, $page)->getResult();
        
        return $this->respond(['data' => $result, 'total' => $total,
            'displayColumns' => $columns
        ], ResponseInterface::HTTP_OK);
    }
}