<?php
namespace App\Helpers;

use Brevo\Client\Configuration;
use Brevo\Client\Api\TransactionalEmailsApi;
use GuzzleHttp;
use Exception;

class EmailHelper
{


    public static function sendBrevoEmail(EmailConfig $emailConfig)
    {

        $apiKey = getenv('BREVO_EMAIL_API_KEY');
        // Configure API key authorization: api-key
        $config = Configuration::getDefaultConfiguration()->setApiKey('api-key', $apiKey);
        // Uncomment below to setup prefix (e.g. Bearer) for API key, if needed
// $config = Brevo\Client\Configuration::getDefaultConfiguration()->setApiKeyPrefix('api-key', 'Bearer');
// Configure API key authorization: partner-key
// $config = Configuration::getDefaultConfiguration()->setApiKey('partner-key', 'YOUR_API_KEY');
// Uncomment below to setup prefix (e.g. Bearer) for API key, if needed
// $config = Brevo\Client\Configuration::getDefaultConfiguration()->setApiKeyPrefix('partner-key', 'Bearer');

        $apiInstance = new TransactionalEmailsApi(
            // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
            // This is optional, `GuzzleHttp\Client` will be used as default.
            new GuzzleHttp\Client(),
            $config
        );
        $sendSmtpEmail = new \Brevo\Client\Model\SendSmtpEmail([
            'subject' => $emailConfig->subject,
            'sender' => ['name' => $emailConfig->senderName, 'email' => $emailConfig->sender],
            'replyTo' => ['name' => $emailConfig->senderName, 'email' => $emailConfig->sender],
            'to' => [['name' => null, 'email' => $emailConfig->to]],
            'htmlContent' => "<html><body>{$emailConfig->message}</body></html>"
        ]); // \Brevo\Client\Model\SendSmtpEmail | Values to send a transactional email

        try {
            $result = $apiInstance->sendTransacEmail($sendSmtpEmail);


            return $result;
        } catch (Exception $e) {
            log_message('error', $e->getMessage());
            echo 'Exception when calling TransactionalEmailsApi->sendTransacEmail: ', $e->getMessage(), PHP_EOL;
        }
    }

    public static function sendSmtpEmail(EmailConfig $emailConfig)
    {
        $appSettings = json_decode(file_get_contents(ROOTPATH . 'app-settings.json'), true);

        $email = \Config\Services::email();

        // Set the email configuration
        $config['protocol'] = 'smtp';
        $config['SMTPHost'] = $appSettings['smtpHost'];
        $config['SMTPUser'] = $appSettings['smtpUser'];
        $config['SMTPPass'] = '';
        $config['SMTPPort'] = $appSettings['smtpPort'];
        $config['SMTPCrypto'] = $appSettings['smtpCrypto'];
        $config['mailType'] = $appSettings['mailType'];
        $config['charset'] = $appSettings['charset'];

        // Initialize the email library with the configuration
        $email->initialize($config);

        // Set the email properties
        $email->setFrom($emailConfig->sender, $appSettings['defaultEmailSenderName']);
        $email->setTo($emailConfig->to);
        $email->setSubject($emailConfig->subject);
        $email->setMessage($emailConfig->message);

        // Add CC recipients if provided
        if (!is_null($emailConfig->cc)) {
            $email->setCC($emailConfig->cc);
        }

        // Add BCC recipients if provided
        if (!is_null($emailConfig->bcc)) {
            $email->setBCC($emailConfig->bcc);
        }

        // Add attachments if provided
        if (!is_null($emailConfig->attachments)) {
            foreach ($emailConfig->attachments as $attachment) {
                $email->attach($attachment);
            }
        }
        try {
            // Send the email
            if ($email->send()) {
                return true;
            } else {
                log_message('error', 'Email not sent by default smtp');
                throw new Exception("Email not sent by default smtp");
            }
        } catch (Exception $e) {
            log_message('error', 'Email not sent by default smtp');
            log_message('error', $e->getMessage());
        }
    }

