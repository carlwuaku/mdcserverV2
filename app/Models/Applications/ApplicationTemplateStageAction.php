<?php

namespace App\Models\Applications;

/**
 * Base Template Action Class
 */
abstract class TemplateAction
{
    public string $type;
    public array $config;

    public function __construct(string $type, array $config = [])
    {
        $this->type = $type;
        $this->config = $config;
        $this->validateConfig();
    }

    /**
     * Validate the configuration for this action type
     */
    abstract protected function validateConfig(): void;

    /**
     * Get the action as an array (for JSON serialization)
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'config' => $this->config
        ];
    }

    /**
     * Create action from array data
     */
    public static function fromArray(array $data): TemplateAction
    {
        $type = $data['type'] ?? throw new \InvalidArgumentException('Action type is required');
        $config = $data['config'] ?? [];

        return match ($type) {
            'email' => new EmailAction($config),
            'admin_email' => new AdminEmailAction($config),
            'api_call' => new ApiCallAction($config),
            'database_update' => new DatabaseUpdateAction($config),
            'webhook' => new WebhookAction($config),
            'file_generation' => new FileGenerationAction($config),
            'sms_notification' => new SmsNotificationAction($config),
            'slack_notification' => new SlackNotificationAction($config),
            default => throw new \InvalidArgumentException("Unsupported action type: {$type}")
        };
    }
}

/**
 * Email Action Class
 */
class EmailAction extends TemplateAction
{
    public string $template;
    public string $subject;
    public bool $useDefaultStyling;
    public array $attachments;

    public function __construct(array $config)
    {
        $this->template = $config['template'] ?? '';
        $this->subject = $config['subject'] ?? '';
        $this->useDefaultStyling = $config['use_default_styling'] ?? true;
        $this->attachments = $config['attachments'] ?? [];

        parent::__construct('email', $config);
    }

    protected function validateConfig(): void
    {
        if (empty($this->template)) {
            throw new \InvalidArgumentException('Email template is required');
        }

        if (empty($this->subject)) {
            throw new \InvalidArgumentException('Email subject is required');
        }
    }

    public function getTemplate(): string
    {
        return $this->template;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function hasAttachments(): bool
    {
        return !empty($this->attachments);
    }

    public function getAttachments(): array
    {
        return $this->attachments;
    }
}

/**
 * Admin Email Action Class
 */
class AdminEmailAction extends TemplateAction
{
    public string $template;
    public string $subject;
    public string $adminEmail;
    public bool $useDefaultStyling;

    public function __construct(array $config)
    {
        $this->template = $config['template'] ?? '';
        $this->subject = $config['subject'] ?? '';
        $this->adminEmail = $config['admin_email'] ?? '';
        $this->useDefaultStyling = $config['use_default_styling'] ?? true;

        parent::__construct('admin_email', $config);
    }

    protected function validateConfig(): void
    {
        if (empty($this->template)) {
            throw new \InvalidArgumentException('Email template is required');
        }

        if (empty($this->subject)) {
            throw new \InvalidArgumentException('Email subject is required');
        }

        if (empty($this->adminEmail)) {
            throw new \InvalidArgumentException('Admin email is required');
        }

        // Basic email validation (can be template)
        if (!str_contains($this->adminEmail, '{{') && !filter_var($this->adminEmail, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Invalid admin email format');
        }
    }

    public function getAdminEmail(): string
    {
        return $this->adminEmail;
    }
}

/**
 * API Call Action Class
 */
class ApiCallAction extends TemplateAction
{
    public string $endpoint;
    public string $method;
    public ?string $authToken;
    public array $headers;
    public array $bodyMapping;
    public array $queryParams;
    public int $timeout;
    public int $retryAttempts;

    /**
     * Creates a new ApiCallAction instance.
     *
     * @param array $config action configuration
     *     - endpoint: string, API endpoint URL (required)
     *     - method: string, HTTP method (GET, POST, PUT, DELETE, PATCH) (default: GET)
     *     - auth_token: string|null, authentication token (optional)
     *     - headers: array, custom HTTP headers (optional)
     *     - body_mapping: array, API body mapping (optional)
     *     - query_params: array, query parameters (optional)
     *     - timeout: int, request timeout in seconds (default: 30)
     *     - retry_attempts: int, number of retry attempts (default: 0)
     */
    public function __construct(array $config)
    {
        $this->endpoint = $config['endpoint'] ?? '';
        $this->method = strtoupper($config['method'] ?? 'GET');
        $this->authToken = $config['auth_token'] ?? null;
        $this->headers = $config['headers'] ?? [];
        $this->bodyMapping = $config['body_mapping'] ?? [];
        $this->queryParams = $config['query_params'] ?? [];
        $this->timeout = $config['timeout'] ?? 30;
        $this->retryAttempts = $config['retry_attempts'] ?? 0;

        parent::__construct('api_call', $config);
    }

