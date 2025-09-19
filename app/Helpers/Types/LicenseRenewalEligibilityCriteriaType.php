<?php
namespace App\Helpers\Types;

class LicenseRenewalEligibilityCriteriaType
{
    /**
     * if true, the person must be in good standing to be able to apply for renewal. else the user can apply after their current renewal has expired
     * @var bool
     */
    public bool $restrict;
    public string $year;
    public int $cpdTotalCutoff;
    public int $category1Cutoff;
    public int $category2Cutoff;
    public int $category3Cutoff;
    public string $register;
    public int $revalidationPeriod;
    public string $revalidationMessage;
    public string $revalidationManualMessage;
    public bool $permitRetention;

    public function __construct(
        bool $restrict = false,
        string $year = '',
        int $cpdTotalCutoff = 0,
        int $category1Cutoff = 0,
        int $category2Cutoff = 0,
        int $category3Cutoff = 0,
        string $register = '',
        int $revalidationPeriod = 0,
        string $revalidationMessage = '',
        string $revalidationManualMessage = '',
        bool $permitRetention = false
    ) {
        $this->restrict = $restrict;
        $this->year = $year;
        $this->cpdTotalCutoff = $cpdTotalCutoff;
        $this->category1Cutoff = $category1Cutoff;
        $this->category2Cutoff = $category2Cutoff;
        $this->category3Cutoff = $category3Cutoff;
        $this->register = $register;
        $this->revalidationPeriod = $revalidationPeriod;
        $this->revalidationMessage = $revalidationMessage;
        $this->revalidationManualMessage = $revalidationManualMessage;
        $this->permitRetention = $permitRetention;
    }

    /**
     * Convert the object to an associative array
     */
    public function toArray(): array
    {
        return [
            'restrict' => $this->restrict,
            'year' => $this->year,
            'cpdTotalCutoff' => $this->cpdTotalCutoff,
            'category1Cutoff' => $this->category1Cutoff,
            'category2Cutoff' => $this->category2Cutoff,
            'category3Cutoff' => $this->category3Cutoff,
            'register' => $this->register,
            'revalidationPeriod' => $this->revalidationPeriod,
            'revalidationMessage' => $this->revalidationMessage,
            'revalidationManualMessage' => $this->revalidationManualMessage,
            'permitRetention' => $this->permitRetention,
        ];
    }

    /**
     * Create an instance from an associative array
     */
    public static function fromArray(array $data): self
    {
        return new self(
            restrict: $data['restrict'] ?? false,
            year: $data['year'] ?? '',
            cpdTotalCutoff: $data['cpdTotalCutoff'] ?? 0,
            category1Cutoff: $data['category1Cutoff'] ?? 0,
            category2Cutoff: $data['category2Cutoff'] ?? 0,
            category3Cutoff: $data['category3Cutoff'] ?? 0,
            register: $data['register'] ?? '',
            revalidationPeriod: $data['revalidationPeriod'] ?? 0,
            revalidationMessage: $data['revalidationMessage'] ?? '',
            revalidationManualMessage: $data['revalidationManualMessage'] ?? '',
            permitRetention: $data['permitRetention'] ?? false
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
     * Get all category cutoffs as an array
     */
    public function getCategoryCutoffs(): array
    {
        return [
            'category1' => $this->category1Cutoff,
            'category2' => $this->category2Cutoff,
            'category3' => $this->category3Cutoff,
        ];
    }

    /**
     * Check if any category cutoffs are set
     */
    public function hasCategoryCutoffs(): bool
    {
        return $this->category1Cutoff > 0 || $this->category2Cutoff > 0 || $this->category3Cutoff > 0;
    }

    /**
     * Get the total of all category cutoffs
     */
    public function getTotalCategoryCutoffs(): int
    {
        return $this->category1Cutoff + $this->category2Cutoff + $this->category3Cutoff;
    }

    /**
     * Check if the criteria is active/enabled
     */
    public function isActive(): bool
    {
        return $this->restrict && !empty($this->year) && !empty($this->register);
    }

    /**
     * Validate that the object has required fields
     */
    public function validate(): bool
    {
        if ($this->restrict) {
            return !empty($this->year) &&
                !empty($this->register) &&
                ($this->cpdTotalCutoff > 0 || $this->hasCategoryCutoffs());
        }
        return true;
    }

    /**
     * Get validation errors
     */
    public function getValidationErrors(): array
    {
        $errors = [];

        if ($this->restrict) {
            if (empty($this->year)) {
                $errors[] = 'Year is required when restrictions are enabled';
            }
            if (empty($this->register)) {
                $errors[] = 'Register is required when restrictions are enabled';
            }
            if ($this->cpdTotalCutoff <= 0 && !$this->hasCategoryCutoffs()) {
                $errors[] = 'Either CPD total cutoff or category cutoffs must be set when restrictions are enabled';
            }
        }

        return $errors;
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