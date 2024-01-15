<?php

namespace App\Models;

use CodeIgniter\Model;

class PractitionerRenewalModel extends Model
{
    protected $table            = 'practitioner_renewal';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'uuid',
        'registration_number',
        'deleted_by',
        'deleted',
        'modified_by',
        'created_by',
        'created_on',
        'modified_on',
        'receipt',
        'qr_code',
        'qr_text',
        'expiry',
        'specialty',
        'place_of_work',
        'region',
        'institution_type',
        'district',
        'status',
        'payment_date',
        'payment_file',
        'payment_file_date',
        'subspecialty',
        'college_membership',
        'payment_invoice_number',
        'first_name',
        'middle_name',
        'last_name',
        'title',
        'maiden_name',
        'marital_status',
        'picture',

    ];

    // Dates
    protected $useTimestamps = false;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    // Validation
    protected $validationRules      = [];
    protected $validationMessages   = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert   = [];
    protected $afterInsert    = [];
    protected $beforeUpdate   = [];
    protected $afterUpdate    = [];
    protected $beforeFind     = [];
    protected $afterFind      = [];
    protected $beforeDelete   = [];
    protected $afterDelete    = [];
}
