<?php

namespace App\Services;

use App\Helpers\PaymentUtils;
use App\Helpers\TemplateEngineHelper;
use App\Helpers\Types\InvoicePaymentOptionType;
use App\Helpers\Types\PaymentInvoiceItemType;
use App\Helpers\Utils;
use App\Models\ActivitiesModel;
use App\Models\Payments\FeesModel;
use App\Models\Payments\InvoiceLineItemModel;
use App\Models\Payments\InvoiceModel;
use App\Models\Payments\InvoicePaymentOptionModel;
use App\Models\Payments\PaymentFileUploadsModel;
use App\Models\Payments\PaymentFileUploadsViewModel;
use App\Models\PrintTemplateModel;
use Exception;
use CodeIgniter\Events\Events;

/**
 * License Service - Handles all license-related business logic
 */
class PaymentsService
{

    private FeesModel $feesModel;
    private ActivitiesModel $activitiesModel;
    private InvoiceModel $invoiceModel;

    private InvoiceLineItemModel $invoiceLineItemModel;

    private InvoicePaymentOptionModel $invoicePaymentOptionModel;

    private PaymentFileUploadsModel $paymentFileUploadsModel;
    private PaymentFileUploadsViewModel $paymentFileUploadsViewModel;


    public function __construct()
    {
        $this->feesModel = new FeesModel();
        $this->activitiesModel = new ActivitiesModel();
        $this->invoiceModel = new InvoiceModel();
        $this->invoiceLineItemModel = new InvoiceLineItemModel();
        $this->invoicePaymentOptionModel = new InvoicePaymentOptionModel();
        $this->paymentFileUploadsModel = new PaymentFileUploadsModel();
        $this->paymentFileUploadsViewModel = new PaymentFileUploadsViewModel();
    }
    //create fee
    //update fee
    //delete fee
    //get fee by id
    //get all fees

    /**
     * Create a new fee record in the database.
     *
     * Validates the input data against the defined rules and inserts a new fee entry
     * into the database if the data is valid. Throws an exception if validation fails.
     *
     * @param array $data An associative array containing the fee data to be inserted.
     *                    Expected keys are 'name', 'rate', 'service_code', and 'supports_variable_amount'.
     * @return mixed The ID of the newly created fee record.
     * @throws \InvalidArgumentException if the validation of data fails.
     */

    public function createFee(array $data)
    {
        // Validate and process the data
        $rules = [
            "name" => "required|is_unique[fees.name]",
            "rate" => "numeric",
            "service_code" => "required",
            "currency" => "required"
        ];

        $validator = \Config\Services::validation();
        $validator->setRules($rules);
        if (!$validator->run($data)) {
            $message = implode(" ", array_values($validator->getErrors()));
            throw new \InvalidArgumentException("Validation failed: " . $message);
        }
        // Insert into the database
        $examData = $this->feesModel->createArrayFromAllowedFields($data);

        $id = $this->feesModel->insert($examData);

        // Return the exam ID
        return $id;
    }


    public function updateFee(string $id, array $data, array $letters = null): bool
    {
        // Validate and process the data
        $rules = [
            "title" => "permit_empty|is_unique[fees.name,id,{$id}]",
            "rate" => "permit_empty|numeric",
            "supports_variable_amount" => "permit_empty|in_list[yes,no]"
        ];

        $validator = \Config\Services::validation();
        $validator->setRules($rules);
        if (!$validator->run($data)) {
            $message = implode(" ", array_values($validator->getErrors()));
            throw new \InvalidArgumentException("Validation failed: " . $message);
        }

        // Get the old data first (before any updates)
        $oldData = $this->feesModel->where(['id' => $id])->first();
        if (!$oldData) {
            throw new \InvalidArgumentException("fee with ID {$id} not found");
        }

        $feeId = $oldData['id'];

        // Update the exam in the database
        $feeData = $this->feesModel->createArrayFromAllowedFields($data);


        unset($data['id']);
        $changes = implode(", ", Utils::compareObjects($oldData, $data));
        // Update main exam data
        $this->feesModel->builder()->where(['id' => $id])->update($feeData);

        $this->activitiesModel->logActivity("Updated fee {$oldData['name']}. Changes: $changes");
        return true;
    }

