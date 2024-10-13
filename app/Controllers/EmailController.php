<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use App\Helpers\EmailHelper;
use App\Helpers\EmailConfig;
use CodeIgniter\HTTP\ResponseInterface;
class EmailController extends ResourceController
{
   public function send()
   {
      try {
         //code...

         $subject = $this->request->getVar('subject');
         $message = $this->request->getVar('message');
         $email = $this->request->getVar('email');
         $sender = $this->request->getVar('sender');
         $receiver = $this->request->getVar('receiver');
         $cc = $this->request->getVar('cc');
         $bcc = $this->request->getVar('bcc');
         $attachment = $this->request->getVar('attachment');
         $emailConfig = new EmailConfig($message, $subject, $receiver, $sender, $cc, $bcc, $attachment);

         if (empty($subject)) {
            throw new \Exception('Subject is required');
         }
         if (empty($message)) {
            throw new \Exception('Message is required');
         }
         if (empty($receiver)) {
            throw new \Exception('Receiver is required');
         }
         if (empty($sender)) {
            throw new \Exception('Sender is required');
         }

         //  Utils::sendEmail($message, $subject, $receiver, $sender);
         EmailHelper::sendBrevoEmail($emailConfig);
      } catch (\Throwable $th) {
         return $this->respond(['message' => $th->getMessage()], ResponseInterface::HTTP_BAD_REQUEST);
      }
   }

}
