<?php

namespace App\Services;

use App\Models\ApiIntegration\ApiKeyModel;
use App\Models\ApiIntegration\ApiKeyPermissionModel;
use App\Models\ApiIntegration\ApiRequestLogModel;
use App\Models\ApiIntegration\InstitutionModel;

class HmacAuthService
{
    private ApiKeyModel $apiKeyModel;
    private ApiKeyPermissionModel $apiKeyPermissionModel;
    private InstitutionModel $institutionModel;
    private ApiRequestLogModel $apiRequestLogModel;

    // Maximum allowed time difference (5 minutes)
    private const MAX_TIMESTAMP_DIFF = 300;

    public function __construct()
    {
        $this->apiKeyModel = new ApiKeyModel();
        $this->apiKeyPermissionModel = new ApiKeyPermissionModel();
        $this->institutionModel = new InstitutionModel();
        $this->apiRequestLogModel = new ApiRequestLogModel();
    }

    /**
     * Verify HMAC signature from request
     *
     * @param string $keyId
     * @param string $signature
     * @param string $timestamp
     * @param string $method
     * @param string $path
     * @param string $body
     * @return array|false Returns API key data if valid, false otherwise
     */
    public function verifyRequest(
        string $keyId,
        string $signature,
        string $timestamp,
        string $method,
        string $path,
        string $body = ''
    ) {
        // Validate timestamp
        if (!$this->validateTimestamp($timestamp)) {
            return [
                'valid' => false,
                'error' => 'Invalid or expired timestamp',
                'error_code' => 'INVALID_TIMESTAMP',
            ];
        }

        // Find API key
        $apiKey = $this->apiKeyModel->findByKeyId($keyId);

        if (!$apiKey) {
            return [
                'valid' => false,
                'error' => 'Invalid API key',
                'error_code' => 'INVALID_KEY',
            ];
        }

        // Check if key is expired
        if ($this->apiKeyModel->isExpired($apiKey)) {
            return [
                'valid' => false,
                'error' => 'API key has expired',
                'error_code' => 'KEY_EXPIRED',
            ];
        }

        // Check if key is revoked
        if ($apiKey['status'] !== 'active') {
            return [
                'valid' => false,
                'error' => 'API key is not active',
                'error_code' => 'KEY_INACTIVE',
            ];
        }

        // Get institution
        $institution = $this->institutionModel->find($apiKey['institution_id']);

        if (!$institution || $institution['status'] !== 'active') {
            return [
                'valid' => false,
                'error' => 'Institution is not active',
                'error_code' => 'INSTITUTION_INACTIVE',
            ];
        }

        // Check IP whitelist if configured
        if (!empty($institution['ip_whitelist'])) {
            $clientIp = $this->getClientIp();
            if (!$this->isIpAllowed($clientIp, $institution['ip_whitelist'])) {
                return [
                    'valid' => false,
                    'error' => 'IP address not whitelisted',
                    'error_code' => 'IP_NOT_ALLOWED',
                ];
            }
        }

        // Verify signature
        $expectedSignature = $this->generateSignature(
            $method,
            $path,
            $timestamp,
            $body,
            $apiKey['key_secret_hash']
        );

        // Note: We need to decrypt the stored hash to get the actual secret
        // Since we're using password_hash, we need a different approach
        // We'll verify by reconstructing the signature using the secret from verification
        if (!$this->verifySignature($signature, $method, $path, $timestamp, $body, $apiKey)) {
            return [
                'valid' => false,
                'error' => 'Invalid signature',
                'error_code' => 'INVALID_SIGNATURE',
            ];
        }

        // Check rate limits
        $rateLimitCheck = $this->checkRateLimits($apiKey['id'], $apiKey);

        if (!$rateLimitCheck['allowed']) {
            return [
                'valid' => false,
                'error' => $rateLimitCheck['error'],
                'error_code' => 'RATE_LIMIT_EXCEEDED',
                'retry_after' => $rateLimitCheck['retry_after'] ?? null,
            ];
        }

        // Check endpoint permissions if specified
        if (!empty($apiKey['allowed_endpoints'])) {
            if (!$this->isEndpointAllowed($path, $apiKey['allowed_endpoints'])) {
                return [
                    'valid' => false,
                    'error' => 'Endpoint not allowed for this API key',
                    'error_code' => 'ENDPOINT_NOT_ALLOWED',
                ];
            }
        }

        // Update last used
        $this->apiKeyModel->updateLastUsed($apiKey['id'], $this->getClientIp());

        return [
            'valid' => true,
            'api_key' => $apiKey,
            'institution' => $institution,
        ];
    }

