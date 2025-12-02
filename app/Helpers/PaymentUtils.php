<?php
namespace App\Helpers;
use App\Exceptions\DefaultInvoiceFeeQuantityNotSetException;
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
                log_message("info", "No criteria for default invoice item: " . json_encode($defaultInvoiceItem));
                $defaultServiceCodes = $defaultInvoiceItem["feeServiceCodes"];
                continue;
            }
            $criteria = $defaultInvoiceItem["criteria"];
            //check if the object matches the criteria
            log_message("info", "Checking criteria for default invoice item: " . json_encode($defaultInvoiceItem));

            if (self::criteriaMatch($criteria, $object)) {
                $serviceCodes = $defaultInvoiceItem["feeServiceCodes"];
                log_message("info", "Matched criteria for default invoice item: " . json_encode($defaultInvoiceItem));
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
            $quantity = self::parseDefaultFeeQuantity($serviceCode['quantity'], $object);
            $total = $quantity * $rate;
            $fees[] = new PaymentInvoiceItemType(null, $serviceCode['service_code'], $fee['name'], $serviceCode['quantity'], $rate, $total);
        }
        return $fees;
    }

    /**
     * @param string $uuid The uuid of the invoice
     * @return array|null The details of the invoice with the given uuid. null if no such invoice exists.
     */
    public static function getInvoiceDetails($uuid): array
    {
        $model = new \App\Models\Payments\InvoiceModel();
        return $model->where("uuid", $uuid)->first();
    }

    /**
     * Parses the default fee quantity given a string or int and an object.
     * If the quantity is an int, it is returned as is.
     * If the quantity is a string, it is checked if it is a field in the object.
     * If it is, and the value is an array, the count of the array is returned.
     * If it is, and the value is not an array, the value is cast to an int and returned.
     * If the quantity is not an int or a string, or if it is a string but not a field in the object, an exception is thrown.
     * @param string|int $quantity The default fee quantity
     * @param array $object The object to check for the default fee quantity field
     * @return int The parsed default fee quantity
     * @throws \App\Exceptions\DefaultInvoiceFeeQuantityNotSetException If the default fee quantity is invalid
     */
    private static function parseDefaultFeeQuantity(string|int $quantity, array $object)
    {
        //if it's an int, return it. if its a string, check if it's a field in the object. if it is, and the value is an array return the count of the array. else return the value if it can be cast to an int. else throw an exception
        if (is_int($quantity)) {
            return $quantity;
        }
        if (is_string($quantity)) {
            if (isset($object[$quantity])) {
                if (is_array($object[$quantity])) {
                    return count($object[$quantity]);
                }
                return (int) $object[$quantity];
            }
        }
        throw new DefaultInvoiceFeeQuantityNotSetException("Invalid default fee quantity: $quantity");
    }
}