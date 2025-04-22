<?php

namespace App\Models\Housemanship;

use App\Helpers\Interfaces\TableDisplayInterface;
use App\Helpers\Interfaces\FormInterface;
use CodeIgniter\Database\BaseBuilder;
use App\Models\MyBaseModel;

class HousemanshipDisciplinesModel extends MyBaseModel implements TableDisplayInterface, FormInterface
{
    protected $table = 'housemanship_disciplines';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    protected $protectFields = true;
    protected $allowedFields = [
        'name'
    ];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected array $casts = [];
    protected array $castHandlers = [];

    // Dates
    protected $useTimestamps = true;
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
        'name'
    ];



    public function getDisplayColumns(): array
    {

        return ['name'];
    }

    public function getDisplayColumnLabels(): array
    {
        return [];
    }







    public function getDisplayColumnFilters(): array
    {

        $default = [

        ];

        return $default;
    }




    public function getFormFields(): array
    {

        return [
            [
                "label" => "Discipline",
                "name" => "name",
                "type" => "text",
                "hint" => "",
                "options" => [],
                "value" => "",
                "required" => true,
                "showOnly" => false
            ]

        ];
    }
}
