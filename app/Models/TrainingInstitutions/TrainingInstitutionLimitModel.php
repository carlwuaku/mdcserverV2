<?php

namespace App\Models\TrainingInstitutions;

use App\Models\MyBaseModel;

class TrainingInstitutionLimitModel extends MyBaseModel
{
    protected $table = 'training_institutions_limits';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'training_institution_uuid',
        'student_limit',
        'year'
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
     * Get limit for a specific training institution and year
     *
     * @param string $institutionUuid The UUID of the training institution
     * @param string $year The year
     * @return array|null
     */
    public function getLimitByInstitutionAndYear(string $institutionUuid, string $year): ?array
    {
        return $this->where([
            'training_institution_uuid' => $institutionUuid,
            'year' => $year
        ])->first();
    }

    /**
     * Get all limits for a specific training institution
     *
     * @param string $institutionUuid The UUID of the training institution
     * @return array
     */
    public function getLimitsByInstitution(string $institutionUuid): array
    {
        return $this->where('training_institution_uuid', $institutionUuid)
            ->orderBy('year', 'DESC')
            ->findAll();
    }

    /**
     * Set or update limit for a specific training institution and year
     *
     * @param string $institutionUuid The UUID of the training institution
     * @param string $year The year
     * @param int $limit The student limit
     * @return bool|int
     */
    public function setLimit(string $institutionUuid, string $year, int $limit)
    {
        $existing = $this->getLimitByInstitutionAndYear($institutionUuid, $year);

        if ($existing) {
            return $this->update($existing['id'], ['student_limit' => $limit]);
        } else {
            return $this->insert([
                'training_institution_uuid' => $institutionUuid,
                'year' => $year,
                'student_limit' => $limit
            ]);
        }
    }
}
