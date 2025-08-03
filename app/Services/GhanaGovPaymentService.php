<?php

namespace App\Services;

use App\Helpers\LicenseUtils;
use App\Helpers\NetworkUtils;
use App\Helpers\PaymentUtils;
use App\Helpers\Types\InvoicePaymentOptionType;
use App\Helpers\Types\PaymentInvoiceItemType;
use App\Helpers\Utils;
use App\Models\ActivitiesModel;
use App\Models\Payments\FeesModel;
use App\Models\Payments\InvoiceLineItemModel;
use App\Models\Payments\InvoiceModel;
use App\Models\Payments\InvoicePaymentOptionModel;
use App\Models\Payments\PaymentModel;
use CodeIgniter\Database\BaseBuilder;
use Exception;
use InvalidArgumentException;
class GhanaGovPaymentService
{
    private FeesModel $feesModel;
    private ActivitiesModel $activitiesModel;
    private InvoiceModel $invoiceModel;

    private InvoiceLineItemModel $invoiceLineItemModel;

    private InvoicePaymentOptionModel $invoicePaymentOptionModel;

    private PaymentModel $paymentModel;

    private $baseUrl;
    private $username;
    private $password;
    private $token = null;

    private $apiKey;

    private $createInvoiceUrl;
    private $checkoutUrl;
    private $queryInvoiceUrl;

    public function __construct()
    {
        $this->feesModel = new FeesModel();
        $this->activitiesModel = new ActivitiesModel();
        $this->invoiceModel = new InvoiceModel();
        $this->invoiceLineItemModel = new InvoiceLineItemModel();
        $this->invoicePaymentOptionModel = new InvoicePaymentOptionModel();
        $this->paymentModel = new PaymentModel();
        $this->apiKey = getenv("GHANA_GOV_API_KEY");
        $this->checkoutUrl = getenv("GHANA_GOV_CHECKOUT_URL");
        $this->createInvoiceUrl = getenv("GHANA_GOV_CREATE_INVOICE_URL");
        $this->queryInvoiceUrl = getenv("GHANA_GOV_QUERY_INVOICE_URL");
    }

    public function createCheckoutSession(string $uuid)
    {
        try {

            /**
             * @var array
             */
            $invoiceData = $this->invoiceModel->where(['uuid' => $uuid])->first();
            if (!$invoiceData) {
                throw new InvalidArgumentException("Invoice not found");
            }
            $invoiceItems = $this->invoiceLineItemModel->where(['invoice_uuid' => $uuid])->findAll();
            $mdaBranchCode = $invoiceData['mda_branch_code'];

            $apiData = json_encode([
                "request" => "create",
                "api_key" => $this->apiKey,
                "mda_branch_code" => $mdaBranchCode,
                "firstname" => $invoiceData['first_name'],
                "lastname" => $invoiceData['last_name'],
                "phonenumber" => $invoiceData['phone_number'],
                "email" => $invoiceData['email'],
                "application_id" => $invoiceData['application_id'],
                "invoice_items" => $invoiceItems,
                "redirect_url" => site_url("payment/payment_redirect/" . $invoiceData['application_id']),
                "post_url" => site_url("payment/paymentDone")
            ]);


            $networkResponse = NetworkUtils::makeCURLRequest(
                'POST',
                $this->createInvoiceUrl,
                $apiData,
                ['Content-Type: application/json']
            );
            // echo $get_data;
            log_message(
                "error",
                "create ghana.gov invoice called. data : $networkResponse by {$invoiceData['last_name']} for {$invoiceData['purpose']}",
            );
            $apiResponse = json_decode($networkResponse, true);
            // print_r($json_data);
            if ($apiResponse['status'] == '0') {
                // update the invoice table with the response
                $updateData = [
                    'invoice_number' => $apiResponse['invoice_number'],
                    'online_payment_status' => $apiResponse['status'],
                    'online_payment_response' => $networkResponse
                ];


                $this->invoiceModel->where(['uuid' => $uuid])->update($updateData);
                //update the appropriate table with the payment invoice_id
                //TODO: EMIT EVENT HERE TO SIGNAL THAT INVOICE WAS CREATED

                $this->activitiesModel->logActivity(
                    "create invoice was successful for {$invoiceData['unique_id']} for {$invoiceData['purpose']}.  invoice number: {$apiResponse['invoice_number']} ",

                    "0",
                    "Payments"
                );
                $message = "Your invoice has been registered. Please make payment using 
                any of the payment methods available before {$networkResponse['invoice_expires']}";

                return $message;
            } else {
                // echo "Error in creating your invoice. Please try again in a few minutes";
                $this->activitiesModel->logActivity(

                    "create invoice failed for  {$invoiceData['unique_id']} for {$invoiceData['purpose']}.  message number: {$apiResponse['message']} ",
                    "0",
                    "system"
                );
                throw new Exception($apiResponse['message']);

            }
        } catch (\Throwable $th) {
            log_message('error', $th);
            throw $th;
        }
    }

