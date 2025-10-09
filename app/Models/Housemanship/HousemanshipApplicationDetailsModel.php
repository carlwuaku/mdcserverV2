<?php

namespace App\Models\Housemanship;

use App\Helpers\Interfaces\TableDisplayInterface;
use App\Helpers\Interfaces\FormInterface;
use App\Models\MyBaseModel;

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

    public function getNonAdminFormFields(): array
    {
        $facilitiesModel = new HousemanshipFacilitiesModel();
        $facilitiesList = $facilitiesModel->getDistinctValuesAsKeyValuePairs('name');

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
                "required" => true,
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
}
