<?php
namespace App\Helpers;
class EmailConfig
{
    public $message;
    public $subject;
    public $to;
    public $sender;
    public $cc;
    public $bcc;
    public $attachments;
    public $senderName;

    public function __construct($message, $subject, $to, $sender = null, $cc = null, $bcc = null, $attachments = null, $senderName = null)
    {
        $appSettings = json_decode(file_get_contents(ROOTPATH . 'app-settings.json'), true);
        if ($sender == null) {
            $sender = $appSettings['defaultEmailSenderEmail'];
        }
        if ($senderName == null) {
            $senderName = $appSettings['defaultEmailSenderName'];
        }
        $this->message = $message;
        $this->subject = $subject;
        $this->to = $to;
        $this->sender = $sender;
        $this->cc = $cc;
        $this->bcc = $bcc;
        $this->attachments = $attachments;
        $this->senderName = $senderName;
    }

}