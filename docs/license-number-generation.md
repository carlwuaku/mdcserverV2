# License Number Generation System

## Overview

The license number generation system provides a flexible, criteria-based mechanism for automatically generating license numbers based on configurable formats. This system uses the existing `CriteriaType` matching framework to determine which format to apply based on license attributes.

## Architecture

### Components

1. **LicenseNumberFormatType** (`app/Helpers/Types/LicenseNumberFormatType.php`)
   - Defines a license number format with criteria matching
   - Includes format template, criteria rules, and sequence tracking configuration

2. **LicenseNumberGenerator** (`app/Helpers/LicenseNumberGenerator.php`)
   - Core generator class that creates license numbers
   - Finds matching formats, retrieves next sequence numbers, and generates formatted license numbers

3. **LicenseService Integration** (`app/Services/LicenseService.php`)
   - Automatically generates license numbers during license creation if not provided
   - Falls back to legacy generation method if no formats are configured

## Configuration

License number formats are defined in the `app-settings-*.json` files under each license type's configuration.

### Format Structure

```json
{
  "licenseTypes": {
    "practitioners": {
      "licenseNumberFormats": [
        {
          "criteria": [
            {
              "field": "practitioner_type",
              "operator": "equals",
              "value": ["Doctor"]
            },
            {
              "field": "register_type",
              "operator": "equals",
              "value": ["Provisional"]
            }
          ],
          "format": "MDC/PN/{number:5}",
          "sequenceKey": "practitioner_type",
          "description": "Doctor - Provisional Register"
        }
      ]
    }
  }
}
```

### Configuration Fields

- **criteria**: Array of `CriteriaType` objects that must all match for this format to be used
  - `field`: The license data field to check
  - `operator`: Comparison operator (equals, not_equals, in, contains, etc.)
  - `value`: Array of values to match against

- **format**: Template string for the license number
  - Use `{number}` for auto-incrementing number without padding
  - Use `{number:X}` for zero-padded number with X digits
  - Examples:
    - `"MDC/PN/{number:5}"` → `MDC/PN/00001`, `MDC/PN/00042`
    - `"HPA {number}"` → `HPA 1`, `HPA 999`
    - `"PT/{number:4}"` → `PT/0001`, `PT/9999`

- **sequenceKey**: Field name to use for tracking sequences
  - Licenses with the same value in this field share a sequence
  - Example: `"practitioner_type"` means all Doctors share one sequence regardless of register_type
  - Leave empty to give each format its own independent sequence

- **description**: Human-readable description for documentation

## Examples

### MDC Ghana - Practitioners

```json
{
  "licenseNumberFormats": [
    {
      "criteria": [
        {"field": "practitioner_type", "operator": "equals", "value": ["Doctor"]},
        {"field": "register_type", "operator": "equals", "value": ["Provisional"]}
      ],
      "format": "MDC/PN/{number:5}",
      "sequenceKey": "practitioner_type",
      "description": "Doctor - Provisional Register"
    },
    {
      "criteria": [
        {"field": "practitioner_type", "operator": "equals", "value": ["Doctor"]},
        {"field": "register_type", "operator": "equals", "value": ["Permanent"]}
      ],
      "format": "MDC/RN/{number:5}",
      "sequenceKey": "practitioner_type",
      "description": "Doctor - Permanent Register"
    },
    {
      "criteria": [
        {"field": "practitioner_type", "operator": "equals", "value": ["Physician Assistant"]},
        {"field": "register_type", "operator": "equals", "value": ["Provisional"]}
      ],
      "format": "MDC/PA/PN/{number:5}",
      "sequenceKey": "practitioner_type",
      "description": "Physician Assistant - Provisional Register"
    }
  ]
}
```

**Sequence Behavior:**
- All Doctor licenses (Provisional, Permanent, Temporary) share the same number sequence
- If the last Doctor license is `MDC/PN/00042`, the next Permanent Doctor will be `MDC/RN/00043`
- Physician Assistants have their own separate sequence

### Pharmacy Council Ghana - Practitioners

```json
{
  "licenseNumberFormats": [
    {
      "criteria": [
        {"field": "practitioner_type", "operator": "equals", "value": ["Pharmacist"]}
      ],
      "format": "HPA {number}",
      "sequenceKey": "practitioner_type",
      "description": "Pharmacist - No padding"
    },
    {
      "criteria": [
        {"field": "practitioner_type", "operator": "equals", "value": ["Pharmacy Technician"]}
      ],
      "format": "PT/{number:4}",
      "sequenceKey": "practitioner_type",
      "description": "Pharmacy Technician - Zero-padded"
    }
  ]
}
```

