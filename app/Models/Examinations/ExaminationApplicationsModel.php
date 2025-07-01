<?php

namespace App\Models\Examinations;

use App\Models\MyBaseModel;
use App\Helpers\Interfaces\TableDisplayInterface;
use CodeIgniter\Database\BaseBuilder;
use App\Helpers\Utils;
use App\Models\Licenses\LicensesModel;

class ExaminationApplicationsModel extends MyBaseModel implements TableDisplayInterface
{
    protected $table = 'examination_applications';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'intern_code',
        'exam_id',
        'application_status',
        'created_at',
        'updated_at'
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
        'intern_code',
        'exam_candidates.first_name',
        'exam_candidates.last_name',
        'exam_candidates.middle_name',
        'licenses.email',
        'licenses.phone'
    ];


    public function getDisplayColumns(): array
    {

        return [
            'picture',
            'last_name',
            'first_name',
            'middle_name',
            'intern_code',
            'application_status',
            'practitioner_type',
            'qualification',
            'training_institution',
            'number_of_exams',
            'category',
            'speciality',
            'phone',
            'email',
            'created_at'
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
                "label" => "Search applications",
                "name" => "param",
                "type" => "text",
                "hint" => "search by intern code, first name, last name, middle name, email or phone",
                "options" => [],
                "value" => "",
                "required" => false,
                "api_url" => "",
                "apiKeyProperty" => "",
                "apiLabelProperty" => "",
                "apiType" => ""
            ],
            [
                "label" => "Category",
                "name" => "child_category",
                "type" => "api",
                "hint" => "",
                "options" => [],
                "value" => "",
                "required" => false,
                "api_url" => "admin/distinct-values/exam_candidates/category",
                "apiKeyProperty" => "category",
                "apiLabelProperty" => "category",
                "apiType" => "select"
            ],
            [
                "label" => "Practitioner Type",
                "name" => "child_practitioner_type",
                "type" => "api",
                "hint" => "",
                "options" => [],
                "value" => "",
                "required" => false,
                "api_url" => "admin/distinct-values/exam_candidates/practitioner_type",
                "apiKeyProperty" => "practitioner_type",
                "apiLabelProperty" => "practitioner_type",
                "apiType" => "select"
            ],
            [
                "label" => "Is Specialist",
                "name" => "child_specialty",
                "type" => "select",
                "hint" => "",
                "options" => [
                    [
                        "key" => "Yes",
                        "value" => "--Not Null--"
                    ],
                    [
                        "key" => "No",
                        "value" => "--Null Or Empty--"
                    ],
                ],
                "value" => "",
                "required" => false,
                "api_url" => "",
                "apiKeyProperty" => "",
                "apiLabelProperty" => "",
                "apiType" => ""
            ]
        ];

        return $default;
    }






    /**
     * Adds custom fields to the query builder for examinations.
     * This method joins the examination applications and registrations tables
     * with the examinations table to compute the number of applications and candidates
     * for each examination. It selects the fields from the examinations table
     * along with the computed counts and groups the results by examination ID.
     *
     * @param BaseBuilder $builder The query builder instance.
     * @return BaseBuilder The modified query builder with custom fields added.
     */

    public function addCustomFields(BaseBuilder $builder): BaseBuilder
    {
        $licensesModel = new LicensesModel();
        $licenseDef = Utils::getLicenseSetting("exam_candidates");
        $fields = $licenseDef->selectionFields;
        $licenseTypeTable = $licenseDef->table;

        $builder->select("{$this->table}.*, first_name, middle_name, last_name, picture, email, phone, category, specialty, practitioner_type, qualification, training_institution, number_of_exams")->
            join($licenseTypeTable, "{$this->table}.intern_code = {$licenseTypeTable}.intern_code", "left")
            ->join($licensesModel->table, "{$this->table}.intern_code = {$licensesModel->table}.license_number", "left")
        ;

        return $builder;
    }
}
