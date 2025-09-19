<?php

namespace App\Models\Payments;

use App\Helpers\Interfaces\TableDisplayInterface;
use App\Helpers\Interfaces\FormInterface;
use App\Helpers\Utils;
use CodeIgniter\Database\BaseBuilder;
use App\Models\MyBaseModel;


class InvoiceModel extends MyBaseModel implements TableDisplayInterface
{
    protected $table = 'invoices';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'invoice_number',
        'unique_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'amount',
        'application_id',
        'post_url',
        'redirect_url',
        'purpose',
        'year',
        'currency',
        'due_date',
        'status',
        'notes',
        'purpose_table',
        'purpose_table_uuid',
        'payment_method',
        'origin',
        'payment_file',
        'payment_date',
        'payment_file_date',
        'online_payment_status',
        'online_payment_response',
        'mda_branch_code',
        'description',
        'invoice_template',
        'selected_payment_method'
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
    protected $validationRules = [

    ];
    protected $validationMessages = [

    ];
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
        'unique_id',
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
            'first_name',
            'last_name',
            'unique_id',
            'invoice_number',
            'email',
            'phone_number',
            'amount',
            'purpose',
            'due_date',
            'status',
            'notes',
            'payment_method',
            'payment_file',
            'payment_date',
            'payment_file_date',
            'online_payment_status',
            'online_payment_response',
            'mda_branch_code',
            'description',
            'selected_payment_method'
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
                "hint" => "Search invoice number, name, email, phone number",
                "options" => [],
                "value" => "",
                "required" => false
            ],
            [
                "label" => "Status",
                "name" => "status",
                "type" => "select",
                "hint" => "",
                "options" => $this->getDistinctValuesAsKeyValuePairs('status'),
                "value" => "",
                "required" => false
            ],
            [
                "label" => "Due Date",
                "name" => "due_date",
                "type" => "date-range",
                "hint" => "",
                "options" => [],
                "value" => "",
                "required" => false
            ],
            [
                "label" => "Purpose",
                "name" => "purpose",
                "type" => "select",
                "hint" => "",
                "options" => $this->getDistinctValuesAsKeyValuePairs('purpose'),
                "value" => "",
                "required" => false
            ]
        ];

        return $default;
    }




    /**
     * generate a random string for an invoice number. it will have the unique id appended
     * @param string $uid the license number or registration number of the payer
     * @return string 
     */
    public function generateInvoiceApplicationId($uid)
    {
        $uid = str_replace(['+', '/', '=', ' '], '', $uid);
        return substr(str_replace(['+', '/', '='], '', base64_encode(random_bytes(32))), 0, 18) . $uid;
    }




}