## Usage

### Automatic Generation

When creating a license through `LicenseService::createLicense()`, if no license number is provided:

```php
$licenseService = new LicenseService();

$licenseData = [
    'type' => 'practitioners',
    'practitioner_type' => 'Doctor',
    'register_type' => 'Provisional',
    'first_name' => 'John',
    'last_name' => 'Doe',
    // ... other fields
    // license_number is NOT provided
];

$result = $licenseService->createLicense($licenseData);
// License number is automatically generated as MDC/PN/00001 (or next in sequence)
```

### Manual Preview

To preview what the next license number would be:

```php
$generator = new LicenseNumberGenerator();

$preview = $generator->previewLicenseNumber('practitioners', [
    'practitioner_type' => 'Doctor',
    'register_type' => 'Permanent'
]);

// Returns:
// [
//     'format' => 'MDC/RN/{number:5}',
//     'preview' => 'MDC/RN/00043',
//     'nextNumber' => 43,
//     'description' => 'Doctor - Permanent Register'
// ]
```

### Validation

To check if a license number is valid for given data:

```php
$generator = new LicenseNumberGenerator();

$isValid = $generator->isValidLicenseNumber(
    'MDC/PN/00001',
    'practitioners',
    ['practitioner_type' => 'Doctor', 'register_type' => 'Provisional']
);
// Returns true or false
```

## Sequence Number Logic

The system determines the next sequence number by:

1. Filtering existing licenses by the `sequenceKey` field value (if specified)
2. Finding all licenses that match the format's prefix pattern
3. Extracting the numeric part from each matching license number
4. Taking the maximum number found and adding 1

### Example Sequence Flow

**Initial state:** No licenses exist

1. Create Doctor Provisional → `MDC/PN/00001` (sequence: 1)
2. Create Doctor Provisional → `MDC/PN/00002` (sequence: 2)
3. Create Doctor Permanent → `MDC/RN/00003` (sequence: 3, shares Doctor sequence)
4. Create Doctor Temporary → `MDC/TN/00004` (sequence: 4, shares Doctor sequence)
5. Create PA Provisional → `MDC/PA/PN/00001` (sequence: 1, separate PA sequence)

## Adding New Formats

To add a new license number format:

1. Edit the appropriate `app-settings-*.json` file
2. Locate the license type (e.g., `practitioners`, `facilities`)
3. Add a new format object to the `licenseNumberFormats` array
4. Define the criteria that must match
5. Specify the format template with `{number}` placeholder
6. Set the `sequenceKey` to determine sequence sharing
7. Add a descriptive label

Example:

```json
{
  "criteria": [
    {"field": "category", "operator": "equals", "value": ["Student"]},
    {"field": "status", "operator": "equals", "value": ["Active"]}
  ],
  "format": "MDC/STU/{number:6}",
  "sequenceKey": "category",
  "description": "Student - Active"
}
```

## Supported Criteria Operators

The system supports all operators from `CriteriaType`:

- `equals`, `=`, `==`, `in`: Value matches any in the array
- `not_equals`, `!=`, `not_in`: Value doesn't match any in the array
- `greater_than`, `>`: Numeric/date comparison
- `greater_than_or_equal`, `>=`: Numeric/date comparison
- `less_than`, `<`: Numeric/date comparison
- `less_than_or_equal`, `<=`: Numeric/date comparison
- `contains`: String contains any value
- `not_contains`: String doesn't contain any value
- `starts_with`: String starts with any value
- `ends_with`: String ends with any value
- `regex`: Matches any regex pattern

## Backward Compatibility

The system is fully backward compatible:

- If no `licenseNumberFormats` are configured, the legacy `LicenseUtils::generateLicenseNumber()` is used
- Existing licenses are not affected
- Manual license number entry is still supported

## Error Handling

If no format matches the provided license data, the system throws an exception:

```
Exception: No license number format matches the provided data for license type: practitioners
```

This ensures data integrity and prevents licenses from being created without proper numbering.

## Testing

The system has been tested with various scenarios:

- ✓ Format parsing (with and without padding)
- ✓ Number generation (zero-padded and non-padded)
- ✓ Criteria matching (multiple fields and operators)
- ✓ Format validation (required fields and placeholders)
- ✓ JSON configuration validity

All tests pass successfully.
