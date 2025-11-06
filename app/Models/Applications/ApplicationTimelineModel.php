<?php

namespace App\Models\Applications;

use App\Models\MyBaseModel;
use App\Models\UsersModel;
use App\Models\Applications\ApplicationsModel;

class ApplicationTimelineModel extends MyBaseModel
{
    protected $table = 'application_timeline';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    protected $protectFields = true;
    protected $allowedFields = [
        'uuid',
        'application_uuid',
        'user_id',
        'from_status',
        'to_status',
        'stage_data',
        'actions_executed',
        'actions_results',
        'submitted_data',
        'notes',
        'ip_address',
        'user_agent',
    ];

    protected bool $allowEmptyInserts = false;

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = 'deleted_at';

    // Validation
    protected $validationRules = [
        'application_uuid' => 'required|max_length[36]',
        'to_status' => 'required|max_length[255]',
    ];
    protected $validationMessages = [];
    protected $skipValidation = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert = ['encodeJsonFields'];
    protected $beforeUpdate = ['encodeJsonFields'];
    protected $afterFind = ['decodeJsonFields'];

    /**
     * Encode JSON fields before insert/update
     */
    protected function encodeJsonFields(array $data): array
    {
        $jsonFields = ['stage_data', 'actions_executed', 'actions_results', 'submitted_data'];

        foreach ($jsonFields as $field) {
            if (isset($data['data'][$field]) && is_array($data['data'][$field])) {
                $data['data'][$field] = json_encode($data['data'][$field]);
            }
        }

        return $data;
    }

    /**
     * Decode JSON fields after find
     */
    protected function decodeJsonFields(array $data): array
    {
        $jsonFields = ['stage_data', 'actions_executed', 'actions_results', 'submitted_data'];

        if (isset($data['data'])) {
            // Single record
            if (is_array($data['data']) && !isset($data['data'][0])) {
                foreach ($jsonFields as $field) {
                    if (isset($data['data'][$field]) && is_string($data['data'][$field])) {
                        $data['data'][$field] = json_decode($data['data'][$field], true);
                    }
                }
            }
            // Multiple records
            else if (isset($data['data'][0])) {
                foreach ($data['data'] as $key => $record) {
                    foreach ($jsonFields as $field) {
                        if (isset($record[$field]) && is_string($record[$field])) {
                            $data['data'][$key][$field] = json_decode($record[$field], true);
                        }
                    }
                }
            }
        }

        return $data;
    }

    /**
     * Get timeline for a specific application
     *
     * @param string $applicationUuid
     * @param UsersModel $userData
     * @param array $options Optional parameters (limit, offset, orderBy, orderDir)
     * @return array
     */
    public function getApplicationTimeline(string $applicationUuid, UsersModel $userData, array $options = []): array
    {
        // Ensure creation event exists for existing applications
        $this->ensureCreationEventExists($applicationUuid);

        $limit = $options['limit'] ?? 100;
        $offset = $options['offset'] ?? 0;
        $orderBy = $options['orderBy'] ?? 'created_at';
        $orderDir = $options['orderDir'] ?? 'DESC';

        $builder = $this->builder();
        $builder->select('application_timeline.*, users.username, users.email as user_email')
            ->join('users', 'users.id = application_timeline.user_id', 'left')
            ->where('application_timeline.application_uuid', $applicationUuid)
            ->orderBy($orderBy, $orderDir)
            ->limit($limit, $offset);

        $results = $builder->get()->getResultArray();
        $isAdmin = $userData->isAdmin();
        $adminOnlyFields = ['stage_data', 'actions_executed', 'actions_results', 'submitted_data', 'ip_address', 'user_agent', 'username', 'user_email'];
        // Decode JSON fields manually since afterFind callback doesn't work with builder
        foreach ($results as &$result) {
            $jsonFields = ['stage_data', 'actions_executed', 'actions_results', 'submitted_data'];
            foreach ($jsonFields as $field) {
                if (isset($result[$field]) && is_string($result[$field])) {
                    $result[$field] = json_decode($result[$field], true);
                }
            }

            if (!$isAdmin) {
                foreach ($adminOnlyFields as $field) {
                    if (isset($result[$field])) {
                        unset($result[$field]);
                    }
                }
            }
        }

        return $results;
    }

