<?php
/**
 * this helper class contains methods to help with the creation of submitted application forms
 */
namespace App\Helpers;

use CodeIgniter\Events\Events;
use stdClass;
use App\Helpers\Types\CriteriaType;
use App\Helpers\Types\ApplicationStageType;


class ApplicationFormActionHelper extends Utils
{
    /**
     * this method runs a provided action on the application form
     * @param ApplicationStageType $action
     * @param array $data
     * @return array
     */
    public static function runAction(ApplicationStageType $action, array $data)
    {
        try {
            $result = [];
            //get the license types so that if it's an internal_api_call, we check if it's creating or updating a license
            switch ($action->config_type) {
                case 'email':
                    $result = self::sendEmailToApplicant($action, $data);
                    break;
                case 'admin_email':
                    $result = self::sendEmailToAdmin($action, $data);
                    break;
                case 'payment':
                    $result = self::runPayment($action, $data);
                    break;
                case 'portal_edit':
                    $result = self::runPortalEdit($action, $data);
                    break;
                case 'api_call':
                    $result = self::callApi($action, $data);
                    break;
                case 'internal_api_call':
                    $result = self::runInternalApiCall($action, $data);
                    break;
                default:
                    $result = $data;
            }
            Events::trigger(EVENT_APPLICATION_FORM_ACTION_COMPLETED, $action, $data, $result);
            return $result;
        } catch (\Throwable $th) {
            log_message('error', 'Error running action: ' . $th->getMessage());
            //log this to the actions database
            throw $th;
        }

    }

    public static function runPortalEdit(ApplicationStageType $action, array $data)
    {
        if ($action->type == 'edit_personal_details') {
            return self::updateLicenseDetails($action, $data);

        } else {
            throw new \InvalidArgumentException('Unsupported portal edit action type: ' . $action->type);
        }
    }

    /**
     * Run a payment action
     * If the action type is 'create_invoice', generate an invoice for the application
     * If the action has criteria, check if the data matches the criteria. If it does, add the action to the list of actions to run.
     * @param ApplicationStageType $action The payment action to run
     * @param array $data The data to use when running the action
     * @return array{invoiceData: array, invoiceItems: array, paymentOptions: array} The result of running the action
     */
    private static function runPayment(ApplicationStageType $action, array $data)
    {
        //convert to criteriatype array
        $criteria = array_map(function ($criterion) {
            return CriteriaType::fromArray($criterion);
        }, $action->criteria);

        if (!CriteriaType::matchesCriteria($data, $criteria)) {
            return [];
        }

        if ($action->type == 'create_invoice') {
            return self::generateInvoice($action, $data);
        } elseif ($action->type == 'create_custom_invoice') {
            return self::generateCustomInvoice($action, $data);
        } else {
            throw new \InvalidArgumentException('Unsupported payment action type: ' . $action->type);
        }
    }

    private static function runInternalApiCall($action, $data)
    {
        $licenseTypesSettings = self::getAppSettings("licenseTypes");
        // Check if the action is for creating or updating a license
        //the type of the action can be create_xxx where xxx is the type of the license. if it's a create, get whatever follows create_ and check if it's a license type
        if (strpos($action->type, 'create_') === 0) {
            //get the part after create_
            $licenseType = substr($action->type, strlen('create_'));
            // Check if the license type is in the configured license types
            if (in_array($licenseType, array_keys($licenseTypesSettings))) {
                // If it's a license type, create a license
                return self::createLicense($action, $data);
            } else {
                //handle other types of internal API calls
                log_message('info', 'Handling internal API call for non-license type: ' . $licenseType);
                throw new \InvalidArgumentException('Unsupported internal API call type: ' . $action->type);
            }
        }
        // Check if the action is for updating a license or renewal_status or renewal
        if (strpos($action->type, 'update_') === 0) {
            //get the part after update_
            $licenseType = substr($action->type, strlen('update_'));
            //if it's 'renewal_status'
            if ($licenseType === 'renewal_status') {
                return self::updateRenewalStatus($action, $data);
            }
            // Check if the license type is in the configured license types

            if (in_array($licenseType, array_keys($licenseTypesSettings))) {
                // If it's a license type, update a license
                return self::updateLicense($action, $data);
            }
            //handle other types of internal API calls
            log_message('info', 'Handling internal API call for non-license type: ' . $licenseType);
            throw new \InvalidArgumentException('Unsupported internal API call type: ' . $action->type);

        }

        // If not a license action, throw an exception
        throw new \InvalidArgumentException('Unsupported internal API call type: ' . $action->type);
    }

