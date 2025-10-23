<?php
namespace App\Helpers\Types;

class LicenseSettingValueType
{
    public string|array|bool $value;
    /**
     * a list of criteria which must all be met for the value to be valid
     * @var CriteriaType[]
     */
    public array $criteria;

    public static function fromArray(array $data): self
    {
        $instance = new self();
        $instance->value = $data['value'] ?? '';
        $instance->criteria = array_map(
            fn($criteriaData) => CriteriaType::fromArray($criteriaData),
            $data['criteria'] ?? []
        );
        return $instance;
    }

    public function toArray(): array
    {
        return [
            'value' => $this->value,
            'criteria' => array_map(
                fn(CriteriaType $criteria) => $criteria->toArray(),
                $this->criteria
            ),
        ];
    }

    public static function fromJson(string $json): self
    {
        return self::fromArray(json_decode($json, true));
    }

    public function toJson(): string
    {
        return json_encode($this->toArray());
    }
}

class SystemSettingsType
{
    public string $class;
    public string $key;
    /**
     * 
     * @var LicenseSettingValueType[]
     */
    public array $value;
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
        ?string $context,
        ?string $control_type,
        ?string $description
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

    public static function fromArray(array $data): self
    {
        return new self(
            $data['class'] ?? '',
            $data['key'] ?? '',
            $data['label'] ?? '',
            array_map(
                fn($valueData) => LicenseSettingValueType::fromArray($valueData),
                $data['value'] ?? []
            ),
            $data['type'] ?? '',
            $data['context'] ?? null,
            $data['control_type'] ?? '',
            $data['description'] ?? null
        );
    }

    public function toArray(): array
    {
        return [
            'class' => $this->class,
            'key' => $this->key,
            'label' => $this->label,
            'value' => array_map(
                fn(LicenseSettingValueType $value) => $value->toArray(),
                $this->value
            ),
            'type' => $this->type,
            'context' => $this->context,
            'control_type' => $this->control_type,
            'description' => $this->description,
        ];
    }

    public static function fromJson(string $json): self
    {
        return self::fromArray(json_decode($json, true));
    }

    public function toJson(): string
    {
        return json_encode($this->toArray());
    }

}