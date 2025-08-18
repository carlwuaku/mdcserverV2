<?php
namespace App\Helpers\Types;

class FieldType
{
    public string $label;
    public string $name;
    public string $hint;
    public array $options;
    public string $type;
    public string $value;
    public bool $required;

    public function __construct(
        string $label,
        string $name,
        string $hint = '',
        array $options = [],
        string $type = 'text',
        string $value = '',
        bool $required = false
    ) {
        $this->label = $label;
        $this->name = $name;
        $this->hint = $hint;
        $this->options = $options;
        $this->type = $type;
        $this->value = $value;
        $this->required = $required;
    }

    public function toArray(): array
    {
        return [
            'label' => $this->label,
            'name' => $this->name,
            'hint' => $this->hint,
            'options' => $this->options,
            'type' => $this->type,
            'value' => $this->value,
            'required' => $this->required
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['label'] ?? '',
            $data['name'] ?? '',
            $data['hint'] ?? '',
            $data['options'] ?? [],
            $data['type'] ?? 'text',
            $data['value'] ?? '',
            $data['required'] ?? false
        );
    }
}