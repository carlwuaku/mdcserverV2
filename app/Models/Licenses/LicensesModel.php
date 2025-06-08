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
        'license_number',
        'email',
        'phone',
        'region',
        'district'
    ];



    public function getDisplayColumns(): array
    {
        //get the fields for the selected type, if present, or go with the default fields if not available
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
            $displayColumns = $licenseDef['displayColumns'];

            return $displayColumns;
        }
        return Utils::reorderPriorityColumns($defaultColumns);
    }

    public function getDisplayColumnLabels(): array
    {
        return [];
    }



    public function getTableName(): string
    {
        return $this->table;
    }



    public function getDisplayColumnFilters(): array
    {

        $default = [
            [
                "label" => "Search",
                "name" => "param",
                "type" => "text",
                "hint" => "Search names, emails, phone numbers",
                "options" => [],
                "value" => "",
                "required" => false
            ],
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

    /**
     * add license details to the builder
     * @param BaseBuilder $builder
     * @param string $licenseType
     * @param bool $addJoin in some cases a join may have been added already, particularly if there is a search operation
     * @return BaseBuilder
     */
    public function addLicenseDetails(BaseBuilder $builder, string $licenseType, bool $addJoin, string $licenseJoinConditions = ''): BaseBuilder
    {
        try {
            $licenseDef = Utils::getLicenseSetting($licenseType);
            $fields = $licenseDef->selectionFields;
            $licenseTypeTable = $licenseDef->table;
            $columns = [];
            for ($i = 0; $i < count($fields); $i++) {
                $columns[] = $licenseTypeTable . "." . $fields[$i];
            }
            $builder->select($columns);
            $fullLicenseJoinConditions = $this->table . ".license_number = $licenseTypeTable." . $licenseDef->uniqueKeyField;
            if ($licenseJoinConditions) {
                $builder->where($licenseJoinConditions);
            }
            if ($addJoin) {
                $builder->join($licenseTypeTable, $fullLicenseJoinConditions);
            }

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
            $uniqueKeyField = $licenseDef->uniqueKeyField;
            $licenseFields = $licenseDef->fields;
            $data = new \stdClass();
            //make sure $formdata is an array
            if (!is_array($formData)) {
                $formData = (array) $formData;
            }

            foreach ($licenseFields as $field) {
                $name = $field['name'];
                if (array_key_exists($name, $formData) && $name !== 'license_number') {
                    $data->$name = $formData[$name];
                }
            }
            //check if the unique key field is present in the form data. if not use license_number
            if (!array_key_exists($uniqueKeyField, $formData)) {
                $formData[$uniqueKeyField] = $formData['license_number'];
            }
            $license = $this->builder($table)->where($uniqueKeyField, $formData[$uniqueKeyField])->get()->getFirstRow('array');

            if (count(get_object_vars($data)) > 0) {
                if ($license) {
                    $this->builder($table)->set((array) $data)->where([$uniqueKeyField => $formData['license_number']])->update();
                } else {
                    $this->builder($table)->insert($data);
                }
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
        if (empty($type)) {
            throw new \Exception("License type not specified");
        }
        $licenseDef = Utils::getLicenseSetting($type);
        if (!$licenseDef) {
            throw new \Exception("License type not found in app settings");
        }
        if (!isset($licenseDef->table) || empty($licenseDef->table)) {
            throw new \Exception("License table not defined in app settings for type: $type");
        }
        if (!isset($licenseDef->uniqueKeyField) || empty($licenseDef->uniqueKeyField)) {
            throw new \Exception("No unique key defined for license type: $type");
        }

        $table = $licenseDef->table;
        $uniqueKeyField = $licenseDef->uniqueKeyField;
        $builder = $this->builder($table)->where($uniqueKeyField, $data['license_number']);

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
            [
                "label" => "Last Revalidation Date",
                "name" => "last_revalidation_date",
                "type" => "date",
                "hint" => "",
                "options" => [],
                "value" => "",
                "required" => false
            ],
            [
                "label" => "Require Revalidation",
                "name" => "require_revalidation",
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
            [
                "label" => "Register Type",
                "name" => "register_type",
                "type" => "select",
                "hint" => "",
                "options" => [
                    [
                        "key" => "Provisional",
                        "value" => "Provisional"
                    ],
                    [
                        "key" => "Permanent",
                        "value" => "Permanent"
                    ],
                    [
                        "key" => "Temporary",
                        "value" => "Temporary"
                    ]
                ],
                "value" => "",
                "required" => true
            ],
        ];
    }

    /**
     * get the fields for the basic statistics
     * @param string $licenseType
     * @return array{default: BasicStatisticsField[], custom: BasicStatisticsField[]}
     */
    public function getBasicStatisticsFields($licenseType = '')
    {
        if (!empty($licenseType)) {
            $licenseDef = Utils::getLicenseSetting($licenseType);
            $fields = $licenseDef->basicStatisticsFields ?? [];
            $fields = array_map(function ($field) {
                return new BasicStatisticsField($field['label'], $field['name'], $field['type'], $field['xAxisLabel'], $field['yAxisLabel']);
            }, $fields);
        } else {
            $fields = [];
        }
        $defaultFields = [
            new BasicStatisticsField("Year of registration", "year(registration_date) as year", "bar", "Year", "Number of licenses"),
            new BasicStatisticsField("Status", "status", "bar", "Status", "Number of licenses"),
            new BasicStatisticsField("Region", "region", "bar", "Region", "Number of licenses"),
            new BasicStatisticsField("District", "district", "bar", "District", "Number of licenses"),
            new BasicStatisticsField("Country of practice", "country_of_practice", "bar", "Country of practice", "Number of licenses"),
        ];

        return [
            "default" => $defaultFields,
            "custom" => $fields
        ];
    }

    public function getBasicStatisticsFilterFields($licenseType = '')
    {
        if (!empty($licenseType)) {
            $licenseDef = Utils::getLicenseSetting($licenseType);
            $fields = $licenseDef->basicStatisticsFilterFields ?? [];
        } else {
            $fields = [

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
                    "label" => "Register Type",
                    "name" => "register_type",
                    "type" => "select",
                    "hint" => "",
                    "options" => [
                        [
                            "key" => "Provisional",
                            "value" => "Provisional"
                        ],
                        [
                            "key" => "Permanent",
                            "value" => "Permanent"
                        ],
                        [
                            "key" => "Temporary",
                            "value" => "Temporary"
                        ]
                    ],
                    "value" => "",
                    "required" => true
                ]
            ];
        }
        $defaultFields = [];
        return [
            "default" => $defaultFields,
            "custom" => $fields
        ];
    }
}


