<?php
namespace App\Helpers\Types;

class ApplicationFormTemplateActionConfig
{

    public ?string $template;
    public ?string $subject;
    public ?string $adminEmail;
    public ?string $endpoint;
    public ?string $method;
    public ?string $authToken;
    public ?array $headers;
    public ?array $bodyMapping;
    public ?array $queryParams;
    public ?string $payment_purpose;
    public ?array $payment_invoice_items;

    public function __construct(
        ?string $template = null,
        ?string $subject = null,
        ?string $adminEmail = null,
        ?string $endpoint = null,
        ?string $method = null,
        ?string $authToken = null,
        ?array $headers = null,
        ?array $bodyMapping = null,
        ?array $queryParams = null,
        ?string $payment_purpose = null,
        ?array $payment_invoice_items = null
    ) {
        $this->template = $template;
        $this->subject = $subject;
        $this->adminEmail = $adminEmail;
        $this->endpoint = $endpoint;
        $this->method = $method;
        $this->authToken = $authToken;
        $this->headers = $headers;
        $this->bodyMapping = $bodyMapping;
        $this->queryParams = $queryParams;
        $this->payment_purpose = $payment_purpose;
        $this->payment_invoice_items = $payment_invoice_items;
    }

    public function toArray(): array
    {
        $result = [];

        if ($this->template !== null)
            $result['template'] = $this->template;
        if ($this->subject !== null)
            $result['subject'] = $this->subject;
        if ($this->adminEmail !== null)
            $result['admin_email'] = $this->adminEmail;
        if ($this->endpoint !== null)
            $result['endpoint'] = $this->endpoint;
        if ($this->method !== null)
            $result['method'] = $this->method;
        if ($this->authToken !== null)
            $result['auth_token'] = $this->authToken;
        if ($this->headers !== null)
            $result['headers'] = $this->headers;
        if ($this->bodyMapping !== null)
            $result['body_mapping'] = $this->bodyMapping;
        if ($this->queryParams !== null)
            $result['query_params'] = $this->queryParams;
        if ($this->payment_purpose !== null)
            $result['payment_purpose'] = $this->payment_purpose;
        if ($this->payment_invoice_items !== null)
            $result['payment_invoice_items'] = $this->payment_invoice_items;

        return $result;
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['template'] ?? null,
            $data['subject'] ?? null,
            $data['admin_email'] ?? null,
            $data['endpoint'] ?? null,
            $data['method'] ?? null,
            $data['auth_token'] ?? null,
            $data['headers'] ?? null,
            $data['body_mapping'] ?? null,
            $data['query_params'] ?? null,
            $data['payment_purpose'] ?? null,
            $data['payment_invoice_items'] ?? null
        );
    }
}

class ApplicationStageType
{
    public const CONFIG_TYPE_EMAIL = 'email';
    public const CONFIG_TYPE_ADMIN_EMAIL = 'admin_email';
    public const CONFIG_TYPE_API_CALL = 'api_call';
    public const CONFIG_TYPE_INTERNAL_API_CALL = 'internal_api_call';
    public const CONFIG_TYPE_PAYMENT = 'payment';
    public const CONFIG_TYPE_PORTAL_EDIT = 'portal_edit';

    public const VALID_CONFIG_TYPES = [
        self::CONFIG_TYPE_EMAIL,
        self::CONFIG_TYPE_ADMIN_EMAIL,
        self::CONFIG_TYPE_API_CALL,
        self::CONFIG_TYPE_INTERNAL_API_CALL,
        self::CONFIG_TYPE_PAYMENT,
        self::CONFIG_TYPE_PORTAL_EDIT
    ];

    public string $type;
    public ?string $label;
    public string $config_type;
    public array $config;

    public array $criteria;

    public function __construct(
        string $type,
        ?string $label,
        string $configType,
        array $config,
        array $criteria = []
    ) {
        $this->validateConfigType($configType);

        $this->type = $type;
        $this->label = $label;
        $this->config_type = $configType;
        $this->config = $config;
        $this->criteria = $criteria;
    }

    /**
     * Validate that the config type is one of the allowed values
     */
    private function validateConfigType(string $configType): void
    {
        if (!in_array($configType, self::VALID_CONFIG_TYPES)) {
            throw new \InvalidArgumentException(
                "Invalid config_type '{$configType}'. Must be one of: " .
                implode(', ', self::VALID_CONFIG_TYPES)
            );
        }
    }

