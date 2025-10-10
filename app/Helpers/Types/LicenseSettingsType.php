<?php
namespace App\Helpers\Types;

class LicenseSettingsType
{
    public string $class;
    public string $key;
    public array $values;
    public string $type;
    public ?string $context;
    public string $control_type;
    public ?string $description;
    public string $label;

    public function __construct(
        string $class,
        string $key,
        string $label,
        array $value,
        string $type,
        string $context,
        string $control_type,
        string $description
    ) {
        $this->class = $class;
        $this->key = $key;
        $this->label = $label;
        $this->value = $value;
        $this->type = $type;
        $this->context = $context;
        $this->control_type = $control_type;
        $this->description = $description;
    }
}