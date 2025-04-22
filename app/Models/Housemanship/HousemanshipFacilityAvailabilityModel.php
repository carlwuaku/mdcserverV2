<?php

namespace App\Models\Housemanship;

use App\Helpers\Utils;
use App\Models\MyBaseModel;
use App\Helpers\Interfaces\TableDisplayInterface;
use App\Helpers\Interfaces\FormInterface;
use App\Helpers\Enums\HousemanshipSetting;

class HousemanshipFacilityAvailabilityModel extends MyBaseModel implements TableDisplayInterface, FormInterface
{
    protected $table = 'housemanship_facility_availability';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'facility_name',
        'year',
        'category',
        'available'
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
        'facility_name',
        'year',
        'category'
    ];

    /**
     * get the availability of each category for a given facility for a given year as key-value pair
     * @param string $facilityName
     * @param string $year
     * @return array
     */
    public function getFacilityYearAvailabilities($facilityName, $year)
    {
        try {
            $builder = $this->builder();
            $builder->where('facility_name', $facilityName);
            $builder->where('year', $year);
            $results = $builder->get()->getResultArray();
            $availability = [];
            foreach ($results as $result) {
                $availability[$result['category']] = $result['available'];
            }
            return $availability;
        } catch (\Exception $e) {
            log_message('error', 'Error fetching facility year availabilities: ' . $e->getMessage());
            throw $e;
        }
    }





    public function getDisplayColumns(): array
    {

        return ['facility_name', 'year', 'category', 'available'];
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
                "api_url" => "housemanship/facilities",
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
                "label" => "Category",
                "name" => "category",
                "type" => "select",
                "hint" => "",
                "options" => $this->getDistinctValuesAsKeyValuePairs('category'),
                "value" => "",
                "required" => false
            ]
        ];

        return $default;
    }




    public function getFormFields(): array
    {
        $categories = Utils::getHousemanshipSetting(HousemanshipSetting::AVAILABILITY_CATEGORIES);
        return [
            [
                "label" => "Facility Name",
                "name" => "facility_name",
                "type" => "api",
                "value" => "",
                "required" => true,
                "api_url" => "housemanship/facilities",
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
                "label" => "Category",
                "name" => "category",
                "type" => "text",
                "hint" => "",
                "options" => $categories,
                "value" => "",
                "required" => true
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

    /**
     * Get the distinct values of the availability categories as a string array
     * @param string $column
     * @return string[]
     */
    public function getAvailabilityCategories(): array
    {
        $categories = Utils::getHousemanshipSetting(HousemanshipSetting::AVAILABILITY_CATEGORIES);
        /**
         * @var string[]
         */
        $availabilityCategories = [];
        foreach ($categories as $category) {
            $availabilityCategories[] = $category['value'];
        }
        return $availabilityCategories;
    }
}

class CategoryAvailability
{
    public string $category;
    public int $available;

    public function __construct(array $data)
    {
        $this->category = $data['category'];
        $this->available = (int) $data['available'];
    }
}
