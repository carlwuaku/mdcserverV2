<?php

namespace App\Models;

use App\Helpers\Interfaces\TableDisplayInterface;
use CodeIgniter\Database\BaseBuilder;

class PractitionerRenewalModel extends MyBaseModel implements TableDisplayInterface
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
        'year',
        'practitioner_uuid'

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

    public $searchFields = ["first_name", "last_name", "middle_name",
        "registration_number", "maiden_name", 'specialty',
        'place_of_work',
        'region',
        'institution_type',
        'district',
        'status',];

    public function getDisplayColumns(): array
    {
        return [
            "registration_number",
            "created_on", 
            "expiry",  
            "first_name", 
            "middle_name", 
            "last_name", 
            "status",
            
            "picture", 
            "year", 
            'specialty',
            'place_of_work',
            'region',
            'institution_type',
            'district',
            'payment_date',
            'payment_file',
            'payment_file_date',
            'subspecialty',
            'college_membership',
            'payment_invoice_number',
            'deleted_at'
        ];
    }

    public function getDisplayColumnLabels(): array
    {
        return [];
    }

    public function addCustomFields(BaseBuilder $builder): BaseBuilder
    {
        
        $filteredColumns = [
            "registration_number","created_on", "expiry",  "first_name", "middle_name", "last_name", "status",
            
            "picture", "year", 'specialty',
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
            'uuid',
            'practitioner_uuid'
        ];
        $builder
            ->select(implode(', ', $filteredColumns))
            
            ->select("CONCAT('" . base_url("file-server/image-render") . "','/practitioners_images/'," . "picture) as picture");
        return $builder;
    }

    public function getTableName(): string{
        return $this->table;
    }
}
