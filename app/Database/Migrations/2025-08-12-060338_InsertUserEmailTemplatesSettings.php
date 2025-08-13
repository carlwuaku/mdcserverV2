<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class InsertUserEmailTemplatesSettings extends Migration
{
    public function up()
    {
        $this->forge->addColumn('settings', [
            'description' => [
                'type' => 'TEXT',
                'null' => true,
                'default' => null
            ]
        ]);

        $this->db->table("settings")->insert([
            "class" => "General",
            "key" => "reset_password_token_timeout",
            "value" => '15',
            "type" => "string",
            "control_type" => "text",
            "description" => "The number of minutes before a password reset token expires."
        ]);

        $this->db->table("settings")->insert([
            "class" => "General",
            "key" => "reset_password_email_template",
            "value" => '
        <div style="display:flex; flex-direction: column; width: 500px;">
        
        <div style="text-align: center;">
            <div class="logo-placeholder">
                <img src="[institution_logo]" height="50" alt="Logo">
            </div>
            <h1 class="company-name">[institution_name]</h1>
        </div>
        
        <!-- Main Content -->
        <div >
           <p>Hello [display_name],</p>
            <p>You have requested to change your password. Enter this token to reset your password: <b>[token]</b></p>
            <p>This token expires in [timeout] minutes.</p>
            
        </div>
        
        <!-- Footer -->
        <div class="footer">
            <div style="text-align: center;">
                <p><strong>[institution_name] </strong></p>
                <p>This is an automated message. Please do not reply to this email.</p>
                <p>Need help? Visit our website for help or contact us directly.</p>
                <p>© 2025 [institution_name]. All rights reserved.</p>
                
                <div style="display:flex; gap: 10px; justify-content: center; align-items: center; flex-wrap: wrap">
                    <a href="[institution_website]">Our website</a>
                    <a href="[institution_phone]">Contact Support</a>
                </div>
            </div>
        </div>
        </div>
    ',
            "type" => "string",
            "control_type" => "textarea",
            "description" => "Variables are placed in square brackets []. Example: [display_name] will be replaced with the user's full name. The available variables are: [display_name], [token], [timeout], [institution_logo], [institution_name], [institution_website], [institution_phone]"
        ]);

        $this->db->table("settings")->insert([
            "class" => "General",
            "key" => "reset_password_email_subject",
            "value" => 'Reset your password',
            "type" => "string",
            "control_type" => "textarea",
            "description" => ""

        ]);
        ////////////////////

        $this->db->table("settings")->insert([
            "class" => "General",
            "key" => "reset_password_confirmation_email_template",
            "value" => '
        <div style="display:flex; flex-direction: column; width: 500px;">
        
        <div style="text-align: center;">
            <div class="logo-placeholder">
                <img src="[institution_logo]" height="50" alt="Logo">
            </div>
            <h1 class="company-name">[institution_name]</h1>
        </div>
        
        <!-- Main Content -->
        <div >
           <p>Hello [display_name],</p>
            <p>Your password has been reset successfully.</p>
            <p>You can now log in with your new password.</p>
            <p>If you did not request this change, please contact us immediately.</p>
            
        </div>
        
        <!-- Footer -->
        <div class="footer">
            <div style="text-align: center;">
                <p><strong>[institution_name] </strong></p>
                <p>This is an automated message. Please do not reply to this email.</p>
                <p>Need help? Visit our website for help or contact us directly.</p>
                <p>© 2025 [institution_name]. All rights reserved.</p>
                
                <div style="display:flex; gap: 10px; justify-content: center; align-items: center; flex-wrap: wrap">
                    <a href="[institution_website]">Our website</a>
                    <a href="[institution_phone]">Contact Support</a>
                </div>
            </div>
        </div>
        </div>
    ',
            "type" => "string",
            "control_type" => "textarea",
            "description" => "Variables are placed in square brackets []. Example: [display_name] will be replaced with the user's full name. The available variables are: [display_name],  [institution_logo], [institution_name], [institution_website], [institution_phone]"
        ]);

        $this->db->table("settings")->insert([
            "class" => "General",
            "key" => "reset_password_confirmation_email_subject",
            "value" => 'Reset your password',
            "type" => "string",
            "control_type" => "textarea",
            "description" => ""

        ]);
        ////////////////////

        $this->db->table("settings")->insert([
            "class" => "General",
            "key" => "two_factor_authentication_setup_email_template",
            "value" => '
        <div style="display:flex; flex-direction: column; width: 500px;">
        
        <div style="text-align: center;">
            <div class="logo-placeholder">
                <img src="[institution_logo]" height="50" alt="Logo">
            </div>
            <h1 class="company-name">[institution_name]</h1>
        </div>
        
        <!-- Main Content -->
        <div >
           <p>Hello [display_name],</p>
            <p>Please follow the instructions below to setup two factor authentication:</p>
            <ol>
            <li>Download the Google Authenticator or Microsoft Authenticator app on your mobile device.</li>
            <li>Open the app and scan the QR code below.</li>
            <li>Enter the verification code generated by the app.</li>
            <li>Click "Enable Two-Factor Authentication".</li>
            </ol>
            <p>Once you have completed the setup, you will be able to use two factor authentication to log in to your account.</p>
            <p>If you did not request this change, please contact us immediately.</p>
        </div>
        
        <!-- Footer -->
        <div class="footer">
            <div style="text-align: center;">
                <p><strong>[institution_name] </strong></p>
                <p>This is an automated message. Please do not reply to this email.</p>
                <p>Need help? Visit our website for help or contact us directly.</p>
                <p>© 2025 [institution_name]. All rights reserved.</p>
                
                <div style="display:flex; gap: 10px; justify-content: center; align-items: center; flex-wrap: wrap">
                    <a href="[institution_website]">Our website</a>
                    <a href="[institution_phone]">Contact Support</a>
                </div>
            </div>
        </div>
        </div>
    ',
            "type" => "string",
            "control_type" => "textarea",
            "description" => "Variables are placed in square brackets []. Example: [display_name] will be replaced with the user's full name. The available variables are: [display_name], [qr_code_url], [secret], [institution_logo], [institution_name], [institution_website], [institution_phone]"
        ]);

        $this->db->table("settings")->insert([
            "class" => "General",
            "key" => "two_factor_authentication_setup_email_subject",
            "value" => 'Set up two factor authentication',
            "type" => "string",
            "control_type" => "textarea",
            "description" => ""

        ]);
        ////////////////////

        $this->db->table("settings")->insert([
            "class" => "General",
            "key" => "two_factor_authentication_verification_email_template",
            "value" => '
        <div style="display:flex; flex-direction: column; width: 500px;">
        
        <div style="text-align: center;">
            <div class="logo-placeholder">
                <img src="[institution_logo]" height="50" alt="Logo">
            </div>
            <h1 class="company-name">[institution_name]</h1>
        </div>
        
        <!-- Main Content -->
        <div >
           <p>Hello [display_name],</p>
            <p>Your two factor authentication setup is now complete and your account is now protected. </p>

            <p>Please note that you\'ll need to use two factor authentication to log in to your account in the future.</p>
            
        </div>
        
        <!-- Footer -->
        <div class="footer">
            <div style="text-align: center;">
                <p><strong>[institution_name] </strong></p>
                <p>This is an automated message. Please do not reply to this email.</p>
                <p>Need help? Visit our website for help or contact us directly.</p>
                <p>© 2025 [institution_name]. All rights reserved.</p>
                
                <div style="display:flex; gap: 10px; justify-content: center; align-items: center; flex-wrap: wrap">
                    <a href="[institution_website]">Our website</a>
                    <a href="[institution_phone]">Contact Support</a>
                </div>
            </div>
        </div>
        </div>
    ',
            "type" => "string",
            "control_type" => "textarea",
            "description" => "Variables are placed in square brackets []. Example: [display_name] will be replaced with the user's full name. The available variables are: [display_name], [institution_logo], [institution_name], [institution_website], [institution_phone]"
        ]);

        $this->db->table("settings")->insert([
            "class" => "General",
            "key" => "two_factor_authentication_verification_email_subject",
            "value" => 'Two-factor authentication set up complete',
            "type" => "string",
            "control_type" => "textarea",
            "description" => ""

        ]);
        ////////////////////

        $this->db->table("settings")->insert([
            "class" => "General",
            "key" => "two_factor_authentication_disabled_email_template",
            "value" => '
        <div style="display:flex; flex-direction: column; width: 500px;">
        
        <div style="text-align: center;">
            <div class="logo-placeholder">
                <img src="[institution_logo]" height="50" alt="Logo">
            </div>
            <h1 class="company-name">[institution_name]</h1>
        </div>
        
        <!-- Main Content -->
        <div >
           <p>Hello [display_name],</p>
            <p>Two-factor authentication has been disabled for your account. </p>
            <p>We strongly recommend that you enable two-factor authentication to ensure the security of your account.</p>
            
        </div>
        
        <!-- Footer -->
        <div class="footer">
            <div style="text-align: center;">
                <p><strong>[institution_name] </strong></p>
                <p>This is an automated message. Please do not reply to this email.</p>
                <p>Need help? Visit our website for help or contact us directly.</p>
                <p>© 2025 [institution_name]. All rights reserved.</p>
                
                <div style="display:flex; gap: 10px; justify-content: center; align-items: center; flex-wrap: wrap">
                    <a href="[institution_website]">Our website</a>
                    <a href="[institution_phone]">Contact Support</a>
                </div>
            </div>
        </div>
        </div>
    ',
            "type" => "string",
            "control_type" => "textarea",
            "description" => "Variables are placed in square brackets []. Example: [display_name] will be replaced with the user's full name. The available variables are: [display_name], [institution_logo], [institution_name], [institution_website], [institution_phone]"
        ]);

        $this->db->table("settings")->insert([
            "class" => "General",
            "key" => "two_factor_authentication_disabled_email_subject",
            "value" => 'Two-factor authentication disabled',
            "type" => "string",
            "control_type" => "textarea",
            "description" => ""

        ]);
        ////////////////////

        $this->db->table("settings")->insert([
            "class" => "General",
            "key" => "user_admin_added_email_template",
            "value" => '
        <div style="display:flex; flex-direction: column; width: 500px;">
        
        <div style="text-align: center;">
            <div class="logo-placeholder">
                <img src="[institution_logo]" height="50" alt="Logo">
            </div>
            <h1 class="company-name">[institution_name]</h1>
        </div>
        
        <!-- Main Content -->
        <div >
           <p>Hello [display_name],</p>
            <p>Your account has been added to the [institution_name] administration system. On your first login, please click the \'forgot password\' link to set your password.</p>
            <p>If you have any questions or need help, please contact us the system administrator.</p>
            
        </div>
        
        <!-- Footer -->
        <div class="footer">
            <div style="text-align: center;">
                <p><strong>[institution_name] </strong></p>
                <p>This is an automated message. Please do not reply to this email.</p>
                <p>Need help? Visit our website for help or contact us directly.</p>
                <p>© 2025 [institution_name]. All rights reserved.</p>
                
                <div style="display:flex; gap: 10px; justify-content: center; align-items: center; flex-wrap: wrap">
                    <a href="[institution_website]">Our website</a>
                    <a href="[institution_phone]">Contact Support</a>
                </div>
            </div>
        </div>
        </div>
    ',
            "type" => "string",
            "control_type" => "textarea",
            "description" => "Variables are placed in square brackets []. Example: [display_name] will be replaced with the user's full name. The available variables are: [display_name],  [institution_logo], [institution_name], [institution_website], [institution_phone]"
        ]);

        $this->db->table("settings")->insert([
            "class" => "General",
            "key" => "user_admin_added_email_subject",
            "value" => 'Admin account added',
            "type" => "string",
            "control_type" => "textarea",
            "description" => ""

        ]);
        ////////////////////
        $this->db->table("settings")->insert([
            "class" => "General",
            "key" => "user_license_added_email_template",
            "value" => '
        <div style="display:flex; flex-direction: column; width: 500px;">
        
        <div style="text-align: center;">
            <div class="logo-placeholder">
                <img src="[institution_logo]" height="50" alt="Logo">
            </div>
            <h1 class="company-name">[institution_name]</h1>
        </div>
        
        <!-- Main Content -->
        <div >
           <p>Hello [display_name],</p>
            <p>Your account has been added to the [institution_name] portal. You can log in here: [portal_url].</p>
            <p>Username: [username]</p>
            <p>On your first visit to the portal, please click the \'forgot password\' link to set your password.</p>
            <p>You will also be asked to set up two-factor authentication to secure your account.</p>
            
        </div>
        
        <!-- Footer -->
        <div class="footer">
            <div style="text-align: center;">
                <p><strong>[institution_name] </strong></p>
                <p>This is an automated message. Please do not reply to this email.</p>
                <p>Need help? Visit our website for help or contact us directly.</p>
                <p>© 2025 [institution_name]. All rights reserved.</p>
                
                <div style="display:flex; gap: 10px; justify-content: center; align-items: center; flex-wrap: wrap">
                    <a href="[institution_website]">Our website</a>
                    <a href="[institution_phone]">Contact Support</a>
                </div>
            </div>
        </div>
        </div>
    ',
            "type" => "string",
            "control_type" => "textarea",
            "description" => "Variables are placed in square brackets []. Example: [display_name] will be replaced with the user's full name. The available variables are: [display_name], [portal_url], [username], [institution_logo], [institution_name], [institution_website], [institution_phone]"
        ]);


        $this->db->table("settings")->insert([
            "class" => "General",
            "key" => "user_license_added_email_subject",
            "value" => 'Portal account added',
            "type" => "string",
            "control_type" => "textarea",
            "description" => ""

        ]);
        ////////////////////

        $this->db->table("settings")->insert([
            "class" => "General",
            "key" => "user_cpd_added_email_template",
            "value" => '
        <div style="display:flex; flex-direction: column; width: 500px;">
        
        <div style="text-align: center;">
            <div class="logo-placeholder">
                <img src="[institution_logo]" height="50" alt="Logo">
            </div>
            <h1 class="company-name">[institution_name]</h1>
        </div>
        
        <!-- Main Content -->
        <div >
           <p>Hello [display_name],</p>
            <p>Your account has been added to the [institution_name] portal. You can log in here: [portal_url].</p>
            <p>Username: [username]</p>
            <p>On your first visit to the portal, please click the \'forgot password\' link to set your password.</p>
            <p>You will also be asked to set up two-factor authentication to secure your account.</p>
            
        </div>
        
        <!-- Footer -->
        <div class="footer">
            <div style="text-align: center;">
                <p><strong>[institution_name] </strong></p>
                <p>This is an automated message. Please do not reply to this email.</p>
                <p>Need help? Visit our website for help or contact us directly.</p>
                <p>© 2025 [institution_name]. All rights reserved.</p>
                
                <div style="display:flex; gap: 10px; justify-content: center; align-items: center; flex-wrap: wrap">
                    <a href="[institution_website]">Our website</a>
                    <a href="[institution_phone]">Contact Support</a>
                </div>
            </div>
        </div>
        </div>
    ',
            "type" => "string",
            "control_type" => "textarea",
            "description" => "Variables are placed in square brackets []. Example: [display_name] will be replaced with the user's full name. The available variables are: [display_name], [portal_url], [username], [institution_logo], [institution_name], [institution_website], [institution_phone]"
        ]);

        $this->db->table("settings")->insert([
            "class" => "General",
            "key" => "user_cpd_added_email_subject",
            "value" => 'Portal account added',
            "type" => "string",
            "control_type" => "textarea",
            "description" => ""

        ]);
        ////////////////////

        $this->db->table("settings")->insert([
            "class" => "General",
            "key" => "user_student_added_email_template",
            "value" => '
        <div style="display:flex; flex-direction: column; width: 500px;">
        
        <div style="text-align: center;">
            <div class="logo-placeholder">
                <img src="[institution_logo]" height="50" alt="Logo">
            </div>
            <h1 class="company-name">[institution_name]</h1>
        </div>
        
        <!-- Main Content -->
        <div >
           <p>Hello [display_name],</p>
            <p>Your account has been added to the [institution_name] portal. You can log in here: [portal_url].</p>
            <p>Username: [username]</p>
            <p>On your first visit to the portal, please click the \'forgot password\' link to set your password.</p>
            <p>You will also be asked to set up two-factor authentication to secure your account.</p>
            
        </div>
        
        <!-- Footer -->
        <div class="footer">
            <div style="text-align: center;">
                <p><strong>[institution_name] </strong></p>
                <p>This is an automated message. Please do not reply to this email.</p>
                <p>Need help? Visit our website for help or contact us directly.</p>
                <p>© 2025 [institution_name]. All rights reserved.</p>
                
                <div style="display:flex; gap: 10px; justify-content: center; align-items: center; flex-wrap: wrap">
                    <a href="[institution_website]">Our website</a>
                    <a href="[institution_phone]">Contact Support</a>
                </div>
            </div>
        </div>
        </div>
    ',
            "type" => "string",
            "control_type" => "textarea",
            "description" => "Variables are placed in square brackets []. Example: [display_name] will be replaced with the user's full name. The available variables are: [display_name], [portal_url], [username], [institution_logo], [institution_name], [institution_website], [institution_phone]"
        ]);

        $this->db->table("settings")->insert([
            "class" => "General",
            "key" => "user_student_added_email_subject",
            "value" => 'Portal account added',
            "type" => "string",
            "control_type" => "textarea",
            "description" => ""

        ]);
        ////////////////////

        $this->db->table("settings")->insert([
            "class" => "General",
            "key" => "user_guest_added_email_template",
            "value" => '
        <div style="display:flex; flex-direction: column; width: 500px;">
        
        <div style="text-align: center;">
            <div class="logo-placeholder">
                <img src="[institution_logo]" height="50" alt="Logo">
            </div>
            <h1 class="company-name">[institution_name]</h1>
        </div>
        
        <!-- Main Content -->
        <div >
           <p>Hello [display_name],</p>
            <p>Your account has been added to the [institution_name] portal. You can log in here: [portal_url].</p>
            <p>Username: [username]</p>
            <p>On your first visit to the portal, please click the \'forgot password\' link to set your password.</p>
            <p>You will also be asked to set up two-factor authentication to secure your account.</p>
            
        </div>
        
        <!-- Footer -->
        <div class="footer">
            <div style="text-align: center;">
                <p><strong>[institution_name] </strong></p>
                <p>This is an automated message. Please do not reply to this email.</p>
                <p>Need help? Visit our website for help or contact us directly.</p>
                <p>© 2025 [institution_name]. All rights reserved.</p>
                
                <div style="display:flex; gap: 10px; justify-content: center; align-items: center; flex-wrap: wrap">
                    <a href="[institution_website]">Our website</a>
                    <a href="[institution_phone]">Contact Support</a>
                </div>
            </div>
        </div>
        </div>
    ',
            "type" => "string",
            "control_type" => "textarea",
            "description" => "Variables are placed in square brackets []. Example: [display_name] will be replaced with the user's full name. The available variables are: [display_name], [portal_url], [username], [institution_logo], [institution_name], [institution_website], [institution_phone]"
        ]);

        $this->db->table("settings")->insert([
            "class" => "General",
            "key" => "user_guest_added_email_subject",
            "value" => 'Portal account added',
            "type" => "string",
            "control_type" => "textarea",
            "description" => ""

        ]);
        ////////////////////

        $this->db->table("settings")->insert([
            "class" => "General",
            "key" => "user_exam_candidate_added_email_template",
            "value" => '
        <div style="display:flex; flex-direction: column; width: 500px;">
        
        <div style="text-align: center;">
            <div class="logo-placeholder">
                <img src="[institution_logo]" height="50" alt="Logo">
            </div>
            <h1 class="company-name">[institution_name]</h1>
        </div>
        
        <!-- Main Content -->
        <div >
           <p>Hello [display_name],</p>
            <p>Your account has been added to the [institution_name] portal. You can log in here: [portal_url].</p>
            <p>Username: [username]</p>
            <p>On your first visit to the portal, please click the \'forgot password\' link to set your password.</p>
            <p>You will also be asked to set up two-factor authentication to secure your account.</p>
            
        </div>
        
        <!-- Footer -->
        <div class="footer">
            <div style="text-align: center;">
                <p><strong>[institution_name] </strong></p>
                <p>This is an automated message. Please do not reply to this email.</p>
                <p>Need help? Visit our website for help or contact us directly.</p>
                <p>© 2025 [institution_name]. All rights reserved.</p>
                
                <div style="display:flex; gap: 10px; justify-content: center; align-items: center; flex-wrap: wrap">
                    <a href="[institution_website]">Our website</a>
                    <a href="[institution_phone]">Contact Support</a>
                </div>
            </div>
        </div>
        </div>
    ',
            "type" => "string",
            "control_type" => "textarea",
            "description" => "Variables are placed in square brackets []. Example: [display_name] will be replaced with the user's full name. The available variables are: [display_name], [portal_url], [username], [institution_logo], [institution_name], [institution_website], [institution_phone]"
        ]);

        $this->db->table("settings")->insert([
            "class" => "General",
            "key" => "user_housemanship_facility_added_email_subject",
            "value" => 'Portal account added',
            "type" => "string",
            "control_type" => "textarea",
            "description" => ""

        ]);
        ////////////////////

        $this->db->table("settings")->insert([
            "class" => "General",
            "key" => "user_housemanship_facility_added_email_template",
            "value" => '
        <div style="display:flex; flex-direction: column; width: 500px;">
        
        <div style="text-align: center;">
            <div class="logo-placeholder">
                <img src="[institution_logo]" height="50" alt="Logo">
            </div>
            <h1 class="company-name">[institution_name]</h1>
        </div>
        
        <!-- Main Content -->
        <div >
           <p>Hello [display_name],</p>
            <p>Your account has been added to the [institution_name] portal. You can log in here: [portal_url].</p>
            <p>Username: [username]</p>
            <p>On your first visit to the portal, please click the \'forgot password\' link to set your password.</p>
            <p>You will also be asked to set up two-factor authentication to secure your account.</p>
            
        </div>
        
        <!-- Footer -->
        <div class="footer">
            <div style="text-align: center;">
                <p><strong>[institution_name] </strong></p>
                <p>This is an automated message. Please do not reply to this email.</p>
                <p>Need help? Visit our website for help or contact us directly.</p>
                <p>© 2025 [institution_name]. All rights reserved.</p>
                
                <div style="display:flex; gap: 10px; justify-content: center; align-items: center; flex-wrap: wrap">
                    <a href="[institution_website]">Our website</a>
                    <a href="[institution_phone]">Contact Support</a>
                </div>
            </div>
        </div>
        </div>
    ',
            "type" => "string",
            "control_type" => "textarea",
            "description" => "Variables are placed in square brackets []. Example: [display_name] will be replaced with the user's full name. The available variables are: [display_name], [portal_url], [username], [institution_logo], [institution_name], [institution_website], [institution_phone]"
        ]);

        $this->db->table("settings")->insert([
            "class" => "General",
            "key" => "user_exam_candidate_added_email_subject",
            "value" => 'Portal account added',
            "type" => "string",
            "control_type" => "textarea",
            "description" => ""

        ]);
        ////////////////////
    }

    public function down()
    {
        //
    }
}
