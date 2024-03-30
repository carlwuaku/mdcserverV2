<?php
namespace App\Helpers;

class Utils
{
    /**
     * Compares two objects and returns the keys with different values.
     *
     * @param mixed $oldObject The first object to compare
     * @param mixed $newObject The second object to compare
     * @return array The keys with different values between the two objects
     */
    public static function compareObjects($oldObject, $newObject)
    {
        if (is_array($oldObject)) {
            $oldObject = (object) $oldObject;
        }

        if (is_array($newObject)) {
            $newObject = (object) $newObject;
        }
        $obj1Vars = get_object_vars($oldObject);
        $obj2Vars = get_object_vars($newObject);
        $differentKeys = [];
        foreach ($obj2Vars as $key => $value) {
            if ($key !== "qr_code" &&isset($obj1Vars[$key]) && $obj1Vars[$key] !== $value) {
                $differentKeys[] = $key . ": {$obj1Vars[$key]} -> $value";
            }
        }

        return $differentKeys;
    }

    public static function sendEmail($content, $subject, $to,$sender, $cc = null, $bcc = null, $attachments = null)
    {
        $email = \Config\Services::email();

        // Set the email configuration
        $config['protocol'] = 'smtp';
        $config['SMTPHost'] = 'mail.mdcghana.org';
        $config['SMTPUser'] = 'external@mdcghana.org';
        $config['SMTPPass'] = '';
        $config['SMTPPort'] = 587; // or 465
        $config['SMTPCrypto'] = 'tls'; // or 'ssl'
        $config['mailType'] = 'html';
        $config['charset'] = 'utf-8';

        // Initialize the email library with the configuration
        $email->initialize($config);

        // Set the email properties
        $email->setFrom($sender, 'Mdc Ghana');
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

    /**
     * Generate renewal expiry date based on practitioner and start date.
     *
     * @param array $practitioner The practitioner details
     * @param string $startDate The start date for the renewal
     * @return string The expiry date for the renewal
     */
    public static function generateRenewalExpiryDate(array $practitioner, string $startDate): string
    {
        $year = date('Y', strtotime($startDate));
        //if expiry is empty, and $practitioner->register_type is Permanent, set to the end of the year in $data->year. if $practitioner->register_type is Temporary, set to 3 months from today. if $practitioner->register_type is Provisional, set to a year from the start date in $year
        if ($practitioner['register_type'] === "Temporary") {
            // add 3 months to the date in $startDate
            return date("Y-m-d", strtotime($startDate . " +3 months"));
        } elseif ($practitioner['register_type'] === "Provisional") {
            return date("Y-m-d", strtotime($startDate . " +1 year"));
        } else
            return date("Y-m-d", strtotime($year . "-12-31"));

    }
}
