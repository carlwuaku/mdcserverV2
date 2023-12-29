<?php

namespace App\Models;

use CodeIgniter\Model;

class PermissionsModel extends Model
{
    protected $table            = 'permissions';
    protected $primaryKey       = 'permission_id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['name','description','status'];

    // Dates
    protected $useTimestamps = false;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    // Validation
    protected $validationRules      = [
        'name'=> 'required',
        
    ];
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

    /**
     * return the table name
     */
    public function getTableName():string{
        return $this->table;
    }

    /**
     * get the permissions as an array of [{id: string, permission_name: string}] NOT assigned to a given role id
     */
    public function getRoleExcludedPermissions(string $role_id): array
    {
        $rolepermissionsModel = new RolePermissionsModel();
        $rolepermissionsTableName = $rolepermissionsModel->getTableName();
        $this->select("permission_id, name");
        $this->where("permission_id NOT IN (SELECT permission_id FROM $rolepermissionsTableName WHERE role_id = $role_id)", NULL, FALSE);
        return $this->findAll();
    }

    /**
     * get the permissions as an array of [{id: string, permission_name: string}] assigned to a given role id
     * if $namesOnly is true, it returns an array of strings made of the permission_name of each role
     */
    public function getRolePermissions(string $role_id, bool $namesOnly = false): array
    {
        $rolepermissionsModel = new RolePermissionsModel();
        $rolepermissionsTableName = $rolepermissionsModel->getTableName();
        $this->select("permission_id, name");
        $this->where("permission_id IN (SELECT permission_id FROM $rolepermissionsTableName WHERE role_id = $role_id)", NULL, FALSE);
        $results = $this->findAll();
        if($namesOnly){
            $names = array_map(function ($obj){ return $obj['name']; }, $results);
            return $names;
        }
        return $this->findAll();

        // $permissionsModel = new PermissionsModel();
        // $permissionsTableName = $permissionsModel->getTableName();
        // $this->select("$permissionsTableName.permission_id, $permissionsTableName.name");
        // $this->join($permissionsTableName, "$permissionsTableName.permission_id = $this->table.permission_id");
        // $this->where("role_id", $role_id);
        // $results = $this->findAll();
        // if($namesOnly){
        //     $names = array_map(function ($obj){ return $obj['permission_name']; }, $results);
        //     return $names;
        // }
        // return $this->findAll();
    }
    
}
