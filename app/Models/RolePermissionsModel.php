<?php

namespace App\Models;

use CodeIgniter\Model;
use App\Models\PermissionsModel;

class RolePermissionsModel extends Model
{
    protected $table = 'role_permissions';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = ['permission', 'role'];

    // Dates
    protected $useTimestamps = false;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = 'deleted_at';

    // Validation
    protected $validationRules = [
        'role' => 'required',
        'permission' => 'required',
    ];
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
     * return the table name
     */
    public function getTableName(): string
    {
        return $this->table;
    }

    /**
     * return true if a role_id has a permission_id/name in the role_permissions table.
     * @var string $role_name 
     * @var string $permission the name or id of the permission
     * @return boolean
     */
    public function hasPermission(string $role_name, string $permission): bool
    {
        $rows = $this->where('role', $role_name)->where("permission", $permission)->findAll();
        return count($rows) > 0;
    }
}
