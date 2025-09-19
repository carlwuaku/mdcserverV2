<?php
namespace App\Helpers\Types;

class SettingsType
{
    public string $class;
    public string $key;
    public array|string|object|bool $value;
    public string $type;
    public ?string $context;
    public string $control_type;
    public ?string $description;

    public function __construct(
        string $class,
        string $key,
        mixed $value,
        string $type,
        string $context,
        string $control_type,
        string $description
    ) {
        $this->class = $class;
        $this->key = $key;
        $this->value = $value;
        $this->type = $type;
        $this->context = $context;
        $this->control_type = $control_type;
        $this->description = $description;
    }
}