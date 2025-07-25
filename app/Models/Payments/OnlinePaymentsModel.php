<?php

namespace App\Models\Payments;

use CodeIgniter\Model;

class OnlinePaymentsModel extends Model
{
    protected $table = 'online_payments';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'collecting_agent_branch_code',
        'mda_branch_code',
        'first_name',
        'last_name',
        'email',
        'phone_number',
        'application_id',
        'description',
        'invoice_items',
        'redirect_url',
        'post_url',
        'response_status',
        'response_message',
        'invoice_expires',
        'invoice_total_amounts',
        'response',
        'invoice_currencies',
        'payment_qr_code',
        'unique_id',
        'created_at',
        'purpose',
        'year',
        'status',
        'invoice_number',
        'origin',
        'updated_at',
        'deleted_at'
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
}
