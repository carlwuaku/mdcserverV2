<?php

namespace App\Models\Housemanship;

use App\Helpers\Interfaces\TableDisplayInterface;
use App\Helpers\Interfaces\FormInterface;
use App\Models\MyBaseModel;

class HousemanshipFacilitiesModel extends MyBaseModel implements TableDisplayInterface, FormInterface
{
    protected $table = 'housemanship_facilities';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'name',
        'region',
        'location',
        'type',
        'phone',
        'email'
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

        return ['name', 'region', 'location', 'type', 'phone', 'email'];
    }

    public function getDisplayColumnLabels(): array
    {
        return [];
    }







    public function getDisplayColumnFilters(): array
    {

        $default = [
            [
                "label" => "Region",
                "name" => "region",
                "type" => "select",
                "hint" => "",
                "options" => $this->getDistinctValuesAsKeyValuePairs('region'),
                "value" => "",
                "required" => false
            ]
        ];

        return $default;
    }




    public function getFormFields(): array
    {

        return [
            [
                "label" => "Name",
                "name" => "name",
                "type" => "text",
                "hint" => "",
                "options" => [],
                "value" => "",
                "required" => true,
                "showOnly" => false
            ],
            [
                "label" => "Region",
                "name" => "region",
                "hint" => "",
                "options" => [],
                "type" => "api",
                "value" => "",
                "required" => true,
                "api_url" => "regions/regions",
                "apiKeyProperty" => "name",
                "apiLabelProperty" => "name",
                "apiType" => "select"
            ],

            [
                "label" => "Location",
                "name" => "location",
                "type" => "text",
                "hint" => "",
                "options" => [],
                "value" => "",
                "required" => true
            ],
            [
                "label" => "Facility Type",
                "name" => "type",
                "type" => "api",
                "value" => "",
                "required" => false,
                "api_url" => "admin/settings/Doctors.work_institution_types",
                "apiKeyProperty" => "name",
                "apiLabelProperty" => "name",
                "apiType" => "datalist"
            ],
            [
                "label" => "Phone",
                "name" => "phone",
                "type" => "text",
                "hint" => "",
                "options" => [],
                "value" => "",
                "required" => false
            ],
            [
                "label" => "Email",
                "name" => "email",
                "type" => "email",
                "hint" => "",
                "options" => [],
                "value" => "",
                "required" => false
            ]

        ];
    }

}
