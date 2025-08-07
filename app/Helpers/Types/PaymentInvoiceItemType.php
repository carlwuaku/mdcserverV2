<?php
namespace App\Helpers\Types;

class PaymentInvoiceItemType
{

    public function __construct(
        public ?string $invoice_uuid,
        public string $service_code,
        public ?string $name,
        public int $quantity,
        public float $unit_price,
        public ?float $line_total
    ) {

    }

    public function toArray(): array
    {
        return [
            'invoice_uuid' => $this->invoice_uuid,
            'service_code' => $this->service_code,
            'name' => $this->name,
            'quantity' => $this->quantity,
            'unit_price' => $this->unit_price,
            'line_total' => $this->line_total
        ];
    }

    /**
     * create a letter object with its criteria from a typical request object
     * @param object{invoiceUuid: ?string, service_code: string, name: ?string, quantity: int, unit_price: float} $object
     * @return PaymentInvoiceItemType
     */
    public function createFromRequest($object)
    {
        if (!property_exists($object, 'service_code') || $object->service_code === null || trim($object->service_code) === '') {
            throw new \InvalidArgumentException('Fee service code cannot be empty');
        }
        if (!property_exists($object, 'quantity') || $object->quantity === null || $object->quantity === 0) {
            throw new \InvalidArgumentException('Quantity cannot be empty or 0');
        }
        if (!property_exists($object, 'unit_price') || $object->unit_price === null || $object->unit_price === 0) {
            throw new \InvalidArgumentException('Unit price cannot be empty or 0');
        }
        if (!property_exists($object, 'name') || $object->unit_price === null || $object->unit_price === 0) {
            throw new \InvalidArgumentException('Fee name cannot be empty');
        }

        $this->invoice_uuid = property_exists($object, 'invoice_uuid') ? $object->invoice_uuid : null;
        $this->service_code = $object->service_code;
        $this->name = $object->name;
        $this->quantity = $object->quantity;
        $this->unit_price = $object->unit_price;
        $this->line_total = $this->quantity * $this->unit_price;



        return $this;

    }
}