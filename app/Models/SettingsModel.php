<?php

namespace App\Models;
use App\Helpers\Interfaces\TableDisplayInterface;

use CodeIgniter\Model;

class SettingsModel extends MyBaseModel implements TableDisplayInterface
{
    protected $table            = 'settings';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ["key","value","type","context","class"];

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

    public function getDisplayColumns(): array
    {
        return [
            "key","value","type","context","class","created_at","deleted_at"
        ];
    }

    public function getDisplayColumnLabels(): array
    {
        return [];
    }

    public $searchFields = [ "key","value"];

    public function getDisplayColumnFilters(): array{
        return [];
    }
}
