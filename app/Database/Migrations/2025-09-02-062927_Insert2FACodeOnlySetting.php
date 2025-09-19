<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class Insert2FACodeOnlySetting extends Migration
{
    public function up()
    {
        $this->db->table("settings")->insert([
            "class" => "General",
            "key" => "two_factor_authentication_setup_email_template_code_only",
            "value" => '<p>Hello [display_name],</p>
            <p>Please follow the instructions below to setup two factor authentication:</p>
            <ol>
            <li>Download the Google Authenticator or Microsoft Authenticator app on your mobile device.</li>
            <li>Open the app and click the + button to add a new account.</li>
            <li>This will open the QR code scanner. Click the button below it to enter the code manually</li>
            <li>Enter the name as "Portal" and type in the secret code: [SECRET]</li>
            <li>Submit.</li>
            </ol>
            <p>Once you have completed the setup, you will be able to use two factor authentication to log in to your account.</p>
            <p>If you did not request this change, please contact us immediately.</p>',
            "type" => "string",
            "control_type" => "textarea",
            "description" => "Variables are placed in square brackets []. Example: [display_name] will be replaced with the user's full name. The available variables are: [display_name], [secret], [institution_logo], [institution_name], [institution_website], [institution_phone]"

        ]);
    }

    public function down()
    {
        //
    }
}
