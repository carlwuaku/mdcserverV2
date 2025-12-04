<?php

namespace App\Models\ApiIntegration;

use App\Models\MyBaseModel;

class ApiRequestLogModel extends MyBaseModel
{
    protected $table = 'api_requests_log';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'api_key_id',
        'institution_id',
        'request_id',
        'method',
        'endpoint',
        'query_params',
        'request_body_size',
        'response_status',
        'response_time_ms',
        'ip_address',
        'user_agent',
        'error_message',
    ];

    protected bool $allowEmptyInserts = false;

    protected array $casts = [
        'query_params' => 'json',
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = null;
    protected $deletedField = null;

    // Validation
    protected $validationRules = [];
    protected $validationMessages = [];
    protected $skipValidation = false;
    protected $cleanValidationRules = true;

    public $searchFields = [
        'endpoint',
        'ip_address',
        'method',
    ];

    /**
     * Log an API request
     */
    public function logRequest(array $data): bool
    {
        // Generate unique request ID if not provided
        if (empty($data['request_id'])) {
            $data['request_id'] = $this->generateRequestId();
        }

        return $this->insert($data) !== false;
    }

    /**
     * Generate unique request ID
     */
    private function generateRequestId(): string
    {
        return uniqid('req_', true) . '_' . bin2hex(random_bytes(8));
    }

    /**
     * Get recent logs for an API key
     */
    public function getRecentByApiKey(string $apiKeyId, int $limit = 100)
    {
        return $this->where('api_key_id', $apiKeyId)
            ->orderBy('created_at', 'DESC')
            ->limit($limit)
            ->findAll();
    }

    /**
     * Get recent logs for an institution
     */
    public function getRecentByInstitution(string $institutionId, int $limit = 100)
    {
        return $this->where('institution_id', $institutionId)
            ->orderBy('created_at', 'DESC')
            ->limit($limit)
            ->findAll();
    }

    /**
     * Get request statistics for an API key
     */
    public function getStatsByApiKey(string $apiKeyId, ?string $startDate = null, ?string $endDate = null): array
    {
        $builder = $this->where('api_key_id', $apiKeyId);

        if ($startDate) {
            $builder->where('created_at >=', $startDate);
        }

        if ($endDate) {
            $builder->where('created_at <=', $endDate);
        }

        $stats = $builder->select(
            'COUNT(*) as total_requests,
            SUM(CASE WHEN response_status >= 200 AND response_status < 300 THEN 1 ELSE 0 END) as successful_requests,
            SUM(CASE WHEN response_status >= 400 THEN 1 ELSE 0 END) as failed_requests,
            AVG(response_time_ms) as avg_response_time_ms,
            MAX(response_time_ms) as max_response_time_ms,
            MIN(response_time_ms) as min_response_time_ms'
        )->first();

        return $stats ?? [
            'total_requests' => 0,
            'successful_requests' => 0,
            'failed_requests' => 0,
            'avg_response_time_ms' => 0,
            'max_response_time_ms' => 0,
            'min_response_time_ms' => 0,
        ];
    }

    /**
     * Get request count for rate limiting
     */
    public function getRequestCount(string $apiKeyId, int $seconds): int
    {
        $since = date('Y-m-d H:i:s', time() - $seconds);

        return $this->where('api_key_id', $apiKeyId)
            ->where('created_at >=', $since)
            ->countAllResults();
    }

    /**
     * Get failed requests for analysis
     */
    public function getFailedRequests(string $apiKeyId, int $limit = 50)
    {
        return $this->where('api_key_id', $apiKeyId)
            ->where('response_status >=', 400)
            ->orderBy('created_at', 'DESC')
            ->limit($limit)
            ->findAll();
    }

    /**
     * Clean up old logs (for maintenance)
     */
    public function cleanupOldLogs(int $daysToKeep = 90): int
    {
        $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$daysToKeep} days"));

        return $this->where('created_at <', $cutoffDate)->delete();
    }
}
