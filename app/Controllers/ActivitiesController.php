<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\ActivitiesModel;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;


class ActivitiesController extends ResourceController
{
    public function index()
    {
        try {
            $per_page = $this->request->getVar('limit') ? (int) $this->request->getVar('limit') : 1000;
            $page = $this->request->getVar('page') ? (int) $this->request->getVar('page') : 0;
            $withDeleted = $this->request->getVar('withDeleted') && $this->request->getVar('withDeleted') === "yes";
            $param = $this->request->getVar('param');
            $sortBy = $this->request->getVar('sortBy') ?? "id";
            $sortOrder = $this->request->getVar('sortOrder') ?? "asc";
            $model = new ActivitiesModel();
            $user_id = $this->request->getGet('user_id');
            $unique_id = $this->request->getGet('unique_id');
            $builder = $param ? $model->search($param) : $model->builder();
            
            if ($withDeleted) {
                $model->withDeleted();
            }
            if ($user_id !== null) {
                $builder->where('user_id', $user_id);
            }
            if ($unique_id !== null) {
                $builder->like('activity', "$unique_id ")->orLike('activity', "$unique_id.");
            }

            $builder->orderBy($sortBy, $sortOrder);
            $totalBuilder = clone $builder;
            $total = $totalBuilder->countAllResults();
            $result = $builder->get($per_page, $page)->getResult();
            
            return $this->respond(['data' => $result, 'total' => $total,
                'displayColumns' => $model->getDisplayColumns()
            ], ResponseInterface::HTTP_OK);
        } catch (\Throwable $th) {
            log_message('error', __METHOD__ .''. $th->getMessage());
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    
}
