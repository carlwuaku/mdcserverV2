<?php

namespace App\Models\Payments;

use CodeIgniter\Model;

class PaymentModel extends Model
{
    protected $table = 'payments';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'invoice_number',
        'method_name',
        'amount',
        'currency',
        'payment_date',
        'status',
        'reference_number',
        'notes'
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
        'invoice_number' => 'required|not_unique[invoices.invoice_number]',
        'method_name' => 'required|not_unique[payment_methods.method_name]',
        'amount' => 'required|decimal|greater_than[0]',
        'currency' => 'permit_empty|max_length[3]',
        'payment_date' => 'required|valid_date',
        'status' => 'required|in_list[pending,completed,failed,refunded]',
        'reference_number' => 'permit_empty|max_length[100]',
        'notes' => 'permit_empty'
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


    protected function createAuditLog(array $data): array
    {
        if (isset($data['id'])) {
            $auditModel = new PaymentAuditLogModel();
            $auditModel->insert([
                'payment_id' => $data['id'],
                'action' => isset($data['insert']) ? 'created' : 'updated',
                'new_status' => $data['data']['status'] ?? null,
                'changed_by' => session('user_id') ?? 'system',
                'change_reason' => 'Payment ' . (isset($data['insert']) ? 'created' : 'updated')
            ]);
        }
        return $data;
    }

    public function findByReference(string $reference): ?array
    {
        return $this->where('reference_number', $reference)->first();
    }

    public function getWithDetails(string $paymentId): ?array
    {
        $db = \Config\Database::connect();

        return $db->table('payments p')
            ->select('p.*, i.invoice_number, i.amount as invoice_amount, 
                           l.license_number, l.holder_name, pm.method_name, pm.method_type')
            ->join('invoices i', 'p.invoice_id = i.invoice_id')
            ->join('licenses l', 'i.license_id = l.license_id')
            ->join('payment_methods pm', 'p.method_id = pm.method_id')
            ->where('p.payment_id', $paymentId)
            ->get()
            ->getRowArray();
    }

    public function getByInvoice(string $invoiceId): array
    {
        return $this->where('invoice_id', $invoiceId)
            ->orderBy('payment_date', 'DESC')
            ->findAll();
    }

    public function getTotalPaidForInvoice(string $invoiceId): float
    {
        $result = $this->where('invoice_id', $invoiceId)
            ->where('status', 'completed')
            ->selectSum('amount')
            ->first();

        return (float) ($result['amount'] ?? 0);
    }

    public function getRecentPayments(int $limit = 10): array
    {
        $db = \Config\Database::connect();

        return $db->table('payments p')
            ->select('p.*, i.invoice_number, l.holder_name, pm.method_name')
            ->join('invoices i', 'p.invoice_id = i.invoice_id')
            ->join('licenses l', 'i.license_id = l.license_id')
            ->join('payment_methods pm', 'p.method_id = pm.method_id')
            ->orderBy('p.payment_date', 'DESC')
            ->limit($limit)
            ->get()
            ->getResultArray();
    }

    public function getPaymentSummary(array $filters = []): array
    {
        $builder = $this->builder();

        if (!empty($filters['status'])) {
            $builder->where('status', $filters['status']);
        }

        if (!empty($filters['date_from'])) {
            $builder->where('payment_date >=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $builder->where('payment_date <=', $filters['date_to'] . ' 23:59:59');
        }

        return $builder->select('COUNT(*) as total_count, SUM(amount) as total_amount, 
                                AVG(amount) as average_amount')
            ->get()
            ->getRowArray();
    }

    public function markAsCompleted(string $paymentId, ?string $referenceNumber = null): bool
    {
        $updateData = ['status' => 'completed'];

        if ($referenceNumber) {
            $updateData['reference_number'] = $referenceNumber;
        }

        return $this->update($paymentId, $updateData);
    }

    public function markAsFailed(string $paymentId, ?string $reason = null): bool
    {
        $updateData = ['status' => 'failed'];

        if ($reason) {
            $updateData['notes'] = $reason;
        }

        return $this->update($paymentId, $updateData);
    }
}
