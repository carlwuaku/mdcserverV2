<?php

use CodeIgniter\Test\CIUnitTestCase;
use Config\App;
use Config\Services;
use Tests\Support\Libraries\ConfigReader;
use App\Helpers\Utils;
/**
 * @internal
 */
final class UtilsTest extends CIUnitTestCase
{

    public function testGetLicenseRenewalStageValidation()
    {
        $fields = [
            [
                "label" => "Renewal Date",
                "name" => "renewal_date",
                "hint" => "",
                "options" => [],
                "type" => "date",
                "value" => "",
                "required" => true
            ],
            [
                "label" => "Expiry Date",
                "name" => "expiry",
                "hint" => "",
                "options" => [],
                "type" => "date",
                "value" => "",
                "required" => true
            ],
            [
                "label" => "Status",
                "name" => "status",
                "hint" => "",
                "options" => [],
                "type" => "text",
                "value" => "",
                "required" => false
            ]
        ];
        $utils = new Utils();
        $result = $utils->getRulesFromFormGeneratorFields($fields);
        $this->assertIsArray($result);
        $this->assertArrayHasKey("renewal_date", $result);
        $this->assertArrayHasKey("expiry", $result);
        $this->assertArrayHasKey("status", $result);
    }

    public function testGenerateQRCode()
    {
        $utils = new Utils();
        $result = $utils->generateQRCode("https://www.google.com", true, 'myqr');
        //output the result
        echo $result;
        $this->assertFileExists(WRITEPATH . QRCODES_ASSETS_FOLDER . DIRECTORY_SEPARATOR . "myqr.png");
    }
}