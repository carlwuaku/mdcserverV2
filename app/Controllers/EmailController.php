<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use App\Helpers\Utils;

class EmailController extends ResourceController
{
   public function send(){
    $subject = $this->request->getVar('subject');
    $message = $this->request->getVar('message');
    $email = $this->request->getVar('email');
    $sender = $this->request->getVar('sender');
    $receiver = $this->request->getVar('receiver');
    $cc = $this->request->getVar('cc');
    $bcc = $this->request->getVar('bcc');
    $attachment = $this->request->getVar('attachment');

    Utils::sendEmail($message, $subject, $receiver);
   }
}
