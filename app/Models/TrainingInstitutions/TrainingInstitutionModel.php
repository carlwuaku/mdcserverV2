<?php

namespace App\Models\TrainingInstitutions;

use App\Models\MyBaseModel;
use App\Helpers\Interfaces\TableDisplayInterface;
use CodeIgniter\Database\BaseBuilder;
class TrainingInstitutionModel extends MyBaseModel implements TableDisplayInterface
{
    protected $table = 'training_institutions';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    protected $protectFields = true;
    protected $allowedFields = [
        'uuid',
        'name',
        'location',
        'contact_name',
        'contact_position',
        'region',
        'district',
        'type',
        'phone',
        'email',
        'status',
        'default_limit',
        'registration_start_month',
        'registration_end_month',
        'category',
        'accredited_program'
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

    protected $searchFields = [
        'name',
        'location',
        'contact_name',
        'contact_position',
        'region',
        'district',
        'type',
        'phone',
        'email',
        'status',
        'category',
        'accredited_program'
    ];

    /**
     * Get training institution with student count for a specific year
     *
     * @param string $year The year to count students for
     * @param int $limit Number of records to return
     * @param int $offset Pagination offset
     * @return array
     */
    public function getWithStudentCount(?string $year = null, ?int $limit = null, int $offset = 0): array
    {
        $currentYear = $year ?? date('Y');

        $builder = $this->builder()
            ->select('training_institutions.*, COUNT(student_indexes.id) as student_count')
            ->join('student_indexes', "student_indexes.training_institution = training_institutions.name AND student_indexes.year = {$currentYear}", 'left')
            ->groupBy('training_institutions.id');

        if ($limit !== null) {
            $builder->limit($limit, $offset);
        }

        return $builder->get()->getResultArray();
    }

    /**
     * Get a single training institution with student count for a specific year
     *
     * @param string $uuid The UUID of the training institution
     * @param string $year The year to count students for
     * @return array|null
     */
    public function getByUuidWithStudentCount(string $uuid, ?string $year = null): ?array
    {
        $currentYear = $year ?? date('Y');

        $result = $this->builder()
            ->select('training_institutions.*, COUNT(student_indexes.id) as student_count')
            ->join('student_indexes', "student_indexes.training_institution = training_institutions.name AND student_indexes.year = {$currentYear}", 'left')
            ->where('training_institutions.uuid', $uuid)
            ->groupBy('training_institutions.id')
            ->get()
            ->getRowArray();

        return $result ?: null;
    }

    /**
     * Search training institutions with student count for a specific year
     *
     * @param string $searchString The search string
     * @param string $year The year to count students for
     * @param int $limit Number of records to return
     * @param int $offset Pagination offset
     * @return array
     */
    // public function searchWithStudentCount(string $searchString, string $year = null, int $limit = null, int $offset = 0): array
    // {
    //     $currentYear = $year ?? date('Y');

    //     $builder = $this->search($searchString)
    //         ->select('training_institutions.*, COUNT(student_indexes.id) as student_count')
    //         ->join('student_indexes', "student_indexes.training_institution = training_institutions.name AND student_indexes.year = {$currentYear}", 'left')
    //         ->groupBy('training_institutions.id');

    //     if ($limit !== null) {
    //         $builder->limit($limit, $offset);
    //     }

    //     return $builder->get()->getResultArray();
    // }

    // public function addStudentCount(BaseBuilder $builder, ?string $year = null): BaseBuilder
    // {

    //     $builder->select('training_institutions.*, COUNT(student_indexes.id) as student_count');
    //     if($year){
    //         $builder->join('student_indexes', "student_indexes.training_institution = training_institutions.name AND student_indexes.year = {$year}", 'left');
    //     }
    //     else{
    //         $builder->join('student_indexes', "student_indexes.training_institution = training_institutions.name", 'left');
    //     }
    //     $builder->groupBy('training_institutions.id');

    //     return $builder;
    // }

    public function getDisplayColumns(): array
    {
        return [
            'name',
            'location',
            'contact_name',
            'contact_position',
            'region',
            'district',
            'type',
            'phone',
            'email',
            'status',
            'category',
            'accredited_program'
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
                "hint" => "Search any field",
                "options" => [],
                "value" => "",
                "required" => false
            ],
            [
                "label" => "Region",
                "name" => "region",
                "type" => "api",
                "hint" => "",
                "options" => [],
                "value" => "",
                "required" => false,
                "api_url" => "admin/distinct-values/training_institutions/region",
                "apiKeyProperty" => "region",
                "apiLabelProperty" => "region",
                "apiType" => "select"
            ]
        ];

        return $default;
    }
}
