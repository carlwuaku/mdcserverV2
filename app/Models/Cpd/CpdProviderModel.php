<?php

namespace App\Models\Cpd;
use App\Models\MyBaseModel;
use App\Helpers\Interfaces\TableDisplayInterface;
use CodeIgniter\Database\BaseBuilder;

class CpdProviderModel extends MyBaseModel implements TableDisplayInterface
{
    protected $table = 'cpd_providers';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'name',
        'created_on',
        'location',
        'phone',
        'email'
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
        'name',
        'location',
        'phone',
        'email'
    ];

    public function getDisplayColumns(): array
    {
        return [
            'name',
            'number_of_cpd',
            'created_on',
            'location',
            'phone',
            'email'
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
        $builder->select("{$this->table}.*, count(cpd_topics.id) as number_of_cpd")->
            join("cpd_topics", "cpd_topics.provider_uuid = {$this->table}.uuid", "left")->
            groupBy("{$this->table}.id");
        ;
        return $builder;
    }

}
