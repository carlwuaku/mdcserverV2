<?php

namespace App\Models\ApiIntegration;

use App\Helpers\Interfaces\FormInterface;
use App\Helpers\Interfaces\TableDisplayInterface;
use App\Models\MyBaseModel;

class InstitutionModel extends MyBaseModel implements TableDisplayInterface, FormInterface
{
    protected $table = 'institutions';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = false;
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    protected $protectFields = true;
    protected $allowedFields = [
        'name',
        'code',
        'email',
        'phone',
        'address',
        'contact_person_name',
        'contact_person_email',
        'contact_person_phone',
        'description',
        'status',
        'ip_whitelist',
        'created_by',
    ];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected array $casts = [
        'ip_whitelist' => 'json',
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = 'deleted_at';

    // Validation
    protected $validationRules = [
        'name' => 'required|max_length[255]',
        'code' => 'required|max_length[50]|is_unique[institutions.code,id,{id}]',
        'email' => 'permit_empty|valid_email|max_length[255]',
        'phone' => 'permit_empty|max_length[50]',
        'contact_person_email' => 'permit_empty|valid_email|max_length[255]',
        'contact_person_phone' => 'permit_empty|max_length[50]',
        'status' => 'permit_empty|in_list[active,inactive,suspended]',
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

    public $searchFields = [
        'name',
        'code',
        'email',
        'phone',
        'contact_person_name',
        'contact_person_email',
    ];

    public function getDisplayColumns(): array
    {
        return [
            'code',
            'name',
            'email',
            'phone',
            'contact_person_name',
            'contact_person_email',
            'status',
            'created_at',
        ];
    }

    public function getDisplayColumnLabels(): array
    {
        return [
            'code' => 'Institution Code',
            'name' => 'Institution Name',
            'contact_person_name' => 'Contact Person',
            'contact_person_email' => 'Contact Email',
            'created_at' => 'Created Date',
        ];
    }

    public function getDisplayColumnFilters(): array
    {
        return [
            [
                "label" => "Status",
                "name" => "status",
                "type" => "select",
                "hint" => "",
                "options" => [
                    ["key" => "Active", "value" => "active"],
                    ["key" => "Inactive", "value" => "inactive"],
                    ["key" => "Suspended", "value" => "suspended"],
                ],
                "value" => "",
                "required" => false,
            ],
        ];
    }

    public function getFormFields(): array
    {
        return [
            [
                "label" => "Institution Name",
                "name" => "name",
                "type" => "text",
                "hint" => "Full name of the institution",
                "options" => [],
                "value" => "",
                "required" => true,
                "showOnly" => false,
            ],
            [
                "label" => "Institution Code",
                "name" => "code",
                "type" => "text",
                "hint" => "Unique identifier/code for the institution",
                "options" => [],
                "value" => "",
                "required" => true,
                "showOnly" => false,
            ],
            [
                "label" => "Email",
                "name" => "email",
                "type" => "email",
                "hint" => "Institution's contact email",
                "options" => [],
                "value" => "",
                "required" => false,
                "showOnly" => false,
            ],
            [
                "label" => "Phone",
                "name" => "phone",
                "type" => "text",
                "hint" => "Institution's contact phone number",
                "options" => [],
                "value" => "",
                "required" => false,
                "showOnly" => false,
            ],
            [
                "label" => "Address",
                "name" => "address",
                "type" => "textarea",
                "hint" => "Physical address of the institution",
                "options" => [],
                "value" => "",
                "required" => false,
                "showOnly" => false,
            ],
            [
                "label" => "Contact Person Name",
                "name" => "contact_person_name",
                "type" => "text",
                "hint" => "Name of the primary contact person",
                "options" => [],
                "value" => "",
                "required" => false,
                "showOnly" => false,
            ],
            [
                "label" => "Contact Person Email",
                "name" => "contact_person_email",
                "type" => "email",
                "hint" => "Email of the primary contact person",
                "options" => [],
                "value" => "",
                "required" => false,
                "showOnly" => false,
            ],
            [
                "label" => "Contact Person Phone",
                "name" => "contact_person_phone",
                "type" => "text",
                "hint" => "Phone number of the primary contact person",
                "options" => [],
                "value" => "",
                "required" => false,
                "showOnly" => false,
            ],
            [
                "label" => "Description",
                "name" => "description",
                "type" => "textarea",
                "hint" => "Additional notes or description",
                "options" => [],
                "value" => "",
                "required" => false,
                "showOnly" => false,
            ],
            [
                "label" => "Status",
                "name" => "status",
                "type" => "select",
                "hint" => "Current status of the institution",
                "options" => [
                    ["key" => "Active", "value" => "active"],
                    ["key" => "Inactive", "value" => "inactive"],
                    ["key" => "Suspended", "value" => "suspended"],
                ],
                "value" => "active",
                "required" => true,
                "showOnly" => false,
            ],
            [
                "label" => "IP Whitelist",
                "name" => "ip_whitelist",
                "type" => "json",
                "hint" => "JSON array of allowed IP addresses (optional). Example: [\"192.168.1.1\", \"10.0.0.0/24\"]",
                "options" => [],
                "value" => "",
                "required" => false,
                "showOnly" => false,
            ],
        ];
    }

    /**
     * Get institution with API key count
     */
    public function getInstitutionWithKeyCount(string $id)
    {
        return $this->select('institutions.*, COUNT(api_keys.id) as api_key_count')
            ->join('api_keys', 'api_keys.institution_id = institutions.id', 'left')
            ->where('institutions.id', $id)
            ->groupBy('institutions.id')
            ->first();
    }

    /**
     * Get all institutions with their API key counts
     */
    public function getAllWithKeyCounts()
    {
        return $this->select('institutions.*, COUNT(api_keys.id) as api_key_count')
            ->join('api_keys', 'api_keys.institution_id = institutions.id AND api_keys.deleted_at IS NULL', 'left')
            ->where('institutions.deleted_at', null)
            ->groupBy('institutions.id')
            ->findAll();
    }
}
