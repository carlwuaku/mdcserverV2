<?php

namespace App\Models\Payments;

use App\Helpers\Interfaces\TableDisplayInterface;
use App\Helpers\Interfaces\FormInterface;
use App\Helpers\Utils;
use CodeIgniter\Database\BaseBuilder;
use App\Models\MyBaseModel;


class PaymentPurposeModel extends MyBaseModel
{
    protected $table = 'payment_purposes';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = ['purpose_code', 'purpose_name', 'description', 'is_active'];

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
        'purpose_code' => 'required|max_length[50]|is_unique[payment_purposes.purpose_code,purpose_id,{purpose_id}]',
        'purpose_name' => 'required|max_length[100]',
        'description' => 'permit_empty',
        'is_active' => 'permit_empty|in_list[0,1]'
    ];
    protected $validationMessages = [
        'purpose_code' => [
            'required' => 'Purpose code is required',
            'is_unique' => 'Purpose code already exists'
        ]
    ];
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

    public function getActive(): array
    {
        return $this->where('is_active', 1)
            ->orderBy('purpose_name', 'ASC')
            ->findAll();
    }

    public function findByCode(string $code): ?array
    {
        return $this->where('purpose_code', $code)
            ->where('is_active', 1)
            ->first();
    }

    /**
     * Gets all distinct purpose names as key-value pairs, with the key and value being the same.
     * @return array
     */
    public function getPurposeNamesForSelect()
    {
        return $this->getDistinctValuesAsKeyValuePairs('purpose_name');
    }
}
