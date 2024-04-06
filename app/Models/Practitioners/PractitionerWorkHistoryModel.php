<?php

namespace App\Models\Practitioners;
use App\Models\MyBaseModel;
use App\Helpers\Interfaces\TableDisplayInterface;

class PractitionerWorkHistoryModel extends MyBaseModel implements TableDisplayInterface
{
    protected $table = 'practitioner_work_history';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    protected $protectFields = true;
    protected $allowedFields = [
        "registration_number",
        "institution",
        "location",
        "start_date",
        "end_date",
        "position",
        "institution_type",
        "region",
        "created_by",
        "created_on",
        "modified_by",
        "deleted_by",
        "status",
        "deleted_at",
    ];

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
        "registration_number",
        "institution",
        "start_date",
        "end_date",
        "position",
        "institution_type",

    ];

    public function getDisplayColumns(): array
    {
        return [
            "institution",
            "position",
            "institution_type",
            "start_date",
            "end_date",
            "created_by",
            "created_on",
            "modified_on",
            "status",
            "deleted_at"
        ];
    }

    public function getDisplayColumnLabels(): array
    {
        return [];
    }

    public function getTableName(): string
    {
        return $this->table;
    }


    public function getDisplayColumnFilters(): array{
        return [];
    }
}
