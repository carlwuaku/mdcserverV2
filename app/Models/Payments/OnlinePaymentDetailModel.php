<?php

namespace App\Models\Payments;

use App\Helpers\Interfaces\TableDisplayInterface;
use App\Helpers\Interfaces\FormInterface;
use App\Helpers\Utils;
use CodeIgniter\Database\BaseBuilder;
use App\Models\MyBaseModel;


class OnlinePaymentDetailModel extends MyBaseModel
{
    protected $table = 'online_payment_details';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'payment_uuid',
        'payment_gateway',
        'transaction_id',
        'gateway_response',
        'gateway_status',
        'processing_fee',
        'net_amount'
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
        'payment_uuid' => 'required|max_length[36]',
        'payment_gateway' => 'required|max_length[100]',
        'transaction_id' => 'permit_empty|max_length[255]',
        'gateway_status' => 'permit_empty|max_length[50]',
        'processing_fee' => 'permit_empty|decimal',
        'net_amount' => 'permit_empty|decimal'
    ];
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

    public function findByPayment(string $paymentId): ?array
    {
        return $this->where('payment_id', $paymentId)->first();
    }

    public function findByTransactionId(string $transactionId): ?array
    {
        return $this->where('transaction_id', $transactionId)->first();
    }

    public function updateGatewayResponse(string $paymentId, array $response, string $status): bool
    {
        return $this->where('payment_id', $paymentId)
            ->set([
                'gateway_response' => json_encode($response),
                'gateway_status' => $status
            ])
            ->update();
    }
}
