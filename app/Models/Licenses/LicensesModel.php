<?php

namespace App\Models\Licenses;

use App\Helpers\Interfaces\TableDisplayInterface;
use App\Helpers\Interfaces\FormInterface;
use App\Helpers\Utils;
use CodeIgniter\Database\BaseBuilder;
use App\Models\MyBaseModel;

class LicensesModel extends MyBaseModel implements TableDisplayInterface, FormInterface
{
    protected $table = 'licenses';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    protected $protectFields = true;
    protected $allowedFields = [
        'uuid',
        'license_number',
        'name',
        'registration_date',
        'status',
        'email',
        'postal_address',
        'picture',
        'type',
        'phone',
        'region',
        'district',
        'portal_access',
        'last_renewal_start',
        'last_renewal_expiry',
        'last_renewal_status',
        'deleted_at',
        'created_on'
    ];

    protected bool $allowEmptyInserts = false;

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
    public $licenseType = null;

    public $renewalDate = "";
    public function setRenewalDate(string $date)
    {
        $this->renewalDate = $date;
    }

    public function __construct($licenseType = null)
    {
        parent::__construct();
        $this->licenseType = $licenseType;
    }

    public $searchFields = [
        'type',
        'license_number',
        'email',
        'phone',
        'region',
        'district'
    ];



    public function getDisplayColumns(): array
    {
        //get the fields for the selected type and merge with the default fields
        $defaultColumns = [
            'picture',
            'type',
            'license_number',
            'name',
            'in_good_standing',
            'status',
            'registration_date',
            'email',
            'phone',
            'postal_address',
            'region',
            'district',
            'created_on',
            'portal_access',
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
            $fields = $licenseDef['fields'];
            $columns = array_map(function ($field) {
                return $field['name'];
            }, $fields);
            return Utils::reorderPriorityColumns(array_merge($defaultColumns, $columns, ['deleted_at']));
        }
        return Utils::reorderPriorityColumns($defaultColumns);
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




    public function addCustomFields(BaseBuilder $builder): BaseBuilder
    {
        if ($this->renewalDate === "") {
            $this->renewalDate = date("Y-m-d");
        }

        $filteredColumns = [
            'uuid',
            'license_number',
            'name',
            'registration_date',
            'status',
            'email',
            'postal_address',
            'picture',
            'type',
            'phone',
            'region',
            'district',
            'portal_access',
            "last_renewal_start",
            "last_renewal_expiry",
            "last_renewal_status",
            "deleted_at",
            "created_on"
        ];

        //add the table name to the columns
        $filteredColumns = array_map(function ($column) {
            return $this->table . '.' . $column;
        }, $filteredColumns);

        $builder
            ->select(implode(', ', $filteredColumns))
            ->select("(CASE  
            when last_renewal_status = 'Approved' and '$this->renewalDate' BETWEEN last_renewal_start AND last_renewal_expiry THEN 'In Good Standing'
            when last_renewal_status = 'Pending Payment' and '$this->renewalDate' BETWEEN last_renewal_start AND last_renewal_expiry THEN 'Pending Payment'
            when last_renewal_status = 'Pending Approval' and '$this->renewalDate' BETWEEN last_renewal_start AND last_renewal_expiry THEN 'Pending Approval'
             ELSE 'Not In Good Standing'
             END) as in_good_standing");
        // ->
        // select("CONCAT('" . base_url("file-server/image-render") . "','/applications/'," . "picture) as picture");

        return $builder;
    }

    public function addLicenseDetails(BaseBuilder $builder, string $licenseType): BaseBuilder
    {
        try {
            $licenseDef = Utils::getLicenseSetting($licenseType);
            $fields = $licenseDef->fields;
            $table = $licenseDef->table;
            $columns = [];
            for ($i = 0; $i < count($fields); $i++) {
                $columns[] = $table . $fields[$i]['name'];
            }
            $builder->select($columns);
            $builder->join($table, $table . '.license_number = licenses.license_number');
            return $builder;
        } catch (\Throwable $th) {
            log_message('error', $th->getMessage());
            return $builder;
        }
    }

    /**
     * insert or update license details. the table is obtained from the license type in app settings
     * @param string $licenseType
     * @param array $formData
     * @return void
     */
    public function createOrUpdateLicenseDetails($licenseType, $formData)
    {
        try {
            $licenseDef = Utils::getLicenseSetting($licenseType);
            $table = $licenseDef->table;
            $licenseFields = $licenseDef->fields;
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

    public function getLicenseDetailsFromSubTable(string $uuid, string $type = ""): array
    {
        $data = $this->where('uuid', $uuid)->orWhere('license_number', $uuid)->first();
        if (!$data) {
            throw new \Exception("License not found");
        }
        if (empty($type)) {
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

    public function addLastRenewalField(BaseBuilder $builder): BaseBuilder
    {
        $licensesTable = $this->table;
        $renewalTable = "license_renewal";
        $builder->select("(SELECT $renewalTable.uuid 
             FROM $renewalTable 
             WHERE $renewalTable.license_uuid = $licensesTable.uuid AND '$this->renewalDate' BETWEEN $renewalTable.start_date AND $renewalTable.expiry 
             LIMIT 1) AS last_renewal_uuid");
        return $builder;
    }

    public function getFormFields(): array
    {
        return [
            [
                "label" => "License Type",
                "name" => "type",
                "type" => "select",
                "hint" => "",
                "options" => [
                    [
                        "key" => "Facility",
                        "value" => "facilities"
                    ],
                    [
                        "key" => "Practitioner",
                        "value" => "practitioners"
                    ]
                ],
                "value" => "",
                "required" => true
            ],
            [
                "label" => "Registration Date",
                "name" => "registration_date",
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
