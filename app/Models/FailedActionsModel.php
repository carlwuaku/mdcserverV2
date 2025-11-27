<?php

namespace App\Models;

use CodeIgniter\I18n\Time;

class FailedActionsModel extends MyBaseModel
{
    protected $table = 'failed_actions';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    protected $protectFields = true;
    protected $allowedFields = [
        'application_uuid',
        'action_config',
        'action_data',
        'error_message',
        'error_trace',
        'status',
        'retry_count',
        'last_retry_at',
        'resolved_at',
        'created_by',
        'updated_by'
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = 'deleted_at';

    protected $validationRules = [
        'action_config' => 'required',
        'action_data' => 'required',
        'error_message' => 'required',
        'status' => 'in_list[failed,retrying,resolved]'
    ];

    protected $validationMessages = [];
    protected $skipValidation = false;
    protected $cleanValidationRules = true;

    protected $searchFields = [
        'application_uuid',
        'error_message',
        'status'
    ];

    /**
     * Log a failed action to the database
     *
     * @param object $actionConfig The ApplicationStageType configuration
     * @param array $data The form data that was being processed
     * @param \Throwable $exception The exception that was thrown
     * @param string|null $applicationUuid The application UUID if available
     * @param string|null $userId The user ID who triggered the action
     * @return int|false The inserted ID or false on failure
     */
    public function logFailedAction(object $actionConfig, array $data, \Throwable $exception, ?string $applicationUuid = null, ?string $userId = null)
    {
        $failedActionData = [
            'application_uuid' => $applicationUuid,
            'action_config' => json_encode($actionConfig),
            'action_data' => json_encode($data),
            'error_message' => $exception->getMessage(),
            'error_trace' => $exception->getTraceAsString(),
            'status' => 'failed',
            'retry_count' => 0,
            'created_by' => $userId
        ];

        return $this->insert($failedActionData);
    }

    /**
     * Increment retry count and update last retry timestamp
     *
     * @param int $id The failed action ID
     * @return bool Success status
     */
    public function incrementRetryCount(int $id): bool
    {
        $failedAction = $this->find($id);
        if (!$failedAction) {
            return false;
        }

        return $this->update($id, [
            'retry_count' => $failedAction['retry_count'] + 1,
            'last_retry_at' => Time::now()->toDateTimeString(),
            'status' => 'retrying'
        ]);
    }

    /**
     * Mark a failed action as resolved
     *
     * @param int $id The failed action ID
     * @param string|null $userId The user ID who resolved it
     * @return bool Success status
     */
    public function markAsResolved(int $id, ?string $userId = null): bool
    {
        return $this->update($id, [
            'status' => 'resolved',
            'resolved_at' => Time::now()->toDateTimeString(),
            'updated_by' => $userId
        ]);
    }

    /**
     * Get all failed actions with pagination
     *
     * @param int $limit Number of records per page
     * @param int $offset Offset for pagination
     * @param string|null $status Filter by status
     * @return array
     */
    public function getFailedActions(int $limit = 20, int $offset = 0, ?string $status = null): array
    {
        $builder = $this->builder();

        if ($status) {
            $builder->where('status', $status);
        }

        return $builder
            ->orderBy('created_at', 'DESC')
            ->limit($limit, $offset)
            ->get()
            ->getResultArray();
    }

    /**
     * Get count of failed actions
     *
     * @param string|null $status Filter by status
     * @return int
     */
    public function getFailedActionsCount(?string $status = null): int
    {
        $builder = $this->builder();

        if ($status) {
            $builder->where('status', $status);
        }

        return $builder->countAllResults();
    }

    /**
     * Delete old resolved actions (cleanup utility)
     *
     * @param int $daysOld Number of days to keep resolved actions
     * @return int Number of deleted records
     */
    public function deleteOldResolvedActions(int $daysOld = 30): int
    {
        $cutoffDate = Time::now()->subDays($daysOld)->toDateTimeString();

        return $this->where('status', 'resolved')
            ->where('resolved_at <', $cutoffDate)
            ->delete();
    }
}
