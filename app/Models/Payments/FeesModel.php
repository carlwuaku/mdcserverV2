<?php

namespace App\Models\Payments;

use App\Helpers\Interfaces\TableDisplayInterface;
use App\Helpers\Interfaces\FormInterface;
use App\Helpers\Utils;
use CodeIgniter\Database\BaseBuilder;
use App\Models\MyBaseModel;

class FeesModel extends MyBaseModel implements TableDisplayInterface, FormInterface
{
    protected $table = 'fees';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'payer_type',
        'name',
        'rate',
        'currency',
        'category',
        'service_code',
        'chart_of_account',
        'supports_variable_amount'
    ];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected array $casts = [];
    protected array $castHandlers = [];

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
        'payer_type',
        'name',
        'rate',
        'category',
        'service_code'
    ];

    public function getDisplayColumns(): array
    {
        //get the fields for the selected type, if present, or go with the default fields if not available
        return [
            'payer_type',
            'name',
            'rate',
            'currency',
            'category',
            'service_code',
            'chart_of_account',
            'supports_variable_amount'
        ];

    }

    public function getDisplayColumnLabels(): array
    {
        return [];
    }

    public function getDisplayColumnFilters(): array
    {

        $default = [
            [
                "label" => "Search",
                "name" => "param",
                "type" => "text",
                "hint" => "Search payer type, name, service code",
                "options" => [],
                "value" => "",
                "required" => false
            ]
        ];

        return $default;
    }


    public function getFormFields(): array
    {
        return [
            [
                "label" => "Payer type",
                "name" => "payer_type",
                "type" => "text",
                "hint" => "",
                "options" => [

                ],
                "value" => "",
                "required" => true
            ],
            [
                "label" => "Name",
                "name" => "name",
                "type" => "text",
                "hint" => "",
                "options" => [],
                "value" => "",
                "required" => true
            ],
            [
                "label" => "Rate",
                "name" => "rate",
                "type" => "text",
                "hint" => "",
                "options" => [

                ],
                "value" => "",
                "required" => true
            ],
            [
                "label" => "Category",
                "name" => "category",
                "type" => "text",
                "hint" => "",
                "options" => [],
                "value" => "",
                "required" => true
            ],
            [
                "label" => "Service Code",
                "name" => "service_code",
                "type" => "text",
                "hint" => "",
                "options" => [],
                "value" => "",
                "required" => true
            ],
            [
                "label" => "Chart of Account",
                "name" => "chart_of_account",
                "type" => "text",
                "hint" => "",
                "options" => [],
                "value" => "",
                "required" => true
            ],
            [
                "label" => "Supports Variable Amount",
                "name" => "supports_variable_amount",
                "type" => "select",
                "hint" => "",
                "options" => [
                    [
                        "label" => "Yes",
                        "value" => "1"
                    ],
                    [
                        "label" => "No",
                        "value" => "0"
                    ]
                ],
                "value" => "",
                "required" => true
            ]
        ];
    }
}
