<?php
namespace App\Helpers;

class Utils{
    /**
     * Compares two objects and returns the keys with different values.
     *
     * @param mixed $oldObject The first object to compare
     * @param mixed $newObject The second object to compare
     * @return array The keys with different values between the two objects
     */
    public static function compareObjects($oldObject, $newObject) {
        if(is_array($oldObject)){
            $oldObject = (object) $oldObject;
        }
        
        if(is_array($newObject)){
            $newObject = (object) $newObject;
        }
        $obj1Vars = get_object_vars($oldObject);
        $obj2Vars = get_object_vars($newObject);
        $differentKeys = [];
        foreach ($obj2Vars as $key => $value) {
            if (isset($obj1Vars[$key]) && $obj1Vars[$key] !== $value) {
                $differentKeys[] = $key.": $value -> {$obj1Vars[$key]}";
            }
        }
    
        return $differentKeys;
    }

    public static function sendEmail($content, $subject, $to, $cc = null, $bcc = null, $attachments = null)
    {
        $email = \Config\Services::email();

        // Set the email configuration
        $config['protocol'] = 'smtp';
        $config['SMTPHost'] = 'mail.yourdomain.com';
        $config['SMTPUser'] = 'external@mdcghana.org';
        $config['SMTPPass'] = 'C0KcoYva_;Hu';
        $config['SMTPPort'] = 587; // or 465
        $config['SMTPCrypto'] = 'tls'; // or 'ssl'
        $config['mailType'] = 'html';
        $config['charset'] = 'utf-8';

        // Initialize the email library with the configuration
        $email->initialize($config);

        // Set the email properties
        $email->setFrom('external@mdcghana.org', 'Mdc Ghana');
        $email->setTo($to);
        $email->setSubject($subject);
        $email->setMessage($content);

        // Add CC recipients if provided
        if (!is_null($cc)) {
            $email->setCC($cc);
        }

        // Add BCC recipients if provided
        if (!is_null($bcc)) {
            $email->setBCC($bcc);
        }

        // Add attachments if provided
        if (!is_null($attachments)) {
            foreach ($attachments as $attachment) {
                $email->attach($attachment);
            }
        }

        // Send the email
        if ($email->send()) {
            echo 'Email successfully sent';
        } else {
            $data = $email->printDebugger(['headers']);
            print_r($data);
        }
    }
}
 