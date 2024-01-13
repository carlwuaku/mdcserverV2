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
    protected $allowedFields = ['permission_id', 'role_id'];

    // Dates
    protected $useTimestamps = false;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = 'deleted_at';

    // Validation
    protected $validationRules = [
        'role_id' => 'required',
        'permission_id' => 'required',
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
    public function getTableName():string{
        return $this->table;
    }

    /**
     * return true if a role_id has a permission_id/name in the role_permissions table.
     * @var string $role_id 
     * @var string $permission the name or id of the permission
     * @return boolean
     */
    public function hasPermission(string $role_id, string $permission):bool{
        $rows = $this->where('role_id', $role_id)->where("permission_id in (select permission_id from permissions where name = '$permission' or permission_id = '$permission' )")->findAll();
        return count($rows) > 0;
    }
}