    /**
     * Validate configuration based on config type
     */
    public function validateConfig(): array
    {
        $errors = [];

        switch ($this->config_type) {
            case self::CONFIG_TYPE_EMAIL:
                if (empty($this->config['template'])) {
                    $errors[] = "Template is required for email config type";
                }
                if (empty($this->config['subject'])) {
                    $errors[] = "Subject is required for email config type";
                }
                break;

            case self::CONFIG_TYPE_ADMIN_EMAIL:
                if (empty($this->config['adminEmail'])) {
                    $errors[] = "Admin email is required for admin_email config type";
                }
                if (empty($this->config['subject'])) {
                    $errors[] = "Subject is required for admin_email config type";
                }
                break;

            case self::CONFIG_TYPE_PAYMENT:
                if (empty($this->config['payment_purpose'])) {
                    $errors[] = "Payment purpose is required for payment config type";
                }
                break;


            case self::CONFIG_TYPE_API_CALL:
            case self::CONFIG_TYPE_INTERNAL_API_CALL:
                if (empty($this->config['endpoint'])) {
                    $errors[] = "Endpoint is required for API call config types";
                }
                if (empty($this->config['method'])) {
                    $errors[] = "Method is required for API call config types";
                }
                break;
        }

        return $errors;
    }

    /**
     * Check if the action is valid
     */
    public function isValid(): bool
    {
        return empty($this->validateConfig());
    }

    /**
     * Check if this is an email-based action
     */
    public function isEmailAction(): bool
    {
        return in_array($this->config_type, [
            self::CONFIG_TYPE_EMAIL,
            self::CONFIG_TYPE_ADMIN_EMAIL
        ]);
    }

    /**
     * Check if this is an API-based action
     */
    public function isApiAction(): bool
    {
        return in_array($this->config_type, [
            self::CONFIG_TYPE_API_CALL,
            self::CONFIG_TYPE_INTERNAL_API_CALL
        ]);
    }

    /**
     * Get HTTP method for API actions
     */
    public function getHttpMethod(): ?string
    {
        return $this->isApiAction() ? $this->config['method'] : null;
    }

    /**
     * Get endpoint URL for API actions
     */
    public function getEndpoint(): ?string
    {
        return $this->isApiAction() ? $this->config['endpoint'] : null;
    }

    /**
     * Get email template for email actions
     */
    public function getEmailTemplate(): ?string
    {
        return $this->isEmailAction() ? $this->config['template'] : null;
    }

    /**
     * Get email subject for email actions
     */
    public function getEmailSubject(): ?string
    {
        return $this->isEmailAction() ? $this->config['subject'] : null;
    }

    /**
     * Convert to array representation
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'label' => $this->label,
            'config_type' => $this->config_type,
            'config' => $this->config,
            'criteria' => $this->criteria
        ];
    }

    /**
     * Create instance from array data
     */
    public static function fromArray(array $data): self
    {
        if (!isset($data['type']) || !isset($data['config_type']) || !isset($data['config'])) {
            throw new \InvalidArgumentException('Missing required fields: type, config, and config_type are required');
        }

        $config = $data['config'];// ApplicationFormTemplateActionConfig::fromArray($data['config'] ?? []);

        return new self(
            $data['type'],
            $data['label'] ?? null,
            $data['config_type'] ?? $data['configType'] ?? null,
            $config,
            $data['criteria'] ?? []
        );
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
     * Convert to JSON string
     */
    public function toJson(int $flags = 0): string
    {
        return json_encode($this->toArray(), $flags);
    }

    /**
     * Create an email action
     */
    public static function createEmailAction(
        string $type,
        string $label,
        string $template,
        string $subject
    ): self {
        $config = [
            'template' => $template,
            'subject' => $subject
        ];

        return new self($type, $label, self::CONFIG_TYPE_EMAIL, $config);
    }

    /**
     * Create an admin email action
     */
    public static function createAdminEmailAction(
        string $type,
        string $label,
        string $adminEmail,
        string $subject,
        ?string $template = null
    ): self {
        $config = [
            'template' => $template,
            'subject' => $subject,
            'adminEmail' => $adminEmail
        ];

        return new self($type, $label, self::CONFIG_TYPE_ADMIN_EMAIL, $config);
    }

    /**
     * Create an API call action
     */
    public static function createApiCallAction(
        string $type,
        string $label,
        string $endpoint,
        string $method,
        ?string $authToken = null,
        ?array $headers = null,
        ?array $bodyMapping = null,
        ?array $queryParams = null,
        bool $isInternal = false
    ): self {
        $config = [
            'endpoint' => $endpoint,
            'method' => $method,
            'authToken' => $authToken,
            'headers' => $headers,
            'bodyMapping' => $bodyMapping,
            'queryParams' => $queryParams
        ];

        $configType = $isInternal ? self::CONFIG_TYPE_INTERNAL_API_CALL : self::CONFIG_TYPE_API_CALL;

        return new self($type, $label, $configType, $config);
    }

}