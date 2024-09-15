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
            'replyTo' => ['name' => 'Sendinblue', 'email' => 'contact@sendinblue.com'],
            'to' => [['name' => 'Max Mustermann', 'email' => 'wuakuc@gmail.com']],
            'htmlContent' => '<html><body><h1>This is a transactional email {{params.bodyMessage}}</h1></body></html>',
            'params' => ['bodyMessage' => 'made just for you!']
        ]); // \Brevo\Client\Model\SendSmtpEmail | Values to send a transactional email

        try {
            $result = $apiInstance->sendTransacEmail($sendSmtpEmail);
            print_r($result);
        } catch (Exception $e) {
            echo 'Exception when calling TransactionalEmailsApi->sendTransacEmail: ', $e->getMessage(), PHP_EOL;
        }
    }

    public static function sendEmail(EmailConfig $emailConfig)
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
        $email->setMessage($emailConfig->content);

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
                log_message('info', 'Email sent successfully');
            } else {
                log_message('error', 'Email not sent');
                // $data = $email->printDebugger(['headers']);
                // print_r($data);
            }
        } catch (Exception $e) {
            log_message('error', 'Email not sent');
            log_message('error', $e->getMessage());
        }
    }
}
