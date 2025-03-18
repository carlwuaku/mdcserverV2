<?php

namespace App\Controllers;

use App\Models\DistrictModel;
use App\Models\RegionModel;
use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\HTTP\ResponseInterface;
use App\Helpers\CacheHelper;
use App\Traits\CacheInvalidatorTrait;

/**
 * @OA\Tag(
 *     name="Regions",
 *     description="Operations for managing regions and districts"
 * )
 */
class RegionController extends ResourceController
{
    use CacheInvalidatorTrait;

    /**
     * @OA\Get(
     *     path="/regions",
     *     summary="Get all regions",
     *     description="Returns a list of regions with optional filtering and pagination",
     *     tags={"Regions"},
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Number of items per page",
     *         required=false,
     *         @OA\Schema(type="integer", default=1000)
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number",
     *         required=false,
     *         @OA\Schema(type="integer", default=0)
     *     ),
     *     @OA\Parameter(
     *         name="withDeleted",
     *         in="query",
     *         description="Include deleted records",
     *         required=false,
     *         @OA\Schema(type="string", enum={"yes", "no"})
     *     ),
     *     @OA\Parameter(
     *         name="param",
     *         in="query",
     *         description="Search parameter",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="sortBy",
     *         in="query",
     *         description="Field to sort by",
     *         required=false,
     *         @OA\Schema(type="string", default="id")
     *     ),
     *     @OA\Parameter(
     *         name="sortOrder",
     *         in="query",
     *         description="Sort order (asc/desc)",
     *         required=false,
     *         @OA\Schema(type="string", default="asc")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of regions",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="total", type="integer"),
     *             @OA\Property(property="displayColumns", type="array", @OA\Items(type="string"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error"
     *     ),
     *     security={{"bearerAuth": {}}}
     * )
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

    /**
     * @OA\Get(
     *     path="/districts/{regionName}",
     *     summary="Get districts by region",
     *     description="Returns a list of districts for a specific region with optional filtering and pagination",
     *     tags={"Regions"},
     *     @OA\Parameter(
     *         name="regionName",
     *         in="path",
     *         description="Name of the region",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Number of items per page",
     *         required=false,
     *         @OA\Schema(type="integer", default=1000)
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number",
     *         required=false,
     *         @OA\Schema(type="integer", default=0)
     *     ),
     *     @OA\Parameter(
     *         name="withDeleted",
     *         in="query",
     *         description="Include deleted records",
     *         required=false,
     *         @OA\Schema(type="string", enum={"yes", "no"})
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of districts",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="total", type="integer"),
     *             @OA\Property(property="displayColumns", type="array", @OA\Items(type="string"))
     *         )
     *     ),
     *     security={{"bearerAuth": {}}}
     * )
     */
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

    /**
     * @OA\Post(
     *     path="/regions",
     *     summary="Create a new region",
     *     tags={"Regions"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Region created successfully"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validation error"
     *     ),
     *     security={{"bearerAuth": {}}}
     * )
     */
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

    /**
     * @OA\Put(
     *     path="/regions/{id}",
     *     summary="Update a region",
     *     tags={"Regions"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Region updated successfully"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validation error"
     *     ),
     *     security={{"bearerAuth": {}}}
     * )
     */
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

    /**
     * @OA\Delete(
     *     path="/regions/{id}",
     *     summary="Delete a region",
     *     tags={"Regions"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Region deleted successfully"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Deletion failed"
     *     ),
     *     security={{"bearerAuth": {}}}
     * )
     */
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

    /**
     * @OA\Post(
     *     path="/districts",
     *     summary="Create a new district",
     *     tags={"Regions"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "region_name"},
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="region_name", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="District created successfully"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validation error"
     *     ),
     *     security={{"bearerAuth": {}}}
     * )
     */
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

    /**
     * @OA\Put(
     *     path="/districts/{id}",
     *     summary="Update a district",
     *     tags={"Regions"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "region_name"},
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="region_name", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="District updated successfully"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validation error"
     *     ),
     *     security={{"bearerAuth": {}}}
     * )
     */
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

    /**
     * @OA\Delete(
     *     path="/districts/{id}",
     *     summary="Delete a district",
     *     tags={"Regions"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="District deleted successfully"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Deletion failed"
     *     ),
     *     security={{"bearerAuth": {}}}
     * )
     */
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
