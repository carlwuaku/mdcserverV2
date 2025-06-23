<?php

namespace App\Models\Examinations;

use App\Helpers\Interfaces\TableDisplayInterface;
use App\Helpers\Interfaces\FormInterface;
use App\Helpers\Utils;
use App\Models\MyBaseModel;
use CodeIgniter\Database\BaseBuilder;

class ExaminationsModel extends MyBaseModel implements TableDisplayInterface, FormInterface
{
    protected $table = 'examinations';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'uuid',
        'title',
        'exam_type',
        'open_from',
        'open_to',
        'type',
        'publish_scores',
        'publish_score_date',
        'deleted_at',
        'created_at',
        'updated_at',
        'next_exam_month',
        'metadata',
        'scores_names'
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
        'title'
    ];



    public function getDisplayColumns(): array
    {

        return [
            'title',
            'exam_type',
            'open_from',
            'number_of_applications',
            'number_of_candidates',
            'open_to',
            'type',
            'scores_names',
            'publish_scores',
            'publish_score_date',
            'deleted_at',
            'created_at',
            'next_exam_month'
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
                "label" => "Exam Type",
                "name" => "exam_type",
                "type" => "select",
                "hint" => "",
                "options" => $this->getDistinctValuesAsKeyValuePairs('exam_type'),
                "value" => "",
                "required" => false
            ],
            [
                "label" => "Practitioner Type",
                "name" => "type",
                "type" => "select",
                "hint" => "",
                "options" => $this->getDistinctValuesAsKeyValuePairs('type'),
                "value" => "",
                "required" => false
            ]
        ];

        return $default;
    }




    public function getFormFields(): array
    {
        // get the exam types from app settings
        /**
         * @var array{examination_types:array, examination_letters:array, practitionerTypes: array, metadataFields:array}
         */
        $examSettings = Utils::getAppSettings('examinations');
        $examTypes = [];
        $practitionerTypes = [];
        $metadataFields = [];
        if (array_key_exists("metadataFields", $examSettings)) {
            $metadataFields = $examSettings['metadataFields'];
        }
        if (array_key_exists("practitionerTypes", $examSettings)) {
            $practitionerTypes = $this->prepResultsAsValuesArray($examSettings['practitionerTypes']);
        }
        if (array_key_exists("examination_types", $examSettings)) {
            $examTypes = $this->prepResultsAsValuesArray(array_keys($examSettings['examination_types']));
        }

        $defaultFields = [
            [
                "label" => "Title",
                "name" => "title",
                "type" => "text",
                "hint" => "",
                "options" => [],
                "value" => "",
                "required" => true,
                "showOnly" => false
            ],
            [
                "label" => "Exam Type",
                "name" => "exam_type",
                "hint" => "",
                "options" => $examTypes,
                "type" => "select",
                "value" => "",
                "required" => true,
                "api_url" => "",
                "apiKeyProperty" => "",
                "apiLabelProperty" => "",
                "apiType" => ""
            ],

            [
                "label" => "Application Open From",
                "name" => "open_from",
                "type" => "date",
                "hint" => "The date when applications for this exam open.",
                "options" => [],
                "value" => "",
                "required" => true
            ],
            [
                "label" => "Application Open To",
                "name" => "open_to",
                "type" => "date",
                "hint" => "The date when applications for this exam close.",
                "options" => [],
                "value" => "",
                "required" => true
            ],
            [
                "label" => "Practitioner Type",
                "name" => "type",
                "type" => "select",
                "hint" => "",
                "options" => $practitionerTypes,
                "value" => "",
                "required" => true
            ],
            [
                "label" => "Score Names",
                "name" => "scores_names",
                "type" => "list",
                "hint" => "e.g. Oral, Problem Solving, MCQ. Separate by commas(,). The names entered here will be used to display scores when submitting results and will be seen by the candidates in their result slips.",
                "options" => [],
                "value" => "",
                "required" => true
            ],
            [
                "label" => "Publish Scores",
                "name" => "publish_scores",
                "type" => "select",
                "hint" => "",
                "options" => [
                    [
                        "key" => "Yes",
                        "value" => "yes"
                    ],
                    [
                        "key" => "No",
                        "value" => "no"
                    ]
                ],
                "value" => 0,
                "required" => false
            ],
            [
                "label" => "Publish Score Date",
                "name" => "publish_score_date",
                "type" => "date",
                "hint" => "",
                "options" => [],
                "value" => "",
                "required" => false
            ],
            [
                "label" => "Next Exam Month",
                "name" => "next_exam_month",
                "type" => "text",
                "hint" => "",
                "options" => [],
                "value" => "",
                "required" => false
            ]

        ];

        return array_merge($defaultFields, $metadataFields);
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
        $applicationsModel = new ExaminationApplicationsModel();
        $registrationsModel = new ExaminationRegistrationsModel();

        $builder->select("{$this->table}.*,  count(DISTINCT {$applicationsModel->table}.id) as number_of_applications, count(DISTINCT {$registrationsModel->table}.id) as number_of_candidates")->
            join($applicationsModel->table, "{$this->table}.id = {$applicationsModel->table}.exam_id", "left")->
            join($registrationsModel->table, "{$this->table}.id = {$registrationsModel->table}.exam_id", "left")
            ->groupBy("{$this->table}.id");
        ;
        return $builder;
    }
}
