<?php

namespace App\Controllers;

use App\Models\ActionsAuditModel;
use CodeIgniter\API\ResponseTrait;

class ActionsAuditController extends BaseController
{
    use ResponseTrait;

    protected $actionsAuditModel;

    public function __construct()
    {
        $this->actionsAuditModel = new ActionsAuditModel();
    }

    /**
     * Get all audit records with pagination and filters
     * GET /admin/actions-audit
     * Format matches CPD controller for load-data-list component
     */
    public function index()
    {
        try {
            $cacheKey = \App\Helpers\Utils::generateHashedCacheKey("get_actions_audit", (array) $this->request->getVar());
            return \App\Helpers\CacheHelper::remember($cacheKey, function () {
                $per_page = $this->request->getVar('limit') ? (int) $this->request->getVar('limit') : 100;
                $page = $this->request->getVar('page') ? (int) $this->request->getVar('page') : 0;
                $param = $this->request->getVar('param');
                $sortBy = $this->request->getVar('sortBy') ?? "id";
                $sortOrder = $this->request->getVar('sortOrder') ?? "desc";
                $actionType = $this->request->getVar('action_type') ?? null;
                $applicationUuid = $this->request->getVar('application_uuid') ?? null;
                $withDeleted = $this->request->getVar('withDeleted') && $this->request->getVar('withDeleted') === "yes";

                $model = $this->actionsAuditModel;
                $builder = $param ? $model->search($param) : $model->builder();
                $tableName = $model->getTableName();

                // Apply filters
                if ($actionType) {
                    $builder->where("$tableName.action_type", $actionType);
                }

                if ($applicationUuid) {
                    $builder->where("$tableName.application_uuid", $applicationUuid);
                }

                $builder->orderBy("$tableName.$sortBy", $sortOrder);

                if ($withDeleted) {
                    $model->withDeleted();
                }

                $totalBuilder = clone $builder;
                $total = $totalBuilder->countAllResults();
                $result = $builder->get($per_page, $page)->getResult();

                // Decode JSON fields for better readability
                foreach ($result as &$audit) {
                    $audit->action_config = json_decode($audit->action_config);
                    $audit->action_data = json_decode($audit->action_data);
                    $audit->action_result = json_decode($audit->action_result);
                }

                $displayColumns = [
                    'id',
                    'application_uuid',
                    'action_type',
                    'execution_time_ms',
                    'triggered_by',
                    'created_at'
                ];

                // Get distinct action types for filter
                $distinctTypes = $model->builder()
                    ->distinct()
                    ->select('action_type')
                    ->orderBy('action_type', 'ASC')
                    ->get()
                    ->getResultArray();

                $typeValues = array_map(function ($item) {
                    return ['key' => $item['action_type'], 'value' => $item['action_type']];
                }, $distinctTypes);

                $columnFilters = [
                    [
                        'column' => 'action_type',
                        'values' => $typeValues
                    ]
                ];

                return $this->respond([
                    'data' => $result,
                    'total' => $total,
                    'displayColumns' => $displayColumns,
                    'columnFilters' => $columnFilters
                ], 200);
            });
        } catch (\Throwable $e) {
            log_message('error', 'Error fetching audit records: ' . $e->getMessage());
            return $this->respond(['message' => "Server error"], 400);
        }
    }

    /**
     * Get a single audit record by ID
     * GET /admin/actions-audit/{id}
     */
    public function show($id)
    {
        try {
            $auditRecord = $this->actionsAuditModel->find($id);

            if (!$auditRecord) {
                return $this->failResponse('Audit record not found', 404);
            }

            // Decode JSON fields
            $auditRecord['action_config'] = json_decode($auditRecord['action_config'], true);
            $auditRecord['action_data'] = json_decode($auditRecord['action_data'], true);
            $auditRecord['action_result'] = json_decode($auditRecord['action_result'], true);

            return $this->respond([
                'status' => 'success',
                'data' => $auditRecord
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'Error fetching audit record: ' . $e->getMessage());
            return $this->failResponse('Failed to fetch audit record', 500);
        }
    }

    /**
     * Get statistics about actions
     * GET /admin/actions-audit/stats
     */
    public function stats()
    {
        try {
            $stats = $this->actionsAuditModel->getStatistics();

            return $this->respond([
                'status' => 'success',
                'data' => $stats
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'Error fetching audit statistics: ' . $e->getMessage());
            return $this->failResponse('Failed to fetch statistics', 500);
        }
    }

    /**
     * Get audit records for a specific application
     * GET /admin/actions-audit/application/{uuid}
     */
    public function byApplication($uuid)
    {
        try {
            $auditRecords = $this->actionsAuditModel->getAuditByApplication($uuid);

            // Decode JSON fields for each record
            foreach ($auditRecords as &$record) {
                $record['action_config'] = json_decode($record['action_config'], true);
                $record['action_data'] = json_decode($record['action_data'], true);
                $record['action_result'] = json_decode($record['action_result'], true);
            }

            return $this->respond([
                'status' => 'success',
                'data' => $auditRecords
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'Error fetching audit records by application: ' . $e->getMessage());
            return $this->failResponse('Failed to fetch audit records', 500);
        }
    }

    /**
     * Delete old audit records
     * DELETE /admin/actions-audit/cleanup
     */
    public function cleanup()
    {
        try {
            $daysOld = $this->request->getGet('days') ?? 90;
            $deletedCount = $this->actionsAuditModel->deleteOldAuditRecords($daysOld);

            return $this->respond([
                'status' => 'success',
                'message' => "Deleted {$deletedCount} old audit records",
                'deleted_count' => $deletedCount
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'Error cleaning up audit records: ' . $e->getMessage());
            return $this->failResponse('Failed to cleanup audit records', 500);
        }
    }

    /**
     * Delete a specific audit record
     * DELETE /admin/actions-audit/{id}
     */
    public function delete($id)
    {
        try {
            $auditRecord = $this->actionsAuditModel->find($id);

            if (!$auditRecord) {
                return $this->failResponse('Audit record not found', 404);
            }

            $this->actionsAuditModel->delete($id);

            return $this->respond([
                'status' => 'success',
                'message' => 'Audit record deleted successfully'
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'Error deleting audit record: ' . $e->getMessage());
            return $this->failResponse('Failed to delete audit record', 500);
        }
    }
}
