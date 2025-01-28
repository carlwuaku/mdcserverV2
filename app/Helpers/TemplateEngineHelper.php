<?php
namespace App\Helpers;
class TemplateEngine
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
    private function formatDate(string $value, ?string $format = null): string
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

        // Process in order: variables, resources, settings
        $template = $this->processVariables($template, $data);
        $template = $this->processResources($template);
        $template = $this->processSettings($template);

        return $template;
    }

    /**
     * Process variables in format [variable::transformation||params]
     */
    private function processVariables(string $template, object $data): string
    {
        return preg_replace_callback('/\[([^\]]+)\]/', function ($matches) use ($data) {
            $parts = explode('::', $matches[1]);
            $varName = $parts[0];
            $transformation = $parts[1] ?? null;

            if (!property_exists($data, $varName)) {
                return '';
            }

            $value = $data->$varName;

            // Handle date fields - either by pattern match or explicit transformation
            if ($this->isDateField($varName)) {
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