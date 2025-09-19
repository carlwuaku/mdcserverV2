<?php
namespace App\Helpers\Types;

class PaymentMethodType
{
    public string $label;
    public string $type;
    public bool $isActive;
    public string $onStart;
    public string $onComplete;
    public string $description;
    public string $logo;

    public function __construct(
        string $label,
        string $type,
        bool $isActive,
        string $onStart,
        string $onComplete,
        string $description,
        string $logo
    ) {
        $this->label = $label;
        $this->type = $type;
        $this->isActive = $isActive;
        $this->onStart = $onStart;
        $this->onComplete = $onComplete;
        $this->description = $description;
        //the logo needs to be a url, if not one already
        if (!filter_var($logo, FILTER_VALIDATE_URL)) {
            $this->logo = base_url($logo);
        } else {
            $this->logo = $logo;
        }
    }

    public static function fromArray(array $data)
    {
        return new self(
            $data['label'] ?? '',
            $data['type'] ?? '',
            $data['isActive'] ?? false,
            $data['onStart'] ?? '',
            $data['onComplete'] ?? '',
            $data['description'] ?? '',
            $data['logo'] ?? ''
        );
    }
}