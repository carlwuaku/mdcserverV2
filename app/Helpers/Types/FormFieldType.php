<?php
namespace App\Helpers\Types;

class FormFieldType
{
    public string $label;
    public string $name;
    public string $type;
    public string $hint;
    public array $options; // Array of associative arrays with 'key' and 'value'
    public ?string $className = null;
    public ?string $subtitle = null;
    public mixed $value;
    public bool $required;
    public ?string $api_url = null;
    public ?string $apiLabelProperty = null;
    public ?string $apiKeyProperty = null;
    public ?string $apiType = null; // 'search', 'select', or 'datalist'
    public ?string $selection_mode = null; // 'single' or 'multiple'
    public ?string $apiInitialValue = null;
    public ?string $apiModule = null;
    public ?string $onChange = null;
    public ?int $minLength = null;
    public ?int $maxLength = null;
    public ?array $customValidation = null; // Array with 'fieldsMatch' key containing array of field names
    public ?string $disabled = null; // '' or 'disabled'
    public ?string $hidden = null; // '' or 'hidden'
    public ?bool $showOnly = null;
    public ?string $key = null;
    public ?string $customTemplate = null;
    public ?string $placeholder = null;
    public ?string $file_types = null; // Comma separated file types
    public ?string $assetType = null;

    public function __construct(
        string $label,
        string $name,
        string $type,
        string $hint,
        array $options,
        mixed $value,
        bool $required,
        ?string $className = null,
        ?string $subtitle = null,
        ?string $api_url = null,
        ?string $apiLabelProperty = null,
        ?string $apiKeyProperty = null,
        ?string $apiType = null,
        ?string $selection_mode = null,
        ?string $apiInitialValue = null,
        ?string $apiModule = null,
        ?callable $onChange = null,
        ?int $minLength = null,
        ?int $maxLength = null,
        ?array $customValidation = null,
        ?string $disabled = null,
        ?string $hidden = null,
        ?bool $showOnly = null,
        ?string $key = null,
        ?string $customTemplate = null,
        ?string $placeholder = null,
        ?string $file_types = null,
        ?string $assetType = null
    ) {
        $this->label = $label;
        $this->name = $name;
        $this->type = $type;
        $this->hint = $hint;
        $this->options = $options;
        $this->value = $value;
        $this->required = $required;
        $this->className = $className;
        $this->subtitle = $subtitle;
        $this->api_url = $api_url;
        $this->apiLabelProperty = $apiLabelProperty;
        $this->apiKeyProperty = $apiKeyProperty;
        $this->apiType = $apiType;
        $this->selection_mode = $selection_mode;
        $this->apiInitialValue = $apiInitialValue;
        $this->apiModule = $apiModule;
        $this->onChange = $onChange;
        $this->minLength = $minLength;
        $this->maxLength = $maxLength;
        $this->customValidation = $customValidation;
        $this->disabled = $disabled;
        $this->hidden = $hidden;
        $this->showOnly = $showOnly;
        $this->key = $key;
        $this->customTemplate = $customTemplate;
        $this->placeholder = $placeholder;
        $this->file_types = $file_types;
        $this->assetType = $assetType;
    }

    /**
     * Create a FormGenerator instance from an array
     */
    public static function fromArray(array $data): self
    {
        return new self(
            label: $data['label'],
            name: $data['name'],
            type: $data['type'],
            hint: $data['hint'],
            options: $data['options'] ?? [],
            value: $data['value'],
            required: $data['required'] ?? false,
            className: $data['className'] ?? null,
            subtitle: $data['subtitle'] ?? null,
            api_url: $data['api_url'] ?? null,
            apiLabelProperty: $data['apiLabelProperty'] ?? null,
            apiKeyProperty: $data['apiKeyProperty'] ?? null,
            apiType: $data['apiType'] ?? null,
            selection_mode: $data['selection_mode'] ?? null,
            apiInitialValue: $data['apiInitialValue'] ?? null,
            apiModule: $data['apiModule'] ?? null,
            onChange: $data['onChange'] ?? null,
            minLength: $data['minLength'] ?? null,
            maxLength: $data['maxLength'] ?? null,
            customValidation: $data['customValidation'] ?? null,
            disabled: $data['disabled'] ?? null,
            hidden: $data['hidden'] ?? null,
            showOnly: $data['showOnly'] ?? null,
            key: $data['key'] ?? null,
            customTemplate: $data['customTemplate'] ?? null,
            placeholder: $data['placeholder'] ?? null,
            file_types: $data['file_types'] ?? null,
            assetType: $data['assetType'] ?? null
        );
    }

    /**
     * Convert the FormGenerator instance to an array
     */
    public function toArray(): array
    {
        return [
            'label' => $this->label,
            'name' => $this->name,
            'type' => $this->type,
            'hint' => $this->hint,
            'options' => $this->options,
            'className' => $this->className,
            'subtitle' => $this->subtitle,
            'value' => $this->value,
            'required' => $this->required,
            'api_url' => $this->api_url,
            'apiLabelProperty' => $this->apiLabelProperty,
            'apiKeyProperty' => $this->apiKeyProperty,
            'apiType' => $this->apiType,
            'selection_mode' => $this->selection_mode,
            'apiInitialValue' => $this->apiInitialValue,
            'apiModule' => $this->apiModule,
            'onChange' => $this->onChange,
            'minLength' => $this->minLength,
            'maxLength' => $this->maxLength,
            'customValidation' => $this->customValidation,
            'disabled' => $this->disabled,
            'hidden' => $this->hidden,
            'showOnly' => $this->showOnly,
            'key' => $this->key,
            'customTemplate' => $this->customTemplate,
            'placeholder' => $this->placeholder,
            'file_types' => $this->file_types,
            'assetType' => $this->assetType,
        ];
    }

    /**
     * Validate API type values
     */
    public function isValidApiType(): bool
    {
        return $this->apiType === null || in_array($this->apiType, ['search', 'select', 'datalist']);
    }

    /**
     * Validate selection mode values
     */
    public function isValidSelectionMode(): bool
    {
        return $this->selection_mode === null || in_array($this->selection_mode, ['single', 'multiple']);
    }

    /**
     * Check if field is disabled
     */
    public function isDisabled(): bool
    {
        return $this->disabled === 'disabled';
    }

    /**
     * Check if field is hidden
     */
    public function isHidden(): bool
    {
        return $this->hidden === 'hidden';
    }

    /**
     * Get file types as an array
     */
    public function getFileTypesArray(): array
    {
        if ($this->file_types === null) {
            return [];
        }
        return array_map('trim', explode(',', $this->file_types));
    }
}