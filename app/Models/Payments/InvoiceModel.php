<?php

namespace App\Models\Payments;

use App\Helpers\Interfaces\TableDisplayInterface;
use App\Helpers\Interfaces\FormInterface;
use App\Helpers\Utils;
use CodeIgniter\Database\BaseBuilder;
use App\Models\MyBaseModel;


class InvoiceModel extends MyBaseModel implements TableDisplayInterface, FormInterface
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
        'name',
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
        'purpose_table_uuid'
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
        'name',
        'email',
        'phone_number',
        'amount'
    ];

    public function getDisplayColumns(): array
    {
        //get the fields for the selected type, if present, or go with the default fields if not available
        return [
            'invoice_number',
            'name',
            'email',
            'phone_number',
            'amount',
            'purpose',
            'due_date',
            'status',
            'notes'
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


    public function getFormFields(): array
    {
        return [
            [
                "label" => "Payer Unique ID",
                "hint" => "Unique ID of the payer. e.g. the license number",
                "name" => "payer_type",
                "type" => "text",
                "options" => [

                ],
                "value" => "",
                "required" => true
            ],
            [
                "label" => "Purpose of payment",
                "hint" => "",
                "name" => "purpose",
                "type" => "select",
                "options" => $this->getPaymentPurposes(),
                "value" => "",
                "required" => true
            ],
            [
                "label" => "Due Date",
                "name" => "due_date",
                "type" => "date",
                "hint" => "",
                "options" => [],
                "value" => "",
                "required" => true
            ],
            [
                "label" => "Notes",
                "name" => "notes",
                "type" => "textarea",
                "hint" => "",
                "options" => [],
                "value" => "",
                "required" => false
            ]
        ];
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




    protected function updateOverdueStatus(array $data): array
    {
        if (
            isset($data['data']['due_date']) && $data['data']['due_date'] < date('Y-m-d') &&
            isset($data['data']['status']) && $data['data']['status'] === 'pending'
        ) {
            $data['data']['status'] = 'overdue';
        }
        return $data;
    }

    private function generateUniqueInvoiceNumber(): string
    {
        $year = date('Y');
        $prefix = 'INV-' . $year . '-';

        $lastInvoice = $this->like('invoice_number', $prefix, 'after')
            ->orderBy('invoice_number', 'DESC')
            ->first();

        if ($lastInvoice) {
            $lastNumber = (int) substr($lastInvoice['invoice_number'], -6);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . str_pad($newNumber, 6, '0', STR_PAD_LEFT);
    }

    public function findByInvoiceNumber(string $invoiceNumber): ?array
    {
        return $this->where('invoice_number', $invoiceNumber)->first();
    }

    public function getWithDetails(string $invoiceId): ?array
    {
        $db = \Config\Database::connect();

        return $db->table('invoices i')
            ->select('i.*, l.license_number, l.holder_name, l.holder_email, 
                           pp.purpose_name, pp.description as purpose_description')
            ->join('licenses l', 'i.license_id = l.license_id')
            ->join('payment_purposes pp', 'i.purpose_id = pp.purpose_id')
            ->where('i.invoice_id', $invoiceId)
            ->get()
            ->getRowArray();
    }

    public function getOutstanding(int $limit = null): array
    {
        $builder = $this->where('status', 'pending')
            ->orWhere('status', 'overdue')
            ->orderBy('due_date', 'ASC');

        if ($limit) {
            $builder->limit($limit);
        }

        return $builder->findAll();
    }

    public function getOverdue(): array
    {
        return $this->where('due_date <', date('Y-m-d'))
            ->where('status', 'pending')
            ->findAll();
    }

    public function getTotalAmount(array $filters = []): float
    {
        $builder = $this->builder();

        if (!empty($filters['status'])) {
            $builder->where('status', $filters['status']);
        }

        if (!empty($filters['date_from'])) {
            $builder->where('created_at >=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $builder->where('created_at <=', $filters['date_to']);
        }

        $result = $builder->selectSum('amount')->get()->getRowArray();
        return (float) ($result['amount'] ?? 0);
    }

    public function markAsPaid(string $invoiceId): bool
    {
        return $this->update($invoiceId, ['status' => 'paid']);
    }

    public function updateOverdueInvoices(): int
    {
        return $this->where('due_date <', date('Y-m-d'))
            ->where('status', 'pending')
            ->set(['status' => 'overdue'])
            ->update();
    }

    private function getPaymentPurposes()
    {
        $purposeModel = new PaymentPurposeModel();
        return $purposeModel->getPurposeNamesForSelect();

    }
}
