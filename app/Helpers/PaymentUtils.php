<?php
namespace App\Helpers;
use App\Helpers\Types\PaymentInvoiceItemType;
class PaymentUtils extends Utils
{
    /**
     * Gets the payment methods defined in the app settings
     * @return array the payment methods
     */
    public static function getPaymentMethods()
    {
        return self::getPaymentSettings()["paymentMethods"];
    }

    public static function getPaymentMethodsList()
    {
        return array_keys(self::getPaymentMethods());
    }

    /**
     * Gets the default fee codes for a payment purpose given an object.
     * The object is matched against the criteria defined in the default invoice items for the given purpose.
     * If the object matches the criteria of one of the default invoice items, the fee codes for that item are returned.
     * If no match is found, the default fee codes are returned.
     * @param string $purpose the payment purpose
     * @param array $object the object to match against the criteria
     * @return array{service_code:string, quantity: int}[] the default fee codes
     */
    public static function getDefaultServiceCodes(string $purpose, array $object)
    {
        /**
         * @var array{defaultInvoiceItems: array {criteria: array {field:string, value:string[]}[], feeServiceCodes: array}[], paymentMethods: array, sourceTableName: string}
         */
        $purposes = self::getPaymentSettings()["purposes"];
        //get the config for the purpose
        if (!isset($purposes[$purpose])) {
            throw new \InvalidArgumentException("Invalid payment purpose: $purpose");
        }
        /**
         * @var array{paymentMethods: array, defaultInvoiceItems: array{criteria: array, feeServiceCodes: array}[] }
         */
        $config = $purposes[$purpose];

        if (!isset($config["defaultInvoiceItems"])) {
            throw new \InvalidArgumentException("Payment purpose: $purpose has no default invoice items");
        }
        /**
         * @var array {criteria: array {field:string, value:string[]}[], feeServiceCodes: array}[]
         */
        $defaultInvoiceItems = $config["defaultInvoiceItems"];
        $serviceCodes = [];
        $defaultServiceCodes = [];
        //we want to find the one with the best match for the object. that's the one that has the most matching criteria
        //if no matching criteria is found, we use the default invoice items. that's one with no criteria
        foreach ($defaultInvoiceItems as $defaultInvoiceItem) {
            if (!isset($defaultInvoiceItem["criteria"]) || count($defaultInvoiceItem["criteria"]) == 0) {
                $defaultServiceCodes = $defaultInvoiceItem["feeServiceCodes"];
                continue;
            }
            $criteria = $defaultInvoiceItem["criteria"];
            //check if the object matches the criteria
            if (self::criteriaMatch($criteria, $object)) {
                $serviceCodes = $defaultInvoiceItem["feeServiceCodes"];
                break;
            }
        }
        if (count($serviceCodes) == 0) {
            $serviceCodes = $defaultServiceCodes;
        }
        return $serviceCodes;

    }

    /**
     * @param string $purpose The purpose of the payment
     * @param array $object An object that contains data used to determine the default fees
     * @return PaymentInvoiceItemType[] An array of default fees to be used when creating an invoice
     * @throws \InvalidArgumentException If the payment purpose is invalid or if any of the default fees are invalid
     */
    public static function getDefaultFees(string $purpose, array $object)
    {
        $serviceCodes = self::getDefaultServiceCodes($purpose, $object);
        $fees = [];
        $feesModel = new \App\Models\Payments\FeesModel();
        foreach ($serviceCodes as $serviceCode) {
            $fee = $feesModel->where("service_code", $serviceCode['service_code'])->first();
            if (!$fee) {
                throw new \InvalidArgumentException("Invalid fee code: {$serviceCode['service_code']}");
            }
            $rate = $fee['rate'];
            $total = $serviceCode['quantity'] * $rate;
            $fees[] = new PaymentInvoiceItemType(null, $serviceCode['service_code'], $fee['name'], $serviceCode['quantity'], $rate, $total);
        }
        return $fees;
    }

    public static function generatePresetInvoices()
    {

    }
}