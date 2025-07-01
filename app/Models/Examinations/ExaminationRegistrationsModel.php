<?php

namespace App\Models\Examinations;

use App\Helpers\Interfaces\TableDisplayInterface;
use App\Models\Licenses\LicensesModel;
use App\Models\MyBaseModel;
use CodeIgniter\Database\BaseBuilder;
use App\Helpers\Utils;

class ExaminationRegistrationsModel extends MyBaseModel implements TableDisplayInterface
{
    protected $table = 'examination_registrations';
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
        'registration_letter',
        'result_letter',
        'uuid',
        'index_number',
        'result',
        'publish_result_date',
        'scores',
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
        'index_number',
        'exam_candidates.first_name',
        'exam_candidates.last_name',
        'exam_candidates.middle_name',
        'licenses.email',
        'licenses.phone'
    ];


    public function getDisplayColumns(string $mode = 'candidate'): array
    {

        return $mode === 'candidate' ?
            [
                'picture',
                'title',
                'exam_type',
                'index_number',
                'result',
                'scores',
                'intern_code',
                'publish_result_date',
                'registration_letter',
                'result_letter',
                'created_at'
            ]
            : [
                'picture',
                'last_name',
                'first_name',
                'middle_name',
                'index_number',
                'number_of_exams',
                'result',
                'scores',
                'practitioner_type',
                'intern_code',
                'publish_result_date',
                'registration_letter',
                'result_letter',
                'specialty',
                'category',
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
                "label" => "Search registrations",
                "name" => "param",
                "type" => "text",
                "hint" => "search by intern code, index number, first name, last name, middle name, phone or email",
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
            ],
            [
                "label" => "Result",
                "name" => "result",
                "type" => "select",
                "hint" => "",
                "options" => [
                    [
                        "key" => "Pass",
                        "value" => "Pass"
                    ],
                    [
                        "key" => "Fail",
                        "value" => "Fail"
                    ],
                    [
                        "key" => "Not Set",
                        "value" => "--Null Or Empty--"
                    ],
                ],
                "value" => "",
                "required" => false,
                "api_url" => "",
                "apiKeyProperty" => "",
                "apiLabelProperty" => "",
                "apiType" => ""
            ],
            [
                "label" => "Result Published",
                "name" => "publish_result_date",
                "type" => "select",
                "hint" => "",
                "options" => [
                    [
                        "key" => "Published",
                        "value" => "--Not Null--"
                    ],
                    [
                        "key" => "Not Published",
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

    /**
     * Adds custom fields to the query builder for examination registrations.
     * This method joins the examination registrations table with the licenses table
     * to select the license details for each registration. It also joins the examination
     * applications table with the examination registrations table to compute the number
     * of applications and candidates for each examination. Finally, it groups the results
     * by examination ID.
     *
     * @param BaseBuilder $builder The query builder instance.
     * @return BaseBuilder The modified query builder with custom fields added.
     */
    public function addCustomFields(BaseBuilder $builder): BaseBuilder
    {
        $licensesModel = new LicensesModel();
        $examModel = new ExaminationsModel();
        $licenseDef = Utils::getLicenseSetting("exam_candidates");
        $fields = $licenseDef->selectionFields;
        $licenseTypeTable = $licenseDef->table;

        $builder->select("{$this->table}.*, first_name, middle_name, last_name, picture, email, phone, category, specialty, practitioner_type, number_of_exams, title, exam_type")
            ->join($examModel->getTableName(), "{$this->table}.exam_id = {$examModel->getTableName()}.id", "left")
            ->join($licenseTypeTable, "{$this->table}.intern_code = {$licenseTypeTable}.intern_code", "left")
            ->join($licensesModel->table, "{$this->table}.intern_code = {$licensesModel->table}.license_number", "left")
        ;

        return $builder;
    }
}