    protected function validateConfig(): void
    {
        if (empty($this->endpoint)) {
            throw new \InvalidArgumentException('API endpoint is required');
        }

        $allowedMethods = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH'];
        if (!in_array($this->method, $allowedMethods)) {
            throw new \InvalidArgumentException('Invalid HTTP method. Allowed: ' . implode(', ', $allowedMethods));
        }

        // Validate URL format (allow relative URLs)
        if (!filter_var($this->endpoint, FILTER_VALIDATE_URL) && !$this->isRelativeUrl($this->endpoint)) {
            throw new \InvalidArgumentException('Invalid endpoint URL format');
        }

        if ($this->timeout < 1 || $this->timeout > 300) {
            throw new \InvalidArgumentException('Timeout must be between 1 and 300 seconds');
        }

        if ($this->retryAttempts < 0 || $this->retryAttempts > 5) {
            throw new \InvalidArgumentException('Retry attempts must be between 0 and 5');
        }
    }

    private function isRelativeUrl(string $url): bool
    {
        return !preg_match('/^https?:\/\//', $url);
    }

    public function requiresBody(): bool
    {
        return in_array($this->method, ['POST', 'PUT', 'PATCH']);
    }

    public function hasAuthentication(): bool
    {
        return !empty($this->authToken);
    }

    public function getEndpoint(): string
    {
        return $this->endpoint;
    }

    public function getMethod(): string
    {
        return $this->method;
    }
}

/**
 * Database Update Action Class
 */
class DatabaseUpdateAction extends TemplateAction
{
    public string $table;
    public array $data;
    public array $conditions;
    public string $operation; // insert, update, delete

    public function __construct(array $config)
    {
        $this->table = $config['table'] ?? '';
        $this->data = $config['data'] ?? [];
        $this->conditions = $config['conditions'] ?? [];
        $this->operation = $config['operation'] ?? 'insert';

        parent::__construct('database_update', $config);
    }

    protected function validateConfig(): void
    {
        if (empty($this->table)) {
            throw new \InvalidArgumentException('Database table is required');
        }

        $allowedOperations = ['insert', 'update', 'delete'];
        if (!in_array($this->operation, $allowedOperations)) {
            throw new \InvalidArgumentException('Invalid operation. Allowed: ' . implode(', ', $allowedOperations));
        }

        if ($this->operation !== 'delete' && empty($this->data)) {
            throw new \InvalidArgumentException('Data is required for insert/update operations');
        }

        if (in_array($this->operation, ['update', 'delete']) && empty($this->conditions)) {
            throw new \InvalidArgumentException('Conditions are required for update/delete operations');
        }
    }

    public function getTable(): string
    {
        return $this->table;
    }

    public function getOperation(): string
    {
        return $this->operation;
    }
}

/**
 * Webhook Action Class
 */
class WebhookAction extends TemplateAction
{
    public string $url;
    public string $method;
    public array $headers;
    public array $payload;
    public ?string $secret;
    public bool $verifySSL;

    public function __construct(array $config)
    {
        $this->url = $config['url'] ?? '';
        $this->method = strtoupper($config['method'] ?? 'POST');
        $this->headers = $config['headers'] ?? [];
        $this->payload = $config['payload'] ?? [];
        $this->secret = $config['secret'] ?? null;
        $this->verifySSL = $config['verify_ssl'] ?? true;

        parent::__construct('webhook', $config);
    }

    protected function validateConfig(): void
    {
        if (empty($this->url)) {
            throw new \InvalidArgumentException('Webhook URL is required');
        }

        if (!filter_var($this->url, FILTER_VALIDATE_URL)) {
            throw new \InvalidArgumentException('Invalid webhook URL format');
        }

        $allowedMethods = ['POST', 'PUT', 'PATCH'];
        if (!in_array($this->method, $allowedMethods)) {
            throw new \InvalidArgumentException('Invalid webhook method. Allowed: ' . implode(', ', $allowedMethods));
        }
    }

    public function hasSecret(): bool
    {
        return !empty($this->secret);
    }
}

/**
 * File Generation Action Class
 */
class FileGenerationAction extends TemplateAction
{
    public string $template;
    public string $filename;
    public string $format; // pdf, docx, txt, html
    public string $outputPath;
    public bool $attachToEmail;

    public function __construct(array $config)
    {
        $this->template = $config['template'] ?? '';
        $this->filename = $config['filename'] ?? '';
        $this->format = $config['format'] ?? 'pdf';
        $this->outputPath = $config['output_path'] ?? '';
        $this->attachToEmail = $config['attach_to_email'] ?? false;

        parent::__construct('file_generation', $config);
    }

