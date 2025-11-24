<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddEmailVerificationEmailSettings extends Migration
{
    public function up()
    {
        $this->db->table("settings")->insert([
            "class" => "General",
            "key" => "user_email_verification_template",
            "value" => '
<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f9f9f9;">
    <div style="background-color: #ffffff; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
        <h2 style="color: #333; margin-bottom: 20px;">Email Verification</h2>

        <p style="color: #555; line-height: 1.6;">Dear {{name}},</p>

        <p style="color: #555; line-height: 1.6;">
            Thank you for registering with our platform. To complete your registration, please verify your email address by entering the verification code below:
        </p>

        <div style="background-color: #f5f5f5; padding: 20px; margin: 20px 0; border-radius: 6px; text-align: center;">
            <p style="margin: 0 0 10px 0; color: #666; font-size: 14px;">Your Verification Code:</p>
            <h1 style="margin: 0; color: #667eea; font-size: 36px; letter-spacing: 8px; font-weight: bold;">{{token}}</h1>
        </div>

        <p style="color: #555; line-height: 1.6;">
            This code will expire in {{expiration_hours}} hours. If you did not create an account, please ignore this email.
        </p>

        <p style="color: #555; line-height: 1.6;">
            For security reasons, do not share this code with anyone.
        </p>

        <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #e0e0e0;">
            <p style="color: #888; font-size: 12px; margin: 0;">
                This is an automated message. Please do not reply to this email.
            </p>
        </div>
    </div>
</div>
            ',
            "type" => "string",
            "control_type" => "textarea",
            "description" => "Email template for guest email verification. Available placeholders: {{name}}, {{token}}, {{expiration_hours}}"

        ]);
        $this->db->table("settings")->insert([
            "class" => "General",
            "key" => "user_email_verification_subject",
            "value" => 'Verify Your Email Address - {{token}}',
            "type" => "string",
            "control_type" => "text",
            "description" => "Subject line for guest email verification emails. Available placeholders: {{name}}, {{token}}, {{expiration_hours}}"

        ]);
    }

    public function down()
    {
        $this->db->table("settings")->where("key", "user_email_verification_template")->delete();
        $this->db->table("settings")->where("key", "user_email_verification_subject")->delete();
    }
}
