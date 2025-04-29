<?php

namespace App\Models\Housemanship;
use App\Models\MyBaseModel;

use App\Helpers\Interfaces\TableDisplayInterface;
use App\Helpers\Interfaces\FormInterface;

class HousemanshipFacilityCapacitiesModel extends MyBaseModel implements TableDisplayInterface, FormInterface
{
    protected $table = 'housemanship_facility_capacities';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'facility_name',
        'year',
        'discipline',
        'capacity'
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

    /**
     * get the capacity of each discipline for a given facility for a given year as key-value pair
     * @param string $facilityName
     * @param string $year
     * @return array
     */
    public function getFacilityYearCapacities($facilityName, $year)
    {
        try {
            $builder = $this->builder();
            $builder->where('facility_name', $facilityName);
            $builder->where('year', $year);
            $results = $builder->get()->getResultArray();
            $availability = [];
            foreach ($results as $result) {
                $availability[$result['discipline']] = $result['capacity'];
            }
            return $availability;
        } catch (\Exception $e) {
            log_message('error', 'Error fetching facility year capacities: ' . $e->getMessage());
            throw $e;
        }
    }

    public $searchFields = [
        'facility_name',
        'discipline'
    ];



    public function getDisplayColumns(): array
    {

        return ['facility_name', 'year', 'discipline', 'capacity'];
    }

    public function getDisplayColumnLabels(): array
    {
        return [];
    }







    public function getDisplayColumnFilters(): array
    {

        $default = [

            [
                "label" => "Facility Name",
                "name" => "facility_name",
                "type" => "api",
                "hint" => "",
                "options" => [],
                "value" => "",
                "required" => false,
                "api_url" => "housemanship/facilities/details",
                "apiKeyProperty" => "name",
                "apiLabelProperty" => "name",
                "apiType" => "select"
            ],
            [
                "label" => "Year",
                "name" => "year",
                "type" => "number",
                "hint" => "",
                "options" => [],
                "value" => "",
                "required" => false
            ],
            [
                "label" => "Discipline",
                "name" => "discipline",
                "type" => "select",
                "hint" => "",
                "options" => $this->getDistinctValuesAsKeyValuePairs('discipline'),
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
                "label" => "Facility Name",
                "name" => "facility_name",
                "type" => "api",
                "value" => "",
                "required" => true,
                "api_url" => "housemanship/facilities/details",
                "apiKeyProperty" => "name",
                "apiLabelProperty" => "name",
                "apiType" => "select"
            ],
            [
                "label" => "Year",
                "name" => "year",
                "type" => "number",
                "hint" => "",
                "options" => [],
                "value" => "",
                "required" => true
            ],
            [
                "label" => "Discipline",
                "name" => "discipline",
                "type" => "api",
                "hint" => "",
                "options" => [],
                "value" => "",
                "required" => true,
                "api_url" => "housemanship/disciplines",
                "apiKeyProperty" => "name",
                "apiLabelProperty" => "name",
                "apiType" => "select"
            ],
            [
                "label" => "Capacity",
                "name" => "capacity",
                "type" => "number",
                "hint" => "",
                "options" => [],
                "value" => "",
                "required" => true
            ]

        ];
    }
}

class DisciplineCapacity
{
    public string $discipline;
    public int $capacity;

    public function __construct(array $data)
    {
        $this->discipline = $data['discipline'];
        $this->capacity = (int) $data['capacity'];
    }
}