    protected function validateConfig(): void
    {
        if (empty($this->template)) {
            throw new \InvalidArgumentException('File template is required');
        }

        if (empty($this->filename)) {
            throw new \InvalidArgumentException('Filename is required');
        }

        $allowedFormats = ['pdf', 'docx', 'txt', 'html'];
        if (!in_array($this->format, $allowedFormats)) {
            throw new \InvalidArgumentException('Invalid format. Allowed: ' . implode(', ', $allowedFormats));
        }
    }

    public function getFormat(): string
    {
        return $this->format;
    }

    public function shouldAttachToEmail(): bool
    {
        return $this->attachToEmail;
    }
}

/**
 * SMS Notification Action Class
 */
class SmsNotificationAction extends TemplateAction
{
    public string $message;
    public string $phoneField;
    public string $provider; // twilio, aws_sns, etc.
    public array $providerConfig;

    public function __construct(array $config)
    {
        $this->message = $config['message'] ?? '';
        $this->phoneField = $config['phone_field'] ?? 'phone';
        $this->provider = $config['provider'] ?? 'twilio';
        $this->providerConfig = $config['provider_config'] ?? [];

        parent::__construct('sms_notification', $config);
    }

    protected function validateConfig(): void
    {
        if (empty($this->message)) {
            throw new \InvalidArgumentException('SMS message is required');
        }

        if (empty($this->phoneField)) {
            throw new \InvalidArgumentException('Phone field is required');
        }

        $allowedProviders = ['twilio', 'aws_sns', 'nexmo'];
        if (!in_array($this->provider, $allowedProviders)) {
            throw new \InvalidArgumentException('Invalid SMS provider. Allowed: ' . implode(', ', $allowedProviders));
        }
    }
}

/**
 * Slack Notification Action Class
 */
class SlackNotificationAction extends TemplateAction
{
    public string $webhookUrl;
    public string $channel;
    public string $message;
    public string $username;
    public string $iconEmoji;
    public array $attachments;

    public function __construct(array $config)
    {
        $this->webhookUrl = $config['webhook_url'] ?? '';
        $this->channel = $config['channel'] ?? '';
        $this->message = $config['message'] ?? '';
        $this->username = $config['username'] ?? 'Application Bot';
        $this->iconEmoji = $config['icon_emoji'] ?? ':robot_face:';
        $this->attachments = $config['attachments'] ?? [];

        parent::__construct('slack_notification', $config);
    }

    protected function validateConfig(): void
    {
        if (empty($this->webhookUrl)) {
            throw new \InvalidArgumentException('Slack webhook URL is required');
        }

        if (!filter_var($this->webhookUrl, FILTER_VALIDATE_URL)) {
            throw new \InvalidArgumentException('Invalid webhook URL format');
        }

        if (empty($this->message)) {
            throw new \InvalidArgumentException('Slack message is required');
        }
    }

    public function hasAttachments(): bool
    {
        return !empty($this->attachments);
    }
}

/**
 * Template Action Factory Class
 */
class TemplateActionFactory
{
    /**
     * Create a template action from configuration array
     */
    public static function create(array $config): TemplateAction
    {
        return TemplateAction::fromArray($config);
    }

    /**
     * Create multiple actions from array of configurations
     */
    public static function createMultiple(array $configs): array
    {
        return array_map([self::class, 'create'], $configs);
    }

    /**
     * Validate action configuration without creating the object
     */
    public static function validateConfig(array $config): bool
    {
        try {
            self::create($config);
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Get validation errors for configuration
     */
    public static function getValidationErrors(array $config): array
    {
        try {
            self::create($config);
            return [];
        } catch (\Throwable $e) {
            return [$e->getMessage()];
        }
    }
}

/**
 * Template Action Collection Class
 */
class TemplateActionCollection
{
    private array $actions = [];

    public function add(TemplateAction $action): void
    {
        $this->actions[] = $action;
    }

    public function addFromArray(array $config): void
    {
        $this->actions[] = TemplateActionFactory::create($config);
    }

    public function getActions(): array
    {
        return $this->actions;
    }

    public function getActionsByType(string $type): array
    {
        return array_filter($this->actions, fn($action) => $action->type === $type);
    }

    public function hasActionsOfType(string $type): bool
    {
        return !empty($this->getActionsByType($type));
    }

    public function count(): int
    {
        return count($this->actions);
    }

    public function toArray(): array
    {
        return array_map(fn($action) => $action->toArray(), $this->actions);
    }

    public static function fromArray(array $data): self
    {
        $collection = new self();
        foreach ($data as $actionConfig) {
            $collection->addFromArray($actionConfig);
        }
        return $collection;
    }
}