<?php

namespace App\Controllers;

use App\Models\SettingsModel;
use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\HTTP\ResponseInterface;
use OpenApi\Attributes as OA;

/**
 * @OA\Info(title="API Name", version="1.0")
 * @OA\Tag(name="Tag Name", description="Tag description")
 * @OA\Tag(
 *     name="Admin",
 *     description="Operations for managing and viewing system activities"
 * )
 */
class AdminController extends ResourceController
{
    /**
     * Get a setting or all settings if no setting name is provided
     * @param string|null $name the name of the setting to retrieve
     * @return ResponseInterface
     */
    #[OA\Get(
        path: '/api/users',
        responses: [
            new OA\Response(response: 200, description: 'AOK'),
            new OA\Response(response: 401, description: 'Not allowed'),
        ]
    )]

    public function getSetting($name = null)
    {
        try {
            $settings = service("settings");
            $value = $settings->get($name);
            //legacy settings may be lists represented as ; separated strings
            if (is_string($value) && strpos($value, ';') !== false) {
                $value = explode(';', $value);
            }
            return $this->respond(['message' => '', 'data' => $value], ResponseInterface::HTTP_OK);
        } catch (\Throwable $th) {
            log_message('error', __METHOD__ . '' . $th->getMessage());
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }

    }

    public function getSettings()
    {
        $settings = service("settings");
        try {
            $per_page = $this->request->getVar('limit') ? (int) $this->request->getVar('limit') : 1000;
            $page = $this->request->getVar('page') ? (int) $this->request->getVar('page') : 0;
            $withDeleted = $this->request->getVar('withDeleted') && $this->request->getVar('withDeleted') === "yes";
            $param = $this->request->getVar('param');
            $sortBy = $this->request->getVar('sortBy') ?? "id";
            $sortOrder = $this->request->getVar('sortOrder') ?? "asc";
            $model = new SettingsModel();

            $builder = $param ? $model->search($param) : $model->builder();

            if ($withDeleted) {
                $model->withDeleted();
            }

            $builder->orderBy($sortBy, $sortOrder);
            $totalBuilder = clone $builder;
            $total = $totalBuilder->countAllResults();
            $result = $builder->get($per_page, $page)->getResult();
            foreach ($result as $value) {
                if ($value->type !== 'string') {
                    $value->value = unserialize($value->value);
                }
            }
            return $this->respond([
                'data' => $result,
                'total' => $total,
                'displayColumns' => $model->getDisplayColumns()
            ], ResponseInterface::HTTP_OK);
        } catch (\Throwable $th) {
            log_message('error', __METHOD__ . '' . $th->getMessage());
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function saveSetting()
    {
        $settings = service("settings");
        $name = $this->request->getVar("name");
        $value = $this->request->getVar("value");
        $settings->set($name, $value);
        return $this->respond(['message' => "Setting $name updated successfully", 'data' => null], ResponseInterface::HTTP_OK);
    }

    public function deleteSetting($name)
    {
        $settings = service("settings");
        $settings->delete($name);
        return $this->respond(['message' => "Setting $name deleted successfully", 'data' => null], ResponseInterface::HTTP_OK);
    }

    public function getDistinctValues($table, $column)
    {
        try {
            $tableMap = [
                'specialties' => 'specialties',
                'subspecialties' => 'subspecialties',
                'regions' => 'regions',
                'districts' => 'districts',
                'practitioners' => 'practitioners',
                'facilities' => 'facilities',
                'practitioners_renewal' => 'practitioners_renewal',
                'license_renewal' => 'license_renewal',
                'licenses' => 'licenses',
                'exam_candidates' => 'exam_candidates',
                'examinations' => 'examinations',
                'otcms' => 'otcms',
                'application_forms' => 'application_forms',
                'student_indexes' => 'student_indexes',
                'mca' => 'mca'
            ];
            //check if table is in the map
            if (!array_key_exists($table, $tableMap)) {
                return $this->respond(['message' => "Table $table not found"], ResponseInterface::HTTP_BAD_REQUEST);
            }
            $model = new SettingsModel(); //could be any model
            $result = $model->builder($tableMap[$table])->select($column)->distinct()->get()->getResult();

            return $this->respond(['message' => '', 'data' => $result], ResponseInterface::HTTP_OK);
        } catch (\Exception $th) {
            log_message("error", $th->getMessage());
            return $this->respond(['message' => "An error occurred. Please try again"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
