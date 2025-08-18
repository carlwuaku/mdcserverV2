<?php
namespace App\Helpers\Types;

class RenewalStageType
{

    public string $label;
    public array $allowedTransitions;
    public array $fields; // Array of FieldType objects
    public string $permission;
    public bool $printable;
    public bool $onlineCertificatePrintable;
    public string $title;
    public bool $activeChildren;
    public string $url;
    public array $urlParams;
    public string $icon;
    public array $children;
    public string $description;
    public string $apiCountUrl;
    public string $apiCountText;

    /**
     * various actions to be ran when the stage is first set. i.e when a renewal's status is changed to this
     * @var ApplicationStageType[]
     */
    public array $actions;

    public function __construct(
        string $label = '',
        array $allowedTransitions = [],
        array $fields = [],
        string $permission = '',
        bool $printable = false,
        bool $onlineCertificatePrintable = false,
        string $title = '',
        bool $activeChildren = false,
        string $url = '',
        array $urlParams = [],
        string $icon = '',
        array $children = [],
        string $description = '',
        string $apiCountUrl = '',
        string $apiCountText = '',
        array $actions = []
    ) {
        $this->label = $label;
        $this->allowedTransitions = $allowedTransitions;
        $this->fields = $fields;
        $this->permission = $permission;
        $this->printable = $printable;
        $this->onlineCertificatePrintable = $onlineCertificatePrintable;
        $this->title = $title;
        $this->activeChildren = $activeChildren;
        $this->url = $url;
        $this->urlParams = $urlParams;
        $this->icon = $icon;
        $this->children = $children;
        $this->description = $description;
        $this->apiCountUrl = $apiCountUrl;
        $this->apiCountText = $apiCountText;
        $this->actions = $actions;
    }

    /**
     * Add a field to the workflow status
     */
    public function addField(FieldType $field): self
    {
        $this->fields[] = $field;
        return $this;
    }

    /**
     * Get a field by name
     */
    public function getField(string $name): ?FieldType
    {
        foreach ($this->fields as $field) {
            if ($field->name === $name) {
                return $field;
            }
        }
        return null;
    }

    /**
     * Check if a transition is allowed
     */
    public function canTransitionTo(string $status): bool
    {
        return in_array($status, $this->allowedTransitions);
    }

    /**
     * Get all required fields
     */
    public function getRequiredFields(): array
    {
        return array_filter($this->fields, function (FieldType $field) {
            return $field->required;
        });
    }

    /**
     * Validate that all required fields have values
     */
    public function validateRequiredFields(): array
    {
        $errors = [];
        foreach ($this->getRequiredFields() as $field) {
            if (empty($field->value)) {
                $errors[] = "FieldType '{$field->label}' is required";
            }
        }
        return $errors;
    }

    /**
     * Set field value by name
     */
    public function setFieldValue(string $fieldName, string $value): bool
    {
        $field = $this->getField($fieldName);
        if ($field) {
            $field->value = $value;
            return true;
        }
        return false;
    }

    /**
     * Convert to array representation
     */
    public function toArray(): array
    {
        return [
            'label' => $this->label,
            'allowedTransitions' => $this->allowedTransitions,
            'fields' => array_map(fn(FieldType $field) => $field->toArray(), $this->fields),
            'permission' => $this->permission,
            'printable' => $this->printable,
            'onlineCertificatePrintable' => $this->onlineCertificatePrintable,
            'title' => $this->title,
            'active_children' => $this->activeChildren,
            'url' => $this->url,
            'urlParams' => $this->urlParams,
            'icon' => $this->icon,
            'children' => $this->children,
            'description' => $this->description,
            'apiCountUrl' => $this->apiCountUrl,
            'apiCountText' => $this->apiCountText,
            'actions' => array_map(fn(ApplicationStageType $stage) => $stage->toArray(), $this->actions),
        ];
    }

    /**
     * Create instance from array data
     */
    public static function fromArray(array $data): self
    {
        $fields = [];
        if (isset($data['fields']) && is_array($data['fields'])) {
            $fields = array_map(fn(array $fieldData) => FieldType::fromArray($fieldData), $data['fields']);
        }

        return new self(
            $data['label'] ?? '',
            $data['allowedTransitions'] ?? [],
            $fields,
            $data['permission'] ?? '',
            $data['printable'] ?? false,
            $data['onlineCertificatePrintable'] ?? false,
            $data['title'] ?? '',
            $data['active_children'] ?? false,
            $data['url'] ?? '',
            $data['urlParams'] ?? [],
            $data['icon'] ?? '',
            $data['children'] ?? [],
            $data['description'] ?? '',
            $data['apiCountUrl'] ?? '',
            $data['apiCountText'] ?? '',
            array_map(fn(array $stageData) => ApplicationStageType::fromArray($stageData), $data['actions'] ?? [])
        );
    }

    /**
     * Create instance from JSON string
     */
    public static function fromJson(string $json): self
    {
        $data = json_decode($json, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \InvalidArgumentException('Invalid JSON: ' . json_last_error_msg());
        }
        return self::fromArray($data);
    }

    /**
     * Convert to JSON string
     */
    public function toJson(int $flags = 0): string
    {
        return json_encode($this->toArray(), $flags);
    }

}



