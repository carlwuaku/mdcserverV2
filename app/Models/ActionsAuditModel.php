<?php

namespace App\Models;

class ActionsAuditModel extends MyBaseModel
{
    protected $table = 'actions_audit';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    protected $protectFields = true;
    protected $allowedFields = [
        'application_uuid',
        'action_config',
        'action_data',
        'action_result',
        'action_type',
        'execution_time_ms',
        'triggered_by'
    ];

    protected $useTimestamps = false;
    protected $createdField = 'created_at';
    protected $updatedField = null; // No update field for audit records
    protected $deletedField = 'deleted_at';

    protected $validationRules = [
        'action_config' => 'required',
        'action_data' => 'required',
        'action_type' => 'required|max_length[100]'
    ];

    protected $validationMessages = [];
    protected $skipValidation = false;
    protected $cleanValidationRules = true;

    protected $searchFields = [
        'application_uuid',
        'action_type',
        'triggered_by'
    ];

    /**
     * Log a successful action to the audit trail
     *
     * @param object $actionConfig The ApplicationStageType configuration
     * @param array $data The form data that was processed
     * @param mixed $result The result of the action execution
     * @param int $executionTimeMs Execution time in milliseconds
     * @param string|null $applicationUuid The application UUID if available
     * @param string|null $userId The user ID who triggered the action
     * @return int|false The inserted ID or false on failure
     */
    public function logSuccessfulAction(
        object $actionConfig,
        array $data,
        $result,
        int $executionTimeMs,
        ?string $applicationUuid = null,
        ?string $userId = null
    ) {
        $auditData = [
            'application_uuid' => $applicationUuid,
            'action_config' => json_encode($actionConfig),
            'action_data' => json_encode($data),
            'action_result' => is_string($result) ? $result : json_encode($result),
            'action_type' => $actionConfig->config_type ?? 'unknown',
            'execution_time_ms' => $executionTimeMs,
            'triggered_by' => $userId
        ];
        log_message('info', 'Audit log: ' . print_r($auditData, true));
        return $this->insert($auditData);
    }

    /**
     * Get audit records with pagination
     *
     * @param int $limit Number of records per page
     * @param int $offset Offset for pagination
     * @param string|null $actionType Filter by action type
     * @param string|null $applicationUuid Filter by application UUID
     * @return array
     */
    public function getAuditRecords(
        int $limit = 100,
        int $offset = 0,
        ?string $actionType = null,
        ?string $applicationUuid = null
    ): array {
        $builder = $this->builder();

        if ($actionType) {
            $builder->where('action_type', $actionType);
        }

        if ($applicationUuid) {
            $builder->where('application_uuid', $applicationUuid);
        }

        return $builder
            ->orderBy('created_at', 'DESC')
            ->limit($limit, $offset)
            ->get()
            ->getResultArray();
    }

    /**
     * Get count of audit records
     *
     * @param string|null $actionType Filter by action type
     * @param string|null $applicationUuid Filter by application UUID
     * @return int
     */
    public function getAuditRecordsCount(?string $actionType = null, ?string $applicationUuid = null): int
    {
        $builder = $this->builder();

        if ($actionType) {
            $builder->where('action_type', $actionType);
        }

        if ($applicationUuid) {
            $builder->where('application_uuid', $applicationUuid);
        }

        return $builder->countAllResults();
    }

    /**
     * Get statistics about actions
     *
     * @return array
     */
    public function getStatistics(): array
    {
        $totalCount = $this->countAll();

        // Get counts by action type
        $typeStats = $this->builder()
            ->select('action_type, COUNT(*) as count')
            ->groupBy('action_type')
            ->get()
            ->getResultArray();

        // Get average execution time
        $avgExecution = $this->builder()
            ->selectAvg('execution_time_ms', 'avg_time')
            ->get()
            ->getRow();

        // Get actions per day for last 30 days
        $dailyStats = $this->builder()
            ->select('DATE(created_at) as date, COUNT(*) as count')
            ->where('created_at >=', date('Y-m-d', strtotime('-30 days')))
            ->groupBy('DATE(created_at)')
            ->orderBy('date', 'ASC')
            ->get()
            ->getResultArray();

        return [
            'total' => $totalCount,
            'by_type' => $typeStats,
            'avg_execution_time_ms' => round($avgExecution->avg_time ?? 0, 2),
            'daily_stats' => $dailyStats
        ];
    }

    /**
     * Delete old audit records (cleanup utility)
     *
     * @param int $daysOld Number of days to keep audit records
     * @return int Number of deleted records
     */
    public function deleteOldAuditRecords(int $daysOld = 90): int
    {
        $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$daysOld} days"));

        return $this->where('created_at <', $cutoffDate)->delete();
    }

    /**
     * Get audit records for a specific application
     *
     * @param string $applicationUuid
     * @return array
     */
    public function getAuditByApplication(string $applicationUuid): array
    {
        return $this->where('application_uuid', $applicationUuid)
            ->orderBy('created_at', 'DESC')
            ->findAll();
    }
}
