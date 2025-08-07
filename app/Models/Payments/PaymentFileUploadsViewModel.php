<?php

namespace App\Models\Payments;

use App\Models\MyBaseModel;

class PaymentFileUploadsViewModel extends MyBaseModel
{
    protected $table = 'payment_file_uploads_view';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'unique_id',
        'first_name',
        'last_name',
        'email',
        'phone_number',
        'amount',
        'application_id',
        'purpose',
        'due_date',
        'file_status',
        'invoice_status',
        'payment_date',
        'reference_number'
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
        'application_id',
        'unique_id',
        'first_name',
        'last_name',
        'email',
        'phone_number',
        'amount'
    ];
}