    public function deleteFee(int $id): bool
    {
        // Validate the UUID
        $data = $this->feesModel->where(["id" => $id])->first();

        if (!$data) {
            throw new \RuntimeException("Record not found");
        }

        if (!$this->feesModel->where('id', $id)->delete()) {
            throw new \RuntimeException('Failed to delete fee: ' . json_encode($this->feesModel->errors()));
        }

        // Log activity
        $this->activitiesModel->logActivity("Deleted fee {$data['name']}.");

        return true;
    }

    public function getFeeDetails(string $id): ?array
    {
        $data = $this->feesModel->where(["id" => $id])->first();


        if (!$data) {
            throw new \InvalidArgumentException("Record not found");
        }

        return [
            'data' => $data,
            'displayColumns' => $this->feesModel->getDisplayColumns()
        ];
    }

    /**
     * Retrieves all fees with pagination and filtering
     *
     * @param array $filters A key-value array of filters and values
     * @return array An associative array with the following keys:
     *  - data: The paginated fees data
     *  - total: The total number of records
     *  - displayColumns: An array of column display names
     *  - columnFilters: An array of column filters
     */
    public function getAllFees(array $filters = []): array
    {
        $per_page = $filters['limit'] ?? 100;
        $page = $filters['page'] ?? 0;
        $param = $filters['param'] ?? $filters['child_param'] ?? null;
        $sortBy = $filters['sortBy'] ?? "id";
        $sortOrder = $filters['sortOrder'] ?? "desc";
        // Build query
        $builder = $param ? $this->feesModel->search($param) : $this->feesModel->builder();
        $tableName = $this->feesModel->getTableName();
        $builder->orderBy("$tableName.$sortBy", $sortOrder);
        // Apply filters
        $filterArray = $this->feesModel->createArrayFromAllowedFields($filters);

        array_map(function ($value, $key) use ($builder, $tableName) {
            $value = Utils::parseParam($value);
            $columnName = $tableName . "." . $key;
            $builder = Utils::parseWhereClause($builder, $columnName, $value);
        }, $filterArray, array_keys($filterArray));

        $total = $builder->countAllResults(false);
        $result = $builder->get($per_page, $page)->getResult();

        $displayColumns = $this->feesModel->getDisplayColumns();

        return [
            'data' => $result,
            'total' => $total,
            'displayColumns' => $displayColumns,
            'columnFilters' => $this->feesModel->getDisplayColumnFilters()
        ];
    }

    /**
     * Create an invoice for a given list of items. This will create an invoice entry in the database and
     * will create invoice line items for each item in the list.
     * @param array $data Associative array of data to be inserted in the invoice table.
     *                    The required keys are "unique_id", "purpose" and "due_date".
     *                    The "unique_id" is the license number of the payer.
     *                    The "purpose" is the purpose of the payment and the "due_date" is the due date of the payment.
     * @param PaymentInvoiceItemType[] $items List of items to be added as invoice line items. Each item should be an object of the PaymentInvoiceItem.
     * @param InvoicePaymentOptionType[] $paymentOptions List of payment options .
     * @return int The ID of the created invoice.
     * @throws \InvalidArgumentException If the validation of the data fails.
     * @throws \RuntimeException If the database insertion fails.
     */
    public function createInvoice(array $data, array $items, array $paymentOptions)
    {
        // Validate and process the data
        $rules = [
            "unique_id" => "required|is_unique[invoices.invoice_number]",
            "purpose" => "required",
            "due_date" => "required|valid_date",
            "last_name" => "required",
            "email" => "required|valid_email",
            "phone_number" => "required|numeric"
        ];

        $validator = \Config\Services::validation();
        $validator->setRules($rules);
        if (!$validator->run($data)) {
            $message = implode(" ", array_values($validator->getErrors()));
            throw new \InvalidArgumentException("Validation failed: " . $message);
        }
        //get the name and details of the payer
        $unique_id = $data['unique_id'];
        // try {
        //     $payerDetails = LicenseUtils::getLicenseDetails($unique_id);
        //     $data['name'] = $payerDetails['name'];
        //     $data['email'] = $payerDetails['email'];
        //     $data['phone_number'] = $payerDetails['phone'];
        // } catch (Exception $e) {
        //     //if it's not a valid license. for now we don't want to allow payments for non-licensed users
        //     //if in future we want to allow that, handle the logic for that here. perhaps this can
        //     //come before the validation so that we add rules for names and emails if the license is not valid
        //     throw new \InvalidArgumentException("Invalid license number");
        // }
        $applicationId = $this->invoiceModel->generateInvoiceApplicationId($unique_id);//this is our internal invoice number. if it's an online payment, the payment provider will provide us with their invoice number. this will become the invoice number field value
        $data['application_id'] = $applicationId;
        $data['amount'] = 0;//this will be updated with the database triggers on the invoice_line_items table
        $data['status'] = 'Pending';

        // Insert into the database
        $invoiceData = $this->invoiceModel->createArrayFromAllowedFields($data);
        $this->invoiceModel->db->transException(true)->transStart();
        $invoiceId = $this->invoiceModel->insert($invoiceData);
        if (!$invoiceId) {
            throw new \RuntimeException("Failed to create invoice.");
        }
        //get the invoice uuid
        /**
         * @var array
         */
        $invoice = $this->invoiceModel->find($invoiceId);
        $invoiceUuid = $invoice['uuid'];
        // Create invoice line items
        foreach ($items as $item) {
            $this->createInvoiceItem($invoiceUuid, $item);
        }

        // Create invoice payment options
        foreach ($paymentOptions as $option) {
            $this->createInvoicePaymentOption($invoiceUuid, $option);
        }

        $this->invoiceModel->db->transComplete();
        $this->activitiesModel->logActivity("created invoice $invoiceUuid for $unique_id", null, "Payments");
        Events::trigger(EVENT_INVOICE_CREATED, $invoice, $items);
        // Return the exam ID
        return $invoiceId;
    }

