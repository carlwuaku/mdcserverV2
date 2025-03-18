<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\ActivitiesModel;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

/**
 * @OA\Info(title="API Name", version="1.0")
 * @OA\Tag(name="Tag Name", description="Tag description")
 * @OA\Tag(
 *     name="Activities",
 *     description="Operations for managing and viewing system activities"
 * )
 */

class ActivitiesController extends ResourceController
{
    /**
     * @OA\Get(
     *     path="/activities",
     *     summary="Get system activities",
     *     description="Retrieve a list of system activities with optional filtering and pagination",
     *     tags={"Activities"},
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
     *     @OA\Parameter(
     *         name="user_id",
     *         in="query",
     *         description="Filter by user ID",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="unique_id",
     *         in="query",
     *         description="Filter by unique identifier",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of activities",
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
