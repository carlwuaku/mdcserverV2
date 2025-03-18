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
        'license_uuid'
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
    ];



    public function getDisplayColumns(): array
    {
        //get the fields for the selected type and merge with the default fields
        $defaultColumns = [
            'picture',
            'license_number',
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

            $licenseTypeFields = $licenseDef['fields'];

            $columns = array_map(function ($field) {
                return $field['name'];
            }, array_merge($fields, $licenseTypeFields));
            return Utils::reorderPriorityColumns(array_merge($defaultColumns, $columns, ['deleted_at']));
        }
        return Utils::reorderPriorityColumns($defaultColumns);
    }

    public function getDisplayColumnLabels(): array
    {
        return [];
    }







    public function getDisplayColumnFilters(): array
    {

        $default = [

            // [
            //     "label" => "License Type",
            //     "name" => "license_type",
            //     "type" => "select",
            //     "hint" => "",
            //     "options" => $this->getDistinctValuesAsKeyValuePairs('license_type'),
            //     "value" => "",
            //     "required" => false
            // ],
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
            // [
            //     "label" => "Region",
            //     "name" => "region",
            //     "type" => "select",
            //     "hint" => "",
            //     "options" => $this->getDistinctValuesAsKeyValuePairs('region'),
            //     "value" => "",
            //     "required" => false
            // ],
            // [
            //     "label" => "District",
            //     "name" => "district",
            //     "type" => "select",
            //     "hint" => "",
            //     "options" => $this->getDistinctValuesAsKeyValuePairs('district'),
            //     "value" => "",
            //     "required" => false
            // ],
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
            $fields = $licenseDef['renewalFields'];
            //prepend renewal_ to the names of the fields to differentiate them from the license fields
            $fields = array_map(function ($field) {
                $field['name'] = 'renewal_' . $field['name'];
                return $field;
            }, $fields);

            return array_merge($default, $fields);
        }
        return $default;
    }



    public function getChildRenewalTable(string $licenseType): string
    {
        $licenseDef = Utils::getLicenseSetting($licenseType);
        $renewalSubTable = $licenseDef->renewalTable;
        return $renewalSubTable;
    }


    public function addLicenseDetails(BaseBuilder $builder, string $licenseType, string $licenseJoinConditions = '', string $renewalJoinConditions = ''): BaseBuilder
    {
        try {
            $licenseDef = Utils::getLicenseSetting($licenseType);
            $licenseTypeFields = $licenseDef->fields;
            $licenseTypeTable = $licenseDef->table;
            //get the sub table for that license type
            $renewalSubTable = $licenseDef->renewalTable;
            $renewalSubFields = $licenseDef->renewalFields;
            $extraColumns = [];
            for ($i = 0; $i < count($licenseTypeFields); $i++) {
                $extraColumns[] = $licenseTypeTable . "." . $licenseTypeFields[$i]['name'];
            }
            for ($i = 0; $i < count($renewalSubFields); $i++) {
                $extraColumns[] = $renewalSubTable . "." . $renewalSubFields[$i]['name'];
            }
            $builder->select(array_merge(["{$this->table}.*"], $extraColumns, ["licenses.region", "licenses.district", "licenses.phone", "licenses.email", "licenses.postal_address"]));
            $builder->join("licenses", $this->table . '.license_number = licenses.license_number');

            $fullLicenseJoinConditions = $this->table . ".license_number = $licenseTypeTable.license_number ";
            if ($licenseJoinConditions) {
                $fullLicenseJoinConditions .= ' AND ' . $licenseJoinConditions;
            }

            $fullRenewalJoinConditions = $this->table . ".license_number = $renewalSubTable.license_number ";
            if ($renewalJoinConditions) {
                $fullRenewalJoinConditions .= ' AND ' . $renewalJoinConditions;
            }
            $builder->join($licenseTypeTable, $fullLicenseJoinConditions);
            $builder->join($renewalSubTable, $fullRenewalJoinConditions);
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
    public function createOrUpdateSubDetails($renewalId, $licenseType, $formData)
    {
        try {
            $licenseDef = Utils::getLicenseSetting($licenseType);
            $table = $licenseDef->renewalTable;
            $licenseFields = $licenseDef->renewalFields;
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
            $data['license_number'] = $formData['license_number'];
            $data['renewal_id'] = $renewalId;
            // $this->setAllowedFields(allowedFields: $protectedFields);
            // $this->setTable($table);
            $license = $this->builder($table)->where('renewal_id', $renewalId)->get()->getFirstRow('array');
            log_message('info', print_r($data, true));
            if ($license) {
                log_message('info', 'license found for' . $renewalId);
                //in this case we're using fields not listed in the allowed fields so we need to use set method
                $this->builder($table)->where('renewal_id', $renewalId)->set($data)->update();
            } else {
                log_message('info', 'no license found for' . $renewalId);
                $db = \Config\Database::connect();
                $db->table($table)->set($data)->insert();
                log_message('info', 'inserted subdetails');
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


    public function getFormFields(): array
    {
        /**
         * 'receipt',
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
         */
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
                "label" => "Batch Number",
                "name" => "batch_number",
                "type" => "text",
                "hint" => "",
                "options" => [],
                "value" => "",
                "required" => false
            ],
            [
                "label" => "Payment Date",
                "name" => "payment_date",
                "type" => "date",
                "hint" => "",
                "options" => [],
                "value" => "",
                "required" => false
            ],
            [
                "label" => "Payment File",
                "name" => "payment_file",
                "type" => "file",
                "hint" => "",
                "options" => [],
                "value" => "",
                "required" => false
            ],
            [
                "label" => "Payment File Date",
                "name" => "payment_file_date",
                "type" => "date",
                "hint" => "",
                "options" => [],
                "value" => "",
                "required" => false
            ],
            [
                "label" => "Payment Invoice Number",
                "name" => "payment_invoice_number",
                "type" => "text",
                "hint" => "",
                "options" => [],
                "value" => "",
                "required" => false
            ],
            [
                "label" => "Approve Online Certificate",
                "name" => "approve_online_certificate",
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
                "label" => "Online Certificate Start Date",
                "name" => "online_certificate_start_date",
                "type" => "date",
                "hint" => "",
                "options" => [],
                "value" => "",
            ],
            [
                "label" => "Online Certificate End Date",
                "name" => "online_certificate_end_date",
                "type" => "date",
                "hint" => "",
                "options" => [],
                "value" => "",
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

        ];
    }
}
