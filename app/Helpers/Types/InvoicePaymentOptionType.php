<?php
namespace App\Helpers\Types;

class InvoicePaymentOptionType
{

    public function __construct(
        public ?string $invoiceUuid,
        public string $methodName
    ) {

    }

    public function toArray(): array
    {
        return [
            'invoice_uuid' => $this->invoiceUuid,
            'method_name' => $this->methodName
        ];
    }

    /**
     * create a letter object with its criteria from a typical request object
     * @param object{invoiceUuid: ?string, methodName: string} $object
     * @return InvoicePaymentOptionType
     */
    public function createFromRequest($object)
    {
        if (!property_exists($object, 'methodName') || $object->methodName === null || trim($object->methodName) === '') {
            throw new \InvalidArgumentException('method name code cannot be empty');
        }

        $this->invoiceUuid = $object->invoiceUuid;
        $this->methodName = $object->methodName;
        return $this;

    }
}