<?php

namespace App\Models\Housemanship;

use App\Helpers\Interfaces\TableDisplayInterface;
use App\Helpers\Interfaces\FormInterface;
use App\Helpers\Types\CriteriaType;
use App\Models\MyBaseModel;
use App\Helpers\Utils;
use App\Helpers\Enums\HousemanshipSetting;

class HousemanshipApplicationDetailsModel extends MyBaseModel implements FormInterface
{
    protected $table = 'housemanship_postings_application_details';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'discipline',
        'first_choice',
        'first_choice_region',
        'second_choice',
        'second_choice_region',
        'application_uuid'
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

    ];






    public function getFormFields(): array
    {

        return [
            [
                "label" => "Discpline",
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
                "label" => "First Choice",
                "name" => "first_choice",
                "type" => "api",
                "hint" => "",
                "options" => [],
                "value" => "",
                "required" => true,
                "api_url" => "housemanship/facilities/details",
                "apiKeyProperty" => "name",
                "apiLabelProperty" => "name",
                "apiType" => "select"
            ],
            [
                "label" => "Second Choice",
                "name" => "second_choice",
                "type" => "api",
                "hint" => "",
                "options" => [],
                "value" => "",
                "required" => true,
                "api_url" => "housemanship/facilities/details",
                "apiKeyProperty" => "name",
                "apiLabelProperty" => "name",
                "apiType" => "select"
            ]

        ];
    }


    /**
     * Returns form fields that can be used by non-admin users.
     * It excludes the facility regions provided in the $excludedfacilityRegions parameter.
     * The form fields are in the format: [
     *     [
     *         "label" => "Discipline",
     *         "name" => "discipline",
     *         "type" => "select",
     *         "hint" => "",
     *         "options" => [],
     *         "value" => "",
     *         "required" => false,
     *         "api_url" => "housemanship/disciplines",
     *         "apiKeyProperty" => "name",
     *         "apiLabelProperty" => "name",
     *         "apiType" => "select"
     *     ]
     * ]
     *
     * @param array $data
     * @param string[] $excludedfacilityRegions
     * @return array
     */
    public function getNonAdminFormFields(array $data, array $excludedfacilityRegions = [], array $excludedDisciplines = []): array
    {
        log_message("info", print_r($excludedDisciplines, true));
        $disciplinesList = $this->getDisciplinesList($data, $excludedDisciplines);
        $facilitiesList = $this->getFacilitiesList($data, $excludedfacilityRegions);
        return [
            [
                "label" => "Discpline",
                "name" => "discipline",
                "type" => "select",
                "hint" => "",
                "options" => $disciplinesList,
                "value" => "",
                "required" => false,
                "api_url" => "housemanship/disciplines",
                "apiKeyProperty" => "name",
                "apiLabelProperty" => "name",
                "apiType" => "select"
            ],
            [
                "label" => "First Choice",
                "name" => "first_choice",
                "type" => "select",
                "hint" => "",
                "options" => $facilitiesList,
                "value" => "",
                "required" => true,
                "api_url" => "",
                "apiKeyProperty" => "name",
                "apiLabelProperty" => "name",
                "apiType" => "select"
            ],
            [
                "label" => "Second Choice",
                "name" => "second_choice",
                "type" => "select",
                "hint" => "",
                "options" => $facilitiesList,
                "value" => "",
                "required" => true,
                "api_url" => "",
                "apiKeyProperty" => "name",
                "apiLabelProperty" => "name",
                "apiType" => "select"
            ]

        ];
    }

    public function getCloseSessionFormFields(): array
    {
        $disciplinesModel = new HousemanshipDisciplinesModel();
        $disciplinesList = $disciplinesModel->getDistinctValuesAsKeyValuePairs('name');
        return [
            [
                "label" => "Discpline",
                "name" => "discipline",
                "type" => "select",
                "hint" => "",
                "options" => $disciplinesList,
                "value" => "",
                "required" => false,
                "api_url" => "",
                "apiKeyProperty" => "",
                "apiLabelProperty" => "",
                "apiType" => ""
            ],
            [
                "label" => "Start Date",
                "name" => "start_date",
                "type" => "date",
                "hint" => "",
                "options" => [],
                "value" => "",
                "required" => true,
                "api_url" => "",
                "apiKeyProperty" => "",
                "apiLabelProperty" => "",
                "apiType" => ""
            ],
            [
                "label" => "End Date",
                "name" => "end_date",
                "type" => "date",
                "hint" => "",
                "options" => [],
                "value" => "",
                "required" => true,
                "api_url" => "",
                "apiKeyProperty" => "",
                "apiLabelProperty" => "",
                "apiType" => ""
            ]

        ];
    }

    /**
     * Returns an array of facilities and their corresponding categories that are available for selection by the user
     * 
     * @param array $applicantDetails The data of the user to be used to determine which categories apply to them.
     * @param string[] $excludedfacilityRegions The regions to be excluded from the results.
     * 
     * @return array An array of facility names and their corresponding categories in the format of 'facility_name' => ['category1', 'category2']
     */
    private function getFacilitiesList(?array $applicantDetails = null, array $excludedfacilityRegions = []): array
    {
        $facilitiesModel = new HousemanshipFacilitiesModel();
        $allowedFacilities = null;
        if ($applicantDetails != null) {
            $allowedFacilities = [];
            $categories = Utils::getHousemanshipSetting(HousemanshipSetting::AVAILABILITY_CATEGORIES);
            //use the categories critera to determine which categories apply to the user
            $userCategories = [];
            foreach ($categories as $category) {
                $criteria = array_map(fn($c) => CriteriaType::fromArray($c), $category['criteria']);
                if (CriteriaType::matchesCriteria($applicantDetails, $criteria)) {
                    $userCategories[] = $category['value'];
                }
            }
            $userCategoriesList = implode(",", array_map(fn($c) => "'" . $c . "'", $userCategories));
            //filter by whether the facility is available for selection by the user's category. get only the latest year for each 
            //facility and category
            $availabilityModel = new HousemanshipFacilityAvailabilityModel();
            //categories are stored as 'available'| available_(category)_selection|  (category)| available_(practitioner_type)_(category) etc. each category has its corresponding criteria. 
            //the facility must have each criteria to be available for it to be selectable
            $latestAvailabilities = $availabilityModel
                ->where("{$availabilityModel->getTableName()}.category IN ($userCategoriesList)")
                ->where("{$availabilityModel->getTableName()}.available = 1")
                ->join(
                    "(SELECT facility_name, MAX(year) as max_year 
         FROM {$availabilityModel->getTableName()} 
         WHERE category IN ($userCategoriesList) 
         GROUP BY facility_name) latest",
                    "{$availabilityModel->getTableName()}.facility_name = latest.facility_name AND {$availabilityModel->getTableName()}.year = latest.max_year",
                    'inner'
                )
                ->findAll();
            //convert the $latestAvailabilities to an array of facility names => [categories]
            $facilityDict = [];
            foreach ($latestAvailabilities as $latestAvailability) {
                //if the facility is not already in the dict, add it
                if (!array_key_exists($latestAvailability['facility_name'], $facilityDict)) {
                    $facilityDict[$latestAvailability['facility_name']] = [];
                }
                $facilityDict[$latestAvailability['facility_name']][] = $latestAvailability['category'];
            }
            //filter the facilities based on userCategories. filter out the ones that don't have all the categories
            $hasAllCategories = array_filter($facilityDict, fn($value) => count(array_intersect($value, $userCategories)) == count($userCategories));
            $allowedFacilities = array_keys($hasAllCategories);

        }

        $keyColumns = ['name', 'region'];
        $valueColumn = 'name';
        //convert the afcilityfilters to 
        $results = $builder = $facilitiesModel->builder()->whereIn('name', $allowedFacilities);
        if (count($excludedfacilityRegions) > 0) {
            $builder->whereNotIn('region', $excludedfacilityRegions);
        }
        $results = $builder->get()->getResultArray();
        $keyValuePairs = [];
        foreach ($results as $value) {
            //filter out the ones that are not available for selection
            // if (!in_array($value['name'], $allowedFacilities)) {
            //     continue;
            // }
            //for the key, get the values from the keyColumns separated by a dash e.g. "key1 - key2 - key3"
            $keyVals = [];
            foreach ($keyColumns as $keyColumn) {
                $keyVals[] = $value[$keyColumn];
            }
            $key = implode(" - ", $keyVals);
            $keyValuePairs[] = ["key" => $key, "value" => $value[$valueColumn]];
        }
        return $keyValuePairs;
    }

    /**
     * Returns an array of disciplines and their corresponding names that are available for selection by the user
     * 
     * @param array $applicantDetails The data of the user to be used to determine which disciplines apply to them.
     * @param string[] $excludedDisciplines The disciplines to be excluded from the results.
     * 
     * @return array An array of discipline names and their corresponding names in the format of 'discipline_name' => 'discipline_name'
     */
    private function getDisciplinesList(?array $applicantDetails = null, array $excludedDisciplines = []): array
    {
        $disciplinesModel = new HousemanshipDisciplinesModel();
        $builder = $disciplinesModel->builder();
        if (count($excludedDisciplines) > 0) {
            $builder->whereNotIn('name', $excludedDisciplines);
        }

        //TODO: add this to the app settings
        if ($applicantDetails != null && $applicantDetails['category'] == "Dental") {
            //for Dentists, only return Dentistry
            $builder->like('name', "Dentist", "both");
        }
        $allowedDisciplines = $builder->get()->getResultArray();
        //make it key-value pairs
        $result = array_map(fn($discipline) => ["key" => $discipline['name'], "value" => $discipline['name']], $allowedDisciplines);
        return $result;
    }
}
