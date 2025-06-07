<?php

namespace App\Models\Applications;

use App\Helpers\Interfaces\TableDisplayInterface;
use CodeIgniter\Database\BaseBuilder;
use App\Models\MyBaseModel;

class ApplicationTemplateModel extends MyBaseModel implements TableDisplayInterface
{
    protected $table = 'application_form_templates';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'uuid',
        'form_name',
        'description',
        'guidelines',
        'header',
        'footer',
        'data',
        'open_date',
        'close_date',
        'on_submit_email',
        'on_submit_message',
        'on_approve_email_template',
        'on_deny_email_template',
        'approve_url',
        'deny_url',
        'stages',
        'initialStage',
        'finalStage',
        'restrictions',
        'available_externally',
        'picture',
        'created_on',
        'updated_at',
        'deleted_at',

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
        'form_name',
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
        //return the $allowedFields without the uuid, created_on, updated_at, deleted_at
        return [
            'form_name',
            'description',
            'open_date',
            'close_date',
            'guidelines',
            'on_submit_message',
            'initialStage',
            'finalStage',
            'restrictions',
            'available_externally',
            'picture',
            'created_on',
            'updated_at',
            'deleted_at',

        ];
    }

    public function getDisplayColumnLabels(): array
    {
        return [
            "initialStage" => "Initial Stage",
            "finalStage" => "Final Stage",
        ];
    }



    public function getTableName(): string
    {
        return $this->table;
    }



    public function getDisplayColumnFilters(): array
    {
        return [];
    }

    public function addCustomFields(BaseBuilder $builder): BaseBuilder
    {
        return $builder;
    }
}
