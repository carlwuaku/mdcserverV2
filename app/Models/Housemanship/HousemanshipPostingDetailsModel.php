<?php

namespace App\Models\Housemanship;
use App\Helpers\Interfaces\FormInterface;
use App\Helpers\Utils;
use App\Helpers\Enums\HousemanshipSetting;

use App\Models\MyBaseModel;

class HousemanshipPostingDetailsModel extends MyBaseModel implements FormInterface
{
    protected $table = 'housemanship_postings_details';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'posting_uuid',
        'facility_name',
        'start_date',
        'end_date',
        'discipline',
        'facility_region',
        'facility_details'
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

    public function getHousemanshipSessions()
    {
        $sessionSetting = Utils::getHousemanshipSetting(HousemanshipSetting::SESSIONS);
        return array_keys($sessionSetting);
    }

    /**
     * get the form fields for the discipline/facilities for the posting
     * these fields may be added to the form as an array in the posting form. each field is prefixed with 
     * posting_details- and suffixed with -$index to identify each row. this is done in the controller. in processing the form, the
     * posting_details- prefix is removed and the $index is used to identify the row in the database
     * @return array[]
     */
    public function getFormFields(): array
    {

        return [

            [
                "label" => "Facility",
                "name" => "facility_name",
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
                "label" => "Start Date",
                "name" => "start_date",
                "hint" => "",
                "options" => [],
                "type" => "date",
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
                "hint" => "",
                "options" => [],
                "type" => "date",
                "value" => "",
                "required" => true,
                "api_url" => "",
                "apiKeyProperty" => "",
                "apiLabelProperty" => "",
                "apiType" => ""
            ],

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
            ]

        ];
    }

    public function getDisplayColumnFilters(): array
    {

        $default = [
            [
                "label" => "Facility",
                "name" => "type",
                "type" => "select",
                "hint" => "",
                "options" => $this->getDistinctValuesAsKeyValuePairs('facility_name'),
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
            ],
            [
                "label" => "Region",
                "name" => "facility_region",
                "type" => "select",
                "hint" => "",
                "options" => $this->getDistinctValuesAsKeyValuePairs('facility_region'),
                "value" => "",
                "required" => false
            ],
            [
                "label" => "Start Date",
                "name" => "start_date",
                "type" => "date-range",
                "hint" => "",
                "options" => [],
                "value" => "",
                "required" => false
            ],
            [
                "label" => "End Date",
                "name" => "end_date",
                "type" => "date-range",
                "hint" => "",
                "options" => [],
                "value" => "",
                "required" => false
            ]
        ];

        return $default;
    }
}
