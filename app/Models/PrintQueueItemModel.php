<?php

namespace App\Models;

use CodeIgniter\Model;

class PrintQueueItemModel extends MyBaseModel
{
    protected $table = 'print_queue_items';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = ['queue_uuid', 'item_data', 'status', 'print_order', 'error_message'];

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

    // Custom method to format and prepare data
    public function prepareForPrinting($queueUuid)
    {
        $items = $this->where('queue_uuid', $queueUuid)
            ->where('status', 'pending')
            ->orderBy('print_order', 'ASC')
            ->findAll();

        return $items;
    }

    // Update status after printing
    public function markAsPrinted($itemId)
    {
        return $this->update($itemId, ['status' => 'printed']);
    }
}
