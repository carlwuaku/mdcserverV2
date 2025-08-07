<?php

namespace App\Models\Payments;

use App\Helpers\Interfaces\TableDisplayInterface;
use App\Helpers\Interfaces\FormInterface;
use App\Helpers\Utils;
use CodeIgniter\Database\BaseBuilder;
use App\Models\MyBaseModel;

class InvoiceLineItemModel extends MyBaseModel implements FormInterface
{
    protected $table = 'invoice_line_items';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'invoice_uuid',
        'service_code',
        'description',
        'quantity',
        'unit_price',
        'line_total'
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
        'quantity' => [
            'greater_than' => 'Quantity must be greater than 0'
        ],
        'unit_price' => [
            'greater_than_equal_to' => 'Unit price cannot be negative'
        ]
    ];
    protected $skipValidation = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert = ['calculateLineTotal'];
    protected $afterInsert = [];
    protected $beforeUpdate = ['calculateLineTotal'];
    protected $afterUpdate = [];
    protected $beforeFind = [];
    protected $afterFind = [];
    protected $beforeDelete = [];
    protected $afterDelete = [];


    public $searchFields = [

    ];




    /**
     * Returns form fields for editing an invoice line item.
     * for each item, the invoice number and amount are derived from the form and fees table 
     * @return array form fields
     */
    public function getFormFields(): array
    {
        return [

            [
                "label" => "Purpose of payment",
                "hint" => "",
                "name" => "purpose",
                "type" => "select",
                "options" => $this->getPaymentPurposes(),
                "value" => "",
                "required" => true
            ]
        ];
    }

    protected function calculateLineTotal(array $data): array
    {
        if (isset($data['data']['quantity']) && isset($data['data']['unit_price'])) {
            $data['data']['line_total'] = $data['data']['quantity'] * $data['data']['unit_price'];
        }
        return $data;
    }

    public function getByInvoice(string $invoiceId): array
    {
        $db = \Config\Database::connect();

        return $db->table('invoice_line_items ili')
            ->select('ili.*, pp.purpose_name, pp.purpose_code')
            ->join('payment_purposes pp', 'ili.purpose_id = pp.purpose_id')
            ->where('ili.invoice_id', $invoiceId)
            ->orderBy('ili.created_at', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function addLineItem(
        string $invoiceId,
        string $purposeId,
        string $description,
        float $quantity,
        float $unitPrice
    ): ?string {
        $lineTotal = $quantity * $unitPrice;

        $data = [
            'invoice_id' => $invoiceId,
            'purpose_id' => $purposeId,
            'description' => $description,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'line_total' => $lineTotal
        ];

        $lineItemId = $this->insert($data);
        return $lineItemId ?: null;
    }

    public function updateLineItem(string $lineItemId, float $quantity, float $unitPrice): bool
    {
        return $this->update($lineItemId, [
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'line_total' => $quantity * $unitPrice
        ]);
    }

    public function getInvoiceTotal(string $invoiceId): float
    {
        $result = $this->where('invoice_id', $invoiceId)
            ->selectSum('line_total')
            ->first();

        return (float) ($result['line_total'] ?? 0);
    }

    public function getPurposeSummary(string $invoiceId): array
    {
        $db = \Config\Database::connect();

        return $db->table('invoice_line_items ili')
            ->select('pp.purpose_name, pp.purpose_code, 
                           SUM(ili.line_total) as total_amount,
                           COUNT(*) as line_count')
            ->join('payment_purposes pp', 'ili.purpose_id = pp.purpose_id')
            ->where('ili.invoice_id', $invoiceId)
            ->groupBy('ili.purpose_id, pp.purpose_name, pp.purpose_code')
            ->get()
            ->getResultArray();
    }
}
