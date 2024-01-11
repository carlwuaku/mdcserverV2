<?php

namespace App\Models;

use CodeIgniter\Model;
use App\Helpers\Interfaces\TableDisplayInterface;


class RolesModel extends MyBaseModel implements TableDisplayInterface
{
    protected $table            = 'roles';
    protected $primaryKey       = 'role_id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    public $allowedFields    = ['role_name','description','login_destination','deleted_at'];

    // Dates
    protected $useTimestamps = true;
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

    public $searchFields = ['role_name'];

    public function getDisplayColumns(): array
    {
        return [
            'role_name',
            'description',
            'number_of_users',
            'deleted_at'
        ];
    }

    public function getDisplayColumnLabels(): array
    {
        return [];
    }
}
