<?php
namespace App\Helpers\Types;

class CriteriaType
{

    public string $field;
    public string $operator;
    public array $value;

    public function __construct(string $field, string $operator, array $value)
    {
        $this->field = $field;
        $this->operator = $operator;
        $this->value = $value;
    }

    public function toArray(): array
    {
        return [
            'field' => $this->field,
            'operator' => $this->operator,
            'value' => $this->value
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['field'] ?? '',
            $data['operator'] ?? '',
            $data['value'] ?? []
        );
    }

    /**
     * Check if data matches all criteria in a rule
     *
     * @param array $data License details
     * @param CriteriaType[] $criteria Array of criteria to match
     * @return bool True if all criteria match
     */
    public static function matchesCriteria(array|object $data, array $criteria): bool
    {
        if (is_object($data)) {
            $data = (array) $data;
        }

        foreach ($criteria as $criterion) {
            $field = $criterion->field;
            $operator = $criterion->operator ?? 'equals';
            $expectedValues = $criterion->value;

            // Check if the field exists
            if (!isset($data[$field])) {
                return false;
            }

            $actualValue = $data[$field];

            // Convert to array if single value for consistent processing
            if (!is_array($expectedValues)) {
                $expectedValues = [$expectedValues];
            }

            if (!self::evaluateCriterion($actualValue, $operator, $expectedValues)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Evaluate a single criterion against a value
     *
     * @param mixed $actualValue The actual value from data
     * @param string $operator The comparison operator
     * @param array $expectedValues Array of expected values
     * @return bool True if criterion matches
     */
    private static function evaluateCriterion($actualValue, string $operator, array $expectedValues): bool
    {
        switch ($operator) {
            case 'equals':
            case '=':
            case '==':
            case 'in':
                return in_array($actualValue, $expectedValues);

            case 'not_equals':
            case '!=':
            case 'not_in':
                return !in_array($actualValue, $expectedValues);

            case 'greater_than':
            case '>':
                return self::compareNumericOrDate($actualValue, $expectedValues, '>');

            case 'greater_than_or_equal':
            case '>=':
                return self::compareNumericOrDate($actualValue, $expectedValues, '>=');

            case 'less_than':
            case '<':
                return self::compareNumericOrDate($actualValue, $expectedValues, '<');

            case 'less_than_or_equal':
            case '<=':
                return self::compareNumericOrDate($actualValue, $expectedValues, '<=');

            case 'contains':
                return self::stringContains($actualValue, $expectedValues);

            case 'not_contains':
                return !self::stringContains($actualValue, $expectedValues);

            case 'starts_with':
                return self::stringStartsWith($actualValue, $expectedValues);

            case 'ends_with':
                return self::stringEndsWith($actualValue, $expectedValues);

            case 'regex':
                return self::matchesRegex($actualValue, $expectedValues);

            default:
                throw new \InvalidArgumentException("Unsupported operator: {$operator}");
        }
    }

    /**
     * Compare numeric or date values
     *
     * @param mixed $actualValue The actual value to compare
     * @param array $expectedValues Array of values to compare against
     * @param string $operator The comparison operator
     * @return bool True if any comparison matches
     */
    private static function compareNumericOrDate($actualValue, array $expectedValues, string $operator): bool
    {
        foreach ($expectedValues as $expectedValue) {
            $actualNormalized = self::normalizeValue($actualValue);
            $expectedNormalized = self::normalizeValue($expectedValue);

            if ($actualNormalized === null || $expectedNormalized === null) {
                continue; // Skip invalid comparisons
            }

            $result = match ($operator) {
                '>' => $actualNormalized > $expectedNormalized,
                '>=' => $actualNormalized >= $expectedNormalized,
                '<' => $actualNormalized < $expectedNormalized,
                '<=' => $actualNormalized <= $expectedNormalized,
                default => false
            };

            if ($result) {
                return true; // If any comparison matches, return true
            }
        }

        return false;
    }

    /**
     * Normalize a value to a comparable format (numeric or timestamp)
     *
     * @param mixed $value The value to normalize
     * @return float|int|null Normalized value or null if invalid
     */
    private static function normalizeValue($value)
    {
        // Handle numeric values
        if (is_numeric($value)) {
            return is_float($value) ? (float) $value : (int) $value;
        }

        // Handle date strings
        if (is_string($value)) {
            // Try to parse as date
            $timestamp = strtotime($value);
            if ($timestamp !== false) {
                return $timestamp;
            }
        }

        // Handle DateTime objects
        if ($value instanceof \DateTime || $value instanceof \DateTimeInterface) {
            return $value->getTimestamp();
        }

        return null; // Cannot normalize
    }

    /**
     * Check if string contains any of the expected values
     */
    private static function stringContains($actualValue, array $expectedValues): bool
    {
        if (!is_string($actualValue)) {
            return false;
        }

        foreach ($expectedValues as $expectedValue) {
            if (str_contains(strtolower($actualValue), strtolower((string) $expectedValue))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if string starts with any of the expected values
     */
    private static function stringStartsWith($actualValue, array $expectedValues): bool
    {
        if (!is_string($actualValue)) {
            return false;
        }

        foreach ($expectedValues as $expectedValue) {
            if (str_starts_with(strtolower($actualValue), strtolower((string) $expectedValue))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if string ends with any of the expected values
     */
    private static function stringEndsWith($actualValue, array $expectedValues): bool
    {
        if (!is_string($actualValue)) {
            return false;
        }

        foreach ($expectedValues as $expectedValue) {
            if (str_ends_with(strtolower($actualValue), strtolower((string) $expectedValue))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if value matches any regex pattern
     */
    private static function matchesRegex($actualValue, array $expectedValues): bool
    {
        if (!is_string($actualValue)) {
            return false;
        }

        foreach ($expectedValues as $pattern) {
            if (preg_match((string) $pattern, $actualValue)) {
                return true;
            }
        }

        return false;
    }


}