    /**
     * Update multiple invoices in bulk. the updatable fields are status, and due_date
     * @param array{uuid: string, status: ?string, due_date: ?string} $data The data to update the invoices with. this would be a list of key-value pairs
     * @return string
     * @throws \InvalidArgumentException If the validation of the data fails
     * @throws \RuntimeException If the database update fails
     */
    public function updateBulkInvoice(array $data)
    {

        if (count($data) == 0) {
            throw new \InvalidArgumentException("No data provided");
        }
        $validStatuses = implode(",", VALID_PAYMENT_INVOICE_STATUSES);
        // Validate and process the data
        $rules = [
            "uuid" => "required|is_not_unique[invoices.uuid]",
            "status" => "permit_empty|in_list[$validStatuses]",
            "due_date" => "permit_empty|valid_date"
        ];
        $updateData = [];
        $activityLogMessages = [];
        $validator = \Config\Services::validation();
        $validator->setRules($rules);
        for ($i = 0; $i < count($data); $i++) {
            $invoice = (array) $data[$i];
            if (!$validator->run($invoice)) {
                $message = implode(" ", array_values($validator->getErrors()));
                throw new \InvalidArgumentException("Validation failed : . $message");
            }

            $paymentData = [
                'uuid' => $invoice['uuid']
            ];
            if (isset($invoice['status'])) {
                $paymentData['status'] = $invoice['status'];
                $activityLogMessages[] = "Set status for  invoice {$invoice['uuid']} to {$invoice['status']}";
            }
            if (isset($invoice['due_date']) && $invoice['due_date'] != null) {
                $paymentData['due_date'] = $invoice['due_date'];
                $activityLogMessages[] = "Set due date for  invoice {$invoice['uuid']} to {$invoice['due_date']}";
            }

            $updateData[] = $paymentData;

        }
        $this->invoiceModel->db->transException(true)->transStart();
        $numRows = $this->invoiceModel->updateBatch($updateData, 'id', count($updateData));
        $this->invoiceModel->db->transComplete();
        try {
            $this->activitiesModel->logActivity($activityLogMessages, null, "Payment");
        } catch (\Throwable $th) {
            log_message("error", $th);
        }


        return "Updated $numRows invoices successfully";
    }

    public function getInvoices(array $filters = []): array
    {
        $per_page = $filters['limit'] ?? 100;
        $page = $filters['page'] ?? 0;
        $param = $filters['param'] ?? $filters['child_param'] ?? null;
        $sortBy = $filters['sortBy'] ?? "id";
        $sortOrder = $filters['sortOrder'] ?? "desc";
        // Build query
        $builder = $param ? $this->invoiceModel->search($param) : $this->invoiceModel->builder();
        $tableName = $this->invoiceModel->getTableName();
        $builder->orderBy("$tableName.$sortBy", $sortOrder);
        // Apply filters
        $filterArray = $this->invoiceModel->createArrayFromAllowedFields($filters);

        array_map(function ($value, $key) use ($builder, $tableName) {
            $value = Utils::parseParam($value);
            $columnName = $tableName . "." . $key;
            $builder = Utils::parseWhereClause($builder, $columnName, $value);
        }, $filterArray, array_keys($filterArray));

        $total = $builder->countAllResults(false);
        $result = $builder->get($per_page, $page)->getResult();

        $displayColumns = $this->invoiceModel->getDisplayColumns();

        return [
            'data' => $result,
            'total' => $total,
            'displayColumns' => $displayColumns,
            'columnFilters' => $this->invoiceModel->getDisplayColumnFilters()
        ];
    }

