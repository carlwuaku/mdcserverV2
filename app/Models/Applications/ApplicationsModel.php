<?php

namespace App\Models\Applications;

use App\Helpers\Interfaces\TableDisplayInterface;
use CodeIgniter\Database\BaseBuilder;
use CodeIgniter\Model;
use App\Models\MyBaseModel;

class ApplicationsModel extends MyBaseModel implements TableDisplayInterface
{
    protected $table = 'application_forms';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'uuid',
        'practitioner_type',
        'form_type',
        'picture',
        'first_name',
        'middle_name',
        'last_name',
        'application_code',
        'form_data',
        'status',
        'created_on',
        'deleted_at',
        'email',
        'phone',
        'qr_code'

    ];

    protected bool $allowEmptyInserts = false;

    // Dates
    protected $useTimestamps = false;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_on';
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
        'practitioner_type',
        'status',
        'form_type',
        'first_name',
        'middle_name',
        'last_name',
        'email',
        'phone',
        'application_code',
    ];

    public function getDisplayColumns(): array
    {
        return [
            'picture',
            'status',
            'form_type',
            'first_name',
            'middle_name',
            'last_name',
            'email',
            'phone',
            'application_code',
            'form_data',//this is a placeholder. if there are form fields they will be displayed here
            'created_on',
            'deleted_at',

        ];
    }

    public function getDisplayColumnLabels(): array
    {
        return [
        ];
    }



    public function getTableName(): string
    {
        return $this->table;
    }



    public function getDisplayColumnFilters(): array
    {
        return [
            [
                "label" => "Practitioner Type",
                "name" => "practitioner_type",
                "type" => "select",
                "hint" => "",
                "options" => $this->getDistinctValuesAsKeyValuePairs('practitioner_type'),
                "value" => "",
                "required" => false
            ],
            [
                "label" => "Application Type",
                "name" => "form_type",
                "type" => "select",
                "hint" => "",
                "options" => $this->getDistinctValuesAsKeyValuePairs('form_type'),
                "value" => "",
                "required" => false
            ],
            [
                "label" => "Date created",
                "name" => "created_on",
                "type" => "date",
                "hint" => "",
                "options" => [],
                "value" => "",
                "required" => false
            ],
            [
                "label" => "Status",
                "name" => "status",
                "type" => "select",
                "hint" => "",
                "options" => $this->getDistinctValuesAsKeyValuePairs('status'),
                "value" => "",
                "required" => false
            ],
        ];
    }

    public function addCustomFields(BaseBuilder $builder): BaseBuilder
    {
        $fields = [
            'uuid',
            'practitioner_type',
            'status',
            'form_type',
            'first_name',
            'middle_name',
            'last_name',
            'email',
            'phone',
            'application_code',
            'form_data',
            'created_on',
            'deleted_at',
        ];
        $builder->select("*")->
            select("CONCAT('" . base_url("file-server/image-render") . "','/applications/'," . "picture) as picture");
        ;
        return $builder;
    }
}
