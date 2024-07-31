<?php

namespace App\Services;

class MyGHPayService
{
   private $baseUrl;
   private $username;
   private $password;
   private $token = null;
    
    public function __construct()
    {
      $this->baseUrl = getenv('MYGHPAY_BASE_URL');
      $this->username = getenv('MYGHPAY_USERNAME');
      $this->password = getenv('MYGHPAY_PASSWORD');
      $this->authenticate();
    }

    private function authenticate()
    {
        $url = $this->baseUrl . '/Auth/Login';
        $data = [
            'userName' => $this->username,
            'password' => $this->password
        ];

        $response = $this->makeAuthRequest('POST', $url, $data);

        if ($response && isset($response['payload']['token'])) {
            $this->token = $response['payload']['token'];
        } else {
            throw new \Exception('Authentication failed');
        }
    }

    public function createCheckoutSession($amount, $orderId)
    {
        $url = $this->baseUrl . '/Checkout/CreateSession';
        $data = [
            'title' => "Payment for Order #{$orderId}",
            'transactionReference' => "ORDER{$orderId}_" . time(),
            'callbackUrl' => site_url('payments/callback'),
            'returnUrl' => site_url('payments/return'),
            'description' => "Payment for Order #{$orderId}",
            'amount' => $amount,
            'paymentItems' => [
                [
                    'name' => "Order #{$orderId}",
                    'description' => "Payment for Order #{$orderId}",
                    'amount' => $amount,
                    'quantity' => 1
                ]
            ]
        ];
        return $this->makeRequest('POST', $url, json_encode($data));
    }

    public function checkSessionStatus($sessionCode)
    {
        $url = $this->baseUrl . "/Checkout/CheckSessionStatus/{$sessionCode}";
        return $this->makeRequest('GET', $url);
    }

    public function getCheckoutSession($sessionCode)
    {
        $url = $this->baseUrl . "/checkout/getcheckoutsession/{$sessionCode}";
        return $this->makeRequest('GET', $url);
    }

    private function makeRequest($method, $url, $data = null)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
        curl_setopt($ch, CURLOPT_URL, $url);
        $headers = [
            'Authorization: Bearer ' . $this->token,
            'Content-Type: application/json'
        ];
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        if (!empty($data)) {
            $jsonData = json_encode($data);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        if ($response === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new \Exception('cURL error: ' . $error);
        }
        curl_close($ch);
        $responseData = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('JSON decode error: ' . json_last_error_msg());
        }
        return $responseData;
    }

    private function makeAuthRequest($method, $url, $data = null)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
        curl_setopt($ch, CURLOPT_URL, $url);
        $headers = [
            'Content-Type: application/json'
        ];
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        if (!empty($data)) {
            $jsonData = json_encode($data);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        if ($response === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new \Exception('cURL error: ' . $error);
        }
        curl_close($ch);
        $responseData = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('JSON decode error: ' . json_last_error_msg());
        }
        return $responseData;
    }

    public function processCompletedTransaction($payload)
    {
    // Update your database to mark the order as paid
    // Send confirmation email to the customer
    // Update inventory, etc.
    log_message('info', 'Transaction completed: ' . json_encode($payload));
    }

    public function processFailedTransaction($payload)
    {
    // Update your database to mark the order as failed
    // Optionally notify the customer about the failed payment
    log_message('info', 'Transaction failed: ' . json_encode($payload));
    }

    public function processPendingTransaction($payload)
    {
      $sessionCode = $payload['sessionCode'];
      $sessionStatusResponse = $this->checkSessionStatus($sessionCode);

      if ($sessionStatusResponse && isset($sessionStatusResponse['status'])) {
        $updatedStatus = strtoupper($sessionStatusResponse['status']);
    }
                
        if ($updatedStatus === 'COMPLETED') {
            $this->processCompletedTransaction($payload);
        } elseif ($updatedStatus === 'FAILED') {
            $this->processFailedTransaction($payload);
        } else {
            // Still pending or unknown status
            // You might want to schedule a job to check again later
            log_message('info', 'Transaction still pending: ' . json_encode($payload));
        }
    }
    


    

}