<?php

namespace App\Filters;

use App\Services\HmacAuthService;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class HmacAuthFilter implements FilterInterface
{
    private HmacAuthService $hmacAuthService;

    public function __construct()
    {
        $this->hmacAuthService = new HmacAuthService();
    }

    /**
     * Verify HMAC authentication before request
     *
     * @param RequestInterface $request
     * @param array|null       $arguments Optional permissions required
     *
     * @return mixed
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        $response = service('response');
        $startTime = microtime(true);

        // Get required headers
        $keyId = $request->getHeaderLine('X-API-Key');
        $signature = $request->getHeaderLine('X-Signature');
        $timestamp = $request->getHeaderLine('X-Timestamp');

        // Check if headers are present
        if (empty($keyId) || empty($signature) || empty($timestamp)) {
            $this->logFailedRequest(null, $request, 401, 'Missing required authentication headers');
            return $response->setStatusCode(401)->setJSON([
                'error' => 'Missing required authentication headers',
                'error_code' => 'MISSING_HEADERS',
                'required_headers' => ['X-API-Key', 'X-Signature', 'X-Timestamp'],
            ]);
        }

        // Verify request
        $method = $request->getMethod();
        $path = $request->getUri()->getPath();
        $body = $request->getBody();

        $verificationResult = $this->hmacAuthService->verifyRequest(
            $keyId,
            $signature,
            $timestamp,
            $method,
            $path,
            $body
        );

        if (!$verificationResult['valid']) {
            $responseTime = (int)((microtime(true) - $startTime) * 1000);
            $this->logFailedRequest(null, $request, 401, $verificationResult['error'], $responseTime);

            $responseData = [
                'error' => $verificationResult['error'],
                'error_code' => $verificationResult['error_code'],
            ];

            if (isset($verificationResult['retry_after'])) {
                $response->setHeader('Retry-After', (string)$verificationResult['retry_after']);
                $responseData['retry_after'] = $verificationResult['retry_after'];
            }

            return $response->setStatusCode(401)->setJSON($responseData);
        }

        // Store API key and institution data in request for use in controllers
        $request->apiKey = $verificationResult['api_key'];
        $request->institution = $verificationResult['institution'];
        $request->hmacAuthStartTime = $startTime;

        // Check permissions if specified
        if ($arguments && is_array($arguments) && count($arguments) > 0) {
            $hasPermission = $this->checkPermissions(
                $verificationResult['api_key']['id'],
                $arguments
            );

            if (!$hasPermission) {
                $responseTime = (int)((microtime(true) - $startTime) * 1000);
                $this->hmacAuthService->logRequest(
                    $verificationResult['api_key'],
                    $method,
                    $path,
                    403,
                    $responseTime,
                    'Insufficient permissions'
                );

                return $response->setStatusCode(403)->setJSON([
                    'error' => 'Insufficient permissions',
                    'error_code' => 'PERMISSION_DENIED',
                ]);
            }
        }

        return null;
    }

    /**
     * Log request after processing
     *
     * @param RequestInterface  $request
     * @param ResponseInterface $response
     * @param array|null        $arguments
     *
     * @return mixed
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Log successful request
        if (isset($request->apiKey) && isset($request->hmacAuthStartTime)) {
            $responseTime = (int)((microtime(true) - $request->hmacAuthStartTime) * 1000);

            $this->hmacAuthService->logRequest(
                $request->apiKey,
                $request->getMethod(),
                $request->getUri()->getPath(),
                $response->getStatusCode(),
                $responseTime
            );
        }

        return $response;
    }

    /**
     * Check if API key has required permissions
     */
    private function checkPermissions(string $apiKeyId, array $requiredPermissions): bool
    {
        $apiKeyPermissionModel = new \App\Models\ApiIntegration\ApiKeyPermissionModel();

        foreach ($requiredPermissions as $permission) {
            if (!$apiKeyPermissionModel->hasPermission($apiKeyId, $permission)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Log failed request attempt
     */
    private function logFailedRequest(
        ?array $apiKey,
        RequestInterface $request,
        int $statusCode,
        string $errorMessage,
        ?int $responseTime = null
    ): void {
        $this->hmacAuthService->logRequest(
            $apiKey,
            $request->getMethod(),
            $request->getUri()->getPath(),
            $statusCode,
            $responseTime,
            $errorMessage
        );
    }
}
