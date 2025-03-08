<?php

namespace App\Models;

use CodeIgniter\Model;

class PrintHistoryModel extends MyBaseModel
{
    protected $table = 'print_history';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = ['queue_uuid', 'item_uuid', 'printed_by', 'print_status', 'error_details'];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected array $casts = [];
    protected array $castHandlers = [];

    // Dates
    protected $useTimestamps = false;
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

    public function recordPrint($queueUuid, $itemUuid, $userId)
    {
        return $this->insert([
            'queue_uuid' => $queueUuid,
            'item_uuid' => $itemUuid,
            'printed_by' => $userId,
            'print_status' => 'success'
        ]);
    }

    // Record a failed print
    public function recordFailure($queueUuid, $itemUuid, $userId, $errorDetails)
    {
        return $this->insert([
            'queue_uuid' => $queueUuid,
            'item_uuid' => $itemUuid,
            'printed_by' => $userId,
            'print_status' => 'failed',
            'error_details' => $errorDetails
        ]);
    }
}
