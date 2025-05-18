<?php

namespace App\Models\Housemanship;

use App\Helpers\Interfaces\TableDisplayInterface;
use App\Helpers\Interfaces\FormInterface;
use App\Models\MyBaseModel;
use App\Helpers\Utils;
use App\Helpers\Enums\HousemanshipSetting;
use CodeIgniter\Database\BaseBuilder;
class HousemanshipPostingsModel extends MyBaseModel implements TableDisplayInterface, FormInterface
{
    protected $table = 'housemanship_postings';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [

        'license_number',
        'type',
        'category',
        'session',
        'year',
        'letter_template',
        'practitioner_details'

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
        'license_number'
    ];



    public function getDisplayColumns(): array
    {

        return [
            'first_name',
            'last_name',
            'middle_name',
            'license_number',
            'type',
            'category',
            'session',
            'year',
            'letter_template'
        ];
    }

    public function getDisplayColumnLabels(): array
    {
        return [];
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
                "label" => "Session",
                "name" => "session",
                "type" => "select",
                "hint" => "",
                "options" => $this->getDistinctValuesAsKeyValuePairs('session'),
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
            ],


            [
                "label" => "Letter Template",
                "name" => "letter_template",
                "type" => "api",
                "value" => "",
                "required" => false,
                "api_url" => "print-queue/templates",
                "apiKeyProperty" => "template_name",
                "apiLabelProperty" => "template_name",
                "apiType" => "select"
            ]
        ];
    }

    public function getHousemanshipSessions()
    {
        $sessionSetting = Utils::getHousemanshipSetting(HousemanshipSetting::SESSIONS);
        return array_keys($sessionSetting);
    }

    /**
     * Adds practitioner details fields to the query builder.
     *
     * This function selects specific fields ('first_name', 'last_name', 'middle_name') 
     * from a JSON column named 'practitioner_details' and adds them to the query builder.
     * Each field is extracted from the JSON using JSON_UNQUOTE and JSON_EXTRACT 
     * functions and is aliased with its original name.
     *
     * @param \CodeIgniter\Database\BaseBuilder $builder The query builder to which the fields are added
     * @return \CodeIgniter\Database\BaseBuilder The modified query builder
     */

    public function addPractitionerDetailsFields(BaseBuilder $builder)
    {
        $fields = ["first_name", "last_name", "middle_name"];
        foreach ($fields as $field) {
            $builder->select('JSON_UNQUOTE(JSON_EXTRACT(practitioner_details, "$.' . $field . '")) as ' . $field);
        }
        return $builder;
    }



}
