<?php

namespace App\Models;
use App\Helpers\Interfaces\TableDisplayInterface;
use App\Models\MyBaseModel;

class EmailQueueLogModel extends MyBaseModel implements TableDisplayInterface
{
    protected $table = 'email_queue_log';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = ['email_queue_id', 'status', 'notes'];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected array $casts = [];
    protected array $castHandlers = [];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = 'deleted_at';

    // Validation
    protected $validationRules = [];
    protected $validationMessages = [];
    protected $skipValidation = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert = [];
    protected $afterInsert = [];
    protected $beforeUpdate = [];
    protected $afterUpdate = [];
    protected $beforeFind = [];
    protected $afterFind = [];
    protected $beforeDelete = [];
    protected $afterDelete = [];

    public function logStatusChange($emailQueueId, $status, $notes = null)
    {

        $this->insert([
            'email_queue_id' => $emailQueueId,
            'status' => $status,
            'notes' => $notes
        ]);
    }

    public $searchFields = [
        'subject',
        'message'
    ];

    public function getDisplayColumns(): array
    {
        return [
            'email_queue_id',
            'status',
            'notes'
        ];
    }

    public function getDisplayColumnLabels(): array
    {
        return [];
    }

    public function getDisplayColumnFilters(): array
    {
        return [];
    }
}
