<?php
namespace App\Helpers\Types;

class FormActionType
{
    public string $type;
    public string $config_type;
    public object $config;
    public array $criteria;

    /**
     * Constructor
     */
    public function __construct(
        string $type = '',
        string $config_type = '',
        object $config = null,
        array $criteria = []
    ) {
        $this->type = $type;
        $this->config_type = $config_type;
        $this->config = $config ?? new \stdClass();
        $this->criteria = $criteria;
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
     * Create instance from associative array
     */
    public static function fromArray(array $data): self
    {
        $instance = new self();

        $instance->type = $data['type'] ?? '';
        $instance->config_type = $data['config_type'] ?? '';
        $instance->config = isset($data['config']) ? (object) $data['config'] : new stdClass();
        $instance->criteria = $data['criteria'] ?? [];

        return $instance;
    }

    /**
     * Convert to JSON string
     */
    public function toJson(int $flags = 0): string
    {
        return json_encode($this->toArray(), $flags);
    }

    /**
     * Convert to associative array
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'config_type' => $this->config_type,
            'config' => (array) $this->config,
            'criteria' => $this->criteria
        ];
    }

    /**
     * Convert to pretty-formatted JSON
     */
    public function toPrettyJson(): string
    {
        return $this->toJson(JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Validate the object structure
     */
    public function validate(): bool
    {
        return !empty($this->type) &&
            !empty($this->config_type) &&
            is_object($this->config) &&
            is_array($this->criteria);
    }

    /**
     * Get validation errors
     */
    public function getValidationErrors(): array
    {
        $errors = [];

        if (empty($this->type)) {
            $errors[] = 'Type cannot be empty';
        }

        if (empty($this->config_type)) {
            $errors[] = 'Config type cannot be empty';
        }

        if (!is_object($this->config)) {
            $errors[] = 'Config must be an object';
        }

        if (!is_array($this->criteria)) {
            $errors[] = 'Criteria must be an array';
        }

        return $errors;
    }

    /**
     * Clone the object with deep copy
     */
    public function clone(): self
    {
        return self::fromArray($this->toArray());
    }

    /**
     * Merge with another  FormActionType 
     */
    public function merge(FormActionType $other): self
    {
        $merged = $this->clone();

        if (!empty($other->type)) {
            $merged->type = $other->type;
        }

        if (!empty($other->config_type)) {
            $merged->config_type = $other->config_type;
        }

        // Merge config objects
        $merged->config = (object) array_merge(
            (array) $merged->config,
            (array) $other->config
        );

        // Merge criteria arrays
        $merged->criteria = array_merge($merged->criteria, $other->criteria);

        return $merged;
    }

    /**
     * Get a config value by key
     */
    public function getConfigValue(string $key, $default = null)
    {
        $configArray = (array) $this->config;
        return $configArray[$key] ?? $default;
    }

    /**
     * Set a config value
     */
    public function setConfigValue(string $key, $value): self
    {
        $configArray = (array) $this->config;
        $configArray[$key] = $value;
        $this->config = (object) $configArray;
        return $this;
    }

    /**
     * Add a criterion to the criteria array
     */
    public function addCriterion($criterion): self
    {
        $this->criteria[] = $criterion;
        return $this;
    }

    /**
     * Remove criteria by value
     */
    public function removeCriterion($criterion): self
    {
        $this->criteria = array_values(array_filter(
            $this->criteria,
            fn($item) => $item !== $criterion
        ));
        return $this;
    }

    /**
     * Check if a criterion exists
     */
    public function hasCriterion($criterion): bool
    {
        return in_array($criterion, $this->criteria, true);
    }

    /**
     * Get criteria count
     */
    public function getCriteriaCount(): int
    {
        return count($this->criteria);
    }

    /**
     * Clear all criteria
     */
    public function clearCriteria(): self
    {
        $this->criteria = [];
        return $this;
    }

    /**
     * Check if object equals another  FormActionType 
     */
    public function equals(FormActionType $other): bool
    {
        return $this->toJson() === $other->toJson();
    }

    /**
     * Get hash of the object
     */
    public function getHash(): string
    {
        return hash('sha256', $this->toJson());
    }

    /**
     * Check if object is empty (has default values)
     */
    public function isEmpty(): bool
    {
        return empty($this->type) &&
            empty($this->config_type) &&
            empty((array) $this->config) &&
            empty($this->criteria);
    }

    /**
     * Reset object to default state
     */
    public function reset(): self
    {
        $this->type = '';
        $this->config_type = '';
        $this->config = new \stdClass();
        $this->criteria = [];
        return $this;
    }

    /**
     * Magic method to convert to string (returns JSON)
     */
    public function __toString(): string
    {
        return $this->toJson();
    }

    /**
     * Magic method for var_dump and print_r
     */
    public function __debugInfo(): array
    {
        return $this->toArray();
    }

    /**
     * Create from file (JSON file)
     */
    public static function fromFile(string $filepath): self
    {
        if (!file_exists($filepath)) {
            throw new \InvalidArgumentException("File not found: {$filepath}");
        }

        $content = file_get_contents($filepath);
        if ($content === false) {
            throw new \RuntimeException("Could not read file: {$filepath}");
        }

        return self::fromJson($content);
    }

    /**
     * Save to file (JSON format)
     */
    public function saveToFile(string $filepath, bool $prettyPrint = true): bool
    {
        $json = $prettyPrint ? $this->toPrettyJson() : $this->toJson();
        return file_put_contents($filepath, $json) !== false;
    }
}