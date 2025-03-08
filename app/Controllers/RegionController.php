<?php

namespace App\Controllers;

use App\Models\DistrictModel;
use App\Models\RegionModel;
use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\HTTP\ResponseInterface;
use App\Helpers\CacheHelper;
use App\Traits\CacheInvalidatorTrait;

class RegionController extends ResourceController
{
    use CacheInvalidatorTrait;

    /**
     * Return an array of resource objects, themselves in array format
     *
     * @return mixed
     */
    public function getRegions()
    {
        try {
            $per_page = $this->request->getVar('limit') ? (int) $this->request->getVar('limit') : 1000;
            $page = $this->request->getVar('page') ? (int) $this->request->getVar('page') : 0;
            $withDeleted = $this->request->getVar('withDeleted') && $this->request->getVar('withDeleted')  === "yes";
            $param = $this->request->getVar('param');
            $sortBy = $this->request->getVar('sortBy') ?? "id";
            $sortOrder = $this->request->getVar('sortOrder') ?? "asc";

            // Generate cache key based on query parameters
            $cacheKey = "regions_" . md5(json_encode([
                $per_page, $page, $withDeleted, $param, $sortBy, $sortOrder
            ]));

            return CacheHelper::remember($cacheKey, function() use ($per_page, $page, $withDeleted, $param, $sortBy, $sortOrder) {
                $model = new RegionModel();
                $builder = $param ? $model->search($param) : $model->builder();
                
                if ($withDeleted) {
                    $model->withDeleted();
                }
                $builder->orderBy("$sortBy", $sortOrder);
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

    public function getDistricts($regionName = null)
    {
        try {
            $per_page = $this->request->getVar('limit') ? (int) $this->request->getVar('limit') : 1000;
            $page = $this->request->getVar('page') ? (int) $this->request->getVar('page') : 0;
            $withDeleted = $this->request->getVar('withDeleted') && $this->request->getVar('withDeleted')  === "yes";
            $param = $this->request->getVar('param');
            $sortBy = $this->request->getVar('sortBy') ?? "id";
            $sortOrder = $this->request->getVar('sortOrder') ?? "asc";

            // Generate cache key based on query parameters
            $cacheKey = "districts_" . md5(json_encode([
                $per_page, $page, $withDeleted, $param, $sortBy, $sortOrder, $regionName
            ]));

            return CacheHelper::remember($cacheKey, function() use ($per_page, $page, $withDeleted, $param, $sortBy, $sortOrder, $regionName) {
                $model = new DistrictModel();
                $builder = $param ? $model->search($param) : $model->builder();
                if ($regionName !== null) {
                    $builder->where('region_name', $regionName);
                }
                if ($withDeleted) {
                    $model->withDeleted();
                }
                $builder->orderBy("$sortBy", $sortOrder);
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

    public function createRegion()
    {
        try {
            $rules = [
                "name" => "required|is_unique[regions.name]"
            ];

            if (!$this->validate($rules)) {
                return $this->respond($this->validator->getErrors(), ResponseInterface::HTTP_BAD_REQUEST);
            }

            $model = new RegionModel();
            $data = $this->request->getPost();

            if (!$model->insert($data)) {
                return $this->respond(['message' => $model->errors()], ResponseInterface::HTTP_BAD_REQUEST);
            }

            // Invalidate regions cache
            $this->invalidateCache('regions_');

            return $this->respond(['message' => 'Region created successfully'], ResponseInterface::HTTP_OK);
        } catch (\Throwable $th) {
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function updateRegion($id)
    {
        try {
            $rules = [
                "name" => "required"
            ];

            if (!$this->validate($rules)) {
                return $this->respond($this->validator->getErrors(), ResponseInterface::HTTP_BAD_REQUEST);
            }

            $model = new RegionModel();
            $data = $this->request->getVar();

            if (!$model->update($id, $data)) {
                return $this->respond(['message' => $model->errors()], ResponseInterface::HTTP_BAD_REQUEST);
            }

            // Invalidate regions cache
            $this->invalidateCache('regions_');

            return $this->respond(['message' => 'Region updated successfully'], ResponseInterface::HTTP_OK);
        } catch (\Throwable $th) {
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function deleteRegion($id)
    {
        try {
            $model = new RegionModel();

            if (!$model->delete($id)) {
                return $this->respond(['message' => $model->errors()], ResponseInterface::HTTP_BAD_REQUEST);
            }

            // Invalidate regions cache
            $this->invalidateCache('regions_');

            return $this->respond(['message' => 'Region deleted successfully'], ResponseInterface::HTTP_OK);
        } catch (\Throwable $th) {
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function createDistrict()
    {
        try {
            $rules = [
                "name" => "required|is_unique[districts.name]",
                "region_name" => "required|exists[regions.name]"
            ];

            if (!$this->validate($rules)) {
                return $this->respond($this->validator->getErrors(), ResponseInterface::HTTP_BAD_REQUEST);
            }

            $model = new DistrictModel();
            $data = $this->request->getPost();

            if (!$model->insert($data)) {
                return $this->respond(['message' => $model->errors()], ResponseInterface::HTTP_BAD_REQUEST);
            }

            // Invalidate districts cache
            $this->invalidateCache('districts_');

            return $this->respond(['message' => 'District created successfully'], ResponseInterface::HTTP_OK);
        } catch (\Throwable $th) {
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function updateDistrict($id)
    {
        try {
            $rules = [
                "name" => "required",
                "region_name" => "required|exists[regions.name]"
            ];

            if (!$this->validate($rules)) {
                return $this->respond($this->validator->getErrors(), ResponseInterface::HTTP_BAD_REQUEST);
            }

            $model = new DistrictModel();
            $data = $this->request->getVar();

            if (!$model->update($id, $data)) {
                return $this->respond(['message' => $model->errors()], ResponseInterface::HTTP_BAD_REQUEST);
            }

            // Invalidate districts cache
            $this->invalidateCache('districts_');

            return $this->respond(['message' => 'District updated successfully'], ResponseInterface::HTTP_OK);
        } catch (\Throwable $th) {
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function deleteDistrict($id)
    {
        try {
            $model = new DistrictModel();

            if (!$model->delete($id)) {
                return $this->respond(['message' => $model->errors()], ResponseInterface::HTTP_BAD_REQUEST);
            }

            // Invalidate districts cache
            $this->invalidateCache('districts_');

            return $this->respond(['message' => 'District deleted successfully'], ResponseInterface::HTTP_OK);
        } catch (\Throwable $th) {
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
