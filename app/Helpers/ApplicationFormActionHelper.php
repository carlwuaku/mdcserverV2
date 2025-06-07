<?php
/**
 * this helper class contains methods to help with the creation of submitted application forms
 */
namespace App\Helpers;

use CodeIgniter\HTTP\ResponseInterface;

class ApplicationFormActionHelper extends Utils
{
    /**
     * this method runs a provided action on the application form
     * @param object{type:string, config:object} $action
     * @param array $data
     * @return array
     */
    public static function runAction($action, $data)
    {
        switch ($action->type) {
            case 'email':
                return self::sendEmailToApplicant($action, $data);
            case 'admin_email':
                return self::sendEmailToAdmin($action, $data);
            case 'api_call':
                return self::callApi($action, $data);
            default:
                return $data;
        }
    }

    /**
     * this method sends an email to the applicant
     * @param object{type:string, config:object {template:string, subject:string}} $action
     * @param array $data
     * @return array
     */
    private static function sendEmailToApplicant($action, $data)
    {
        $templateModel = new TemplateEngineHelper();
        $content = $templateModel->process($action->config['template'], $data);
        $subject = $templateModel->process($action->config['subject'], $data);
        $emailConfig = new EmailConfig($content, $subject, $data['email']);

        EmailHelper::sendEmail($emailConfig);
        return $data;
    }

    /**
     * this method sends an email to the admin
     * @param object{type:string, config:object {template:string, subject:string, admin_email:string}} $action
     * @param array $data
     * @return array
     */
    private static function sendEmailToAdmin($action, $data)
    {
        log_message('info', 'Sending email to admin');
        $templateModel = new TemplateEngineHelper();
        $content = $templateModel->process($action->config['template'], $data);
        $subject = $templateModel->process($action->config['subject'], $data);
        $emailConfig = new EmailConfig($content, $subject, $action->config['admin_email']);

        EmailHelper::sendEmail($emailConfig);
        return $data;
    }

    /**
     * this method makes an api call
     * @param object{type:string, config:object {endpoint:string, method:string, headers:array, body_mapping:array, query_params:array, auth_token:string}} $action
     * @param array $data
     * @return array
     */
    private static function callApi($action, $data)
    {
        helper("auth");
        try {
            log_message('info', 'Making API call to: ' . $action->config['endpoint']);
            // Prepare the request options
            $requestOptions = [];

            // Process headers with dynamic values
            $headers = self::processHeaders($action->config['headers'] ?? [], $data);
            $requestOptions['headers'] = $headers;

            // Add authentication if provided
            if (!empty($action->config['auth_token'])) {
                //if the token is __self__, use the auth token of the current user
                if ($action->config['auth_token'] === '__self__') {
                    $requestOptions['headers']['Authorization'] = 'Bearer ' . auth()->user()->generateAccessToken("internal_server_call")->raw_token;
                    log_message('info', 'Using self-generated auth token for API call');
                } else {
                    // Otherwise, use the provided token
                    log_message('info', 'Using provided auth token for API call');
                    $requestOptions['headers']['Authorization'] = 'Bearer ' . $action->config['auth_token'];
                }

            }

            // Process body mapping for POST/PUT requests
            $body = [];
            if (!empty($action->config['body_mapping'])) {
                $body = self::mapDataToBody($action->config['body_mapping'], $data);
            }

            // Process query parameters for GET requests
            $queryParams = [];
            if (!empty($action->config['query_params'])) {
                $queryParams = self::mapDataToQueryParams($action->config['query_params'], $data);
            }

            // Build the full URL with query parameters if needed
            $url = $action->config['endpoint'];
            if (!empty($queryParams) && $action->config['method'] === 'GET') {
                $url .= '?' . http_build_query($queryParams);
            }

            // Make the API call based on method
            $response = null;
            switch (strtoupper($action->config['method'])) {
                case 'GET':
                    $response = NetworkUtils::makeGetRequest($url, $requestOptions);
                    break;
                case 'POST':
                    $requestOptions['json'] = $body;
                    $response = NetworkUtils::makePostRequest($url, $requestOptions);
                    break;
                case 'PUT':
                    $requestOptions['json'] = $body;
                    $response = NetworkUtils::makePutRequest($url, $requestOptions);
                    break;
                case 'DELETE':
                    if (!empty($body)) {
                        $requestOptions['json'] = $body;
                    }
                    $response = NetworkUtils::makeDeleteRequest($url, $requestOptions);
                    break;
                default:
                    log_message('error', 'Unsupported HTTP method: ' . $action->config['method']);
                    throw new \InvalidArgumentException('Unsupported HTTP method: ' . $action->config['method']);
            }

            // Log the response for debugging
            log_message('info', 'API call response: ' . json_encode($response));

            return $data;

        } catch (\Throwable $e) {
            log_message('error', 'API call failed: ' . $e->getMessage());
            log_message('error', 'API call stack trace: ' . $e->getTraceAsString());
            throw $e;
        }
    }