    // public static function sendEmail(EmailConfig $emailConfig)
    // {
    //     log_message('info', 'Sending email');
    //     log_message('info', print_r($emailConfig, true));
    //     $apiKey = getenv('EMAIL_METHOD');
    //     switch ($apiKey) {
    //         case 'brevo':
    //             self::sendBrevoEmail($emailConfig);
    //             break;

    //         default:
    //             self::sendSmtpEmail($emailConfig);
    //             break;
    //     }
    //     //save the message and recipient in the database

    // }

    public static function queueEmail(EmailConfig $emailConfig)
    {
        $emailQueueModel = new \App\Models\EmailQueueModel();
        $emailQueueLogModel = new \App\Models\EmailQueueLogModel();

        $queueData = [
            'to_email' => $emailConfig->to,
            'from_email' => $emailConfig->sender,
            'subject' => $emailConfig->subject,
            'message' => $emailConfig->message,
            'cc' => $emailConfig->cc,
            'bcc' => $emailConfig->bcc,
            'attachment_path' => $emailConfig->attachments,
            'status' => 'pending',
            'priority' => 2, // Default to medium priority
            'scheduled_at' => null // Send immediately
        ];

        // Insert into queue
        $emailId = $emailQueueModel->queueEmail($queueData);

        // Log the initial status
        $emailQueueLogModel->logStatusChange($emailId, 'pending', 'Email queued');

        return $emailId;
    }


    public static function sendEmail(EmailConfig $emailConfig, $emailId = null)
    {
        $emailQueueModel = new \App\Models\EmailQueueModel();
        $emailQueueLogModel = new \App\Models\EmailQueueLogModel();
        if (!$emailId) {
            $emailId = self::queueEmail($emailConfig);
        }
        $method = getenv('EMAIL_METHOD');
        $result = null;

        // Send via appropriate method
        try {
            switch ($method) {
                case 'brevo':
                    $result = self::sendBrevoEmail($emailConfig);
                    break;
                default:
                    $result = self::sendSmtpEmail($emailConfig);
                    break;
            }
            $message = $result === false ? "Sending failed" : $result ?? 'Email sent successfully';
            ;
            log_message('info', $message);
            // Update status to sent. we have to assume that the email was sent successfully. the actual sending is done by the selected method
            //and can only be verified by checking the logs of the email service provider

            $emailQueueModel->updateStatus($emailId, 'sent', $message);
            $emailQueueLogModel->logStatusChange($emailId, 'sent', $message);

        } catch (Exception $e) {
            log_message('error', $e->getMessage());
            $emailQueueModel->updateStatus($emailId, 'failed', $e->getMessage());
            $emailQueueLogModel->logStatusChange($emailId, 'failed', $e->getMessage());

        }
        return true;
    }

    // Method to actually send the email (called by cron job or queue process)
    public static function processQueuedEmail($queuedEmail)
    {
        $emailQueueModel = new \App\Models\EmailQueueModel();
        $emailQueueLogModel = new \App\Models\EmailQueueLogModel();

        // Update status to processing
        $emailQueueModel->updateStatus($queuedEmail['id'], 'processing');
        $emailQueueLogModel->logStatusChange($queuedEmail['id'], 'processing', 'Processing email');

        try {
            // Create EmailConfig object from queue data
            $emailConfig = new EmailConfig(
                $queuedEmail['message'],
                $queuedEmail['subject'],
                $queuedEmail['to_email'],
                $queuedEmail['from_email'],
                $queuedEmail['cc'],
                $queuedEmail['bcc'],
                $queuedEmail['attachment_path']
            );
            self::sendEmail($emailConfig, $queuedEmail['id']);

            return true;
        } catch (\Throwable $th) {
            // Log error and update status
            $errorMessage = $th->getMessage();
            log_message('error', 'Email sending failed: ' . $errorMessage);

            // Update status to failed
            $emailQueueModel->updateStatus($queuedEmail['id'], 'failed', $errorMessage);
            $emailQueueLogModel->logStatusChange($queuedEmail['id'], 'failed', 'Failed: ' . $errorMessage);

            return false;
        }
    }
}
