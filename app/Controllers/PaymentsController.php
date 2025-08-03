<?php

namespace App\Controllers;

use App\Services\GhanaGovPaymentService;
use App\Services\PaymentsService;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

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
            $result = $this->paymentsService->generatePresetInovicesForMultipleUuids($purpose, $uuid, $dueDate, $additionalItems);

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

    private function extractRequestFilters(): array
    {
        $filters = [];
        $filters = array_merge($this->request->getGet(), (array) $this->request->getVar());
        return $filters;
    }
}
