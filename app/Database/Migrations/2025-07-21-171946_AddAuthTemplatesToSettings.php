<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddAuthTemplatesToSettings extends Migration
{
    public function up()
    {
        $this->db->table("settings")->insert([
            "class" => "General",
            "key" => "forgot_password_email_template",
            "value" => '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset Request</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
            line-height: 1.6;
            color: #333333;
            background-color: #f4f4f4;
        }
        
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        
        .header {
            background-color: #ffffff;
            padding: 30px 40px 20px 40px;
            text-align: center;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .logo-placeholder {
            width: 120px;
            height: 60px;
            background-color: #f0f0f0;
            border: 2px dashed #cccccc;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
            color: #888888;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .company-name {
            font-size: 24px;
            font-weight: 600;
            color: #2c3e50;
            margin: 0;
        }
        
        .content {
            padding: 40px;
        }
        
        .greeting {
            font-size: 18px;
            margin-bottom: 20px;
            color: #2c3e50;
        }
        
        .message {
            font-size: 16px;
            line-height: 1.7;
            margin-bottom: 30px;
            color: #555555;
        }
        
        .reset-code {
            background-color: #f8f9fa;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            margin: 30px 0;
        }
        
        .code-label {
            font-size: 14px;
            color: #6c757d;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .code {
            font-size: 32px;
            font-weight: 700;
            color: #007bff;
            font-family: "Courier New", monospace;
            letter-spacing: 4px;
            margin: 0;
        }
        
        .expiry-notice {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px 20px;
            margin: 20px 0;
            border-radius: 0 4px 4px 0;
        }
        
        .expiry-notice p {
            margin: 0;
            font-size: 14px;
            color: #856404;
        }
        
        .security-notice {
            background-color: #d1ecf1;
            border-left: 4px solid #17a2b8;
            padding: 15px 20px;
            margin: 20px 0;
            border-radius: 0 4px 4px 0;
        }
        
        .security-notice p {
            margin: 0;
            font-size: 14px;
            color: #0c5460;
        }
        
        .footer {
            background-color: #f8f9fa;
            padding: 30px 40px;
            border-top: 1px solid #e0e0e0;
            text-align: center;
        }
        
        .footer-content {
            font-size: 12px;
            color: #6c757d;
            line-height: 1.5;
        }
        
        .footer-content p {
            margin: 5px 0;
        }
        
        .footer-links {
            margin-top: 15px;
        }
        
        .footer-links a {
            color: #007bff;
            text-decoration: none;
            margin: 0 10px;
        }
        
        .footer-links a:hover {
            text-decoration: underline;
        }
        
        /* Mobile responsiveness */
        @media only screen and (max-width: 600px) {
            .email-container {
                width: 100% !important;
                margin: 0 !important;
            }
            
            .header, .content, .footer {
                padding: 20px !important;
            }
            
            .company-name {
                font-size: 20px;
            }
            
            .code {
                font-size: 24px;
                letter-spacing: 2px;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- Header -->
        <div class="header">
            <div class="logo-placeholder">
                [LOGO HERE]
            </div>
            <h1 class="company-name">[#COMPANY_NAME#]</h1>
        </div>
        
        <!-- Main Content -->
        <div class="content">
            <div class="greeting">
                Hello [#username#],
            </div>
            
            <div class="message">
                You have requested to reset your password. Please enter this code to proceed.
            </div>
            
            <div class="reset-code">
                <div class="code-label">Your Reset Code</div>
                <div class="code">[#code#]</div>
            </div>
            
            <div class="expiry-notice">
                <p><strong>⏰ Important:</strong> This token will expire in 15 minutes.</p>
            </div>
            
            <div class="security-notice">
                <p><strong>🔒 Security Notice:</strong> If you did not request this password reset, please ignore this email. Your account remains secure.</p>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="footer">
            <div class="footer-content">
                <p><strong>[#COMPANY_NAME#] Security Team</strong></p>
                <p>This is an automated message. Please do not reply to this email.</p>
                <p>© 2025 [#COMPANY_NAME#]. All rights reserved.</p>
                
                <div class="footer-links">
                    <a href="#">Privacy Policy</a>
                    <a href="#">Terms of Service</a>
                    <a href="#">Contact Support</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>',
            "type" => "string",
            "control_type" => "textarea",
        ]);

        $this->db->table("settings")->insert([
            "class" => "General",
            "key" => "2fa_email_template",
            "value" => '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Two-Factor Authentication Setup</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
            line-height: 1.6;
            color: #333333;
            background-color: #f4f4f4;
        }
        
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        
        .header {
            background-color: #ffffff;
            padding: 30px 40px 20px 40px;
            text-align: center;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .logo-placeholder {
            width: 120px;
            height: 60px;
            background-color: #f0f0f0;
            border: 2px dashed #cccccc;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
            color: #888888;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .company-name {
            font-size: 24px;
            font-weight: 600;
            color: #2c3e50;
            margin: 0;
        }
        
        .content {
            padding: 40px;
        }
        
        .greeting {
            font-size: 18px;
            margin-bottom: 20px;
            color: #2c3e50;
        }
        
        .intro-message {
            font-size: 16px;
            line-height: 1.7;
            margin-bottom: 30px;
            color: #555555;
        }
        
        .security-badge {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            padding: 15px 20px;
            border-radius: 8px;
            text-align: center;
            margin: 20px 0;
        }
        
        .security-badge h3 {
            margin: 0 0 5px 0;
            font-size: 18px;
        }
        
        .security-badge p {
            margin: 0;
            font-size: 14px;
            opacity: 0.9;
        }
        
        .setup-section {
            background-color: #f8f9fa;
            border-radius: 12px;
            padding: 30px;
            margin: 30px 0;
            text-align: center;
        }
        
        .section-title {
            font-size: 20px;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 20px;
        }
        
        .step-number {
            background-color: #007bff;
            color: white;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 10px;
        }
        
        .step-content {
            text-align: left;
            margin: 20px 0;
        }
        
        .step-item {
            display: flex;
            align-items: flex-start;
            margin-bottom: 20px;
            padding: 15px;
            background-color: white;
            border-radius: 8px;
            border-left: 4px solid #007bff;
        }
        
        .step-text {
            flex: 1;
        }
        
        .step-text h4 {
            margin: 0 0 8px 0;
            color: #2c3e50;
            font-size: 16px;
        }
        
        .step-text p {
            margin: 0;
            font-size: 14px;
            color: #666666;
        }
        
        .qr-section {
            background-color: white;
            border: 2px solid #e9ecef;
            border-radius: 12px;
            padding: 30px;
            margin: 30px 0;
            text-align: center;
        }
        
        .qr-placeholder {
            width: 200px;
            height: 200px;
            background-color: #f8f9fa;
            border: 2px dashed #007bff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin: 20px auto;
            color: #007bff;
            font-size: 14px;
            text-align: center;
            border-radius: 8px;
        }
        
        .backup-codes {
            background-color: #fff3cd;
            border: 2px solid #ffc107;
            border-radius: 8px;
            padding: 20px;
            margin: 30px 0;
        }
        
        .backup-codes h4 {
            color: #856404;
            margin: 0 0 15px 0;
            font-size: 16px;
        }
        
        .backup-codes p {
            color: #856404;
            font-size: 14px;
            margin: 0 0 15px 0;
        }
        
        .code-list {
            background-color: white;
            padding: 15px;
            border-radius: 4px;
            font-family: "Courier New", monospace;
            font-size: 14px;
            text-align: center;
            color: #495057;
            border: 1px solid #ffc107;
        }
        
        .app-icons {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin: 20px 0;
        }
        
        .app-icon {
            width: 60px;
            height: 60px;
            background-color: #f0f0f0;
            border: 2px solid #cccccc;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #666666;
            font-size: 10px;
            text-align: center;
            text-decoration: none;
        }
        
        .security-notice {
            background-color: #d1ecf1;
            border-left: 4px solid #17a2b8;
            padding: 20px;
            margin: 30px 0;
            border-radius: 0 8px 8px 0;
        }
        
        .security-notice h4 {
            margin: 0 0 10px 0;
            color: #0c5460;
            font-size: 16px;
        }
        
        .security-notice p {
            margin: 0;
            font-size: 14px;
            color: #0c5460;
        }
        
        .footer {
            background-color: #f8f9fa;
            padding: 30px 40px;
            border-top: 1px solid #e0e0e0;
            text-align: center;
        }
        
        .footer-content {
            font-size: 12px;
            color: #6c757d;
            line-height: 1.5;
        }
        
        .footer-content p {
            margin: 5px 0;
        }
        
        .footer-links {
            margin-top: 15px;
        }
        
        .footer-links a {
            color: #007bff;
            text-decoration: none;
            margin: 0 10px;
        }
        
        .footer-links a:hover {
            text-decoration: underline;
        }
        
        /* Mobile responsiveness */
        @media only screen and (max-width: 600px) {
            .email-container {
                width: 100% !important;
                margin: 0 !important;
            }
            
            .header, .content, .footer {
                padding: 20px !important;
            }
            
            .setup-section {
                padding: 20px !important;
            }
            
            .qr-section {
                padding: 20px !important;
            }
            
            .qr-placeholder {
                width: 150px;
                height: 150px;
            }
            
            .step-item {
                flex-direction: column;
                text-align: center;
            }
            
            .step-number {
                margin: 0 0 10px 0;
            }
            
            .app-icons {
                flex-direction: column;
                align-items: center;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- Header -->
        <div class="header">
            <div class="logo-placeholder">
                [LOGO HERE]
            </div>
            <h1 class="company-name">[#COMPANY_NAME#]</h1>
        </div>
        
        <!-- Main Content -->
        <div class="content">
            <div class="greeting">
                Hello [#username#],
            </div>
            
            <div class="intro-message">
                You\'ve successfully enabled Two-Factor Authentication (2FA) for your account. This adds an extra layer of security to protect your account from unauthorized access.
            </div>
            
            <div class="security-badge">
                <h3>🔒 Your Account is Now More Secure</h3>
                <p>Two-Factor Authentication has been activated</p>
            </div>
            
            <!-- Setup Instructions -->
            <div class="setup-section">
                <div class="section-title">Complete Your Setup</div>
                
                <div class="step-content">
                    <div class="step-item">
                        <span class="step-number">1</span>
                        <div class="step-text">
                            <h4>Download an Authenticator App</h4>
                            <p>Install Microsoft Authenticator or Google Authenticator on your mobile device</p>
                        </div>
                    </div>
                    
                    <div class="step-item">
                        <span class="step-number">2</span>
                        <div class="step-text">
                            <h4>Scan the QR Code</h4>
                            <p>Open your authenticator app and scan the QR code below to add your account</p>
                        </div>
                    </div>
                    
                    <div class="step-item">
                        <span class="step-number">3</span>
                        <div class="step-text">
                            <h4>Save Your Backup Codes</h4>
                            <p>Store the backup codes in a safe place - you\'ll need them if you lose access to your phone</p>
                        </div>
                    </div>
                </div>
                
                <div class="app-icons">
                    <div class="app-icon">
                        Microsoft<br>Authenticator
                    </div>
                    <div class="app-icon">
                        Google<br>Authenticator
                    </div>
                </div>
            </div>
            
            <!-- QR Code Section -->
            <div class="qr-section">
                <h3 style="color: #2c3e50; margin-bottom: 15px;">Scan This QR Code</h3>
                <p style="color: #666666; margin-bottom: 20px;">Use your authenticator app to scan this code</p>
                
                <div class="qr-placeholder">
                    [#QR_CODE#]<br>
                    QR Code Goes Here
                </div>
                
                <p style="color: #666666; font-size: 12px; margin-top: 15px;">
                    Can\'t scan? Manual setup key: <strong>[#SETUP_KEY#]</strong>
                </p>
            </div>
            
            <!-- Backup Codes -->
            <div class="backup-codes">
                <h4>🔑 Your Backup Recovery Codes</h4>
                <p>Save these codes in a secure location. Each code can only be used once.</p>
                <div class="code-list">
                    [#BACKUP_CODE_1#]&nbsp;&nbsp;&nbsp;&nbsp;[#BACKUP_CODE_2#]<br>
                    [#BACKUP_CODE_3#]&nbsp;&nbsp;&nbsp;&nbsp;[#BACKUP_CODE_4#]<br>
                    [#BACKUP_CODE_5#]&nbsp;&nbsp;&nbsp;&nbsp;[#BACKUP_CODE_6#]<br>
                    [#BACKUP_CODE_7#]&nbsp;&nbsp;&nbsp;&nbsp;[#BACKUP_CODE_8#]
                </div>
            </div>
            
            <!-- Security Notice -->
            <div class="security-notice">
                <h4>🛡️ Important Security Information</h4>
                <p>From now on, you\'ll need both your password and a code from your authenticator app to sign in. If you lose access to your authenticator app, use one of your backup codes above. Never share your backup codes or authenticator app with anyone.</p>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="footer">
            <div class="footer-content">
                <p><strong>[#COMPANY_NAME#] Security Team</strong></p>
                <p>This is an automated message. Please do not reply to this email.</p>
                <p>Need help? Visit our support center or contact us directly.</p>
                <p>© 2025 [#COMPANY_NAME#]. All rights reserved.</p>
                
                <div class="footer-links">
                    <a href="#">2FA Help Guide</a>
                    <a href="#">Security Center</a>
                    <a href="#">Contact Support</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>',
            "type" => "string",
            "control_type" => "textarea",
        ]);
    }

    public function down()
    {
        //
    }
}