    /**
     * this method sends an email to the applicant
     * @param object{type:string, config:object {template:string, subject:string}} $action
     * @param array $data
     * @return array
     */
    private static function sendEmailToApplicant($action, $data)
    {
        $templateModel = new TemplateEngineHelper();
        $content = $templateModel->process($action->config['template'], $data);
        $subject = $templateModel->process($action->config['subject'], $data);
        $emailConfig = new EmailConfig($content, $subject, $data['email']);

        EmailHelper::sendEmail($emailConfig);
        return $data;
    }

    /**
     * this method sends an email to the admin
     * @param object{type:string, config:object {template:string, subject:string, admin_email:string}} $action
     * @param array $data
     * @return array
     */
    private static function sendEmailToAdmin($action, $data)
    {
        $templateModel = new TemplateEngineHelper();
        $content = $templateModel->process($action->config['template'], $data);
        $subject = $templateModel->process($action->config['subject'], $data);
        $emailConfig = new EmailConfig($content, $subject, $action->config['admin_email']);

        EmailHelper::sendEmail($emailConfig);
        return $data;
    }

    private static function generateInvoice(ApplicationStageType $action, array $data)
    {
        try {
            $paymentsService = \Config\Services::paymentsService();

            $purpose = array_key_exists('paymentPurpose', $action->config) ? $action->config['paymentPurpose'] : $action->config['payment_purpose'];
            /**
             * @var string
             */
            $uuid = $data['uuid']; //comma separated uuids
            $additionalItems = [];//TODO: should there be additional items?
            //this is by default a year from today
            $dueDate = date("Y-m-d", strtotime("+1 year"));
            return $paymentsService->generatePresetInvoiceForSingleUuid($purpose, $uuid, $dueDate, $additionalItems);
        } catch (\Exception $e) {
            log_message('error', 'Error generating invoice: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Generate a custom invoice with items defined in the action config.
     * Required form data fields: email, phone_number (or phone), last_name, unique_id (or registration_number)
     * Optional form data fields: first_name, description
     *
     * Action config should contain:
     * - invoice_items: array of {service_code: string, quantity: number}
     * - payment_methods: array of payment method names (optional, defaults to app settings)
     * - description: invoice description template (optional, supports {{field}} placeholders)
     * - due_date_days: number of days from now for due date (optional, defaults to 30)
     *
     * @param ApplicationStageType $action
     * @param array $data Form data
     * @return array{invoiceData: array, invoiceItems: array, paymentOptions: array}
     */
    private static function generateCustomInvoice(ApplicationStageType $action, array $data)
    {
        try {
            $paymentsService = \Config\Services::paymentsService();
            $feesModel = new \App\Models\Payments\FeesModel();

            // Extract payer details from form data
            $unique_id = $data['unique_id'] ?? $data['registration_number'] ?? null;
            $email = $data['email'] ?? null;
            $phone_number = $data['phone_number'] ?? $data['phone'] ?? null;
            $last_name = $data['last_name'] ?? $data['surname'] ?? null;
            $first_name = $data['first_name'] ?? $data['first_names'] ?? '';

            if (!$unique_id || !$email || !$phone_number || !$last_name) {
                throw new \InvalidArgumentException('Missing required fields: unique_id, email, phone_number, and last_name are required');
            }

            // Get invoice items from config
            $invoiceItemsConfig = $action->config['invoice_items'] ?? [];
            if (empty($invoiceItemsConfig)) {
                throw new \InvalidArgumentException('No invoice items defined in action config');
            }

            // Build invoice items from service codes
            $invoiceItems = [];
            foreach ($invoiceItemsConfig as $itemConfig) {
                $serviceCode = $itemConfig['service_code'] ?? null;
                $quantity = $itemConfig['quantity'] ?? 1;

                if (!$serviceCode) {
                    continue;
                }

                // Look up fee by service code
                $fee = $feesModel->where('service_code', $serviceCode)->first();
                if (!$fee) {
                    log_message('warning', "Fee not found for service code: $serviceCode");
                    continue;
                }

                $qty = (int) $quantity;
                $unitPrice = (float) $fee['rate'];
                $invoiceItems[] = new \App\Helpers\Types\PaymentInvoiceItemType(
                    invoice_uuid: null,
                    service_code: $serviceCode,
                    name: $fee['name'],
                    quantity: $qty,
                    unit_price: $unitPrice,
                    line_total: $qty * $unitPrice
                );
            }

            if (empty($invoiceItems)) {
                throw new \InvalidArgumentException('No valid invoice items could be created from config');
            }

            // Get payment methods from config or use defaults
            $paymentMethods = $action->config['payment_methods'] ?? null;
            if (!$paymentMethods) {
                $paymentSettings = self::getAppSettings('paymentSettings');
                $paymentMethods = array_keys($paymentSettings['paymentMethods'] ?? []);
            }

            $paymentOptions = array_map(function ($method) {
                return new \App\Helpers\Types\InvoicePaymentOptionType(
                    invoiceUuid: null,
                    methodName: $method
                );
            }, $paymentMethods);

            // Calculate due date
            $dueDateDays = $action->config['due_date_days'] ?? 30;
            $dueDate = date("Y-m-d", strtotime("+{$dueDateDays} days"));

            // Build description from template or use default
            $description = $action->config['description'] ?? 'Invoice for {{first_name}} {{last_name}}';
            $templateEngine = new TemplateEngineHelper();
            $description = $templateEngine->process($description, $data);

            // Create invoice
            $invoiceData = [
                'unique_id' => $unique_id,
                'purpose' => $action->config['purpose'] ?? 'application-fee',
                'due_date' => $dueDate,
                'last_name' => $last_name,
                'first_name' => $first_name,
                'email' => $email,
                'phone_number' => $phone_number,
                'description' => $description
            ];

            $invoiceId = $paymentsService->createInvoice($invoiceData, $invoiceItems, $paymentOptions);

            return [
                'invoiceId' => $invoiceId,
                'invoiceData' => $invoiceData,
                'invoiceItems' => $invoiceItems,
                'paymentOptions' => $paymentOptions
            ];
        } catch (\Exception $e) {
            log_message('error', 'Error generating custom invoice: ' . $e->getMessage());
            throw $e;
        }
    }

    private static function updateLicenseDetails(ApplicationStageType $action, array $data)
    {
        try {
            if (!array_key_exists('field', $data)) {
                throw new \InvalidArgumentException('field key not found in data');
            }
            $field = $data['field'];
            if (!array_key_exists('value', $data)) {
                throw new \InvalidArgumentException('value key not found in data');
            }
            $value = $data['value'];
            if (!array_key_exists('registration_number', $data)) {
                throw new \InvalidArgumentException('registration number key not found in data');
            }
            $registration_number = $data['registration_number'];
            $licenseService = \Config\Services::licenseService();

            $result = $licenseService->updateLicense($registration_number, [$field => $value]);

            return $result;
        } catch (\Exception $e) {
            log_message('error', 'Error generating invoice: ' . $e->getMessage());
            throw $e;
        }
    }

    // /**
    //  * this method makes an api call
    //  * @param object{type:string, config:object {endpoint:string, method:string, headers:array, body_mapping:array, query_params:array, auth_token:string}} $action
    //  * @param array $data
    //  * @return array
    //  */
    // private static function callApi($action, $data)
    // {
    //     helper("auth");
    //     try {
    //         log_message('info', 'Making API call to: ' . $action->config['endpoint']);
    //         // Prepare the request options
    //         $requestOptions = [];

    //         // Process headers with dynamic values
    //         $headers = self::processHeaders($action->config['headers'] ?? [], $data);
    //         $requestOptions['headers'] = $headers;

    //         // Add authentication if provided
    //         if (!empty($action->config['auth_token'])) {
    //             //if the token is __self__, use the auth token of the current user
    //             if ($action->config['auth_token'] === '__self__') {
    //                 $requestOptions['headers']['Authorization'] = 'Bearer ' . auth()->user()->generateAccessToken("internal_server_call")->raw_token;
    //                 log_message('info', 'Using self-generated auth token for API call');
    //             } else {
    //                 // Otherwise, use the provided token
    //                 log_message('info', 'Using provided auth token for API call');
    //                 $requestOptions['headers']['Authorization'] = 'Bearer ' . $action->config['auth_token'];
    //             }

    //         }

    //         // Process body mapping for POST/PUT requests
    //         $body = [];
    //         if (!empty($action->config['body_mapping'])) {
    //             $body = self::mapDataToBody($action->config['body_mapping'], $data);
    //         }

    //         // Process query parameters for GET requests
    //         $queryParams = [];
    //         if (!empty($action->config['query_params'])) {
    //             $queryParams = self::mapDataToQueryParams($action->config['query_params'], $data);
    //         }

    //         // Build the full URL with query parameters if needed
    //         $url = $action->config['endpoint'];
    //         if (!empty($queryParams) && $action->config['method'] === 'GET') {
    //             $url .= '?' . http_build_query($queryParams);
    //         }

    //         // Make the API call based on method
    //         $response = null;
    //         switch (strtoupper($action->config['method'])) {
    //             case 'GET':
    //                 $response = NetworkUtils::makeGetRequest($url, $requestOptions);
    //                 break;
    //             case 'POST':
    //                 $requestOptions['json'] = $body;
    //                 $response = NetworkUtils::makePostRequest($url, $requestOptions);
    //                 break;
    //             case 'PUT':
    //                 $requestOptions['json'] = $body;
    //                 $response = NetworkUtils::makePutRequest($url, $requestOptions);
    //                 break;
    //             case 'DELETE':
    //                 if (!empty($body)) {
    //                     $requestOptions['json'] = $body;
    //                 }
    //                 $response = NetworkUtils::makeDeleteRequest($url, $requestOptions);
    //                 break;
    //             default:
    //                 log_message('error', 'Unsupported HTTP method: ' . $action->config['method']);
    //                 throw new \InvalidArgumentException('Unsupported HTTP method: ' . $action->config['method']);
    //         }

    //         // Log the response for debugging
    //         log_message('info', 'API call response: ' . json_encode($response));

    //         return $data;

    //     } catch (\Throwable $e) {
    //         log_message('error', 'API call failed: ' . $e);
    //         log_message('error', 'API call stack trace: ' . $e->getTraceAsString());
    //         throw $e;
    //     }
    // }



    /**
     * Create a license using the service layer
     * @param object $action
     * @param array $data
     * @return array
     */
    private static function createLicense($action, $data)
    {
        try {


            // Get license service using CI4 service() function
            $licenseService = service('licenseService');

            // Map application data to license data
            $licenseData = self::mapDataForLicense($action->config, $data);
            // Create license using service
            $result = $licenseService->createLicense($licenseData);


            return $data; // Return original data to continue workflow

        } catch (\Throwable $e) {
            log_message('error', 'License creation failed: ' . $e);
            throw $e;
        }
    }

    /**
     * Create a renewal using the service layer
     * @param object $action
     * @param array $data
     * @return array
     */
    private static function createRenewal($action, $data)
    {
        try {

            // Get renewal service using CI4 service() function
            $renewalService = \Config\Services::licenseRenewalService();

            // Map application data to renewal data
            $renewalData = self::mapDataForRenewal($action->config, $data);

            // Create renewal using service
            $result = $renewalService->createRenewal($renewalData);


            return $data;

        } catch (\Throwable $e) {
            log_message('error', 'Renewal creation failed: ' . $e);
            throw $e;
        }
    }

    /**
     * Update a license using the service layer
     * @param object $action
     * @param array $data
     * @return array
     */
    private static function updateLicense($action, $data)
    {
        try {

            // Get license service using CI4 service() function
            $licenseService = \Config\Services::licenseService();

            // Get license UUID from config or data
            $licenseUuid = $action->config['license_uuid'] ?? $data['license_uuid'] ?? null;

            if (!$licenseUuid) {
                throw new \InvalidArgumentException('License UUID is required for update');
            }

            // Map application data to license update data
            $updateData = self::mapDataForLicense($action->config, $data);

            // Update license using service
            $result = $licenseService->updateLicense($licenseUuid, $updateData);


            return $data;

        } catch (\Throwable $e) {
            log_message('error', 'License update failed: ' . $e);
            throw $e;
        }
    }

    private static function updateRenewalStatus(object $action, array $data)
    {
        try {
            $renewalService = \Config\Services::licenseRenewalService();
            //get the uuid from the data
            $renewalUuid = $data['uuid'];


            //any other data that need to be updated will be in the action config

            $renewalData = new stdClass();
            $renewalData->uuid = $renewalUuid;
            //get the remaining details to be updated from the data
            foreach ($data as $key => $value) {
                $renewalData->{$key} = $value;
            }

            $bodyMappingData = self::mapDataForRenewal($action->config, $data);
            //since we need to have the status in the config it must be in the bodyMappingData. if not, we cannot proceed.
            if (!isset($bodyMappingData['status'])) {
                throw new \InvalidArgumentException('Renewal status is required for update');
            }
            $status = $bodyMappingData['status'];
            //add the in_print_queue, print_template and online_print_template to the renewalData object if not already set in the data
            $fieldsToAddFromBodyMapping = ['in_print_queue', 'print_template', 'online_print_template'];
            foreach ($fieldsToAddFromBodyMapping as $field) {
                if (!property_exists($renewalData, $field) && isset($bodyMappingData[$field])) {
                    $renewalData->{$field} = $bodyMappingData[$field];
                }
            }
            //the updateBulkRenewals function expects an array of renewal data objects. since the event callback passes in a single renewal object, we need to wrap it in an array

            $result = $renewalService->updateBulkRenewals([$renewalData], $status, false);
            return $result;

        } catch (\Throwable $e) {
            log_message('error', 'Renewal status update failed: ' . $e);
            throw $e;
        }
    }

    /**
     * Enhanced API call method that routes internal calls to services
     * @param object $action
     * @param array $data
     * @return array
     */
    private static function callApi($action, $data)
    {
        try {

            // Check if this is an internal endpoint that should use services
            if (self::isInternalEndpoint($action->config['endpoint'])) {
                return self::routeToInternalService($action, $data);
            }

            // For external APIs, proceed with HTTP call
            return self::makeExternalApiCall($action, $data);

        } catch (\Throwable $e) {
            log_message('error', 'API call failed: ' . $e);
            throw $e;
        }
    }

    /**
     * Check if endpoint is internal (should use services instead of HTTP)
     * @param string $endpoint
     * @return bool
     */
    private static function isInternalEndpoint($endpoint)
    {
        // Define patterns for internal endpoints
        $internalPatterns = [
            '/^\/licenses\//',
            '/^\/renewals\//',
            '/^\/users\//',
            '/^http:\/\/localhost/',
            '/^https:\/\/localhost/',
        ];

        foreach ($internalPatterns as $pattern) {
            if (preg_match($pattern, $endpoint)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Route internal API calls to appropriate services
     * @param object $action
     * @param array $data
     * @return array
     */
    private static function routeToInternalService($action, $data)
    {
        $endpoint = $action->config['endpoint'];
        $method = strtoupper($action->config['method']);

        // Route based on endpoint pattern
        if (preg_match('/\/api\/licenses\/(.*)/', $endpoint, $matches)) {
            return self::handleLicenseServiceCall($action, $data, $method, $matches[1]);
        }

        if (preg_match('/\/api\/renewals\/(.*)/', $endpoint, $matches)) {
            return self::handleRenewalServiceCall($action, $data, $method, $matches[1]);
        }

        // Fallback to external API call if no service route found
        return self::makeExternalApiCall($action, $data);
    }

    /**
     * Handle license service calls
     * @param object $action
     * @param array $data
     * @param string $method
     * @param string $path
     * @return array
     */
    private static function handleLicenseServiceCall($action, $data, $method, $path)
    {
        $licenseService = \Config\Services::licenseService();
        $mappedData = self::mapDataForLicense($action->config, $data);

        switch ($method) {
            case 'POST':
                if (empty($path) || $path === 'details') {
                    return self::executeServiceCall(function () use ($licenseService, $mappedData) {
                        return $licenseService->createLicense($mappedData);
                    }, $data);
                }
                break;

            case 'PUT':
                if (preg_match('/^([a-f0-9-]+)$/', $path, $matches)) {
                    $uuid = $matches[1];
                    return self::executeServiceCall(function () use ($licenseService, $uuid, $mappedData) {
                        return $licenseService->updateLicense($uuid, $mappedData);
                    }, $data);
                }
                break;

            case 'GET':
                if (preg_match('/^([a-f0-9-]+)$/', $path, $matches)) {
                    $uuid = $matches[1];
                    return self::executeServiceCall(function () use ($licenseService, $uuid) {
                        return $licenseService->getLicenseDetails($uuid);
                    }, $data);
                }
                break;
        }

        throw new \InvalidArgumentException("Unsupported license service operation: $method $path");
    }

    /**
     * Handle renewal service calls
     * @param object $action
     * @param array $data
     * @param string $method
     * @param string $path
     * @return array
     */
    private static function handleRenewalServiceCall($action, $data, $method, $path)
    {
        $renewalService = \Config\Services::licenseRenewalService();
        $mappedData = self::mapDataForRenewal($action->config, $data);

        switch ($method) {
            case 'POST':
                if (empty($path)) {
                    return self::executeServiceCall(function () use ($renewalService, $mappedData) {
                        return $renewalService->createRenewal($mappedData);
                    }, $data);
                }
                break;

            case 'PUT':
                if (preg_match('/^([a-f0-9-]+)$/', $path, $matches)) {
                    $uuid = $matches[1];
                    return self::executeServiceCall(function () use ($renewalService, $uuid, $mappedData) {
                        return $renewalService->updateRenewal($uuid, $mappedData);
                    }, $data);
                }
                break;
        }

        throw new \InvalidArgumentException("Unsupported renewal service operation: $method $path");
    }

    /**
     * Execute service call with proper error handling
     * @param callable $serviceCall
     * @param array $originalData
     * @return array
     */
    private static function executeServiceCall(callable $serviceCall, array $originalData)
    {
        try {
            $result = $serviceCall();
            return $originalData; // Return original data to continue workflow
        } catch (\Throwable $e) {
            log_message('error', 'Service call failed: ' . $e);
            throw $e;
        }
    }

    /**
     * Make external API call using existing HTTP methods
     * @param object $action
     * @param array $data
     * @return array
     */
    private static function makeExternalApiCall($action, $data)
    {
        // Use your existing NetworkUtils or HTTP client logic here
        // This is the same as your original callApi implementation

        // Process headers with dynamic values
        $headers = self::processHeaders($action->config['headers'] ?? [], $data);
        $requestOptions['headers'] = $headers;

        // Add authentication if provided
        if (!empty($action->config['auth_token'])) {
            $requestOptions['headers']['Authorization'] = 'Bearer ' . $action->config['auth_token'];
        }

        // Process body mapping for POST/PUT requests
        $body = [];
        if (!empty($action->config['body_mapping'])) {
            $body = self::mapDataToBody($action->config['body_mapping'], $data);
        }

        // Build the full URL and make the request
        $url = $action->config['endpoint'];

        // Make HTTP call using your existing NetworkUtils
        // $response = NetworkUtils::makeRequest($method, $url, $requestOptions, $body);


        return $data;
    }

    /**
     * Map application data to license format
     * @param object $config
     * @param array $data
     * @return array
     */
    private static function mapDataForLicense($config, $data)
    {
        $licenseData = [];

        // Use body_mapping if available, otherwise use default mapping
        if (!empty($config['body_mapping'])) {
            $licenseData = self::mapDataToBody($config['body_mapping'], $data);
        } else {
            // Default license field mapping
            $defaultMapping = [
                'license_number' => '@license_number',
                'first_name' => '@first_name',
                'last_name' => '@last_name',
                'email' => '@email',
                'phone' => '@phone',
                'type' => '@practitioner_type',
                'registration_date' => '@registration_date',
                'status' => 'active'
            ];

            $licenseData = self::mapDataToBody($defaultMapping, $data);
        }

        return $licenseData;
    }

    /**
     * Map application data to renewal format
     * @param object $config
     * @param array $data
     * @return array
     */
    private static function mapDataForRenewal($config, $data)
    {
        $renewalData = [];

        // Use body_mapping if available, otherwise use default mapping
        if (!empty($config['body_mapping'])) {
            $renewalData = self::mapDataToBody($config['body_mapping'], $data);
        } else {
            // Default renewal field mapping
            $defaultMapping = [
                'license_number' => '@license_number',
                'license_uuid' => '@license_uuid',
                'license_type' => '@practitioner_type',
                'status' => 'pending',
                'start_date' => '@start_date',
                'expiry' => '@expiry',

            ];

            $renewalData = self::mapDataToBody($defaultMapping, $data);
        }

        return $renewalData;
    }

    // /**
    //  * Map data to body using mapping configuration
    //  * @param array|object $mapping
    //  * @param array $data
    //  * @return array
    //  */
    // private static function mapDataToBody($mapping, $data)
    // {
    //     $body = [];

    //     foreach ($mapping as $key => $value) {
    //         if (is_string($value) && strpos($value, '@') === 0) {
    //             // Dynamic value from form data
    //             $fieldName = substr($value, 1);
    //             if (isset($data[$fieldName])) {
    //                 $body[$key] = $data[$fieldName];
    //             }
    //         } else {
    //             // Static value
    //             $body[$key] = $value;
    //         }
    //     }

    //     return $body;
    // }

    /**
     * Process headers with dynamic values from application data
     * @param array $headers
     * @param array $data
     * @return array
     */
    private static function processHeaders($headers, $data)
    {
        $processedHeaders = [];

        foreach ($headers as $key => $value) {
            // Support template variables in headers like {{field_name}}
            $processedValue = self::replacePlaceholders($value, $data);
            $processedHeaders[$key] = $processedValue;
        }

        return $processedHeaders;
    }

    /**
     * Map application data to API body parameters
     * @param array $bodyMapping Array where key is API field and value is application field or static value
     * @param array $data Application data
     * @return array
     */
    private static function mapDataToBody($bodyMapping, $data)
    {
        $body = [];
        foreach ($bodyMapping as $apiField => $mapping) {
            if (is_array($mapping)) {
                // Handle complex mapping with transformations
                $body[$apiField] = self::processComplexMapping($mapping, $data);
            } else {
                // Simple field mapping or static value
                $body[$apiField] = self::getValueFromMapping($mapping, $data);
            }
        }

        return $body;
    }

    /**
     * Map application data to query parameters
     * @param array $queryMapping
     * @param array $data
     * @return array
     */
    private static function mapDataToQueryParams($queryMapping, $data)
    {
        $params = [];

        foreach ($queryMapping as $paramName => $mapping) {
            $value = self::getValueFromMapping($mapping, $data);
            if ($value !== null && $value !== '') {
                $params[$paramName] = $value;
            }
        }

        return $params;
    }

    /**
     * Get value from mapping configuration
     * @param string|array $mapping
     * @param array $data
     * @return mixed
     */
    private static function getValueFromMapping($mapping, $data)
    {
        if (is_string($mapping)) {
            // Check if it's a field reference (starts with @)
            if (strpos($mapping, '@') === 0) {
                $fieldName = substr($mapping, 1);
                return $data[$fieldName] ?? null;
            }

            // Check if it's a template with placeholders
            if (strpos($mapping, '{{') !== false) {
                return self::replacePlaceholders($mapping, $data);
            }

            // Static value
            return $mapping;
        }

        return null;
    }

    /**
     * Process complex mapping with transformations
     * @param array $mapping
     * @param array $data
     * @return mixed
     */
    private static function processComplexMapping($mapping, $data)
    {
        $source = $mapping['source'] ?? null;
        $transform = $mapping['transform'] ?? null;
        $default = $mapping['default'] ?? null;

        // Get the source value
        $value = null;
        if ($source) {
            if (strpos($source, '@') === 0) {
                $fieldName = substr($source, 1);
                $value = $data[$fieldName] ?? $default;
            } else {
                $value = $source;
            }
        }

        // Apply transformations
        if ($transform && $value !== null) {
            switch ($transform['type']) {
                case 'date_format':
                    if (!empty($value)) {
                        $fromFormat = $transform['from'] ?? 'Y-m-d';
                        $toFormat = $transform['to'] ?? 'Y-m-d H:i:s';
                        $date = \DateTime::createFromFormat($fromFormat, $value);
                        $value = $date ? $date->format($toFormat) : $value;
                    }
                    break;
                case 'uppercase':
                    $value = strtoupper($value);
                    break;
                case 'lowercase':
                    $value = strtolower($value);
                    break;
                case 'trim':
                    $value = trim($value);
                    break;
                case 'concat':
                    $prefix = $transform['prefix'] ?? '';
                    $suffix = $transform['suffix'] ?? '';
                    $value = $prefix . $value . $suffix;
                    break;
            }
        }

        return $value ?? $default;
    }

    /**
     * Replace placeholders in string with actual data values
     * @param string $template
     * @param array $data
     * @return string
     */
    private static function replacePlaceholders($template, $data)
    {
        return preg_replace_callback('/\{\{([^}]+)\}\}/', function ($matches) use ($data) {
            $fieldName = trim($matches[1]);
            return $data[$fieldName] ?? $matches[0];
        }, $template);
    }

    /**
     * Get the application template based on the form type
     * @param string $formType the form type
     * @return object|null the application template or null if not found
     */
    public static function getApplicationTemplate(string $formType): ?object
    {
        $applicationTemplateModel = new \App\Models\Applications\ApplicationTemplateModel();
        $template = $applicationTemplateModel->builder()
            ->select(['form_name', 'stages', 'initialStage', 'finalStage', 'on_submit_message', 'data'])
            ->where('form_name', $formType)
            ->get()
            ->getFirstRow();
        if (!$template) {
            //check the default from the app-settings
            try {
                $template = Utils::getDefaultApplicationFormTemplate($formType);

            } catch (\Throwable $th) {
                log_message('error', "Error getting default template: " . $th);
                $template = null;
            }

        }

        return $template;
    }
}