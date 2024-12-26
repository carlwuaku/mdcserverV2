<?php

namespace App\Models\Cpd;

use App\Helpers\Interfaces\TableDisplayInterface;
use App\Models\MyBaseModel;
use CodeIgniter\Database\BaseBuilder;

class CpdModel extends MyBaseModel implements TableDisplayInterface
{
    protected $table = 'cpd_topics';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'topic',
        'date',
        'created_on',
        'created_by',
        'modified_on',
        'provider_id',
        'venue',
        'group',
        'credits',
        'category',
        'online',
        'url',
        'start_month',
        'end_month',
    ];

    protected bool $allowEmptyInserts = false;

    // Dates
    protected $useTimestamps = false;
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

    public $searchFields = [
        'topic'
    ];

    public function getDisplayColumns(): array
    {
        return [
            'topic',
            'date',
            'provider_name',
            'credits',
            'category',
            'online',
            'created_on',
            'created_by',
            'url',
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

    public function addCustomFields(BaseBuilder $builder): BaseBuilder
    {
        $providerModel = new CpdProviderModel();
        $builder->select("{$this->table}.*, {$providerModel->table}.name as provider_name")->
            join($providerModel->table, "{$this->table}.provider_id = {$providerModel->table}.id");
        ;
        return $builder;
    }


}
