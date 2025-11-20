<?php

namespace App\Models\Housemanship;

use App\Helpers\Interfaces\TableDisplayInterface;
use App\Helpers\Interfaces\FormInterface;
use App\Models\MyBaseModel;
use CodeIgniter\Database\BaseBuilder;
class HousemanshipApplicationModel extends MyBaseModel implements TableDisplayInterface, FormInterface
{
    protected $table = 'housemanship_postings_applications';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'license_number',
        'date',
        'category',
        'type',
        'year',
        'session',
        'tags'
    ];

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

    public $searchFields = [
        'license_number',
    ];

    public $joinSearchFields = [
        'table' => 'licenses',
        'fields' => [
            'name',
            'license_number',
            'email',
            'phone'
        ],
        'joinCondition' => "licenses.license_number = housemanship_postings_applications.license_number",
    ];

    public function getDisplayColumns(): array
    {

        return [
            'first_name',
            'last_name',
            'middle_name',
            'license_number',
            'phone',
            'email',
            'type',
            'category',
            'session',
            'year',
            'tags'
        ];
    }









    public function getDisplayColumnFilters(): array
    {

        $default = [
            [
                "label" => "Practitioner type",
                "name" => "type",
                "type" => "select",
                "hint" => "",
                "options" => $this->getDistinctValuesAsKeyValuePairs('type'),
                "value" => "",
                "required" => false
            ],
            [
                "label" => "Practitioner category",
                "name" => "category",
                "type" => "select",
                "hint" => "",
                "options" => $this->getDistinctValuesAsKeyValuePairs('category'),
                "value" => "",
                "required" => false
            ],

            [
                "label" => "Year",
                "name" => "year",
                "type" => "select",
                "hint" => "",
                "options" => $this->getDistinctValuesAsKeyValuePairs('year'),
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
                "label" => "Practitioner Registration Number",
                "name" => "license_number",
                "hint" => "",
                "options" => [],
                "type" => "api",
                "value" => "",
                "required" => true,
                "api_url" => "licenses/details",
                "apiKeyProperty" => "license_number",
                "apiLabelProperty" => "name,license_number",
                "apiType" => "search"
            ],
            [
                "label" => "Year",
                "name" => "year",
                "hint" => "",
                "options" => [],
                "type" => "number",
                "value" => "",
                "required" => true,
                "api_url" => "",
                "apiKeyProperty" => "",
                "apiLabelProperty" => "",
                "apiType" => ""
            ]
        ];
    }

    public function getNonAdminFormFields(): array
    {

        return [

            //the tags should be inserted here
        ];
    }

    /**
     * Adds the practitioner details fields to the query builder.
     * This method is used to join the practitioners and licenses tables to get the details of the practitioner.
     * It selects the first name, last name, and middle name from the practitioners table and the phone and email from the licenses table.
     * The join is done on the license_number field.
     * The method returns the modified query builder.
     * @param \CodeIgniter\Database\BaseBuilder $builder
     * @param bool $joinLicenses Whether to join the licenses table or not. when a search is done, a join is done on the licenses table so it should be skipped here by setting it to false
     * @return BaseBuilder
     */
    public function addPractitionerDetailsFields(BaseBuilder $builder, bool $joinLicensesTable = false)
    {
        $practitionersTable = "practitioners";
        $practitionersTableFields = ["first_name", "last_name", "middle_name"];
        $licensesTable = "licenses";
        $licensesTableFields = ["phone", "email"];


        foreach ($practitionersTableFields as $field) {
            $builder->select($practitionersTable . "." . $field);
        }
        foreach ($licensesTableFields as $field) {
            $builder->select($licensesTable . "." . $field);
        }
        $builder->join($practitionersTable, "$practitionersTable.license_number = $this->table.license_number", "left");
        if ($joinLicensesTable) {
            $builder->join($licensesTable, "$licensesTable.license_number = $this->table.license_number", "left");
        }

        return $builder;
    }

    public function getNonAdminDisplayColumns($session = 1): array
    {
        if ($session == 1) {
            return [
                'discipline_1',
                'first_choice_1',
                'first_choice_region_1',
                'second_choice_1',

                'second_choice_region_1'
            ];
        }
        /**category
: 
"Medical"
created_at
: 
"2025-10-29 07:56:15"
date
: 
"0000-00-00"
deleted_at
: 
null
discipline_1
: 
"Surgery"
discipline_2
: 
"General medicine"
email
: 
"wuakuc2@gmail.com"
first_choice_1
: 
"ADIDOME GOV'T HOSPITAL"
first_choice_2
: 
"AFARI COMMUNITY HOSPITAL"
first_choice_region_1
: 
"Volta Region"
first_choice_region_2
: 
"Ashanti Region"
first_name
: 
"Carl"
id
: 
"89"
last_name
: 
"Wuaku"
license_number
: 
"MDC/PN/123456CARL"
middle_name
: 
""
phone
: 
"+233207085244"
second_choice_1
: 
"AFARI COMMUNITY HOSPITAL"
second_choice_2
: 
"ATUA GOVERNMENT HOSPITAL"
second_choice_region_1
: 
"Ashanti Region"
second_choice_region_2
: 
"Eastern Region"
session
: 
"2"
status
: 
"Pending"
tags
: 
""
type
: 
"Doctor"
updated_at
: 
"2025-10-29 07:56:15"
uuid
: 
"bee9756c-b49c-11f0-b40d-0e559479cb96"
year
: 
"2025" */
        return [
            'discipline_1',
            'first_choice_1',
            'first_choice_region_1',
            'second_choice_1',

            'second_choice_region_1',
            'discipline_2',
            'first_choice_2',
            'first_choice_region_2',
            'second_choice_2',

            'second_choice_region_2'
        ];
    }

    public function getDisplayColumnLabels(int $session = 1): array
    {
        if ($session == 1) {
            return [
                'facility_name_1' => 'Facility Name',
                'facility_region_1' => 'Region'
            ];
        }
        return [
            'facility_name_1' => 'Facility 1 Name',
            'facility_region_1' => 'Facility 1 Region',
            'facility_name_2' => 'Facility 2 Name',
            'facility_region_2' => 'Facility 2 Region',
            'discipline_1' => 'Discipline 1',
            'discipline_2' => 'Discipline 2'
        ];
    }

    public function getSortColumns(): array
    {

        return [
            'license_number',
            'date',
            'category',
            'type',
            'year',
            'session',
            'tags'
        ];
    }
}
