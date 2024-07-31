<?php

namespace App\Controllers;

use App\Controllers\BaseController;


class PaymentsController extends BaseController
{
    protected $myghpayService;

    public function __construct()
    {
        $this->myghpayService = new \App\Services\MyGHPayService();
    }

    public function initiatePayment()
    {
        $amount = $this->request->getPost('amount');
        $orderId = $this->request->getPost('orderId');
        $session = $this->myghpayService->createCheckoutSession($amount, $orderId);

        if ($session && isset($session['payload']['redirectUrl'])) {
            return $this->response->setJSON([
                'success' => true,
                'redirectUrl' => $session['payload']['redirectUrl']
            ]);
        } else {
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => 'Failed to create checkout session'
            ]);
        }
    }

    public function checkPaymentStatus($sessionCode)
    {
        $status = $this->myghpayService->checkSessionStatus($sessionCode);
        return $this->response->setJSON($status);
    }

    public function getPaymentDetails($sessionCode)
    {
        $details = $this->myghpayService->getCheckoutSession($sessionCode);
        return $this->response->setJSON($details);
    }

    public function handleCallback() : \CodeIgniter\HTTP\ResponseInterface
    {
        $payload = $this->request->getJSON(true);
        if (!isset($payload['status']) || !isset($payload['sessionCode'])) {
            log_message('error', 'Invalid callback payload: ' . json_encode($payload));
            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'message' => 'Invalid payload'
            ]);
        }

        $status = $payload['status'];
        try {
            switch ($status) {
                case 'COMPLETED':
                    // Transaction is successful
                    $this->myghpayService->processCompletedTransaction($payload);
                    break;
                case 'FAILED':
                    // Transaction failed
                    $this->myghpayService->processFailedTransaction($payload);
                    break;
                case 'PENDING':
                    // Transaction is pending
                    $this->myghpayService->processPendingTransaction($payload);
                    break;
                default:
                    log_message('error', 'Unknown transaction status: ' . $status);
                    return $this->response->setStatusCode(400)->setJSON([
                        'success' => false,
                        'message' => 'Unknown status'
                    ]);
            }
            return $this->response->setJSON(['success' => true]);
        } catch (\Exception $e) {
            log_message('error', 'Exception occurred: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => 'Internal Server Error'
            ]);
        }
    }





}
