<?php

namespace App\Models\Cpd;

use App\Helpers\Interfaces\TableDisplayInterface;
use App\Models\Licenses\LicensesModel;
use App\Models\MyBaseModel;
use CodeIgniter\Database\BaseBuilder;

class CpdAttendanceModel extends MyBaseModel implements TableDisplayInterface
{
    protected $table = 'cpd_attendance';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'cpd_id',
        'license_number',
        'attendance_date',
        'venue'
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
        'license_number',
    ];

    public function getDisplayColumns(): array
    {
        return [
            'license_number',
            'topic',
            'provider_name',
            'attendance_date',
            'credits',
            'category',
            'online',
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
        $cpdModel = new CpdModel();
        $licensesModel = new LicensesModel();
        $builder->select("{$this->table}.*, {$cpdModel->table}.uuid as cpd_uuid, topic, credits, category, CONCAT(' ',first_name, middle_name, last_name) as name")->
            join($cpdModel->table, "{$this->table}.cpd_id = {$cpdModel->table}.id")->
            join($licensesModel->table, "{$this->table}.license_number = {$licensesModel->table}.license_number");
        ;
        return $builder;
    }

}
