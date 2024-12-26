<?php

namespace Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;
use Config\Services;
use \App\Helpers\TemplateEngine;

class TemplateEngineTest extends CIUnitTestCase
{
    private TemplateEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();

        // Initialize with test resource and settings loaders
        $this->engine = new TemplateEngine(
            // Test resource loader
            function ($id) {
                return "<img src='test-resource-{$id}.jpg'>";
            },
            // Test settings loader
            function ($key) {
                $settings = [
                    'COMPANY_NAME' => 'Test Company',
                    'SUPPORT_EMAIL' => 'support@test.com',
                    'PHONE' => '1234567890'
                ];
                return $settings[$key] ?? '';
            }
        );
    }

    public function testBasicVariableReplacement()
    {
        $template = "Hello [name], welcome to [company]!";
        $data = [
            'name' => 'John',
            'company' => 'Acme Corp'
        ];

        $result = $this->engine->process($template, $data);
        $this->assertEquals("Hello John, welcome to Acme Corp!", $result);
    }

    public function testDateFieldDetection()
    {
        $template = "Created: [created_at]\nUpdated: [updated_at]\nBirthday: [birth_date]";
        $data = [
            'created_at' => '2024-01-01 14:30:00',
            'updated_at' => '2024-03-15 09:45:00',
            'birth_date' => '1990-05-15'
        ];

        $result = $this->engine->process($template, $data);

        // Assert each date is properly formatted
        $this->assertMatchesRegularExpression('/Created: \d{1,2}(st|nd|rd|th) [A-Za-z]+ \d{4}/', $result);
        $this->assertMatchesRegularExpression('/Updated: \d{1,2}(st|nd|rd|th) [A-Za-z]+ \d{4}/', $result);
        $this->assertMatchesRegularExpression('/Birthday: \d{1,2}(st|nd|rd|th) [A-Za-z]+ \d{4}/', $result);
    }

    public function testCustomDateFormat()
    {
        $this->engine->setDefaultDateFormat('Y-m-d');

        $template = "Date: [created_at]";
        $data = ['created_at' => '2024-01-01 14:30:00'];

        $result = $this->engine->process($template, $data);
        $this->assertEquals("Date: 2024-01-01", $result);
    }

    public function testDateTransformations()
    {
        $template = "Normal: [date1]\nCustom: [date2::date_transform||d/m/Y]\nAdd: [date3::date_add||1 year]";
        $data = [
            'date1' => '2024-01-01',
            'date2' => '2024-01-01',
            'date3' => '2024-01-01'
        ];

        $result = $this->engine->process($template, $data);

        $this->assertStringContainsString("Normal: 1st January 2024", $result);
        $this->assertStringContainsString("Custom: 01/01/2024", $result);
        $this->assertStringContainsString("Add: 2025-01-01", $result);
    }

    public function testResourceReplacement()
    {
        $template = "Logo: {{resId:123}}";
        $result = $this->engine->process($template, []);

        $this->assertEquals("Logo: <img src='test-resource-123.jpg'>", $result);
    }

    public function testSettingsReplacement()
    {
        $template = 'Contact $_COMPANY_NAME_$ at $_SUPPORT_EMAIL_$ or $_PHONE_$';
        $result = $this->engine->process($template, []);

        $this->assertEquals(
            "Contact Test Company at support@test.com or 1234567890",
            $result
        );
    }

    public function testCustomTransformers()
    {
        $this->engine->addTransformer('reverse', function ($value) {
            return strrev($value);
        });

        $template = "Normal: [text]\nReversed: [text::reverse]";
        $data = ['text' => 'Hello'];

        $result = $this->engine->process($template, $data);
        $this->assertEquals("Normal: Hello\nReversed: olleH", $result);
    }

    public function testMissingVariables()
    {
        $template = "Name: [name]\nMissing: [missing]";
        $data = ['name' => 'John'];

        $result = $this->engine->process($template, $data);
        $this->assertEquals("Name: John\nMissing: ", $result);
    }

    public function testInvalidDates()
    {
        $template = "Date: [created_at]";
        $data = ['created_at' => 'not-a-date'];

        $result = $this->engine->process($template, $data);
        $this->assertEquals("Date: not-a-date", $result);
    }

    public function testCustomDatePattern()
    {
        $this->engine->addDatePattern('/^custom_date_.*$/i');
        $this->engine->setDefaultDateFormat('Y-m-d');

        $template = "Custom: [custom_date_field]";
        $data = ['custom_date_field' => '2024-01-01 14:30:00'];

        $result = $this->engine->process($template, $data);
        $this->assertEquals("Custom: 2024-01-01", $result);
    }

    public function testComplexTemplate()
    {
        $template = "Dear [name],\n\n" .
            "Your account was created on [created_at] and last updated on [updated_at].\n" .
            'Company: $_COMPANY_NAME_$\n' .
            "Signature: {{resId:456}}\n" .
            'Contact us at: $_SUPPORT_EMAIL_$\n' .
            "Next billing date: [billing_date::date_transform||d/m/Y]";

        $data = [
            'name' => 'John Doe',
            'created_at' => '2024-01-01 10:00:00',
            'updated_at' => '2024-03-15 15:30:00',
            'billing_date' => '2024-04-01'
        ];

        $result = $this->engine->process($template, $data);

        $this->assertStringContainsString("Dear John Doe", $result);
        $this->assertStringContainsString("Company: Test Company", $result);
        $this->assertStringContainsString("<img src='test-resource-456.jpg'>", $result);
        $this->assertStringContainsString("support@test.com", $result);
        $this->assertStringContainsString("01/04/2024", $result);
    }

    public function testMultipleResourcesAndSettings()
    {
        $template = '{{resId:1}} $_COMPANY_NAME_$ {{resId:2}} $_SUPPORT_EMAIL_$ {{resId:3}}';
        $result = $this->engine->process($template, []);

        $this->assertStringContainsString("test-resource-1.jpg", $result);
        $this->assertStringContainsString("test-resource-2.jpg", $result);
        $this->assertStringContainsString("test-resource-3.jpg", $result);
        $this->assertStringContainsString("Test Company", $result);
        $this->assertStringContainsString("support@test.com", $result);
    }
}