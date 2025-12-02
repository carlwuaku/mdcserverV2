<?php

namespace App\Controllers;

use App\Models\FailedActionsModel;
use App\Helpers\ApplicationFormActionHelper;
use App\Helpers\Types\ApplicationStageType;
use CodeIgniter\API\ResponseTrait;

class FailedActionsController extends BaseController
{
    use ResponseTrait;

    protected $failedActionsModel;

    public function __construct()
    {
        $this->failedActionsModel = new FailedActionsModel();
    }

    /**
     * Get all failed actions with pagination and optional status filter
     * GET /admin/failed-actions
     * Format matches CPD controller for load-data-list component
     */
    public function index()
    {
        try {
            $cacheKey = \App\Helpers\Utils::generateHashedCacheKey("get_failed_actions", (array) $this->request->getVar());
            return \App\Helpers\CacheHelper::remember($cacheKey, function () {
                $per_page = $this->request->getVar('limit') ? (int) $this->request->getVar('limit') : 20;
                $page = $this->request->getVar('page') ? (int) $this->request->getVar('page') : 0;
                $param = $this->request->getVar('param');
                $sortBy = $this->request->getVar('sortBy') ?? "id";
                $sortOrder = $this->request->getVar('sortOrder') ?? "desc";
                $status = $this->request->getVar('status') ?? null;
                $withDeleted = $this->request->getVar('withDeleted') && $this->request->getVar('withDeleted') === "yes";

                $model = $this->failedActionsModel;
                $builder = $param ? $model->search($param) : $model->builder();
                $tableName = $model->getTableName();

                // Apply filters
                if ($status) {
                    $builder->where("$tableName.status", $status);
                }

                $builder->orderBy("$tableName.$sortBy", $sortOrder);

                if ($withDeleted) {
                    $model->withDeleted();
                }

                $totalBuilder = clone $builder;
                $total = $totalBuilder->countAllResults();
                $result = $builder->get($per_page, $page)->getResult();

                // Decode JSON fields for better readability
                foreach ($result as &$action) {
                    $action->action_config = json_decode($action->action_config);
                    $action->action_data = json_decode($action->action_data);
                    $action->action_type = $action->action_config->config_type ?? 'unknown';
                }

                $displayColumns = [
                    'id',
                    'application_uuid',
                    'action_type',
                    'error_message',
                    'status',
                    'retry_count',
                    'created_at'
                ];

                $columnFilters = [
                    [
                        'column' => 'status',
                        'values' => [
                            ['key' => 'failed', 'value' => 'failed'],
                            ['key' => 'retrying', 'value' => 'retrying'],
                            ['key' => 'resolved', 'value' => 'resolved']
                        ]
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
            log_message('error', 'Error fetching failed actions: ' . $e->getMessage());
            return $this->respond(['message' => "Server error"], 400);
        }
    }

    /**
     * Get a single failed action by ID
     * GET /admin/failed-actions/{id}
     */
    public function show($id)
    {
        try {
            $failedAction = $this->failedActionsModel->find($id);

            if (!$failedAction) {
                return $this->failResponse('Failed action not found', 404);
            }

            // Decode JSON fields
            $failedAction['action_config'] = json_decode($failedAction['action_config'], true);
            $failedAction['action_data'] = json_decode($failedAction['action_data'], true);

            return $this->respond([
                'status' => 'success',
                'data' => $failedAction
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'Error fetching failed action: ' . $e->getMessage());
            return $this->failResponse('Failed to fetch failed action', 500);
        }
    }

    /**
     * Retry a failed action
     * POST /admin/failed-actions/{id}/retry
     */
    public function retry($id)
    {
        try {
            $failedAction = $this->failedActionsModel->find($id);

            if (!$failedAction) {
                return $this->failResponse('Failed action not found', 404);
            }

            // Check if already resolved
            if ($failedAction['status'] === 'resolved') {
                return $this->failResponse('This action has already been resolved', 400);
            }

            // Decode the action config and data
            $actionConfigArray = json_decode($failedAction['action_config'], true);
            $actionData = json_decode($failedAction['action_data'], true);

            // Convert array back to ApplicationStageType object
            $actionConfig = ApplicationStageType::fromArray($actionConfigArray);

            // Increment retry count
            $this->failedActionsModel->incrementRetryCount($id);

            try {
                // Attempt to run the action again
                $result = ApplicationFormActionHelper::runAction($actionConfig, $actionData);

                // If successful, mark as resolved
                $userId = auth()->user()->id ?? null;
                $this->failedActionsModel->markAsResolved($id, $userId);

                return $this->respond([
                    'status' => 'success',
                    'message' => 'Action retried successfully and marked as resolved',
                    'data' => $result
                ]);
            } catch (\Throwable $retryError) {
                // If retry fails, return the error but keep it in retrying status
                log_message('error', 'Retry failed for action ID ' . $id . ': ' . $retryError->getMessage());

                return $this->respond([
                    'status' => 'error',
                    'message' => 'Retry failed: ' . $retryError->getMessage(),
                    'error_trace' => $retryError->getTraceAsString()
                ], 400);
            }
        } catch (\Throwable $e) {
            log_message('error', 'Error retrying failed action: ' . $e->getMessage());
            return $this->failResponse('Failed to retry action', 500);
        }
    }

    /**
     * Delete a failed action
     * DELETE /admin/failed-actions/{id}
     */
    public function delete($id)
    {
        try {
            $failedAction = $this->failedActionsModel->find($id);

            if (!$failedAction) {
                return $this->failResponse('Failed action not found', 404);
            }

            $this->failedActionsModel->delete($id);

            return $this->respond([
                'status' => 'success',
                'message' => 'Failed action deleted successfully'
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'Error deleting failed action: ' . $e->getMessage());
            return $this->failResponse('Failed to delete action', 500);
        }
    }

    /**
     * Bulk delete old resolved actions
     * DELETE /admin/failed-actions/cleanup
     */
    public function cleanup()
    {
        try {
            $daysOld = $this->request->getGet('days') ?? 30;
            $deletedCount = $this->failedActionsModel->deleteOldResolvedActions($daysOld);

            return $this->respond([
                'status' => 'success',
                'message' => "Deleted {$deletedCount} old resolved actions",
                'deleted_count' => $deletedCount
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'Error cleaning up failed actions: ' . $e->getMessage());
            return $this->failResponse('Failed to cleanup actions', 500);
        }
    }

    /**
     * Get statistics about failed actions
     * GET /admin/failed-actions/stats
     */
    public function stats()
    {
        try {
            $failedCount = $this->failedActionsModel->getFailedActionsCount('failed');
            $retryingCount = $this->failedActionsModel->getFailedActionsCount('retrying');
            $resolvedCount = $this->failedActionsModel->getFailedActionsCount('resolved');
            $totalCount = $this->failedActionsModel->countAll();

            return $this->respond([
                'status' => 'success',
                'data' => [
                    'failed' => $failedCount,
                    'retrying' => $retryingCount,
                    'resolved' => $resolvedCount,
                    'total' => $totalCount
                ]
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'Error fetching failed actions stats: ' . $e->getMessage());
            return $this->failResponse('Failed to fetch statistics', 500);
        }
    }
}
