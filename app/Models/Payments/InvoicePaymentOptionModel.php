<?php

namespace App\Models\Payments;

use App\Helpers\Interfaces\TableDisplayInterface;
use App\Helpers\Interfaces\FormInterface;
use App\Helpers\Utils;
use CodeIgniter\Database\BaseBuilder;
use App\Models\MyBaseModel;


class InvoicePaymentOptionModel extends MyBaseModel
{
    protected $table = 'invoice_payment_options';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = ['invoice_uuid', 'method_name'];

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

    public function getPaymentOptionsForInvoice(string $invoiceId): array
    {
        $db = \Config\Database::connect();

        return $db->table('invoice_payment_options ipo')
            ->select('pm.method_id, pm.method_code, pm.method_name, pm.method_type')
            ->join('payment_methods pm', 'ipo.method_id = pm.method_id')
            ->where('ipo.invoice_id', $invoiceId)
            ->where('pm.is_active', 1)
            ->orderBy('pm.method_type', 'ASC')
            ->orderBy('pm.method_name', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function setPaymentOptions(string $invoiceId, array $methodIds): bool
    {
        $db = \Config\Database::connect();

        // Start transaction
        $db->transStart();

        // Delete existing options
        $this->where('invoice_id', $invoiceId)->delete();

        // Insert new options
        $data = [];
        foreach ($methodIds as $methodId) {
            $data[] = [
                'invoice_id' => $invoiceId,
                'method_id' => $methodId,
                'created_at' => date('Y-m-d H:i:s')
            ];
        }

        if (!empty($data)) {
            $this->insertBatch($data);
        }

        $db->transComplete();

        return $db->transStatus();
    }

    public function hasOnlineOptions(string $invoiceId): bool
    {
        $db = \Config\Database::connect();

        $result = $db->table('invoice_payment_options ipo')
            ->join('payment_methods pm', 'ipo.method_id = pm.method_id')
            ->where('ipo.invoice_id', $invoiceId)
            ->where('pm.method_type', 'online')
            ->where('pm.is_active', 1)
            ->countAllResults();

        return $result > 0;
    }

    public function hasOfflineOptions(string $invoiceId): bool
    {
        $db = \Config\Database::connect();

        $result = $db->table('invoice_payment_options ipo')
            ->join('payment_methods pm', 'ipo.method_id = pm.method_id')
            ->where('ipo.invoice_id', $invoiceId)
            ->where('pm.method_type', 'offline')
            ->where('pm.is_active', 1)
            ->countAllResults();

        return $result > 0;
    }
}