    public function getInvoice($uuid): array
    {
        $result = $this->invoiceModel->where('uuid', $uuid)->first();

        if (!$result) {
            throw new \InvalidArgumentException("Invoice not found");
        }
        $result['items'] = $this->invoiceLineItemModel->where('invoice_uuid', $uuid)->get()->getResult();

        return [
            'data' => $result,
            'message' => "Invoice found",
        ];
    }

    /**
     * Delete an invoice
     * @param string $uuid The uuid of the invoice to delete
     * @return bool If the invoice was deleted successfully
     * @throws \InvalidArgumentException If the invoice does not exist
     * @throws \RuntimeException If the delete fails
     */
    public function deleteInvoice(string $uuid): bool
    {
        // Validate the UUID
        $data = $this->invoiceModel->where(["uuid" => $uuid])->first();

        if (!$data) {
            throw new \InvalidArgumentException("Record not found");
        }

        if (!$this->invoiceModel->where('uuid', $uuid)->delete()) {
            throw new \RuntimeException('Failed to delete invoice: ' . json_encode($this->feesModel->errors()));
        }

        // Log activity
        $this->activitiesModel->logActivity("Deleted invoice {$data['application_id']} for {$data['name']} {$data['unique_id']}.");

        return true;
    }

    /**
     * Gets the default fees for a given invoice purpose and uuid.
     * @param string $purpose The purpose of the invoice
     * @param string $uuid The uuid of the license
     * @return \App\Helpers\Types\PaymentInvoiceItemType[] The default fees for the given purpose and uuid
     */
    public function getInvoiceDefaultFees(string $purpose, string $uuid)
    {
        $details = Utils::getUuidDetailsForPayment($purpose, $uuid);
        return PaymentUtils::getDefaultFees($purpose, $details);
    }

    /**
     * Gets the default fees for multiple invoices based on their purposes and uuids.
     *
     * @param string $purpose The purpose of the invoices
     * @param array $uuids An array of uuids for which to retrieve the default fees
     * @return array An associative array where the key is the uuid and the value is the default fees for that uuid
     */

    public function getInoviceDefaultFeesMultipleUuids(string $purpose, array $uuids)
    {
        $data = [];
        foreach ($uuids as $uuid) {
            $data[$uuid] = $this->getInvoiceDefaultFees($purpose, $uuid);
        }

        return $data;
    }

    /**
     * Create an invoice line item
     * @param string $invoiceUuid The UUID of the invoice
     * @param PaymentInvoiceItemType $item The payment invoice item
     * @return int The ID of the created invoice line item
     * @throws \InvalidArgumentException If the validation fails
     * @throws \RuntimeException If the insert fails
     */
    private function createInvoiceItem(string $invoiceUuid, PaymentInvoiceItemType $item): int
    {
        // Validate and process the data
        $rules = [
            "service_code" => "required|is_not_unique[fees.service_code]",
            "quantity" => "required|numeric"
        ];

        $validator = \Config\Services::validation();
        $validator->setRules($rules);
        if (!$validator->run($item->toArray())) {
            $message = implode(" ", array_values($validator->getErrors()));
            throw new \InvalidArgumentException("Validation failed: " . $message);
        }
        //get the fee from the service code
        $fee = $this->feesModel->where(['service_code' => $item->service_code])->first();
        if (!$fee) {
            throw new \InvalidArgumentException("Fee with service code " . $item->service_code . " not found");
        }
        $rate = $fee['rate'];
        $data = [
            'invoice_uuid' => $invoiceUuid,
            'service_code' => $item->service_code,
            'description' => $fee['name'],
            'quantity' => $item->quantity,
            'unit_price' => $rate,
            'line_total' => $rate * $item->quantity
        ];

        // Insert into the database
        $invoiceLineItemData = $this->invoiceLineItemModel->createArrayFromAllowedFields($data);
        $invoiceLineItemId = $this->invoiceLineItemModel->insert($invoiceLineItemData);
        if (!$invoiceLineItemId) {
            throw new \RuntimeException("Failed to create invoice line item.");
        }
        return $invoiceLineItemId;
    }

