<?php

namespace App\Models\Payments;

use App\Helpers\Interfaces\TableDisplayInterface;
use App\Helpers\Interfaces\FormInterface;
use App\Helpers\Utils;
use CodeIgniter\Database\BaseBuilder;
use App\Models\MyBaseModel;

class PaymentFileUploadsModel extends MyBaseModel implements TableDisplayInterface
{
    protected $table = 'payment_file_uploads';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'invoice_uuid',
        'file_path',
        'payment_date',
        'status'
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
        'invoice_number',
        'first_name',
        'last_name',
        'email',
        'phone_number',
        'amount'
    ];

    public function getDisplayColumns(): array
    {
        return [
            'application_id',
            'file_path',
            'first_name',
            'last_name',
            'unique_id',
            'invoice_number',
            'email',
            'phone_number',
            'amount',
            'purpose',
            'due_date',
            'file_status',
            'invoice_status',
            'payment_method',
            'payment_date',
            'created_at'
        ];

    }

    public function getDisplayColumnLabels(): array
    {
        return [
            'file_path' => 'File',
        ];
    }

    public function getDisplayColumnFilters(): array
    {

        $default = [
            [
                "label" => "Search",
                "name" => "param",
                "type" => "text",
                "hint" => "Search invoice number, name, email, phone number",
                "options" => [],
                "value" => "",
                "required" => false
            ],
            [
                "label" => "Status",
                "name" => "file_status",
                "type" => "select",
                "hint" => "",
                "options" => $this->getDistinctValuesAsKeyValuePairs('status'),
                "value" => "",
                "required" => false
            ],
            [
                "label" => "Date submitted",
                "name" => "created_at",
                "type" => "date-range",
                "hint" => "",
                "options" => [],
                "value" => "",
                "required" => false
            ]
        ];

        return $default;
    }
}
