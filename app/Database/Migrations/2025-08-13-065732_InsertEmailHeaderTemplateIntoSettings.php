<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class InsertEmailHeaderTemplateIntoSettings extends Migration
{
    public function up()
    {
        $this->db->table("settings")->insert([
            "class" => "General",
            "key" => "email_header_and_footer_template",
            "value" => '
            <!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Template</title>
    <style>
        /* Reset styles for email clients */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333333;
            background-color: #f4f4f4;
        }
        
        /* Main container */
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        
        /* Header styles */
        .header {
            background-color: #2c3e50;
            color: #ffffff;
            padding: 20px;
            text-align: center;
        }
        
        .logo-container {
            margin-bottom: 10px;
        }
        
        .logo-placeholder {
            width: 120px;
            height: 60px;
            background-color: #34495e;
            border: 2px dashed #7f8c8d;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            color: #bdc3c7;
        }
        
        .company-name {
            font-size: 24px;
            font-weight: bold;
            margin: 0;
        }
        
        .content {
            padding: 30px 20px;
            min-height: 200px;
            background-color: #ffffff;
        }
        
        .content-placeholder {
            color: #7f8c8d;
            text-align: center;
            font-style: italic;
            padding: 50px 20px;
            border: 2px dashed #ecf0f1;
            background-color: #fafafa;
        }
        
        /* Footer styles */
        .footer {
            background-color: #34495e;
            color: #ecf0f1;
            padding: 20px;
            text-align: center;
        }
        
        .footer-links {
            margin-bottom: 15px;
        }
        
        .footer-links a {
            color: #3498db;
            text-decoration: none;
            margin: 0 15px;
            font-size: 14px;
        }
        
        .footer-links a:hover {
            text-decoration: underline;
        }
        
        .social-links {
            margin-bottom: 10px;
        }
        
        .social-links a {
            display: inline-block;
            margin: 0 8px;
            color: #ecf0f1;
            text-decoration: none;
            font-size: 16px;
            width: 32px;
            height: 32px;
            line-height: 32px;
            background-color: #2c3e50;
            border-radius: 50%;
            transition: background-color 0.3s ease;
        }
        
        .social-links a:hover {
            background-color: #3498db;
        }
        
        .footer-text {
            font-size: 12px;
            color: #95a5a6;
            margin-top: 10px;
        }
        
        /* Responsive design */
        @media only screen and (max-width: 600px) {
            .email-container {
                width: 100% !important;
            }
            
            .header, .content, .footer {
                padding: 15px !important;
            }
            
            .company-name {
                font-size: 20px;
            }
            
            .footer-links a {
                display: block;
                margin: 5px 0;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <div class="logo-container">               
                 <img src="[institution_logo]" alt="Company Logo" style="max-width: 120px; height: auto;"> 
            </div>
            <h1 class="company-name">[institution_name]</h1>
        </div>
        
        <div class="content">
            <div class="content-placeholder">
                [email_content]
            </div>
        </div>
        
        <!-- Footer Section -->
        <div class="footer">
            <div class="footer-links">
                <a href="[institution_website]">Visit Our Website</a>
                <a href="tel:[institution_phone]">Contact Us</a>
            </div>
            
            <div class="social-links">
                <a href="https://facebook.com/yourcompany" title="Facebook">f</a>
                <a href="https://twitter.com/yourcompany" title="Twitter">t</a>
                <a href="https://instagram.com/yourcompany" title="Instagram">i</a>
                <a href="https://linkedin.com/company/yourcompany" title="LinkedIn">in</a>
            </div>
            
            <div class="footer-text">
                <p>&copy; [current_year] [institution_name]. All rights reserved.</p>
                <p>[institution_address]</p>
            </div>
        </div>
    </div>
</body>
</html>
            ',
            "type" => "string",
            "control_type" => "textarea",
            "description" => ""

        ]);
    }

    public function down()
    {
        //
    }
}
