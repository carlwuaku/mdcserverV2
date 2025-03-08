<?php

namespace App\Models;

use CodeIgniter\Model;

class PrintTemplateRolesModel extends Model
{
    protected $table = 'print_template_roles';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = ['template_uuid', 'role_name'];

    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    /**
     * Check if a role has access to a template
     */
    public function hasAccess(string $templateUuid, string $roleName): bool
    {
        return $this->where('template_uuid', $templateUuid)
                   ->where('role_name', $roleName)
                   ->countAllResults() > 0;
    }

    /**
     * Get all template UUIDs accessible by a role
     */
    public function getAccessibleTemplateUuids(string $roleName): array
    {
        return array_column(
            $this->select('template_uuid')
                 ->where('role_name', $roleName)
                 ->findAll(),
            'template_uuid'
        );
    }
} 