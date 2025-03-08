<?php

namespace App\Controllers;

use App\Models\SpecialtiesModel;
use App\Models\SubspecialtiesModel;
use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\HTTP\ResponseInterface;
use App\Helpers\CacheHelper;
use App\Traits\CacheInvalidatorTrait;

class SpecialtiesController extends ResourceController
{
    use CacheInvalidatorTrait;

    public function getSpecialties()
    {
        try {
            $per_page = $this->request->getVar('limit') ? (int) $this->request->getVar('limit') : 1000;
            $page = $this->request->getVar('page') ? (int) $this->request->getVar('page') : 0;
            $withDeleted = $this->request->getVar('withDeleted') && $this->request->getVar('withDeleted')  === "yes";
            $param = $this->request->getVar('param');
            $sortBy = $this->request->getVar('sortBy') ?? "id";
            $sortOrder = $this->request->getVar('sortOrder') ?? "asc";

            // Generate cache key based on query parameters
            $cacheKey = "specialties_" . md5(json_encode([
                $per_page, $page, $withDeleted, $param, $sortBy, $sortOrder
            ]));

            return CacheHelper::remember($cacheKey, function() use ($per_page, $page, $withDeleted, $param, $sortBy, $sortOrder) {
                $model = new SpecialtiesModel();
                
                $builder = $param ? $model->search($param) : $model->builder();
                
                if ($withDeleted) {
                    $model->withDeleted();
                }

                $builder->orderBy($sortBy, $sortOrder);
                $totalBuilder = clone $builder;
                $total = $totalBuilder->countAllResults();
                $result = $builder->get($per_page, $page)->getResult();
                return $this->respond(['data' => $result, 'total' => $total,
                    'displayColumns' => $model->getDisplayColumns()
                ], ResponseInterface::HTTP_OK);
            }, 3600); // Cache for 1 hour
        } catch (\Throwable $th) {
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getSubspecialties()
    {
        try {
            $per_page = $this->request->getVar('limit') ? (int) $this->request->getVar('limit') : 1000;
            $page = $this->request->getVar('page') ? (int) $this->request->getVar('page') : 0;
            $withDeleted = $this->request->getVar('withDeleted') && $this->request->getVar('withDeleted')  === "yes";
            $param = $this->request->getVar('param');
            $specialty = $this->request->getVar('specialty');
            $sortBy = $this->request->getVar('sortBy') ?? "id";
            $sortOrder = $this->request->getVar('sortOrder') ?? "asc";

            // Generate cache key based on query parameters
            $cacheKey = "subspecialties_" . md5(json_encode([
                $per_page, $page, $withDeleted, $param, $sortBy, $sortOrder, $specialty
            ]));

            return CacheHelper::remember($cacheKey, function() use ($per_page, $page, $withDeleted, $param, $sortBy, $sortOrder, $specialty) {
                $model = new SubspecialtiesModel();
                
                $builder = $param ? $model->search($param) : $model->builder();
                
                if ($withDeleted) {
                    $model->withDeleted();
                }

                if ($specialty) {
                    $builder->where('specialty', $specialty);
                }

                $builder->orderBy($sortBy, $sortOrder);
                $totalBuilder = clone $builder;
                $total = $totalBuilder->countAllResults();
                $result = $builder->get($per_page, $page)->getResult();
                return $this->respond(['data' => $result, 'total' => $total,
                    'displayColumns' => $model->getDisplayColumns()
                ], ResponseInterface::HTTP_OK);
            }, 3600); // Cache for 1 hour
        } catch (\Throwable $th) {
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function createSpecialty()
    {
        try {
            $rules = [
                "name" => "required|is_unique[specialties.name]"
            ];

            if (!$this->validate($rules)) {
                return $this->respond($this->validator->getErrors(), ResponseInterface::HTTP_BAD_REQUEST);
            }

            $model = new SpecialtiesModel();
            $data = $this->request->getPost();

            if (!$model->insert($data)) {
                return $this->respond(['message' => $model->errors()], ResponseInterface::HTTP_BAD_REQUEST);
            }

            // Invalidate specialties cache
            $this->invalidateCache('specialties_');

            return $this->respond(['message' => 'Specialty created successfully'], ResponseInterface::HTTP_OK);
        } catch (\Throwable $th) {
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function updateSpecialty($id)
    {
        try {
            $rules = [
                "name" => "required"
            ];

            if (!$this->validate($rules)) {
                return $this->respond($this->validator->getErrors(), ResponseInterface::HTTP_BAD_REQUEST);
            }

            $model = new SpecialtiesModel();
            $data = $this->request->getVar();

            if (!$model->update($id, $data)) {
                return $this->respond(['message' => $model->errors()], ResponseInterface::HTTP_BAD_REQUEST);
            }

            // Invalidate specialties cache
            $this->invalidateCache('specialties_');

            return $this->respond(['message' => 'Specialty updated successfully'], ResponseInterface::HTTP_OK);
        } catch (\Throwable $th) {
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function deleteSpecialty($id)
    {
        try {
            $model = new SpecialtiesModel();

            if (!$model->delete($id)) {
                return $this->respond(['message' => $model->errors()], ResponseInterface::HTTP_BAD_REQUEST);
            }

            // Invalidate specialties cache
            $this->invalidateCache('specialties_');

            return $this->respond(['message' => 'Specialty deleted successfully'], ResponseInterface::HTTP_OK);
        } catch (\Throwable $th) {
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function createSubspecialty()
    {
        try {
            $rules = [
                "name" => "required|is_unique[subspecialties.name]",
                "specialty" => "required|exists[specialties.name]"
            ];

            if (!$this->validate($rules)) {
                return $this->respond($this->validator->getErrors(), ResponseInterface::HTTP_BAD_REQUEST);
            }

            $model = new SubspecialtiesModel();
            $data = $this->request->getPost();

            if (!$model->insert($data)) {
                return $this->respond(['message' => $model->errors()], ResponseInterface::HTTP_BAD_REQUEST);
            }

            // Invalidate subspecialties cache
            $this->invalidateCache('subspecialties_');

            return $this->respond(['message' => 'Subspecialty created successfully'], ResponseInterface::HTTP_OK);
        } catch (\Throwable $th) {
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function updateSubspecialty($id)
    {
        try {
            $rules = [
                "name" => "required",
                "specialty" => "required|exists[specialties.name]"
            ];

            if (!$this->validate($rules)) {
                return $this->respond($this->validator->getErrors(), ResponseInterface::HTTP_BAD_REQUEST);
            }

            $model = new SubspecialtiesModel();
            $data = $this->request->getVar();

            if (!$model->update($id, $data)) {
                return $this->respond(['message' => $model->errors()], ResponseInterface::HTTP_BAD_REQUEST);
            }

            // Invalidate subspecialties cache
            $this->invalidateCache('subspecialties_');

            return $this->respond(['message' => 'Subspecialty updated successfully'], ResponseInterface::HTTP_OK);
        } catch (\Throwable $th) {
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function deleteSubspecialty($id)
    {
        try {
            $model = new SubspecialtiesModel();

            if (!$model->delete($id)) {
                return $this->respond(['message' => $model->errors()], ResponseInterface::HTTP_BAD_REQUEST);
            }

            // Invalidate subspecialties cache
            $this->invalidateCache('subspecialties_');

            return $this->respond(['message' => 'Subspecialty deleted successfully'], ResponseInterface::HTTP_OK);
        } catch (\Throwable $th) {
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