    /**
     * Process headers with dynamic values from application data
     * @param array $headers
     * @param array $data
     * @return array
     */
    private static function processHeaders($headers, $data)
    {
        $processedHeaders = [];

        foreach ($headers as $key => $value) {
            // Support template variables in headers like {{field_name}}
            $processedValue = self::replacePlaceholders($value, $data);
            $processedHeaders[$key] = $processedValue;
        }

        return $processedHeaders;
    }

    /**
     * Map application data to API body parameters
     * @param array $bodyMapping Array where key is API field and value is application field or static value
     * @param array $data Application data
     * @return array
     */
    private static function mapDataToBody($bodyMapping, $data)
    {
        $body = [];

        foreach ($bodyMapping as $apiField => $mapping) {
            if (is_array($mapping)) {
                // Handle complex mapping with transformations
                $body[$apiField] = self::processComplexMapping($mapping, $data);
            } else {
                // Simple field mapping or static value
                $body[$apiField] = self::getValueFromMapping($mapping, $data);
            }
        }

        return $body;
    }

    /**
     * Map application data to query parameters
     * @param array $queryMapping
     * @param array $data
     * @return array
     */
    private static function mapDataToQueryParams($queryMapping, $data)
    {
        $params = [];

        foreach ($queryMapping as $paramName => $mapping) {
            $value = self::getValueFromMapping($mapping, $data);
            if ($value !== null && $value !== '') {
                $params[$paramName] = $value;
            }
        }

        return $params;
    }

    /**
     * Get value from mapping configuration
     * @param string|array $mapping
     * @param array $data
     * @return mixed
     */
    private static function getValueFromMapping($mapping, $data)
    {
        if (is_string($mapping)) {
            // Check if it's a field reference (starts with @)
            if (strpos($mapping, '@') === 0) {
                $fieldName = substr($mapping, 1);
                return $data[$fieldName] ?? null;
            }

            // Check if it's a template with placeholders
            if (strpos($mapping, '{{') !== false) {
                return self::replacePlaceholders($mapping, $data);
            }

            // Static value
            return $mapping;
        }

        return null;
    }

    /**
     * Process complex mapping with transformations
     * @param array $mapping
     * @param array $data
     * @return mixed
     */
    private static function processComplexMapping($mapping, $data)
    {
        $source = $mapping['source'] ?? null;
        $transform = $mapping['transform'] ?? null;
        $default = $mapping['default'] ?? null;

        // Get the source value
        $value = null;
        if ($source) {
            if (strpos($source, '@') === 0) {
                $fieldName = substr($source, 1);
                $value = $data[$fieldName] ?? $default;
            } else {
                $value = $source;
            }
        }

        // Apply transformations
        if ($transform && $value !== null) {
            switch ($transform['type']) {
                case 'date_format':
                    if (!empty($value)) {
                        $fromFormat = $transform['from'] ?? 'Y-m-d';
                        $toFormat = $transform['to'] ?? 'Y-m-d H:i:s';
                        $date = \DateTime::createFromFormat($fromFormat, $value);
                        $value = $date ? $date->format($toFormat) : $value;
                    }
                    break;
                case 'uppercase':
                    $value = strtoupper($value);
                    break;
                case 'lowercase':
                    $value = strtolower($value);
                    break;
                case 'trim':
                    $value = trim($value);
                    break;
                case 'concat':
                    $prefix = $transform['prefix'] ?? '';
                    $suffix = $transform['suffix'] ?? '';
                    $value = $prefix . $value . $suffix;
                    break;
            }
        }

        return $value ?? $default;
    }

    /**
     * Replace placeholders in string with actual data values
     * @param string $template
     * @param array $data
     * @return string
     */
    private static function replacePlaceholders($template, $data)
    {
        return preg_replace_callback('/\{\{([^}]+)\}\}/', function ($matches) use ($data) {
            $fieldName = trim($matches[1]);
            return $data[$fieldName] ?? $matches[0];
        }, $template);
    }
}