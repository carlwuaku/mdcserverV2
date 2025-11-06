<?php

namespace App\Controllers;

use App\Services\GhanaGovPaymentService;
use App\Services\PaymentsService;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;
use App\Helpers\AuthHelper;

class PaymentsController extends ResourceController
{
    private PaymentsService $paymentsService;
    private GhanaGovPaymentService $ghanaGovPaymentService;
    public function __construct()
    {
        $this->paymentsService = \Config\Services::paymentsService();
        $this->ghanaGovPaymentService = \Config\Services::ghanaGovPaymentService();
    }

    /**
     * Handles the creation of a fee.
     *
     * This method retrieves the posted data, invokes the PaymentsService to create a fee,
     * and returns the result. If validation fails, it responds with a bad request status.
     * In case of any other errors, it logs the error and returns an internal server error response.
     *
     * @return \CodeIgniter\HTTP\ResponseInterface The response containing the result or error message.
     */

    public function createFee()
    {
        try {
            $data = $this->request->getPost();

            //create the letters objects
            $result = $this->paymentsService->createFee((array) $data);

            return $this->respond($result, ResponseInterface::HTTP_OK);

        } catch (\InvalidArgumentException $e) {
            return $this->respond(['message' => $e->getMessage()], ResponseInterface::HTTP_BAD_REQUEST);
        } catch (\Throwable $e) {
            log_message("error", $e);
            return $this->respond(['message' => "Server error. Please try again"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function updateFee($id)
    {
        try {
            $data = $this->request->getVar();



            //create the letters objects
            $result = $this->paymentsService->updateFee($id, (array) $data);

            return $this->respond($result, ResponseInterface::HTTP_OK);

        } catch (\InvalidArgumentException $e) {
            return $this->respond(['message' => $e->getMessage()], ResponseInterface::HTTP_BAD_REQUEST);
        } catch (\Throwable $e) {
            log_message("error", $e);
            return $this->respond(['message' => "Server error. Please try again"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getFees()
    {
        try {
            $filters = $this->extractRequestFilters();
            $result = $this->paymentsService->getAllFees($filters);

            return $this->respond($result, ResponseInterface::HTTP_OK);

        } catch (\Throwable $e) {
            log_message("error", $e);
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getFee($id)
    {
        try {
            $result = $this->paymentsService->getFeeDetails($id);
            return $this->respond($result, ResponseInterface::HTTP_OK);

        } catch (\Throwable $e) {
            log_message("error", $e);
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function deleteFee($id)
    {
        try {
            $result = $this->paymentsService->deleteFee($id);

            return $this->respond($result, ResponseInterface::HTTP_OK);

        } catch (\Throwable $e) {
            log_message("error", $e);
            return $this->respond(['message' => "Server error. Please try again"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function createInvoice()
    {
        try {
            $data = $this->request->getVar();
            /**
             * @var array
             */
            $items = $this->request->getVar("items");
            $unique_id = $this->request->getVar("unique_id");
            $paymentOptions = $this->request->getVar("paymentOptions");
            if (!is_array($items) || count($items) == 0) {
                throw new \InvalidArgumentException("Invalid items data provided");
            }
            $itemsArray = array_map(function ($item) {
                $itemObj = new \App\Helpers\Types\PaymentInvoiceItemType(0, '', '', 0, 0, 0);
                return $itemObj->createFromRequest($item);
            }, $items);

            //it's okay if no payment options are provided. there are defaults for every payment type
            $paymentOptionsArray = count($paymentOptions) > 0 ? array_map(function ($paymentOption) {
                $paymentOptionObj = new \App\Helpers\Types\InvoicePaymentOptionType(0, '');
                return $paymentOptionObj->createFromRequest($paymentOption);
            }, $paymentOptions) : [];
            //get the details of the license from the database
            try {
                $payerDetails = \App\Helpers\LicenseUtils::getLicenseDetails($unique_id);
                $data->first_name = array_key_exists("first_name", $payerDetails) ? $payerDetails['first_name'] : $payerDetails['name'];
                $data->email = $payerDetails['email'];
                $data->phone_number = $payerDetails['phone'];
                $data->last_name = array_key_exists("last_name", $payerDetails) ? $payerDetails['last_name'] : $unique_id;
            } catch (\Exception $e) {
                //if it's not a valid license. for now we don't want to allow payments for non-licensed users
                //if in future we want to allow that, handle the logic for that here. perhaps this can
                //come before the validation so that we add rules for names and emails if the license is not valid
                throw new \InvalidArgumentException("Invalid license number");
            }

            //create the letters objects
            $result = $this->paymentsService->createInvoice((array) $data, $itemsArray, $paymentOptionsArray);

            return $this->respond(["data" => $result, "message" => "Invoice created successfully"], ResponseInterface::HTTP_OK);

        } catch (\InvalidArgumentException $e) {
            return $this->respond(['message' => $e->getMessage()], ResponseInterface::HTTP_BAD_REQUEST);
        } catch (\Throwable $e) {
            log_message("error", $e);
            return $this->respond(['message' => "Server error. Please try again"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function createPresetInvoices()
    {
        try {
            $purpose = $this->request->getVar('purpose');
            /**
             * @var array
             */
            $uuid = $this->request->getVar('uuids'); //comma separated uuids
            $additionalItems = $this->request->getVar('additionalItems');
            //this selects the items of the invoice based on the purpose and adds any additional items
            $dueDate = $this->request->getVar('dueDate');
            $result = $this->paymentsService->generatePresetInvoicesForMultipleUuids($purpose, $uuid, $dueDate, $additionalItems);

            return $this->respond(["data" => $result, "message" => "Invoice created successfully"], ResponseInterface::HTTP_OK);

        } catch (\InvalidArgumentException $e) {
            return $this->respond(['message' => $e->getMessage()], ResponseInterface::HTTP_BAD_REQUEST);
        } catch (\Throwable $e) {
            log_message("error", $e);
            return $this->respond(['message' => "Server error. Please try again"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function updateInvoices()
    {
        try {
            /**
             * @var array{uuid: string,status: ?string, due_date: ?string}
             */
            $data = $this->request->getVar();



            //create the letters objects
            $result = $this->paymentsService->updateBulkInvoice((array) $data);

            return $this->respond(["message" => $result, "data" => null], ResponseInterface::HTTP_OK);

        } catch (\InvalidArgumentException $e) {
            return $this->respond(['message' => $e->getMessage()], ResponseInterface::HTTP_BAD_REQUEST);
        } catch (\Throwable $e) {
            log_message("error", $e);
            return $this->respond(['message' => "Server error. Please try again"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function updateInvoicePaymentMethod($uuid)
    {
        try {
            $paymentMethod = $this->request->getVar("payment_method") ?? null;
            if (empty($paymentMethod)) {
                throw new \InvalidArgumentException("payment_method is required");
            }
            $result = $this->paymentsService->updateInvoicePaymentMethod($uuid, $paymentMethod);
            return $this->respond($result, ResponseInterface::HTTP_OK);
        } catch (\InvalidArgumentException $e) {
            return $this->respond(['message' => $e->getMessage()], ResponseInterface::HTTP_BAD_REQUEST);
        } catch (\Throwable $e) {
            log_message("error", $e);
            return $this->respond(['message' => "Server error. Please try again"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getInvoices()
    {
        try {
            $filters = $this->extractRequestFilters();
            $result = $this->paymentsService->getInvoices($filters);

            return $this->respond($result, ResponseInterface::HTTP_OK);

        } catch (\Throwable $e) {
            log_message("error", $e);
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    public function getLicenseInvoices()
    {
        try {
            $userId = auth("tokens")->id();
            $userData = AuthHelper::getAuthUser($userId);
            $filters = ["unique_id" => $userData->profile_data['license_number']];
            $param = $this->request->getVar("param") ?? null;
            if (!empty($param)) {
                $filters['param'] = $param;
            }
            $result = $this->paymentsService->getInvoices($filters);
            //remove these fields from each item in data

            $removeFields = ["application_id", "id", "year", "redirect_url", "purpose_table_uuid", "purpose_table", "post_url", "payment_method", "payment_file", "payment_file_date", "mda_branch_code", "online_payment_status", "online_payment_response"];
            $result['data'] = array_map(function ($item) use ($removeFields) {
                foreach ($removeFields as $field) {
                    unset($item->$field);
                }
                return $item;
            }, $result['data']);
            //remove them from the display_columns string[] as well
            $result['displayColumns'] = ["purpose", "amount", "status", "invoice_number", "created_at", "selected_payment_method", "due_date", "notes", "payment_date"];// array_values(array_diff($result['displayColumns'], $removeFields));

            return $this->respond($result, ResponseInterface::HTTP_OK);

        } catch (\Throwable $e) {
            log_message("error", $e);
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function countLicenseInvoices($status)
    {
        try {
            $userId = auth("tokens")->id();
            $userData = AuthHelper::getAuthUser($userId);
            $filters = ["unique_id" => $userData->profile_data['license_number'], "status" => $status];
            $result = $this->paymentsService->getInvoices($filters);

            return $this->respond(['data' => $result['total']], ResponseInterface::HTTP_OK);

        } catch (\Throwable $e) {
            log_message("error", $e);
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getLicenseInvoiceDetails($uuid)
    {
        try {
            $result = $this->paymentsService->getInvoice($uuid);
            return $this->respond($result, ResponseInterface::HTTP_OK);

        } catch (\Throwable $e) {
            log_message("error", $e);
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getInvoice($uuid)
    {
        try {
            $result = $this->paymentsService->getInvoice($uuid);
            return $this->respond($result, ResponseInterface::HTTP_OK);

        } catch (\Throwable $e) {
            log_message("error", $e);
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get an invoice by external uuid, e.g. a renewal uuid
     *
     * @param string $uuid The external uuid of the invoice
     *
     * @return ResponseInterface
     *

     */
    public function getInvoiceByExternal($uuid)
    {
        try {
            $result = $this->paymentsService->getInvoiceByExternalUuid($uuid);
            return $this->respond($result, ResponseInterface::HTTP_OK);

        } catch (\InvalidArgumentException $e) {
            return $this->respond(['message' => $e->getMessage()], ResponseInterface::HTTP_BAD_REQUEST);
        } catch (\Throwable $e) {
            log_message("error", $e);
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function deleteInvoice($uuid)
    {
        try {
            $result = $this->paymentsService->deleteInvoice($uuid);

            return $this->respond($result, ResponseInterface::HTTP_OK);

        } catch (\Throwable $e) {
            log_message("error", $e);
            return $this->respond(['message' => "Server error. Please try again"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @OA\Get(
     *     path="/payments/invoices/default-fees",
     *     tags={"Payments"},
     *     summary="Get default fees for a given invoice purpose and uuids",
     *     description="Get default fees for a given invoice purpose and uuids",
     *     @OA\Parameter(in="query", name="purpose", required=true, description="The purpose of the invoice", example="license-renewal"),
     *     @OA\Parameter(in="query", name="uuids", required=true, description="The uuids of the licenses", example="['uuid1', 'uuid2']"),
     *     @OA\Response(response=200, description="successful operation", @OA\JsonContent()),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
    public function getInvoiceDefaultFees()
    {
        try {
            $purpose = $this->request->getVar('purpose');
            /**
             * @var array
             */
            $uuid = $this->request->getVar('uuids'); //
            $result = $this->paymentsService->getInoviceDefaultFeesMultipleUuids($purpose, $uuid);

            return $this->respond(['data' => $result, 'message' => ''], ResponseInterface::HTTP_OK);

        } catch (\Throwable $e) {
            log_message("error", $e);
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function submitOfflinePayment(string $uuid)
    {
        try {

            $data = $this->request->getVar();

            $result = $this->paymentsService->submitOfflinePayment($uuid, (array) $data);

            return $this->respond(['data' => $result, 'message' => 'Payment submitted successfully'], ResponseInterface::HTTP_OK);

        } catch (\Throwable $e) {
            log_message("error", $e);
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // public function submitOfflinePaymentByLicense(string $invoiceUuid)
    // {
    //     try {
    //         $userId = auth("tokens")->id();
    //         $user = AuthHelper::getAuthUser($userId);
    //         /**
    //          * @var object
    //          */
    //         $data = $this->request->getVar();
    //         //make sure the uuid belongs to the user
    //         $invoice = $this->paymentsService->getInvoice($invoiceUuid);
    //         if (!$invoice) {
    //             return $this->respond(['message' => "Invoice not found"], ResponseInterface::HTTP_NOT_FOUND);
    //         }

    //         if (array_key_exists('unique_id', $invoice) && $invoice['unique_id'] != $user->profile_data['license_number']) {
    //             return $this->respond(['message' => "Invalid invoice number"], ResponseInterface::HTTP_NOT_FOUND);
    //         }
    //         $invoiceData = [
    //             "payment_file" => $data->payment_file,
    //             "payment_date" => $data->payment_date
    //         ];
    //         $result = $this->paymentsService->submitOfflinePayment($invoiceUuid, $invoiceData);

    //         return $this->respond(['data' => $result, 'message' => 'Payment submitted successfully'], ResponseInterface::HTTP_OK);

    //     } catch (\Throwable $e) {
    //         log_message("error", $e);
    //         return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
    //     }
    // }

    public function createGhanaGovInvoice()
    {
        try {
            $invoiceUuid = $this->request->getPost("invoice_uuid");
            $mdaBranch = $this->request->getPost("mda_branch_code");
            $this->ghanaGovPaymentService->createCheckoutSession($invoiceUuid, $mdaBranch);
        } catch (\Exception $e) {
            log_message("error", $e);
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function paymentDone()
    {
        try {
            $invoiceNumber = $this->request->getPost("invoice_number");

            $this->ghanaGovPaymentService->ghanaGovInvoicepaymentDone($invoiceNumber);

            return $this->respond(['data' => null, 'message' => 'Payment submitted successfully'], ResponseInterface::HTTP_OK);

        } catch (\Throwable $e) {
            log_message("error", $e);
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function queryGhanaGovInvoice($invoiceNumber)
    {
        try {

            $response = $this->ghanaGovPaymentService->queryGhanaGovInvoice($invoiceNumber);

            return $this->respond(['data' => $response, 'message' => 'Payment submitted successfully'], ResponseInterface::HTTP_OK);

        } catch (\Throwable $e) {
            log_message("error", $e);
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    public function paymentRedirect($applicationId)
    {
    }

    public function createPaymentFileUpload()
    {
        try {
            $data = $this->request->getPost();

            //create the letters objects
            $this->paymentsService->createPaymentFileUpload((array) $data);

            return $this->respond(['data' => null, 'message' => 'Payment submitted successfully'], ResponseInterface::HTTP_OK);

        } catch (\InvalidArgumentException $e) {
            return $this->respond(['message' => $e->getMessage()], ResponseInterface::HTTP_BAD_REQUEST);
        } catch (\Throwable $e) {
            log_message("error", $e);
            return $this->respond(['message' => "Server error. Please try again"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    public function getPaymentFileUploads()
    {
        try {
            $filters = $this->extractRequestFilters();
            $result = $this->paymentsService->getPaymentFileUploads($filters);

            return $this->respond($result, ResponseInterface::HTTP_OK);

        } catch (\Throwable $e) {
            log_message("error", $e);
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function countPaymentFileUploads()
    {
        try {
            $result = $this->paymentsService->countPaymentFileUploads();

            return $this->respond(['data' => $result], ResponseInterface::HTTP_OK);

        } catch (\Throwable $e) {
            log_message("error", $e);
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function deletePaymentFileUpload($id)
    {
        try {
            $result = $this->paymentsService->deletePaymentFileUpload($id);

            return $this->respond(['data' => $result, 'message' => "Payment deleted successfully"], ResponseInterface::HTTP_OK);

        } catch (\Throwable $e) {
            log_message("error", $e);
            return $this->respond(['message' => "Server error. Please try again"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function approvePaymentFileUpload($id)
    {
        try {

            $this->paymentsService->approvePaymentFileUpload(["id" => $id]);

            return $this->respond(['data' => null, 'message' => 'Payment submitted successfully'], ResponseInterface::HTTP_OK);

        } catch (\InvalidArgumentException $e) {
            log_message("error", $e);
            return $this->respond(['message' => $e->getMessage()], ResponseInterface::HTTP_BAD_REQUEST);
        } catch (\Throwable $e) {
            log_message("error", $e);
            return $this->respond(['message' => "Server error. Please try again"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function generateInvoicePrintouts()
    {
        try {
            $uuids = $this->request->getVar("uuids");
            $templateName = $this->request->getVar("template_name");

            $results = $this->paymentsService->generateInvoicePrintouts($uuids, $templateName);

            return $this->respond(['data' => $results, 'message' => 'Invoices generated successfully'], ResponseInterface::HTTP_OK);

        } catch (\InvalidArgumentException $e) {
            log_message("error", $e);
            return $this->respond(['message' => $e->getMessage()], ResponseInterface::HTTP_BAD_REQUEST);
        } catch (\Throwable $e) {
            log_message("error", $e);
            return $this->respond(['message' => "Server error. Please try again"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getPaymentMethodBranches($paymentMethod)
    {
        try {
            $result = $this->paymentsService->getPaymentMethodBranches($paymentMethod);

            return $this->respond(['data' => $result], ResponseInterface::HTTP_OK);

        } catch (\Throwable $e) {
            log_message("error", $e);
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function extractRequestFilters(): array
    {
        $filters = [];
        $filters = array_merge($this->request->getGet(), (array) $this->request->getVar());
        return $filters;
    }
}
