<?php

namespace App\Models\Payments;

use App\Helpers\Interfaces\TableDisplayInterface;
use App\Helpers\Interfaces\FormInterface;
use App\Helpers\Utils;
use CodeIgniter\Database\BaseBuilder;
use App\Models\MyBaseModel;


class PaymentFileModel extends MyBaseModel
{
    protected $table = 'payment_files';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'payment_uuid',
        'file_name',
        'file_path',
        'file_size',
        'file_type',
        'file_category',
        'uploaded_by'
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
        'file_name' => 'required|max_length[255]',
        'file_path' => 'required|max_length[500]',
        'file_size' => 'permit_empty|integer',
        'file_type' => 'permit_empty|max_length[100]',
        'file_category' => 'required|in_list[receipt,proof_of_payment,bank_statement,other]',
        'uploaded_by' => 'permit_empty|max_length[255]'
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
            ->orderBy('uploaded_at', 'DESC')
            ->findAll();
    }

    public function deleteFile(string $fileId): bool
    {
        $file = $this->find($fileId);

        if ($file && file_exists($file['file_path'])) {
            unlink($file['file_path']);
        }

        return $this->delete($fileId);
    }

    public function getTotalSizeByPayment(string $paymentId): int
    {
        $result = $this->where('payment_id', $paymentId)
            ->selectSum('file_size')
            ->first();

        return (int) ($result['file_size'] ?? 0);
    }
}
