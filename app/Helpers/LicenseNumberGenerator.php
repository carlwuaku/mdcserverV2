<?php

namespace App\Helpers;

use App\Helpers\Types\LicenseNumberFormatType;
use App\Models\Licenses\LicensesModel;
use CodeIgniter\Database\BaseConnection;

/**
 * License Number Generator
 * Handles automatic generation of license numbers based on configured formats
 */
class LicenseNumberGenerator
{
    private BaseConnection $db;

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? \Config\Database::connect();
    }

    /**
     * Generate a license number for the given license type and data
     *
     * @param string $licenseType The type of license (e.g., 'practitioners', 'facilities')
     * @param array $licenseData The license data containing fields to match against criteria
     * @return string|null Generated license number or null if no format matches
     * @throws \Exception If unable to generate license number
     */
    public function generateLicenseNumber(string $licenseType, array $licenseData): ?string
    {
        try {
            // Get license number formats from settings
            $formats = $this->getLicenseNumberFormats($licenseType);

            if (empty($formats)) {
                return null; // No formats configured, caller should handle manually
            }

            // Find the first format that matches the license data
            $matchingFormat = $this->findMatchingFormat($formats, $licenseData);

            if (!$matchingFormat) {
                throw new \Exception("No license number format matches the provided data for license type: $licenseType");
            }

            // Generate the license number using the matching format
            return $this->generateFromFormat($licenseType, $matchingFormat, $licenseData);
        } catch (\Throwable $th) {
            log_message('error', "Error generating license number for $licenseType: " . $th->getMessage());
            throw $th;
        }

    }

    /**
     * Get license number formats for a specific license type from settings
     *
     * @param string $licenseType
     * @return LicenseNumberFormatType[]
     */
    private function getLicenseNumberFormats(string $licenseType): array
    {
        try {
            $licenseSetting = Utils::getLicenseSetting($licenseType);

            if (!isset($licenseSetting->licenseNumberFormats)) {
                return [];
            }

            // Convert array of format definitions to LicenseNumberFormatType objects
            return array_map(
                fn($formatData) => LicenseNumberFormatType::fromArray($formatData),
                $licenseSetting->licenseNumberFormats
            );
        } catch (\Throwable $e) {
            log_message('error', "Error loading license number formats for $licenseType: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Find the first format that matches the license data
     *
     * @param LicenseNumberFormatType[] $formats
     * @param array $licenseData
     * @return LicenseNumberFormatType|null
     */
    private function findMatchingFormat(array $formats, array $licenseData): ?LicenseNumberFormatType
    {
        foreach ($formats as $format) {
            if ($format->matches($licenseData)) {
                return $format;
            }
        }

        return null;
    }

    /**
     * Generate a license number from a specific format
     *
     * @param string $licenseType
     * @param LicenseNumberFormatType $format
     * @param array $licenseData
     * @return string
     * @throws \Exception
     */
    private function generateFromFormat(
        string $licenseType,
        LicenseNumberFormatType $format,
        array $licenseData
    ): string {
        // Get the next sequence number for this format
        $nextNumber = $this->getNextSequenceNumber($licenseType, $format, $licenseData);

        // Generate the license number using the format
        return $format->generateNumber($nextNumber);
    }

    /**
     * Get the next sequence number for a license format
     *
     * @param string $licenseType
     * @param LicenseNumberFormatType $format
     * @param array $licenseData
     * @return int
     */
    private function getNextSequenceNumber(
        string $licenseType,
        LicenseNumberFormatType $format,
        array $licenseData
    ): int {
        $licenseSetting = Utils::getLicenseSetting($licenseType);
        $licenseTable = $licenseSetting->table;

        // Build the base query to find the last license number
        $builder = $this->db->table($licenseTable);

        // If a sequence key is specified, filter by that field value
        // This allows different groups to have separate sequences
        if (!empty($format->sequenceKey)) {
            $sequenceValue = $licenseData[$format->sequenceKey] ?? null;

            if ($sequenceValue !== null) {
                $builder->where($format->sequenceKey, $sequenceValue);
            }
        }

        // Get the format pattern for matching existing license numbers
        $formatPattern = $this->getFormatPattern($format);

        // Find all license numbers matching this format pattern
        if ($formatPattern) {
            // Extract the prefix from the format (everything before {number})
            $prefix = preg_replace('/\{number(?::\d+)?\}.*$/', '', $format->format);

            if (!empty($prefix)) {
                $builder->like('license_number', $prefix, 'after');
            }
        }

        // Get all matching license numbers and extract the highest number
        $existingLicenses = $builder->select('license_number')
            ->orderBy('id', 'DESC')
            ->get()
            ->getResultArray();

        $maxNumber = 0;

        foreach ($existingLicenses as $license) {
            $extractedNumber = $this->extractNumberFromLicense($license['license_number'], $format);
            if ($extractedNumber > $maxNumber) {
                $maxNumber = $extractedNumber;
            }
        }

        return $maxNumber + 1;
    }

    /**
     * Extract the numeric part from a license number based on format
     *
     * @param string $licenseNumber
     * @param LicenseNumberFormatType $format
     * @return int
     */
    private function extractNumberFromLicense(string $licenseNumber, LicenseNumberFormatType $format): int
    {
        // Convert format to regex pattern
        // Replace {number} or {number:X} with a capturing group for digits
        $pattern = preg_quote($format->format, '/');
        $pattern = preg_replace('/\\\{number(?::\d+)?\\\}/', '(\d+)', $pattern);
        $pattern = '/^' . $pattern . '$/';

        if (preg_match($pattern, $licenseNumber, $matches)) {
            return isset($matches[1]) ? (int) $matches[1] : 0;
        }

        return 0;
    }

    /**
     * Get a regex pattern that matches licenses for this format
     *
     * @param LicenseNumberFormatType $format
     * @return string|null
     */
    private function getFormatPattern(LicenseNumberFormatType $format): ?string
    {
        if (empty($format->format)) {
            return null;
        }

        // Convert format template to regex pattern
        $pattern = preg_quote($format->format, '/');
        $pattern = preg_replace('/\\\{number(?::\d+)?\\\}/', '\d+', $pattern);

        return $pattern;
    }

    /**
     * Check if a license number is valid for the given format
     *
     * @param string $licenseNumber
     * @param string $licenseType
     * @param array $licenseData
     * @return bool
     */
    public function isValidLicenseNumber(string $licenseNumber, string $licenseType, array $licenseData): bool
    {
        $formats = $this->getLicenseNumberFormats($licenseType);

        if (empty($formats)) {
            return true; // No validation if no formats configured
        }

        $matchingFormat = $this->findMatchingFormat($formats, $licenseData);

        if (!$matchingFormat) {
            return true; // No specific format requirement
        }

        $pattern = $this->getFormatPattern($matchingFormat);

        if (!$pattern) {
            return true;
        }

        return (bool) preg_match('/^' . $pattern . '$/', $licenseNumber);
    }

    /**
     * Get a preview of what the next license number would be
     * Useful for testing and validation
     *
     * @param string $licenseType
     * @param array $licenseData
     * @return array|null ['format' => string, 'preview' => string, 'nextNumber' => int]
     */
    public function previewLicenseNumber(string $licenseType, array $licenseData): ?array
    {
        $formats = $this->getLicenseNumberFormats($licenseType);

        if (empty($formats)) {
            return null;
        }

        $matchingFormat = $this->findMatchingFormat($formats, $licenseData);

        if (!$matchingFormat) {
            return null;
        }

        $nextNumber = $this->getNextSequenceNumber($licenseType, $matchingFormat, $licenseData);
        $preview = $matchingFormat->generateNumber($nextNumber);

        return [
            'format' => $matchingFormat->format,
            'preview' => $preview,
            'nextNumber' => $nextNumber,
            'description' => $matchingFormat->description
        ];
    }
}
