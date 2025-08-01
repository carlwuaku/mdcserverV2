<?php

namespace App\Models\Payments;

use App\Helpers\Interfaces\TableDisplayInterface;
use App\Helpers\Interfaces\FormInterface;
use App\Helpers\Utils;
use CodeIgniter\Database\BaseBuilder;
use App\Models\MyBaseModel;


class PaymentAuditLogModel extends MyBaseModel
{
    protected $table = 'payment_audit_logs';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'payment_uuid',
        'action',
        'old_status',
        'new_status',
        'changed_by',
        'change_reason'
    ];

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
    protected $validationRules = [
        'payment_uuid' => 'required|max_length[36]',
        'action' => 'required|max_length[50]',
        'old_status' => 'permit_empty|max_length[20]',
        'new_status' => 'permit_empty|max_length[20]',
        'changed_by' => 'permit_empty|max_length[255]',
        'change_reason' => 'permit_empty'
    ];
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

    public function getByPayment(string $paymentId): array
    {
        return $this->where('payment_id', $paymentId)
            ->orderBy('created_at', 'DESC')
            ->findAll();
    }

    public function logStatusChange(
        string $paymentId,
        string $oldStatus,
        string $newStatus,
        string $changedBy,
        string $reason = ''
    ): bool {
        return $this->insert([
            'payment_id' => $paymentId,
            'action' => 'status_changed',
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'changed_by' => $changedBy,
            'change_reason' => $reason
        ]);
    }

    public function logAction(
        string $paymentId,
        string $action,
        string $changedBy,
        string $reason = '',
        ?string $newStatus = null
    ): bool {
        return $this->insert([
            'payment_id' => $paymentId,
            'action' => $action,
            'new_status' => $newStatus,
            'changed_by' => $changedBy,
            'change_reason' => $reason
        ]);
    }

    public function getRecentActivity(int $limit = 50): array
    {
        $db = \Config\Database::connect();

        return $db->table('payment_audit_log pal')
            ->select('pal.*, p.amount, i.invoice_number, l.holder_name')
            ->join('payments p', 'pal.payment_id = p.payment_id')
            ->join('invoices i', 'p.invoice_id = i.invoice_id')
            ->join('licenses l', 'i.license_id = l.license_id')
            ->orderBy('pal.created_at', 'DESC')
            ->limit($limit)
            ->get()
            ->getResultArray();
    }
}
