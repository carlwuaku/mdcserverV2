<?php
namespace App\Helpers;
use App\Helpers\Utils;
/**
 * License Renewal Date Generator with Configuration-based Logic
 * 
 * This system uses a JSON configuration to define renewal date rules
 * based on multiple criteria and flexible date calculation patterns.
 */
class LicenseRenewalDateGenerator extends Utils
{
    private array $config;

    public function __construct()
    {
        try {
            $this->config = self::getAppSettings('renewalRules');
            if ($this->config === null) {
                $this->config = $this->getDefaultConfiguration();
            }
        } catch (\Throwable $th) {
            $this->config = $this->getDefaultConfiguration();
        }

    }

    /**
     * Generate renewal start and expiry dates based on license configuration
     *
     * @param array $license The license details
     * @param string|null $referenceDate Optional reference date (defaults to today)
     * @return array{start_date:string, expiry_date:string} Array with 'start_date' and 'expiry_date' keys
     */
    public function generateRenewalDates(array $license, string $referenceDate = null): array
    {
        $referenceDate = $referenceDate ?: date('Y-m-d');

        // Find the matching rule based on criteria
        $rule = $this->findMatchingRule($license);

        if (!$rule) {
            throw new \InvalidArgumentException('No matching renewal rule found for license');
        }

        $startDate = $this->calculateDate($rule['start_date'], $referenceDate, $license);
        $expiryDate = $this->calculateDate($rule['expiry_date'], $startDate, $license);

        return [
            'start_date' => $startDate,
            'expiry_date' => $expiryDate
        ];
    }

    /**
     * Find the first matching rule based on license criteria
     *
     * @param array $license License details
     * @return array|null Matching rule or null if none found
     */
    private function findMatchingRule(array $license): ?array
    {
        foreach ($this->config as $rule) {
            if ($this->matchesCriteria($license, $rule['criteria'])) {
                return $rule;
            }
        }
        return null;
    }

    /**
     * Check if license matches all criteria in a rule
     *
     * @param array $license License details
     * @param array $criteria Array of criteria to match
     * @return bool True if all criteria match
     */
    private function matchesCriteria(array $license, array $criteria): bool
    {
        foreach ($criteria as $criterion) {
            $field = $criterion['field'];
            $operator = $criterion['operator'] ?? 'equals';
            $expectedValue = $criterion['value'];

            if (!isset($license[$field])) {
                return false;
            }

            $actualValue = $license[$field];

            switch ($operator) {
                case 'equals':
                case '=':
                case '==':
                    if ($actualValue != $expectedValue)
                        return false;
                    break;
                case 'not_equals':
                case '!=':
                    if ($actualValue == $expectedValue)
                        return false;
                    break;
                case 'in':
                    if (!in_array($actualValue, (array) $expectedValue))
                        return false;
                    break;
                case 'not_in':
                    if (in_array($actualValue, (array) $expectedValue))
                        return false;
                    break;
                case 'greater_than':
                case '>':
                    if ($actualValue <= $expectedValue)
                        return false;
                    break;
                case 'less_than':
                case '<':
                    if ($actualValue >= $expectedValue)
                        return false;
                    break;
                default:
                    throw new \InvalidArgumentException("Unsupported operator: {$operator}");
            }
        }
        return true;
    }

    /**
     * Calculate a date based on the configuration pattern
     *
     * @param string|array $dateConfig Date configuration
     * @param string $referenceDate Reference date for calculations
     * @param array $license License details for dynamic calculations
     * @return string Calculated date in Y-m-d format
     */
    private function calculateDate($dateConfig, string $referenceDate, array $license): string
    {
        // Handle array format for complex date calculations
        if (is_array($dateConfig)) {
            return $this->calculateComplexDate($dateConfig, $referenceDate, $license);
        }

        // Handle string format for simple date calculations
        switch (strtolower($dateConfig)) {
            case 'today':
            case 'now':
                return date('Y-m-d');

            case 'start_of_year':
                $year = date('Y', strtotime($referenceDate));
                return "{$year}-01-01";

            case 'end_of_year':
                $year = date('Y', strtotime($referenceDate));
                return "{$year}-12-31";

            case 'start_of_month':
                return date('Y-m-01', strtotime($referenceDate));

            case 'end_of_month':
                return date('Y-m-t', strtotime($referenceDate));

            default:
                // Handle relative date strings like "+1 year", "+3 months", etc.
                if (preg_match('/^[+\-]\s*\d+\s+(year|month|week|day)s?$/i', trim($dateConfig))) {
                    return date('Y-m-d', strtotime($referenceDate . ' ' . $dateConfig));
                }

                // Handle absolute dates
                if (strtotime($dateConfig) !== false) {
                    return date('Y-m-d', strtotime($dateConfig));
                }

                throw new \InvalidArgumentException("Invalid date configuration: {$dateConfig}");
        }
    }

    /**
     * Calculate complex dates using array configuration
     *
     * @param array $config Complex date configuration
     * @param string $referenceDate Reference date
     * @param array $license License details
     * @return string Calculated date
     */
    private function calculateComplexDate(array $config, string $referenceDate, array $license): string
    {
        $baseDate = $referenceDate;

        // Set base date if specified
        if (isset($config['base'])) {
            $baseDate = $this->calculateDate($config['base'], $referenceDate, $license);
        }

        // Apply modifications
        if (isset($config['modify'])) {
            foreach ((array) $config['modify'] as $modification) {
                $baseDate = date('Y-m-d', strtotime($baseDate . ' ' . $modification));
            }
        }

        // Apply conditional modifications
        if (isset($config['conditional'])) {
            foreach ($config['conditional'] as $condition) {
                if ($this->matchesCriteria($license, $condition['criteria'])) {
                    foreach ((array) $condition['modify'] as $modification) {
                        $baseDate = date('Y-m-d', strtotime($baseDate . ' ' . $modification));
                    }
                }
            }
        }

        return $baseDate;
    }

    /**
     * Get the default configuration
     *
     * @return array Default configuration array
     */
    private function getDefaultConfiguration(): array
    {
        return [
            'name' => 'Default Permanent License Rule',
            'criteria' => [], // Empty criteria matches anything
            'start_date' => 'start_of_year',
            'expiry_date' => 'end_of_year'
        ];
    }

    /**
     * Legacy method for backward compatibility
     */
    public static function generateRenewalExpiryDate(array $license, string $startDate): string
    {
        $generator = new self();
        $dates = $generator->generateRenewalDates($license, $startDate);
        return $dates['expiry_date'];
    }

    /**
     * Legacy method for backward compatibility
     */
    public static function generateRenewalStartDate(array $license): string
    {
        $generator = new self();
        $dates = $generator->generateRenewalDates($license);
        return $dates['start_date'];
    }
}