    public function ghanaGovInvoicepaymentDone(string $invoiceNumber)
    {
        try {

            /**
             * @var array
             */
            $invoiceData = $this->invoiceModel->where(['invoice_number' => $invoiceNumber])->first();
            if (!$invoiceData) {
                throw new InvalidArgumentException("Invoice not found");
            }
            $networkResponse = $this->queryGhanaGovInvoice($invoiceNumber);


            //get the invoice details
            if ($networkResponse['status'] == 0) { //successful
                /**
                 * @var array
                 */
                $output = $networkResponse['output'];


                $payment_status_code = $output['payment_status_code'];
                /**
                 * {
                 *    "data": {
                 *        "status": 0,
                 *        "message": "Success",
                 *        "output": {
                 *            "payment_status_code": 1,
                 *            "payment_status_text": "Payment Approved",
                 *            "amount": "345.00",
                 *            "date_processed": "2024-01-28 21:22:50",
                 *            "payment_reference": "d78e877fxd9cd",
                 *            "currency": "GHS",
                 *            "invoice_number": "240128211838149"
                 *        }
                 *    },
                 *    "message": "Payment submitted successfully"
                 *}
                 */
                //if successful, process it appropriately
                switch ($payment_status_code) {
                    /*
                         * 1 = Payment Approved
                            2 = Payment Failed
                            3 = No Payment Record
                            4 = Payment Pending
                         */
                    case 1:
                        $updateData = [
                            'status' => "Paid",
                            'payment_method' => "Ghana.gov Platform",
                            'payment_date' => date("Y-m-d", strtotime($output['date_processed'])),
                            'online_payment_status' => $output['payment_status_text'],
                            'online_payment_response' => json_encode($output)
                        ];

                        $this->invoiceModel->builder()->where(['uuid' => $invoiceData['uuid']])->update($updateData);
                        //TODO; EMIT EVENT HERE TO SIGNAL THAT INVOICE WAS PAID
                        //TODO: PROBABLY SEND EMAIL
                        // $this->processPayment($invoice_number);

                        $message = "Payment for {$invoiceData['description']} - invoice number $invoiceNumber has been updated to 
                        Paid. Thank you.";
                        break;
                    case 2:
                        $message = "Payment  for {$invoiceData['description']} - invoice number $invoiceNumber failed. Please try again or contact the Ghana.gov payment platform fo assistance. Thank you.";
                        break;
                    case 3:
                        $message = "We were unable to retrieve any payment records for your payment  for {$invoiceData['description']} - invoice number $invoiceNumber. 
                         ";
                        break;

                    case 4:
                        $message = "Payment for {$invoiceData['description']} - invoice number $invoiceNumber is pending payment. Please make payment as soon as possible 
                            to complete the process. 
                             ";
                        break;
                    default:
                        $message = "No Payment for {$invoiceData['description']} - invoice number $invoiceNumber was found. Please try again later. 
                 
                 ";
                        break;
                }
                // $this->sendSingleMail($payer_details->email, $message, 'Payment');
                $this->activitiesModel->logActivity(
                    "payment invoice number $invoiceNumber called post_url. " . json_encode($networkResponse),
                    "0",
                    "system"
                );
            } else {
                $this->activitiesModel->logActivity(
                    "payment invoice number $invoiceNumber called post_url with query invoice response status {$networkResponse['status']}. .json_encode($networkResponse)",

                    "0",
                    "system"
                );
            }
        } catch (\Throwable $th) {
            log_message('error', $th);
            throw $th;
        }
    }

    public function queryGhanaGovInvoice(string $invoiceNumber)
    {


        //make the call to the invoice number from here to confirm payment

        $networkResponseString = NetworkUtils::makeCURLRequest(
            'GET',
            $this->queryInvoiceUrl .
            "?request=get_invoice_status&api_key=$this->apiKey&invoice_number=$invoiceNumber",
            [],
            []
        );

        $networkResponse = json_decode($networkResponseString, true);

        return $networkResponse;
    }
}