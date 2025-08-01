<?php

namespace App\Models\Payments;

use App\Helpers\Interfaces\TableDisplayInterface;
use App\Helpers\Interfaces\FormInterface;
use App\Helpers\Utils;
use CodeIgniter\Database\BaseBuilder;
use App\Models\MyBaseModel;


class OfflinePaymentDetailModel extends MyBaseModel
{
    protected $table = 'offline_payment_details';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'payment_uuid',
        'bank_name',
        'branch_name',
        'deposit_slip_number',
        'teller_name',
        'verification_status',
        'verified_by',
        'verified_at',
        'verification_notes'
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
        'payment_uuid' => 'required[36]',
        'bank_name' => 'permit_empty|max_length[255]',
        'branch_name' => 'permit_empty|max_length[255]',
        'deposit_slip_number' => 'permit_empty|max_length[100]',
        'teller_name' => 'permit_empty|max_length[255]',
        'verification_status' => 'required|in_list[pending,verified,rejected]',
        'verified_by' => 'permit_empty|max_length[255]',
        'verification_notes' => 'permit_empty'
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

    public function findByPayment(string $paymentId): ?array
    {
        return $this->where('payment_id', $paymentId)->first();
    }

    public function getPendingVerifications(int $limit = null): array
    {
        $db = \Config\Database::connect();

        $builder = $db->table('offline_payment_details opd')
            ->select('opd.*, p.amount, p.payment_date, p.reference_number,
                              i.invoice_number, l.holder_name, l.holder_email')
            ->join('payments p', 'opd.payment_id = p.payment_id')
            ->join('invoices i', 'p.invoice_id = i.invoice_id')
            ->join('licenses l', 'i.license_id = l.license_id')
            ->where('opd.verification_status', 'pending')
            ->orderBy('p.payment_date', 'ASC');

        if ($limit) {
            $builder->limit($limit);
        }

        return $builder->get()->getResultArray();
    }

    public function verifyPayment(string $detailId, string $verifiedBy, string $notes = ''): bool
    {
        return $this->update($detailId, [
            'verification_status' => 'verified',
            'verified_by' => $verifiedBy,
            'verified_at' => date('Y-m-d H:i:s'),
            'verification_notes' => $notes
        ]);
    }

    public function rejectPayment(string $detailId, string $verifiedBy, string $reason): bool
    {
        return $this->update($detailId, [
            'verification_status' => 'rejected',
            'verified_by' => $verifiedBy,
            'verified_at' => date('Y-m-d H:i:s'),
            'verification_notes' => $reason
        ]);
    }

    public function getVerificationStats(): array
    {
        return $this->select('verification_status, COUNT(*) as count')
            ->groupBy('verification_status')
            ->findAll();
    }
}
