<?php

namespace App\Models\Payments;

use App\Helpers\Interfaces\TableDisplayInterface;
use App\Helpers\Interfaces\FormInterface;
use App\Helpers\Utils;
use CodeIgniter\Database\BaseBuilder;
use App\Models\MyBaseModel;


class PaymentMethodModel extends MyBaseModel
{
    protected $table = 'payment_methods';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = ['method_code', 'method_name', 'method_type', 'is_active'];

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
        'method_code' => 'required|max_length[50]|is_unique[payment_methods.method_code,method_id,{method_id}]',
        'method_name' => 'required|max_length[100]',
        'method_type' => 'required|in_list[online,offline]',
        'is_active' => 'permit_empty|in_list[0,1]'
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

    public function findByCode(string $code): ?array
    {
        return $this->where('method_code', $code)
            ->where('is_active', 1)
            ->first();
    }

    public function getByType(string $type): array
    {
        return $this->where('method_type', $type)
            ->where('is_active', 1)
            ->orderBy('method_name', 'ASC')
            ->findAll();
    }

    public function getActive(): array
    {
        return $this->where('is_active', 1)
            ->orderBy('method_name', 'ASC')
            ->findAll();
    }
}