    /**
     * Ensure a creation event exists for the application
     * Creates one if it doesn't exist (for backward compatibility with existing applications)
     *
     * @param string $applicationUuid
     * @return void
     */
    private function ensureCreationEventExists(string $applicationUuid): void
    {
        // Check if a creation event exists (from_status is null)
        $creationEventExists = $this->where('application_uuid', $applicationUuid)
            ->where('from_status', null)
            ->countAllResults() > 0;

        if (!$creationEventExists) {
            // Get the application details
            $applicationsModel = new ApplicationsModel();
            $application = $applicationsModel->where('uuid', $applicationUuid)->first();

            if ($application) {
                // Create a backdated creation event
                $creationData = [
                    'application_uuid' => $applicationUuid,
                    'to_status' => 'Created',
                    'from_status' => null,
                    'user_id' => null, // Unknown for existing applications
                    'notes' => 'Application created (auto-generated for existing application)',
                    'submitted_data' => null,
                    'stage_data' => null,
                    'actions_executed' => null,
                    'actions_results' => null,
                    'ip_address' => null,
                    'user_agent' => null,
                ];

                // Insert with the original created_at timestamp
                $db = $this->db;
                $builder = $db->table($this->table);
                $builder->insert(array_merge($creationData, [
                    'created_at' => $application['created_on'],
                    'updated_at' => $application['created_on'],
                ]));
            }
        }
    }

    /**
     * Get timeline count for an application
     *
     * @param string $applicationUuid
     * @return int
     */
    public function getTimelineCount(string $applicationUuid): int
    {
        return $this->where('application_uuid', $applicationUuid)->countAllResults();
    }

    /**
     * Get latest status change for an application
     *
     * @param string $applicationUuid
     * @return array|null
     */
    public function getLatestStatusChange(string $applicationUuid): ?array
    {
        $result = $this->where('application_uuid', $applicationUuid)
            ->orderBy('created_at', 'DESC')
            ->first();

        return $result ?: null;
    }

    /**
     * Create a timeline entry
     *
     * @param string $applicationUuid
     * @param string $toStatus
     * @param array $options Optional data (fromStatus, userId, stageData, actions, etc.)
     * @return bool|int
     */
    public function createTimelineEntry(
        string $applicationUuid,
        string $toStatus,
        array $options = []
    ) {
        $data = [
            'application_uuid' => $applicationUuid,
            'to_status' => $toStatus,
            'from_status' => $options['fromStatus'] ?? null,
            'user_id' => $options['userId'] ?? null,
            'stage_data' => $options['stageData'] ?? null,
            'actions_executed' => $options['actionsExecuted'] ?? null,
            'actions_results' => $options['actionsResults'] ?? null,
            'submitted_data' => $options['submittedData'] ?? null,
            'notes' => $options['notes'] ?? null,
            'ip_address' => $options['ipAddress'] ?? null,
            'user_agent' => $options['userAgent'] ?? null,
        ];

        return $this->insert($data);
    }

    /**
     * Get status history (list of status changes) for an application
     *
     * @param string $applicationUuid
     * @return array
     */
    public function getStatusHistory(string $applicationUuid): array
    {
        $builder = $this->builder();
        $builder->select('to_status, created_at, username')
            ->join('users', 'users.id = application_timeline.user_id', 'left')
            ->where('application_uuid', $applicationUuid)
            ->orderBy('created_at', 'ASC');

        return $builder->get()->getResultArray();
    }

    /**
     * Get timeline entries by user
     *
     * @param int $userId
     * @param array $options
     * @return array
     */
    public function getTimelineByUser(int $userId, array $options = []): array
    {
        $limit = $options['limit'] ?? 100;
        $offset = $options['offset'] ?? 0;

        $builder = $this->builder();
        $builder->select('application_timeline.*, application_forms.application_code, application_forms.form_type')
            ->join('application_forms', 'application_forms.uuid = application_timeline.application_uuid', 'left')
            ->where('application_timeline.user_id', $userId)
            ->orderBy('created_at', 'DESC')
            ->limit($limit, $offset);

        return $builder->get()->getResultArray();
    }
}
