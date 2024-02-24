<?php

namespace App\Models;

use CodeIgniter\Model;
use App\Helpers\Interfaces\TableDisplayInterface;

class PractitionerAdditionalQualificationsModel extends MyBaseModel implements TableDisplayInterface
{
    protected $table            = '	practitioner_additional_qualifications';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        "registration_number", 
        "institution", 
        "start_date", 
        "end_date", 
        "qualification",
        "created_by",
        "created_on",
        "modified_on",
        "picture",
        "status",
    ];

    // Dates
    protected $useTimestamps = false;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    // Validation
    protected $validationRules      = [];
    protected $validationMessages   = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert   = [];
    protected $afterInsert    = [];
    protected $beforeUpdate   = [];
    protected $afterUpdate    = [];
    protected $beforeFind     = [];
    protected $afterFind      = [];
    protected $beforeDelete   = [];
    protected $afterDelete    = [];

    public $searchFields = [
        "registration_number", 
    "institution", 
    "start_date", 
    "end_date", 
    "qualification"];

    public function getDisplayColumns(): array
    {
        return [
            "registration_number", 
            "institution", 
            "start_date", 
            "end_date", 
            "qualification",
            "created_by",
            "created_on",
            "modified_on",
            "picture",
            "status",
            "deleted_at"
        ];
    }

    public function getDisplayColumnLabels(): array
    {
        return [];
    }

    public function getTableName(): string{
        return $this->table;
    }
}
