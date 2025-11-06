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
        'qr_code',
        'applicant_unique_id',
        'template'
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
        'applicant_unique_id'
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
            'applicant_unique_id',
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

    /**
     * Get basic statistics fields configuration from app settings
     *
     * @return array Array of field configurations for statistics reports
     */
    public function getBasicStatisticsFields(): array
    {
        $settings = \App\Helpers\Utils::getAppSettings('applicationForms');

        if (!$settings || !isset($settings['basicStatisticsFields'])) {
            // Return default fields if configuration is not found
            return [
                [
                    'label' => 'Form Type',
                    'name' => 'form_type',
                    'type' => 'bar',
                    'xAxisLabel' => 'Form Type',
                    'yAxisLabel' => 'Number of Applications'
                ],
                [
                    'label' => 'Status',
                    'name' => 'status',
                    'type' => 'bar',
                    'xAxisLabel' => 'Status',
                    'yAxisLabel' => 'Number of Applications'
                ]
            ];
        }

        return $settings['basicStatisticsFields'];
    }

    /**
     * Get basic statistics filter fields configuration from app settings
     *
     * @return array Array of filter field configurations
     */
    public function getBasicStatisticsFilterFields(): array
    {
        $settings = \App\Helpers\Utils::getAppSettings('applicationForms');

        if (!$settings || !isset($settings['basicStatisticsFilterFields'])) {
            return [];
        }

        return $settings['basicStatisticsFilterFields'];
    }
}
