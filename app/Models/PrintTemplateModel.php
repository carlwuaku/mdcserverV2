<?php

namespace App\Models;
use App\Helpers\Interfaces\TableDisplayInterface;
use CodeIgniter\Model;

class PrintTemplateModel extends MyBaseModel implements TableDisplayInterface
{
    protected $table = 'print_templates';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = ['uuid', 'template_name', 'template_content', 'created_by', 'active', 'is_default'];

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

    protected function generateUuid(array $data)
    {
        if (!isset($data['data']['uuid'])) {
            $data['data']['uuid'] = \Ramsey\Uuid\Uuid::uuid4()->toString();
        }
        return $data;
    }

    public function getActiveTemplates()
    {
        return $this->where('active', true)->findAll();
    }

    public function getDisplayColumns(): array
    {
        return [
            'template_name',
            'active',
            'created_at'
        ];
    }

    public function getDisplayColumnLabels(): array
    {
        return [];
    }

    public function getDisplayColumnFilters(): array
    {
        return [];
    }

    public $searchFields = [
        'template_name',
        'template_content'
    ];

}
