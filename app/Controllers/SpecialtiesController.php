<?php

namespace App\Controllers;

use App\Models\SpecialtiesModel;
use App\Models\SubspecialtiesModel;
use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\HTTP\ResponseInterface;
use App\Helpers\CacheHelper;
use App\Traits\CacheInvalidatorTrait;

/**
 * @OA\Tag(
 *     name="Specialties",
 *     description="Operations for managing medical specialties and subspecialties"
 * )
 */
class SpecialtiesController extends ResourceController
{
    use CacheInvalidatorTrait;

    /**
     * @OA\Get(
     *     path="/specialties",
     *     summary="Get all specialties",
     *     description="Returns a list of medical specialties with optional filtering and pagination",
     *     tags={"Specialties"},
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
     *         description="List of specialties",
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

    /**
     * @OA\Get(
     *     path="/subspecialties",
     *     summary="Get subspecialties",
     *     description="Returns a list of medical subspecialties with optional filtering and pagination",
     *     tags={"Specialties"},
     *     @OA\Parameter(
     *         name="specialty",
     *         in="query",
     *         description="Filter by specialty name",
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
     *         description="List of subspecialties",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="total", type="integer"),
     *             @OA\Property(property="displayColumns", type="array", @OA\Items(type="string"))
     *         )
     *     ),
     *     security={{"bearerAuth": {}}}
     * )
     */
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

    /**
     * @OA\Post(
     *     path="/specialties",
     *     summary="Create a new specialty",
     *     tags={"Specialties"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string", description="Name of the specialty")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Specialty created successfully"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validation error"
     *     ),
     *     security={{"bearerAuth": {}}}
     * )
     */
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

    /**
     * @OA\Put(
     *     path="/specialties/{id}",
     *     summary="Update a specialty",
     *     tags={"Specialties"},
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
     *             @OA\Property(property="name", type="string", description="New name for the specialty")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Specialty updated successfully"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validation error"
     *     ),
     *     security={{"bearerAuth": {}}}
     * )
     */
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

    /**
     * @OA\Delete(
     *     path="/specialties/{id}",
     *     summary="Delete a specialty",
     *     tags={"Specialties"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Specialty deleted successfully"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Deletion failed"
     *     ),
     *     security={{"bearerAuth": {}}}
     * )
     */
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

    /**
     * @OA\Post(
     *     path="/subspecialties",
     *     summary="Create a new subspecialty",
     *     tags={"Specialties"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "specialty"},
     *             @OA\Property(property="name", type="string", description="Name of the subspecialty"),
     *             @OA\Property(property="specialty", type="string", description="Name of the parent specialty")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Subspecialty created successfully"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validation error"
     *     ),
     *     security={{"bearerAuth": {}}}
     * )
     */
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

    /**
     * @OA\Put(
     *     path="/subspecialties/{id}",
     *     summary="Update a subspecialty",
     *     tags={"Specialties"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "specialty"},
     *             @OA\Property(property="name", type="string", description="New name for the subspecialty"),
     *             @OA\Property(property="specialty", type="string", description="Name of the parent specialty")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Subspecialty updated successfully"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validation error"
     *     ),
     *     security={{"bearerAuth": {}}}
     * )
     */
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

    /**
     * @OA\Delete(
     *     path="/subspecialties/{id}",
     *     summary="Delete a subspecialty",
     *     tags={"Specialties"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Subspecialty deleted successfully"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Deletion failed"
     *     ),
     *     security={{"bearerAuth": {}}}
     * )
     */
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
