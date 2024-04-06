<?php

namespace App\Models\Practitioners;
use App\Models\MyBaseModel;
use App\Helpers\Interfaces\TableDisplayInterface;
use CodeIgniter\Database\BaseBuilder;

use CodeIgniter\Model;

class PractitionerApplicationModel extends MyBaseModel implements TableDisplayInterface
{
    protected $table            = 'practitioner_applications';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [];

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

    public $searchFields = [
        "first_name",
        "last_name",
        "middle_name",
        "application_code",
        "maiden_name",
        "email",
        "provisional_number"
    ];

    public function getDisplayColumns(): array
    {
        return [
            "picture",
            "application_code",
            "first_name",
            "middle_name",
            "last_name",
            "in_good_standing",
            "status",
            "email",
            "phone",
            "maiden_name",
            "sex",
            "register_type",
            "category",
            "registration_date",
            "nationality",
            "date_of_birth",
            "provisional_number",
            "specialty",
            "subspecialty",
            "qualification_at_registration",
            "training_institution",
            "qualification_date",
            "year_of_permanent",
            "year_of_provisional",
            "college_membership",
            "deleted_at",
            "last_renewal_start",
            "last_renewal_expiry",
            "last_renewal_status",
        ];
    }

    public function getDisplayColumnLabels(): array
    {
        return [
            "year" => "Start Date",
        ];
    }

    public function addCustomFields(BaseBuilder $builder): BaseBuilder
    {

        $filteredColumns = [
            "application_code",
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
            'uuid',
            'practitioner_uuid',
            "title",
            "register_type",
            "practitioner_type",
            'qr_code',
        ];
        $builder
            ->select(implode(', ', $filteredColumns))

            ->select("CONCAT('" . base_url("file-server/image-render") . "','/practitioners_images/'," . "picture) as picture");
        return $builder;
    }

    public function getTableName(): string
    {
        return $this->table;
    }

    public function getDisplayColumnFilters(): array
    {
        return [
            [
                "label" => "Registration Number",
                "name" => "application_code",
                "type" => "text",
                "hint" => "",
                "options" => [],
                "value" => "",
                "required" => false
            ],
            [
                "label" => "Date created",
                "name" => "created_on",
                "type" => "date",
                "hint" => "",
                "options" => [],
                "value" => "",
                "required" => false
            ],
            [
                "label" => "Status",
                "name" => "status",
                "type" => "select",
                "hint" => "",
                "options" => [["key" => "Approved", "value" => "Approved"]],
                "value" => "",
                "required" => false
            ],


            // "application_code" => ["type" => "text"],
            // "created_on" => ["type" => "date"],
            // "expiry" => ["type" => "date"],
            // "status" => ["type" => "select", "options" => ["Approved", "Pending Payment", "Pending Approval"]],
            // "year" => ["type" => "date"],
            // 'specialty' => ["type" => "text"],

            // 'subspecialty' => ["type" => "text"],
            // 'college_membership' => ["type" => "text"],
            // 'payment_invoice_number' => ["type" => "text"],

            // "register_type" => ["type" => "select", "options" => ["Permanent", "Provisional", "Temporary"]],
            // "practitioner_type" => ["type" => "select", "options" => ["Doctor", "Physician Assistant"]],
        ];
    }
}
