<?php
namespace App\Helpers;

class TemplateEngineHelper
{
    /**
     * Patterns to match common date field names
     * @var array
     */
    private array $datePatterns = [
        // Exact matches
        '/^date$/i',
        '/^created_at$/i',
        '/^updated_at$/i',
        '/^deleted_at$/i',
        '/^modified_at$/i',
        '/^created_on$/i',
        '/^updated_on$/i',
        '/^deleted_on$/i',
        '/^modified_on$/i',
        '/^timestamp$/i',
        '/^birth_date$/i',
        '/^dob$/i',
        '/^start_date$/i',
        '/^end_date$/i',
        '/^expiry_date$/i',
        '/^expiration_date$/i',

        // Pattern matches
        '/.*_date$/i',           // Matches anything ending with _date
        '/.*_at$/i',            // Matches anything ending with _at
        '/.*_on$/i',            // Matches anything ending with _on
        '/date_.*/i',           // Matches anything starting with date_
        '/.*_timestamp.*/i',    // Matches anything containing _timestamp
        '/.*_year.*/i',         // Matches anything containing _year
        '/year_.*/i',           // Matches anything starting with year_
    ];

    /**
     * Default date format to use when converting dates
     * @var string
     */
    private string $defaultDateFormat = 'jS F Y';

    private array $transformers;
    private $resourceLoader;
    private $settingsLoader;

    public function __construct(callable $resourceLoader = null, callable $settingsLoader = null)
    {
        $this->transformers = [
            'date_add' => function ($value, $params) {
                return date('Y-m-d', strtotime($value . ' ' . $params));
            },
            'date_transform' => function ($value, $params) {
                return date($params, strtotime($value));
            },
            'uppercase' => function ($value) {
                return strtoupper($value);
            },
            'lowercase' => function ($value) {
                return strtolower($value);
            }
        ];

        $this->resourceLoader = $resourceLoader ?? function ($id) {
            return '';
        };
        $this->settingsLoader = $settingsLoader ?? function ($key) {
            return '';
        };
    }

    /**
     * Set custom date format
     */
    public function setDefaultDateFormat(string $format): void
    {
        $this->defaultDateFormat = $format;
    }

    /**
     * Add a custom date pattern
     */
    public function addDatePattern(string $pattern): void
    {
        $this->datePatterns[] = $pattern;
    }

    /**
     * Check if a field name matches any date patterns
     */
    private function isDateField(string $fieldName): bool
    {
        foreach ($this->datePatterns as $pattern) {
            if (preg_match($pattern, $fieldName)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Format a date value
     */
    private function formatDate(?string $value, ?string $format = null): string
    {
        // Skip empty values
        if (empty($value)) {
            return '';
        }

        try {
            $timestamp = strtotime($value);
            if ($timestamp === false) {
                return $value; // Return original value if not a valid date
            }
            return date($format ?? $this->defaultDateFormat, $timestamp);
        } catch (\Exception $e) {
            return $value; // Return original value if any error occurs
        }
    }

    /**
     * Add a custom transformer
     */
    public function addTransformer(string $name, callable $transformer): void
    {
        $this->transformers[$name] = $transformer;
    }

    /**
     * Main template processing method
     * @param string $template
     * @param array|object $data
     * @return string
     */
    public function process(string $template, array|object $data): string
    {
        $data = (object) $data;

        // Process loops first (they might contain variables)
        $template = $this->processLoops($template, $data);

        // Then process in order: variables, resources, settings
        $template = $this->processVariables($template, $data);
        $template = $this->processResources($template);
        $template = $this->processSettings($template);

        return $template;
    }

    /**
     * Process loop blocks in format:
     * [#LOOP:array_property]
     *   Template content with [item.property] variables
     * [/LOOP]
     */
    private function processLoops(string $template, object $data): string
    {
        return preg_replace_callback(
            '/\[#LOOP:([^\]]+)\](.*?)\[\/LOOP\]/s',
            function ($matches) use ($data) {
                $arrayProperty = trim($matches[1]);
                $loopTemplate = $matches[2];
                log_message('debug', 'Processing loop: ' . $loopTemplate);

                // Get the array data
                if (!property_exists($data, $arrayProperty) || !is_array($data->$arrayProperty)) {
                    return ''; // Return empty if property doesn't exist or isn't an array
                }

                $arrayData = $data->$arrayProperty;
                $result = '';

                // Process each item in the array
                foreach ($arrayData as $index => $item) {
                    $itemResult = $loopTemplate;

                    // Create context data for this iteration
                    $loopData = (object) array_merge(
                        (array) $data, // Include parent data
                        [
                            'item' => (object) $item,
                            'index' => $index,
                            'index1' => $index + 1, // 1-based index
                            'first' => $index === 0,
                            'last' => $index === count($arrayData) - 1,
                            'count' => count($arrayData)
                        ]
                    );

                    // Process variables in the loop template
                    $itemResult = $this->processVariables($itemResult, $loopData);

                    $result .= $itemResult;
                }

                return $result;
            },
            $template
        );
    }

    /**
     * Process variables in format [variable::transformation||params]
     * Now also handles nested properties like [item.name] or [user.address.city]
     */
    private function processVariables(string $template, object $data): string
    {
        return preg_replace_callback('/\[([^\]]+)\]/', function ($matches) use ($data) {
            $parts = explode('::', $matches[1]);
            $varName = $parts[0];
            $transformation = $parts[1] ?? null;

            // Handle nested properties (e.g., item.name, user.address.city)
            $value = $this->getNestedProperty($data, $varName);

            if ($value === null) {
                return '';
            }

            // Handle date fields - either by pattern match or explicit transformation
            $finalPropertyName = $this->getFinalPropertyName($varName);
            if ($this->isDateField($finalPropertyName)) {
                $value = $this->formatDate($value);
            }

            // Apply additional transformation if specified
            if ($transformation) {
                $value = $this->transform($value, $transformation);
            }

            return $value;
        }, $template);
    }

    /**
     * Get nested property value using dot notation
     */
    private function getNestedProperty(object $data, string $propertyPath)
    {
        $properties = explode('.', $propertyPath);
        $current = $data;

        foreach ($properties as $property) {
            if (is_object($current) && property_exists($current, $property)) {
                $current = $current->$property;
            } elseif (is_array($current) && isset($current[$property])) {
                $current = $current[$property];
            } else {
                return null;
            }
        }

        return $current;
    }

    /**
     * Get the final property name from a nested path (for date field checking)
     */
    private function getFinalPropertyName(string $propertyPath): string
    {
        $properties = explode('.', $propertyPath);
        return end($properties);
    }

    /**
     * Process resources in format {{resId:123}}
     */
    private function processResources(string $template): string
    {
        return preg_replace_callback('/\{\{resId:(\d+)\}\}/', function ($matches) {
            $resourceId = $matches[1];
            return ($this->resourceLoader)($resourceId);
        }, $template);
    }

    /**
     * Process settings in format $_SETTING_NAME_$
     */
    private function processSettings(string $template): string
    {
        return preg_replace_callback('/\$_([A-Z0-9_]+)_\$/', function ($matches) {
            $settingName = $matches[1];
            return ($this->settingsLoader)($settingName);
        }, $template);
    }

    /**
     * Apply transformation to a value
     */
    private function transform(string $value, string $transformation): string
    {
        $parts = explode('||', $transformation);
        $transformationType = $parts[0];
        $params = $parts[1] ?? null;

        if (!isset($this->transformers[$transformationType])) {
            return $value;
        }

        return $this->transformers[$transformationType]($value, $params);
    }
}