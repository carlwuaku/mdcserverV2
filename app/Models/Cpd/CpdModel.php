<?php

namespace App\Models\Cpd;

use App\Helpers\Interfaces\TableDisplayInterface;
use App\Models\MyBaseModel;
use CodeIgniter\Database\BaseBuilder;

class CpdModel extends MyBaseModel implements TableDisplayInterface
{
    protected $table = 'cpd_topics';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'topic',
        'date',
        'created_on',
        'created_by',
        'modified_on',
        'provider_uuid',
        'venue',
        'group',
        'credits',
        'category',
        'online',
        'url',
        'start_month',
        'end_month',
    ];

    protected bool $allowEmptyInserts = false;

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
        'topic'
    ];

    public function getDisplayColumns(): array
    {
        return [
            'topic',
            'date',
            'provider_name',
            'credits',
            'category',
            'number_of_attendants',
            'online',
            'created_on',
            'created_by',
            'url',
        ];
    }

    public function getDisplayColumnLabels(): array
    {
        return [];
    }

    /**
     * Returns an array of filters to apply to the display columns
     * of the model when rendering a table view of the model.
     * 
     * @return array
     */
    public function getDisplayColumnFilters(): array
    {
        return [
            [
                "label" => "Provider",
                "name" => "provider_uuid",
                "type" => "api",
                "hint" => "",
                "options" => [],
                "value" => "",
                "required" => false,
                "api_url" => "cpd/providers",
                "apiKeyProperty" => "uuid",
                "apiLabelProperty" => "name",
                "apiType" => "search"
            ],
            [
                "label" => "Year",
                "name" => "year",
                "type" => "number",
                "hint" => "",
                "options" => [],
                "value" => "",
                "required" => false
            ],
            [
                "label" => "Category",
                "name" => "category",
                "type" => "number",
                "hint" => "",
                "options" => [],
                "value" => "",
                "required" => false
            ],
            [
                "label" => "Number of credits",
                "name" => "credits",
                "type" => "number",
                "hint" => "",
                "options" => [

                ],
                "value" => "",
                "required" => false
            ],
            [
                "label" => "Available online",
                "name" => "online",
                "type" => "select",
                "hint" => "",
                "options" => [["key" => "Yes", "value" => "1"], ["key" => "No", "value" => "0"]],
                "value" => "",
                "required" => false
            ]
        ];
    }

    public function addCustomFields(BaseBuilder $builder, string $userType = "admin"): BaseBuilder
    {
        $providerModel = new CpdProviderModel();
        $attendanceModel = new CpdAttendanceModel();
        $selectClause = "{$this->table}.*, {$providerModel->table}.name as provider_name, count({$attendanceModel->table}.id) as number_of_attendants";
        if ($userType !== "admin") {
            $selectClause = "{$this->table}.topic,{$this->table}.credits, {$this->table}.category, {$this->table}.online, {$providerModel->table}.name as provider_name, {$providerModel->table}.email as provider_email, {$providerModel->table}.phone as provider_phone";

        }
        $builder->select($selectClause)->
            join($providerModel->table, "{$this->table}.provider_uuid = {$providerModel->table}.uuid", "left")->
            join($attendanceModel->table, "{$this->table}.uuid = {$attendanceModel->table}.cpd_uuid", "left")
            ->groupBy("{$this->table}.id");
        ;
        return $builder;
    }


}
