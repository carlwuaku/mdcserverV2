<?php

namespace App\Models;

use App\Helpers\Interfaces\TableDisplayInterface;
use CodeIgniter\Database\BaseBuilder;

class ActivitiesModel extends MyBaseModel implements TableDisplayInterface
{
    protected $table = 'activities';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    protected $protectFields = true;
    protected $allowedFields = [
        'user_id',
        'module',
        'activity',
        'ip_address',
        'deleted_at',
        'created_on'
    ];

    // Dates
    protected $useTimestamps = false;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_on';
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

    public $searchFields = ["activity"];

    public function getDisplayColumns(): array
    {
        return [
            "created_on",
            "display_name",
            "email",
            "activity",
            "module",
            "deleted_at"
        ];
    }

    public function getDisplayColumnLabels(): array
    {
        return [];
    }

    public function addCustomFields(BaseBuilder $builder): BaseBuilder
    {
        $userTable = "users";
        $thisTable = $this->table;

        $filteredColumns = [
            "activity",
            "module",
            "created_on",
            "deleted_at"
        ];
        $userColumns = ["display_name", "email"];
        $builder->join($userTable, "$thisTable.user_id = $userTable.id", "left")
            ->select(implode(', ', array_map(function ($col) {
                return 'activities.' . $col;
            }, $filteredColumns)))
            ->select(implode(', ', array_map(function ($col) use ($userTable) {
                return $userTable . '.' . $col;
            }, $userColumns)))
        ;
        return $builder;
    }

    public function getTableName(): string
    {
        return $this->table;
    }

    /**
     * save an activity to the database
     */
    public function logActivity(string $activity, string|int|null $userId = null, string $module = "General")
    {
        $ip = $_SERVER['REMOTE_ADDR'];
        $this->insert([
            "user_id" => $userId ?? auth()->id(),
            "activity" => $activity,
            "module" => $module,
            "ip_address" => $ip
        ]);
    }

    public function getDisplayColumnFilters(): array
    {
        return [];
    }
}