    /**
     * Verify signature using stored HMAC secret
     */
    private function verifySignature(
        string $providedSignature,
        string $method,
        string $path,
        string $timestamp,
        string $body,
        array $apiKey
    ): bool {
        try {
            // Decrypt the HMAC secret
            $encrypter = service('encrypter');
            $hmacSecretEncrypted = hex2bin($apiKey['hmac_secret']);
            $hmacSecret = $encrypter->decrypt($hmacSecretEncrypted);

            // Generate body hash
            $bodyHash = hash('sha256', $body);

            // Create message
            $message = "{$method}:{$path}:{$timestamp}:{$bodyHash}";

            // Generate expected signature
            $expectedSignature = hash_hmac('sha256', $message, $hmacSecret);

            // Compare signatures (timing-safe)
            return hash_equals($expectedSignature, $providedSignature);
        } catch (\Exception $e) {
            log_message('error', 'HMAC signature verification failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Generate HMAC signature
     */
    public function generateSignature(
        string $method,
        string $path,
        string $timestamp,
        string $body,
        string $secret
    ): string {
        // Generate body hash
        $bodyHash = hash('sha256', $body);

        // Create message
        $message = "{$method}:{$path}:{$timestamp}:{$bodyHash}";

        // Generate HMAC
        return hash_hmac('sha256', $message, $secret);
    }

    /**
     * Validate timestamp
     */
    private function validateTimestamp(string $timestamp): bool
    {
        if (!is_numeric($timestamp)) {
            return false;
        }

        $now = time();
        $diff = abs($now - (int)$timestamp);

        return $diff <= self::MAX_TIMESTAMP_DIFF;
    }

    /**
     * Check rate limits
     */
    private function checkRateLimits(string $apiKeyId, array $apiKey): array
    {
        // Check per-minute limit
        $requestsLastMinute = $this->apiRequestLogModel->getRequestCount($apiKeyId, 60);

        if ($requestsLastMinute >= $apiKey['rate_limit_per_minute']) {
            return [
                'allowed' => false,
                'error' => 'Rate limit exceeded: too many requests per minute',
                'retry_after' => 60,
            ];
        }

        // Check per-day limit
        $requestsLastDay = $this->apiRequestLogModel->getRequestCount($apiKeyId, 86400);

        if ($requestsLastDay >= $apiKey['rate_limit_per_day']) {
            return [
                'allowed' => false,
                'error' => 'Rate limit exceeded: daily limit reached',
                'retry_after' => 86400 - (time() % 86400),
            ];
        }

        return ['allowed' => true];
    }

    /**
     * Check if IP is in whitelist
     */
    private function isIpAllowed(string $ip, array $whitelist): bool
    {
        foreach ($whitelist as $allowedIp) {
            // Support CIDR notation
            if (strpos($allowedIp, '/') !== false) {
                if ($this->ipInRange($ip, $allowedIp)) {
                    return true;
                }
            } else {
                if ($ip === $allowedIp) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check if IP is in CIDR range
     */
    private function ipInRange(string $ip, string $cidr): bool
    {
        list($subnet, $mask) = explode('/', $cidr);

        $ipLong = ip2long($ip);
        $subnetLong = ip2long($subnet);
        $maskLong = -1 << (32 - (int)$mask);
        $subnetLong &= $maskLong;

        return ($ipLong & $maskLong) === $subnetLong;
    }

    /**
     * Check if endpoint is allowed
     */
    private function isEndpointAllowed(string $path, array $allowedEndpoints): bool
    {
        foreach ($allowedEndpoints as $pattern) {
            // Convert wildcard pattern to regex
            $regex = str_replace(['*', '/'], ['.*', '\/'], $pattern);
            $regex = '/^' . $regex . '$/';

            if (preg_match($regex, $path)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get client IP address
     */
    private function getClientIp(): string
    {
        $request = service('request');

        // Check for forwarded IP (from proxy/load balancer)
        $forwardedFor = $request->getHeaderLine('X-Forwarded-For');
        if ($forwardedFor) {
            $ips = explode(',', $forwardedFor);
            return trim($ips[0]);
        }

        return $request->getIPAddress();
    }

    /**
     * Log API request
     */
    public function logRequest(
        ?array $apiKey,
        string $method,
        string $endpoint,
        int $responseStatus,
        ?int $responseTimeMs = null,
        ?string $errorMessage = null
    ): void {
        $request = service('request');

        $logData = [
            'api_key_id' => $apiKey['id'] ?? null,
            'institution_id' => $apiKey['institution_id'] ?? null,
            'method' => $method,
            'endpoint' => $endpoint,
            'query_params' => $request->getGet() ?? null,
            'request_body_size' => strlen($request->getBody()),
            'response_status' => $responseStatus,
            'response_time_ms' => $responseTimeMs,
            'ip_address' => $this->getClientIp(),
            'user_agent' => $request->getUserAgent()->__toString(),
            'error_message' => $errorMessage,
        ];

        $this->apiRequestLogModel->logRequest($logData);
    }
}
