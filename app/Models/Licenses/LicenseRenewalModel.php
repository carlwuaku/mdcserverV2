<?php

namespace App\Models\Licenses;

use CodeIgniter\Model;
use App\Helpers\Interfaces\TableDisplayInterface;
use App\Helpers\Interfaces\FormInterface;
use App\Helpers\Utils;
use CodeIgniter\Database\BaseBuilder;
use App\Models\MyBaseModel;
use App\Models\Licenses\LicensesModel;


class LicenseRenewalModel extends MyBaseModel implements TableDisplayInterface, FormInterface
{
    protected $table = 'license_renewal';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = false;
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
        'picture',
        'license_type',
        'license_uuid',
        'print_template',
        'online_print_template',
        'in_print_queue',
        'phone',
        'region',
        'district',
        'email',
        'name',
        'country_of_practice'
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
        'license_type',
        'license_number',
        'receipt',
        'batch_number',
        'payment_invoice_number',
        'email',
        'name',
        'phone'
    ];



    public function getDisplayColumns(): array
    {
        //get the fields for the selected type and merge with the default fields
        $defaultColumns = [
            'picture',
            'name',
            'license_number',
            'region',
            'district',
            'start_date',
            'expiry',
            'status',
            'created_by',
            'created_on',
            'receipt',
            'qr_code',
            'batch_number',
            'payment_date',
            'payment_file',
            'payment_file_date',
            'payment_invoice_number',
            'approve_online_certificate',
            'online_certificate_start_date',
            'online_certificate_end_date',
            'print_template',
            'online_print_template',
            'in_print_queue'
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
            $fields = $licenseDef['renewalDisplayFields'];
            return $fields;
        }
        return $defaultColumns;
    }

    public function getDisplayColumnLabels(): array
    {
        return [];
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
                "label" => "Start Date",
                "name" => "start_date",
                "type" => "date-range",
                "hint" => "",
                "options" => "",
                "value" => "",
                "required" => false
            ],
            [
                "label" => "End Date",
                "name" => "expiry",
                "type" => "date-range",
                "hint" => "",
                "options" => [],
                "value" => "",
                "required" => false
            ],
            [
                "label" => "Created On",
                "name" => "created_on",
                "type" => "date-range",
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
            [
                "label" => "Batch number",
                "name" => "batch_number",
                "type" => "select",
                "hint" => "",
                "options" => $this->getDistinctValuesAsKeyValuePairs('batch_number'),
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
            $fields = $licenseDef['renewalFilterFields'];
            //prepend renewal_ to the names of the fields to differentiate them from the license fields
            $fields = array_map(function ($field) {
                $field['name'] = 'renewal_' . $field['name'];
                return $field;
            }, $fields);

            return array_merge($default, $fields);
        }
        return $default;
    }



    /**
     * gets the table name for the renewal sub table from app.settings.json
     * @param string $licenseType the license type
     * @return string the table name
     */
    public function getChildRenewalTable(string $licenseType): string
    {
        $licenseDef = Utils::getLicenseSetting($licenseType);
        $renewalSubTable = $licenseDef->renewalTable;
        return $renewalSubTable;
    }


    /**
     * add license details to the builder
     * @param BaseBuilder $builder
     * @param string $licenseType
     * @param bool $addLicenseJoin in some cases a join may have been added already, particularly if there is a search operation
     * @param bool $addRenewalJoin in some cases a join may have been added already, particularly if there is a search operation
     * @param string $licenseJoinConditions if there are any additional join conditions for the license table
     * @param string $renewalJoinConditions if there are any additional join conditions for the renewal table
     * @param bool $addSelectClause in some cases we may not want to add the select clause, for example when getting a gazette as they are not needed
     * @return BaseBuilder
     */
    public function addLicenseDetails(BaseBuilder $builder, string $licenseType, bool $addLicenseJoin = true, bool $addRenewalJoin = true, string $licenseJoinConditions = '', string $renewalJoinConditions = '', bool $addSelectClause = true): BaseBuilder
    {
        try {
            $licenseDef = Utils::getLicenseSetting($licenseType);
            //we now have the data_snapshot field which holds all the data from the 
            //license type table at the time of renewal
            // $licenseTypeTable = $licenseDef->table;
            //get the sub table for that license type
            $renewalSubTable = $licenseDef->renewalTable;
            $renewalSubFields = $licenseDef->renewalFields;

            //in some cases we may not want to add the select clause, for example when getting a gazette as they are not needed
            if ($addSelectClause) {
                $extraColumns = [];

                for ($i = 0; $i < count($renewalSubFields); $i++) {
                    $extraColumns[] = $renewalSubTable . "." . $renewalSubFields[$i]['name'];
                }
                $builder->select(array_merge(["{$this->table}.*"], $extraColumns));
            }
            // $builder->join("licenses", $this->table . '.license_number = licenses.license_number', 'left');

            // $fullLicenseJoinConditions = $this->table . ".license_number = $licenseTypeTable.license_number ";
            // if ($licenseJoinConditions) {
            //     $builder->where($licenseJoinConditions);
            // }

            $fullRenewalJoinConditions = $this->table . ".id = $renewalSubTable.renewal_id ";
            if ($renewalJoinConditions) {
                $builder->where($renewalJoinConditions);
            }

            // in some cases we may not want to add the renewal join, for example when we are doing a search as it would have been added already
            // in the search method
            // if ($addLicenseJoin) {
            //     $builder->join($licenseTypeTable, $fullLicenseJoinConditions, 'left');
            // }
            // in some cases we may not want to add the renewal join, for example when we are doing a search as it would have been added already
            // in the search method
            if ($addRenewalJoin) {
                $builder->join($renewalSubTable, $fullRenewalJoinConditions, 'left');
            }
            return $builder;
        } catch (\Throwable $th) {
            log_message('error', $th->getMessage());
            return $builder;
        }
    }

    /**
     * insert or update renewal details. the table is obtained from the license type in app.settings.json
     * @param string $licenseType
     * @param array $formData an array from the merge of the incoming form and existing data
     * @return void
     */
    public function createOrUpdateSubDetails($renewalId, $licenseType, $formData)
    {
        try {
            $licenseDef = Utils::getLicenseSetting($licenseType);
            $table = $licenseDef->renewalTable;
            $licenseFields = $licenseDef->renewalFields;
            $implicitFields = $licenseDef->implicitRenewalFields;//these fields will be taken from the existing license data
            $data = [];
            // $protectedFields = [];
            //make sure $formdata is an array
            if (!is_array(value: $formData)) {
                $formData = (array) $formData;
            }

            foreach ($licenseFields as $field) {
                $name = $field['name'];
                if (array_key_exists($name, $formData)) {
                    $data[$name] = $formData[$name];
                }
            }
            foreach ($implicitFields as $field) {
                $data[$field] = $formData[$field];
            }
            $data['license_number'] = $formData['license_number'];
            $data['renewal_id'] = $renewalId;
            // $this->setAllowedFields(allowedFields: $protectedFields);
            // $this->setTable($table);
            $license = $this->builder($table)->where('renewal_id', $renewalId)->get()->getFirstRow('array');
            if ($license) {
                //in this case we're using fields not listed in the allowed fields so we need to use set method
                $this->builder($table)->where('renewal_id', $renewalId)->set($data)->update();
            } else {
                $db = \Config\Database::connect();
                $db->table($table)->set($data)->insert();
                // log_message('info', 'no license found for' . $renewalId);
                // $this->insert((object) $data);
                // log_message('info', $this->builder()->getCompiledInsert(false));
                // $this->builder()->insert();
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

    /**
     * gets a builder instance for the sub table associated with the given license type
     * @param string $type the license type
     * @return BaseBuilder the builder instance
     */
    public function getRenewalSubTableBuilder(string $type): BaseBuilder
    {
        $table = $this->getChildRenewalTable($type);
        $builder = $this->builder($table);
        return $builder;
    }

    public function getFormFields(): array
    {

        return [
            [
                "label" => "License Number",
                "name" => "license_number",
                "type" => "text",
                "hint" => "",
                "options" => [],
                "value" => "",
                "required" => true,
                "showOnly" => true
            ],

            [
                "label" => "Batch Number",
                "name" => "batch_number",
                "type" => "text",
                "hint" => "",
                "options" => [],
                "value" => "",
                "required" => false
            ]


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
            $fields = $licenseDef->renewalBasicStatisticsFields ?? [];
            $fields = array_map(function ($field) {
                return new BasicStatisticsField($field['label'], $field['name'], $field['type'], $field['xAxisLabel'], $field['yAxisLabel']);
            }, $fields);
        } else {
            $fields = [];
        }
        $defaultFields = [
            new BasicStatisticsField("Status", "status", "bar", "Status", "Number of licenses")
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
        $defaultFields = [
            [
                "label" => "Date Created",
                "name" => "created_on",
                "type" => "date-range",
                "hint" => "",
                "options" => [

                ],
                "value" => "",
                "required" => true
            ],
            [
                "label" => "Status",
                "name" => "status",
                "type" => "select",
                "hint" => "",
                "options" => $this->getDistinctValuesAsKeyValuePairs('status'),
                "value" => "",
                "required" => true
            ]
        ];
        return [
            "default" => $defaultFields,
            "custom" => $fields
        ];
    }
}


