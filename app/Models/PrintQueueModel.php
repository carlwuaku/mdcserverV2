<?php

namespace App\Models;

use CodeIgniter\Model;

class PrintQueueModel extends MyBaseModel
{
    protected $table = 'print_queues';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = ['queue_name', 'template_uuid', 'status', 'created_by', 'priority'];

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

    // Get pending queues ordered by priority
    public function getPendingQueues()
    {
        return $this->where('status', 'pending')
            ->orderBy('priority', 'DESC')
            ->orderBy('created_at', 'ASC')
            ->findAll();
    }

    // Get queue with its items
    public function getQueueWithItems($queueUuid)
    {
        $queue = $this->find($queueUuid);

        if (!$queue) {
            return null;
        }

        $itemModel = new PrintQueueItemModel();
        $queue['items'] = $itemModel->where('queue_uuid', $queueUuid)
            ->orderBy('print_order', 'ASC')
            ->findAll();

        return $queue;
    }
}
