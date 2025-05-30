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
                "label" => "Direct entry",
                "name" => "tags",
                "type" => "text",
                "value" => "",
                "required" => false,
                "api_url" => "",
                "apiKeyProperty" => "",
                "apiLabelProperty" => "",
                "apiType" => ""
            ]
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

}