    private function createInvoicePaymentOption(string $invoiceUuid, InvoicePaymentOptionType $item)
    {
        // Validate and process the data
        $paymentMethods = implode(",", PaymentUtils::getPaymentMethodsList());
        $rules = [
            "method_name" => "required|in_list[$paymentMethods]"
        ];

        $validator = \Config\Services::validation();
        $validator->setRules($rules);
        if (!$validator->run($item->toArray())) {
            $message = implode(" ", array_values($validator->getErrors()));
            throw new \InvalidArgumentException("Validation failed: " . $message);
        }

        $data = [
            'invoice_uuid' => $invoiceUuid,
            'method_name' => $item->methodName
        ];

        // Insert into the database
        $invoicePaymentData = $this->invoicePaymentOptionModel->createArrayFromAllowedFields($data);
        $invoicePaymentId = $this->invoicePaymentOptionModel->insert($invoicePaymentData);
        if (!$invoicePaymentId) {
            throw new \RuntimeException("Failed to create invoice payment option.");
        }
        return $invoicePaymentId;
    }

    public function generatePresetInovicesForMultipleUuids(string $purpose, array $uuids, $dueDate, array $additionalInvoiceItems)
    {
        try {
            $success = [];
            $fail = [];
            //get the settings for the purpose
            /**
             * @var array{defaultInvoiceItems: array {criteria: array {field:string, value:string[]}[], feeServiceCodes: array}[], paymentMethods: array, sourceTableName: string, description: string}
             */
            $purposes = Utils::getPaymentSettings()["purposes"];
            //get the default fees
            if (!isset($purposes[$purpose])) {
                throw new \InvalidArgumentException("Invalid payment purpose: $purpose");
            }
            $sourceTable = $purposes[$purpose]["sourceTableName"];
            $descriptionTemplate = $purposes[$purpose]["description"];
            $paymentMethods = $purposes[$purpose]["paymentMethods"];

            foreach ($uuids as $uuid) {
                $details = Utils::getUuidDetailsForPayment($purpose, $uuid);
                $templateEngineHelper = new TemplateEngineHelper();
                $description = $templateEngineHelper->process($descriptionTemplate, $details);
                try {

                    $defaultFees = $this->getInvoiceDefaultFees($purpose, $uuid);
                    $invoiceData = [
                        'first_name' => array_key_exists("first_name", $details) ? $details["first_name"] : $details["name"],
                        'last_name' => array_key_exists("last_name", $details) ? $details["last_name"] : $details["unique_id"],
                        'email' => $details["email"],
                        'phone_number' => $details["phone"],
                        'purpose_table' => $sourceTable,
                        'purpose_table_uuid' => $uuid,
                        'purpose' => $purpose,
                        'due_date' => $dueDate,
                        'unique_id' => $details["unique_id"],
                        'description' => $description
                    ];
                    $itemsArray = array_map(function ($item) {
                        $itemObj = new PaymentInvoiceItemType(0, '', '', 0, 0, 0);
                        return $itemObj->createFromRequest($item);
                    }, $additionalInvoiceItems);

                    $paymentOptionsArray = count($paymentMethods) > 0 ? array_map(function ($paymentMethod) {
                        $paymentOptionObj = new InvoicePaymentOptionType(0, $paymentMethod);
                        return $paymentOptionObj;
                    }, $paymentMethods) : [];

                    $invoiceItems = array_merge($defaultFees, $itemsArray);
                    $this->createInvoice($invoiceData, $invoiceItems, $paymentOptionsArray);
                    $success[] = $details["name"];
                } catch (\Throwable $th) {
                    log_message("error", $th);
                    $fail[] = $details["name"];
                    throw $th;
                }


            }

            return ["success" => $success, "fail" => $fail];
        } catch (Exception $e) {
            throw $e;
        }


    }

    public function uploadPaymentEvidence(string $uuid)
    {

    }

