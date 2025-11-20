<?php
namespace App\Helpers\Types;

/**
 * Defines a license number format with criteria matching
 * Used for automatic license number generation
 */
class LicenseNumberFormatType
{
    /**
     * Array of criteria that must match for this format to be used
     * @var CriteriaType[]
     */
    public array $criteria;

    /**
     * The format template for the license number
     * Can include placeholders:
     * - {prefix}: Static prefix text
     * - {number}: Auto-incrementing number
     * - {number:5}: Zero-padded number with specified length
     * Examples: "MDC/PN/{number:5}", "HPA {number}", "PT/{number:4}"
     *
     * @var string
     */
    public string $format;

    /**
     * Field to use for tracking the sequence number
     * This determines which licenses share the same sequence
     * Examples: "practitioner_type", "license_type", "register_type"
     * If empty, each format gets its own sequence
     *
     * @var string
     */
    public string $sequenceKey;

    /**
     * Optional description for documentation purposes
     * @var string
     */
    public string $description;

    public function __construct(
        array $criteria = [],
        string $format = '',
        string $sequenceKey = '',
        string $description = ''
    ) {
        $this->criteria = array_map(
            fn($c) => $c instanceof CriteriaType ? $c : CriteriaType::fromArray($c),
            $criteria
        );
        $this->format = $format;
        $this->sequenceKey = $sequenceKey;
        $this->description = $description;
    }

    /**
     * Convert the object to an associative array
     */
    public function toArray(): array
    {
        return [
            'criteria' => array_map(fn($c) => $c->toArray(), $this->criteria),
            'format' => $this->format,
            'sequenceKey' => $this->sequenceKey,
            'description' => $this->description,
        ];
    }

    /**
     * Create an instance from an associative array
     */
    public static function fromArray(array $data): self
    {
        return new self(
            criteria: $data['criteria'] ?? [],
            format: $data['format'] ?? '',
            sequenceKey: $data['sequenceKey'] ?? '',
            description: $data['description'] ?? ''
        );
    }

    /**
     * Convert the object to JSON string
     */
    public function toJson(int $flags = 0): string
    {
        return json_encode($this->toArray(), $flags);
    }

    /**
     * Create an instance from JSON string
     */
    public static function fromJson(string $json): self
    {
        $data = json_decode($json, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \InvalidArgumentException('Invalid JSON provided: ' . json_last_error_msg());
        }
        return self::fromArray($data);
    }

    /**
     * Check if this format matches the given data
     *
     * @param array|object $data License details to match against
     * @return bool True if all criteria match
     */
    public function matches(array|object $data): bool
    {
        return CriteriaType::matchesCriteria($data, $this->criteria);
    }

    /**
     * Validate that the format has required fields
     */
    public function validate(): bool
    {
        if (empty($this->format)) {
            return false;
        }

        // Check that format contains {number} placeholder
        if (!str_contains($this->format, '{number')) {
            return false;
        }

        return true;
    }

    /**
     * Get validation errors
     */
    public function getValidationErrors(): array
    {
        $errors = [];

        if (empty($this->format)) {
            $errors[] = 'Format is required';
        } elseif (!str_contains($this->format, '{number')) {
            $errors[] = 'Format must contain {number} or {number:length} placeholder';
        }

        return $errors;
    }

    /**
     * Parse the format to extract number padding information
     *
     * @return array ['padding' => int|null, 'hasNumber' => bool]
     */
    public function parseFormat(): array
    {
        $hasNumber = false;
        $padding = null;

        // Match {number:5} or {number}
        if (preg_match('/\{number(?::(\d+))?\}/', $this->format, $matches)) {
            $hasNumber = true;
            $padding = isset($matches[1]) ? (int)$matches[1] : null;
        }

        return [
            'hasNumber' => $hasNumber,
            'padding' => $padding
        ];
    }

    /**
     * Generate a license number using this format
     *
     * @param int $nextNumber The next sequence number to use
     * @return string The generated license number
     */
    public function generateNumber(int $nextNumber): string
    {
        $formatInfo = $this->parseFormat();

        // Format the number with padding if specified
        $formattedNumber = $formatInfo['padding']
            ? str_pad((string)$nextNumber, $formatInfo['padding'], '0', STR_PAD_LEFT)
            : (string)$nextNumber;

        // Replace the {number} or {number:X} placeholder
        $licenseNumber = preg_replace('/\{number(?::\d+)?\}/', $formattedNumber, $this->format);

        return $licenseNumber;
    }

    /**
     * Create a copy of the object with modified properties
     */
    public function with(array $properties): self
    {
        $data = $this->toArray();
        foreach ($properties as $key => $value) {
            if (array_key_exists($key, $data)) {
                $data[$key] = $value;
            }
        }
        return self::fromArray($data);
    }

    /**
     * Compare with another instance
     */
    public function equals(self $other): bool
    {
        return $this->toArray() === $other->toArray();
    }

    /**
     * Get a string representation of the object
     */
    public function __toString(): string
    {
        return $this->toJson(JSON_PRETTY_PRINT);
    }

    /**
     * Magic method for var_export compatibility
     */
    public static function __set_state(array $array): self
    {
        return self::fromArray($array);
    }
}
