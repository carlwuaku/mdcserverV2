<?php

namespace App\Models\TrainingInstitutions;

use App\Models\MyBaseModel;
use App\Helpers\Interfaces\TableDisplayInterface;
class StudentIndexModel extends MyBaseModel implements TableDisplayInterface
{
    protected $table = 'student_indexes';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'index_number',
        'first_name',
        'middle_name',
        'last_name',
        'date_of_birth',
        'sex',
        'student_id',
        'nationality',
        'training_institution',
        'year',
        'created_by'
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

    protected $searchFields = [
        'index_number',
        'first_name',
        'middle_name',
        'last_name',
        'student_id',
        'nationality',
        'training_institution'
    ];

    /**
     * Get student count by training institution and year
     *
     * @param string $institutionName The name of the training institution
     * @param string $year The year
     * @return int
     */
    public function countByInstitutionAndYear(string $institutionName, string $year): int
    {
        return $this->where([
            'training_institution' => $institutionName,
            'year' => $year
        ])->countAllResults();
    }

    /**
     * Get students by training institution and year
     *
     * @param string $institutionName The name of the training institution
     * @param string $year The year
     * @return array
     */
    public function getByInstitutionAndYear(string $institutionName, string $year): array
    {
        return $this->where([
            'training_institution' => $institutionName,
            'year' => $year
        ])->findAll();
    }

    public function getDisplayColumns(): array
    {
        //get the fields for the selected type, if present, or go with the default fields if not available
        return [
            'index_number',
            'first_name',
            'middle_name',
            'last_name',
            'date_of_birth',
            'sex',
            'student_id',
            'nationality',
            'training_institution',
            'year',
            'created_by'
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
                "label" => "Search",
                "name" => "param",
                "type" => "text",
                "hint" => "Search first name, last name, middle name, email, phone number",
                "options" => [],
                "value" => "",
                "required" => false
            ],
            [
                "label" => "Training institution",
                "name" => "training_institution",
                "type" => "api",
                "hint" => "",
                "options" => [],
                "value" => "",
                "required" => false,
                "api_url" => "admin/distinct-values/training_institutions/name",
                "apiKeyProperty" => "name",
                "apiLabelProperty" => "name",
                "apiType" => "select"
            ]
        ];

        return $default;
    }
}