    /**
     * Submit an offline payment.
     * @param array $data The data to submit. Must contain the following keys:
     *  - uuid: The uuid of the invoice.
     *  - payment_file: The payment file.
     *  - payment_date: The payment date.
     *  - payment_file_date: The date of the payment file.
     * @return bool Whether the payment was successfully submitted.
     * @throws \InvalidArgumentException If the validation fails.
     */
    public function submitOfflinePayment(string $uuid, array $data)
    {
        try {

            // Validate and process the data
            $rules = [
                "payment_file" => "required",
                "payment_date" => "required|valid_date"
            ];

            $validator = \Config\Services::validation();
            $validator->setRules($rules);
            if (!$validator->run($data)) {
                $message = implode(" ", array_values($validator->getErrors()));
                throw new \InvalidArgumentException("Validation failed: " . $message);
            }
            //get the details of the invoice
            $invoiceDetails = $this->invoiceModel->where(["uuid" => $uuid])->first();
            if (!$invoiceDetails) {
                throw new \InvalidArgumentException("Invalid invoice uuid");
            }
            $unique_id = $invoiceDetails['unique_id'];
            if (!$unique_id) {
                throw new \InvalidArgumentException("Invalid invoice uuid");
            }
            $uuid = $invoiceDetails['uuid'];
            $data['status'] = 'Paid';
            $data['payment_file_date'] = date("Y-m-d");
            $data['payment_method'] = "In-Person";
            $paymentData = $this->invoiceModel->createArrayFromAllowedFields($data);
            $this->invoiceModel->db->transException(true)->transStart();
            //update the invoice
            $this->invoiceModel->builder()->where(['uuid' => $uuid])->update($paymentData);



            $this->invoiceModel->db->transComplete();
            $this->activitiesModel->logActivity("submitted payment for invoice $uuid for $unique_id", null, "Payments");
            Events::trigger(EVENT_INVOICE_PAYMENT_COMPLETED, $uuid);
            return true;
        } catch (\Throwable $th) {
            log_message("error", $th);
            throw $th;
        }
    }


    public function createPaymentFileUpload(array $data)
    {
        // Validate and process the data
        $rules = [
            "invoice_uuid" => "required|is_not_unique[invoices.uuid]",
            "file_path" => "required",
            "payment_date" => "required|valid_date"
        ];

        $validator = \Config\Services::validation();
        $validator->setRules($rules);
        if (!$validator->run($data)) {
            $message = implode(" ", array_values($validator->getErrors()));
            throw new \InvalidArgumentException("Validation failed: " . $message);
        }
        $paymentFileUpload = $this->invoiceModel->where(["uuid" => $data['invoice_uuid']])->first();

        //non-admins should only be able to upload payments for their own invoices
        $user = auth()->getUser();
        if (!$user->user_type === "admin") {
            //the usernames are the unique_id (license_number, application_code, etc) of the payers. these are also the usernames of the users
            if ($paymentFileUpload['unique_id'] !== $user->username) {
                throw new Exception("{$user->username} does not have permission to upload this payment file for an invoice belonging to user with unique id {$paymentFileUpload->unique_id}");
            }
        }
        // Insert into the database
        $examData = $this->paymentFileUploadsModel->createArrayFromAllowedFields($data);

        $id = $this->paymentFileUploadsModel->insert($examData);

        // Return the exam ID
        return $id;
    }

    public function approvePaymentFileUpload(array $data)
    {
        try {
            // Validate and process the data
            $rules = [
                "id" => "required|is_not_unique[payment_file_uploads.id]"
            ];
            $validator = \Config\Services::validation();
            $validator->setRules($rules);
            if (!$validator->run($data)) {
                $message = implode(" ", array_values($validator->getErrors()));
                throw new \InvalidArgumentException("Validation failed: " . $message);
            }
            $paymentFileUpload = $this->paymentFileUploadsViewModel->builder()->where(["id" => $data['id']])->get()->getRow();
            if (!$paymentFileUpload) {
                throw new \InvalidArgumentException("Record not found");
            }
            // update the status
            $updateData = [
                "status" => "Approved",
            ];
            $this->paymentFileUploadsModel->builder()->where(["id" => $data['id']])->update($updateData);

            //update the invoice
            $invoiceData = [
                "payment_file" => $paymentFileUpload->file_path,
                "payment_date" => $paymentFileUpload->payment_date,
            ];
            $this->submitOfflinePayment($paymentFileUpload->invoice_uuid, $invoiceData);
        } catch (\Throwable $th) {
            throw $th;
        }




    }

