<?php

namespace App\Models\Licenses;

use CodeIgniter\Model;
use App\Helpers\Interfaces\TableDisplayInterface;
use App\Helpers\Interfaces\FormInterface;
use App\Helpers\Utils;
use CodeIgniter\Database\BaseBuilder;
use App\Models\MyBaseModel;
use App\Models\Licenses\LicensesModel;


class LicenseRenewal extends MyBaseModel implements TableDisplayInterface, FormInterface
{
    protected $table = 'license_renewal';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'uuid',
        'license_number',
        'updated_at',
        'deleted_at',
        'modified_by',
        'created_by',
        'created_on',
        'modified_on',
        'start_date',
        'receipt',
        'qr_code',
        'qr_text',
        'expiry',
        'status',
        'batch_number',
        'payment_date',
        'payment_file',
        'payment_file_date',
        'payment_invoice_number',
        'approve_online_certificate',
        'online_certificate_start_date',
        'online_certificate_end_date',
        'picture'
    ];

    protected bool $allowEmptyInserts = false;

    // Dates
    protected $useTimestamps = false;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_on';
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
    public $licenseType = null;

    public function __construct($licenseType = null)
    {
        parent::__construct();
        $this->licenseType = $licenseType;
    }

    public $searchFields = [
        'type',
        'license_number',
        'receipt',
        'batch_number',
        'payment_invoice_number',
    ];



    public function getDisplayColumns(): array
    {
        //get the fields for the selected type and merge with the default fields
        $defaultColumns = [
            'picture',
            'type',
            'license_number',
            'created_by',
            'created_on',
            'modified_on',
            'start_date',
            'receipt',
            'qr_code',
            'qr_text',
            'expiry',
            'status',
            'batch_number',
            'payment_date',
            'payment_file',
            'payment_file_date',
            'payment_invoice_number',
            'approve_online_certificate',
            'online_certificate_start_date',
            'online_certificate_end_date',
        ];
        if ($this->licenseType) {
            $licenseTypes = Utils::getAppSettings("licenseTypes");
            if (
                !$licenseTypes || !is_array($licenseTypes) ||
                empty($licenseTypes) || !array_key_exists($this->licenseType, $licenseTypes)
            ) {
                return $defaultColumns;
            }
            $licenseDef = $licenseTypes[$this->licenseType];
            $fields = $licenseDef['renewalFields'];
            $columns = array_map(function ($field) {
                return $field['name'];
            }, $fields);
            return array_merge($defaultColumns, $columns, ['deleted_at']);
        }
        return $defaultColumns;
    }

    public function getDisplayColumnLabels(): array
    {
        return [
        ];
    }



    public function getTableName(): string
    {
        return $this->table;
    }



    public function getDisplayColumnFilters(): array
    {

        $default = [
            [
                "label" => "License Number",
                "name" => "license_number",
                "type" => "text",
                "hint" => "",
                "options" => [],
                "value" => "",
                "required" => false
            ],
            [
                "label" => "License Type",
                "name" => "type",
                "type" => "select",
                "hint" => "",
                "options" => $this->getDistinctValuesAsKeyValuePairs('type'),
                "value" => "",
                "required" => false
            ],
            [
                "label" => "Registration Date",
                "name" => "registration_date",
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
                "options" => $this->getDistinctValuesAsKeyValuePairs('status'),
                "value" => "",
                "required" => false
            ],
            [
                "label" => "Region",
                "name" => "region",
                "type" => "select",
                "hint" => "",
                "options" => $this->getDistinctValuesAsKeyValuePairs('region'),
                "value" => "",
                "required" => false
            ],
            [
                "label" => "District",
                "name" => "district",
                "type" => "select",
                "hint" => "",
                "options" => $this->getDistinctValuesAsKeyValuePairs('district'),
                "value" => "",
                "required" => false
            ],
        ];

        if ($this->licenseType) {
            $licenseTypes = Utils::getAppSettings("licenseTypes");
            if (
                !$licenseTypes || !is_array($licenseTypes) ||
                empty($licenseTypes) || !array_key_exists($this->licenseType, $licenseTypes)
            ) {
                return $default;
            }
            $licenseDef = $licenseTypes[$this->licenseType];
            $fields = $licenseDef['fields'];

            return array_merge($default, $fields);
        }
        return $default;
    }






    public function addLicenseDetails(BaseBuilder $builder, string $licenseType): BaseBuilder
    {
        try {
            $licenseDef = Utils::getLicenseSetting($licenseType);
            $licenseTypeFields = $licenseDef->fields;
            $licenseTypeTable = $licenseDef->table;
            //get the sub table for that license type
            $renewalSubTable = $licenseDef->renewalTable;
            $renewalSubFields = $licenseDef->renewalFields;
            $columns = [];
            for ($i = 0; $i < count($licenseTypeFields); $i++) {
                $columns[] = $licenseTypeTable . $licenseTypeFields[$i]['name'];
            }
            for ($i = 0; $i < count($renewalSubFields); $i++) {
                $columns[] = $renewalSubTable . $renewalSubFields[$i]['name'];
            }
            $builder->select($columns);
            $builder->join("licenses", $this->table . '.license_number = licenses.license_number');

            $builder->join($licenseTypeTable, $this->table . ".license_number = $licenseTypeTable.license_number");
            $builder->join($renewalSubTable, $this->table . ".license_number = $renewalSubTable.license_number");
            return $builder;
        } catch (\Throwable $th) {
            log_message('error', $th->getMessage());
            return $builder;
        }
    }

    /**
     * insert or update renewal details. the table is obtained from the license type in app.settings.json
     * @param string $licenseType
     * @param array $formData
     * @return void
     */
    public function createOrUpdateSubDetails($licenseType, $formData)
    {
        try {
            $licenseDef = Utils::getLicenseSetting($licenseType);
            $table = $licenseDef->renewalTable;
            $licenseFields = $licenseDef->renewalFields;
            $data = new \stdClass();
            //make sure $formdata is an array
            if (!is_array($formData)) {
                $formData = (array) $formData;
            }

            foreach ($licenseFields as $field) {
                $name = $field['name'];
                if (array_key_exists($name, $formData)) {
                    $data->$name = $formData[$name];
                }
            }
            $license = $this->builder($table)->where('license_number', $formData['license_number'])->get()->getFirstRow('array');

            if ($license) {
                $this->builder($table)->where('license_number', $license['license_number'])->update($data);
            } else {
                $this->builder($table)->insert($data);
            }
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function getDetailsFromSubTable(string $uuid, string $type = ""): array
    {
        $data = $this->where('uuid', $uuid)->orWhere('license_number', $uuid)->first();
        if (!$data) {
            throw new \Exception("License renewal not found");
        }
        if (empty($type)) {
            $licenseModel = new LicensesModel();
            $license = $licenseModel->where('license_number', $data['license_number'])->first();
            if (!$license) {
                throw new \Exception("License not found");
            }
            $type = $data['type'];
        }
        $table = Utils::getLicenseTable($type);
        $builder = $this->builder($table)->where('license_number', $data['license_number']);

        $data = $builder->get()->getFirstRow('array');
        if (!$data) {
            throw new \Exception("License details not found");
        }

        return $data;
    }


    public function getFormFields(): array
    {
        return [
            [
                "label" => "License Number",
                "name" => "license_number",
                "type" => "api",
                "hint" => "",
                "options" => [],
                "value" => "",
                "required" => true,
                "api_url" => "licenses/licenses",
                "apiKeyProperty" => "license_number",
                "apiLabelProperty" => "name",
                "apiType" => "search" | "select" | "datalist"
            ],
            [
                "label" => "Start Date",
                "name" => "start_date",
                "type" => "date",
                "hint" => "",
                "options" => [],
                "value" => "",
                "required" => true
            ],

            [
                "label" => "End Date",
                "name" => "expiry",
                "type" => "date",
                "hint" => "",
                "options" => [],
                "value" => "",
                "required" => true
            ],
            [
                "label" => "Status",
                "name" => "status",
                "type" => "select",
                "hint" => "",
                "options" => [
                    [
                        "key" => "Active",
                        "value" => "active"
                    ],
                    [
                        "key" => "Inactive",
                        "value" => "inactive"
                    ],
                    [
                        "key" => "Suspended",
                        "value" => "suspended"
                    ]
                ],
                "value" => "",
                "required" => true
            ],
            [
                "label" => "Email",
                "name" => "email",
                "type" => "email",
                "hint" => "",
                "options" => [],
                "value" => "",
                "required" => true
            ],
            [
                "label" => "Picture",
                "name" => "picture",
                "type" => "picture",
                "hint" => "",
                "options" => [],
                "value" => "",
                "required" => false
            ],
            [
                "label" => "Postal Address",
                "name" => "postal_address",
                "type" => "text",
                "hint" => "",
                "options" => [],
                "value" => "",
                "required" => false
            ],
            [
                "label" => "Phone",
                "name" => "phone",
                "type" => "text",
                "hint" => "",
                "options" => [],
                "value" => "",
                "required" => false
            ],
            [
                "label" => "Region",
                "name" => "region",
                "type" => "api",
                "hint" => "",
                "options" => [],
                "value" => "",
                "required" => false,
                "api_url" => "regions/regions",
                "apiKeyProperty" => "name",
                "apiLabelProperty" => "name",
                "apiType" => "select",
            ],
            [
                "label" => "District",
                "name" => "district",
                "type" => "api",
                "hint" => "",
                "options" => [],
                "value" => "",
                "required" => false,
                "api_url" => "regions/districts",
                "apiKeyProperty" => "district",
                "apiLabelProperty" => "district",
                "apiType" => "select",
            ],
            [
                "label" => "Portal Access",
                "name" => "portal_access",
                "type" => "select",
                "hint" => "",
                "options" => [
                    [
                        "key" => "Yes",
                        "value" => "yes"
                    ],
                    [
                        "key" => "No",
                        "value" => "no"
                    ]
                ],
                "value" => "",
                "required" => false
            ],
        ];
    }
}
