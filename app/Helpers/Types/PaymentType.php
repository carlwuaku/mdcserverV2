<?php
namespace App\Helpers\Types;

class PaymentType
{

    public function __construct(
        public string $invoice_uuid,
        public string $method_name,
        public float $amount,
        public string $currency,
        public string $payment_date,
        public string $status,
        public string $reference_number,
        public ?string $notes
    ) {

    }

    public function toArray(): array
    {
        return [
            'invoice_uuid' => $this->invoice_uuid,
            'method_name' => $this->method_name,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'payment_date' => $this->payment_date,
            'status' => $this->status,
            'reference_number' => $this->reference_number,
            'notes' => $this->notes
        ];
    }

    /**
     * create a letter object with its criteria from a typical request object
     * @param object{invoiceUuid: string, method_name: string, amount: float, currency: string, payment_date: string, status: string} $object
     * @return PaymentType
     */
    public function createFromRequest($object)
    {
        if (!property_exists($object, 'invoice_uuid') || $object->invoice_uuid === null || trim($object->invoice_uuid) === '') {
            throw new \InvalidArgumentException('Invoice uuidcannot be empty');
        }
        if (!property_exists($object, 'method_name') || $object->method_name === null || trim($object->method_name) === '') {
            throw new \InvalidArgumentException('Method name cannot be empty');
        }
        if (!property_exists($object, 'amount') || $object->amount === null || $object->amount === 0) {
            throw new \InvalidArgumentException('Amount cannot be empty or 0');
        }
        if (!property_exists($object, 'currency') || $object->currency === null || trim($object->currency) === '') {
            throw new \InvalidArgumentException('Currency cannot be empty');
        }
        if (!property_exists($object, 'payment_date') || $object->payment_date === null || trim($object->payment_date) === '') {
            throw new \InvalidArgumentException('Payment date cannot be empty');
        }
        if (!property_exists($object, 'status') || $object->status === null || trim($object->status) === '') {
            throw new \InvalidArgumentException('Status cannot be empty');
        }
        if (!property_exists($object, 'reference_number') || $object->reference_number === null || trim($object->reference_number) === '') {
            throw new \InvalidArgumentException('Reference number cannot be empty');
        }

        $this->invoice_uuid = $object->invoice_uuid;
        $this->method_name = $object->method_name;
        $this->amount = $object->amount;
        $this->currency = $object->currency;
        $this->payment_date = $object->payment_date;
        $this->status = $object->status;
        $this->reference_number = $object->reference_number;
        $this->notes = property_exists($object, 'notes') ? $object->notes : null;


        return $this;

    }
}