    public function deletePaymentFileUpload(string $id): bool
    {
        $paymentFileUpload = $this->paymentFileUploadsViewModel->where(["id" => $id])->get()->getRow();
        if (!$paymentFileUpload) {
            throw new \InvalidArgumentException("Record not found");
        }
        //if the user is not an admin, they can only delete their own payment file uploads
        $user = auth()->getUser();
        if (!$user->user_type === "admin") {
            //the usernames are the unique_id (license_number, application_code, etc) of the payers. these are also the usernames of the users
            if ($paymentFileUpload->unique_id !== $user->username) {
                throw new Exception("{$user->username} does not have permission to delete this payment file upload with uinque id {$paymentFileUpload->unique_id}");
            }
        }

        if (!$this->paymentFileUploadsModel->where('id', $id)->delete()) {
            throw new \RuntimeException('Failed to delete payment file upload: ' . json_encode($this->paymentFileUploadsModel->errors()));
        }

        // Log activity
        $this->activitiesModel->logActivity("Deleted payment file upload for {$paymentFileUpload->unique_id} for payment {$paymentFileUpload->invoice_uuid} ");

        return true;
    }


    public function getPaymentFileUploads(array $filters = []): array
    {
        $per_page = $filters['limit'] ?? 100;
        $page = $filters['page'] ?? 0;
        $param = $filters['param'] ?? $filters['child_param'] ?? null;
        $sortBy = $filters['sortBy'] ?? "id";
        $sortOrder = $filters['sortOrder'] ?? "desc";
        $user = auth()->getUser();
        //admins should be able to see all payment file uploads. non-admins should only be able to see their own 
        if ($user->user_type === "admin") {
            // Build query
            $builder = $param ? $this->paymentFileUploadsViewModel->search($param) : $this->paymentFileUploadsViewModel->builder();
            $tableName = $this->paymentFileUploadsViewModel->getTableName();
            $builder->orderBy("$tableName.$sortBy", $sortOrder);
            // Apply filters
            $filterArray = $this->paymentFileUploadsViewModel->createArrayFromAllowedFields($filters);

            array_map(function ($value, $key) use ($builder, $tableName) {
                $value = Utils::parseParam($value);
                $columnName = $tableName . "." . $key;
                $builder = Utils::parseWhereClause($builder, $columnName, $value);
            }, $filterArray, array_keys($filterArray));

        } else {
            $uniqueId = $user->username;
            $builder = $this->paymentFileUploadsViewModel->builder();
            $tableName = $this->paymentFileUploadsViewModel->getTableName();
            $builder->orderBy("$tableName.$sortBy", $sortOrder);
            $builder = Utils::parseWhereClause($builder, "unique_id", $uniqueId);
        }


        $total = $builder->countAllResults(false);
        $result = $builder->get($per_page, $page)->getResult();

        $displayColumns = $this->paymentFileUploadsModel->getDisplayColumns();

        return [
            'data' => $result,
            'total' => $total,
            'displayColumns' => $displayColumns,
            'columnFilters' => $this->paymentFileUploadsModel->getDisplayColumnFilters()
        ];
    }

    /**
     * Generates a printout for the given invoices based on the given template.
     * @param array $uuids The uuids of the invoices to generate a printout for
     * @param string $templateName The name of the template to use
     * @return array The generated printouts
     * @throws \InvalidArgumentException If the template is not found
     */
    public function generateInvoicePrintouts(array $uuids, string $templateName)
    {
        //get the invoice details along with their items
        /**
         * @var array
         */
        $invoices = $this->invoiceModel->whereIn("uuid", $uuids)->get()->getResult();
        /**
         * @var array
         */
        $items = $this->invoiceLineItemModel->whereIn("invoice_uuid", $uuids)->get()->getResult();
        $printTemplateModel = new PrintTemplateModel();

        $template = $printTemplateModel->where(["template_name" => $templateName])->get()->getRow();
        if (!$template) {
            //TODO: consider creating a default template
            throw new \InvalidArgumentException("Print template not found");
            //
        }
        $results = [];
        foreach ($invoices as $invoice) {
            $invoice->items = array_filter($items, function ($item) use ($invoice) {
                return $item->invoice_uuid === $invoice->uuid;
            });
            $templateEngineHelper = new TemplateEngineHelper();
            $invoiceTemplate = $templateEngineHelper->process($template->template_content, $invoice);
            $results[] = '<div class="page-break"> ' . $invoiceTemplate . '</div>';

        }

        return $results;

    }
}