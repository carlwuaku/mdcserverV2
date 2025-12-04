<?php

namespace App\Services;

use App\Models\ApiIntegration\ApiKeyModel;
use App\Models\ApiIntegration\ApiKeyPermissionModel;
use App\Models\ApiIntegration\InstitutionModel;

class ApiKeyService
{
    private ApiKeyModel $apiKeyModel;
    private ApiKeyPermissionModel $apiKeyPermissionModel;
    private InstitutionModel $institutionModel;

    public function __construct()
    {
        $this->apiKeyModel = new ApiKeyModel();
        $this->apiKeyPermissionModel = new ApiKeyPermissionModel();
        $this->institutionModel = new InstitutionModel();
    }

    /**
     * Generate a new API key pair
     *
     * @return array ['key_id' => string, 'key_secret' => string, 'last_4_secret' => string, 'key_secret_hash' => string, 'hmac_secret' => string]
     */
    public function generateKeyPair(): array
    {
        // Generate key ID (public identifier)
        $keyId = 'mdc_' . bin2hex(random_bytes(16)); // 32 chars + prefix

        // Generate secret key (private, only shown once)
        $keySecret = base64_encode(random_bytes(48)); // 64 chars base64

        // Hash the secret for storage (for authentication)
        $keySecretHash = password_hash($keySecret, PASSWORD_ARGON2ID);

        // Generate separate HMAC secret for signature verification
        $hmacSecret = base64_encode(random_bytes(32)); // 43 chars base64

        // Encrypt HMAC secret for storage (using CI4's encryption)
        $encrypter = service('encrypter');
        $hmacSecretEncrypted = bin2hex($encrypter->encrypt($hmacSecret));

        // Store last 4 characters for identification
        $last4Secret = substr($keySecret, -4);

        return [
            'key_id' => $keyId,
            'key_secret' => $keySecret, // Only returned once, never stored plaintext
            'last_4_secret' => $last4Secret,
            'key_secret_hash' => $keySecretHash,
            'hmac_secret' => $hmacSecretEncrypted, // Stored encrypted for HMAC operations
            'hmac_secret_plaintext' => $hmacSecret, // Only returned once, never stored
        ];
    }

    /**
     * Create a new API key for an institution
     *
     * @param string $institutionId
     * @param array $data Key data (name, rate_limits, expires_at, etc.)
     * @param array $permissions Optional array of permission names
     * @param int|null $createdBy User ID who created the key
     * @return array|false Returns key data with plaintext secret on success, false on failure
     */
    public function createApiKey(string $institutionId, array $data, array $permissions = [], ?int $createdBy = null)
    {
        // Verify institution exists
        $institution = $this->institutionModel->find($institutionId);
        if (!$institution) {
            return false;
        }

        // Generate key pair
        $keyPair = $this->generateKeyPair();

        // Prepare API key data
        $apiKeyData = [
            'institution_id' => $institutionId,
            'name' => $data['name'] ?? 'API Key',
            'key_id' => $keyPair['key_id'],
            'key_secret_hash' => $keyPair['key_secret_hash'],
            'hmac_secret' => $keyPair['hmac_secret'],
            'last_4_secret' => $keyPair['last_4_secret'],
            'status' => 'active',
            'rate_limit_per_minute' => $data['rate_limit_per_minute'] ?? 60,
            'rate_limit_per_day' => $data['rate_limit_per_day'] ?? 10000,
            'scopes' => $data['scopes'] ?? null,
            'allowed_endpoints' => $data['allowed_endpoints'] ?? null,
            'metadata' => $data['metadata'] ?? null,
            'expires_at' => $data['expires_at'] ?? null,
            'created_by' => $createdBy,
        ];

        // Insert API key
        $apiKeyId = $this->apiKeyModel->insert($apiKeyData);

        if (!$apiKeyId) {
            return false;
        }

        // Set permissions if provided
        if (!empty($permissions)) {
            $this->apiKeyPermissionModel->setPermissions($apiKeyId, $permissions);
        }

        // Get complete key data
        $createdKey = $this->apiKeyModel->find($apiKeyId);

        // Add plaintext secrets (only time they're available)
        $createdKey['key_secret'] = $keyPair['key_secret'];
        $createdKey['hmac_secret_plaintext'] = $keyPair['hmac_secret_plaintext'];
        $createdKey['institution'] = $institution;

        return $createdKey;
    }

    /**
     * Verify API key credentials
     *
     * @param string $keyId
     * @param string $keySecret
     * @return array|false Returns key data if valid, false otherwise
     */
    public function verifyKey(string $keyId, string $keySecret)
    {
        $apiKey = $this->apiKeyModel->findByKeyId($keyId);

        if (!$apiKey) {
            return false;
        }

        // Check if key is expired
        if ($this->apiKeyModel->isExpired($apiKey)) {
            return false;
        }

        // Verify secret
        if (!password_verify($keySecret, $apiKey['key_secret_hash'])) {
            return false;
        }

        return $apiKey;
    }

    /**
     * Revoke an API key
     *
     * @param string $apiKeyId
     * @param string $reason
     * @param int|null $revokedBy
     * @return bool
     */
    public function revokeKey(string $apiKeyId, string $reason, ?int $revokedBy = null): bool
    {
        return $this->apiKeyModel->revokeKey($apiKeyId, $reason, $revokedBy);
    }

    /**
     * Get permissions for an API key
     *
     * @param string $apiKeyId
     * @return array
     */
    public function getKeyPermissions(string $apiKeyId): array
    {
        return $this->apiKeyPermissionModel->getPermissions($apiKeyId);
    }

    /**
     * Check if API key has specific permission
     *
     * @param string $apiKeyId
     * @param string $permission
     * @return bool
     */
    public function hasPermission(string $apiKeyId, string $permission): bool
    {
        return $this->apiKeyPermissionModel->hasPermission($apiKeyId, $permission);
    }

    /**
     * Update API key permissions
     *
     * @param string $apiKeyId
     * @param array $permissions
     * @return bool
     */
    public function updatePermissions(string $apiKeyId, array $permissions): bool
    {
        return $this->apiKeyPermissionModel->setPermissions($apiKeyId, $permissions);
    }

    /**
     * Rotate API key (generate new secret)
     *
     * @param string $apiKeyId
     * @return array|false Returns new key data with plaintext secret on success
     */
    public function rotateKey(string $apiKeyId)
    {
        $existingKey = $this->apiKeyModel->find($apiKeyId);

        if (!$existingKey) {
            return false;
        }

        // Generate new key pair
        $keyPair = $this->generateKeyPair();

        // Update with new credentials
        $updateData = [
            'key_id' => $keyPair['key_id'],
            'key_secret_hash' => $keyPair['key_secret_hash'],
            'last_4_secret' => $keyPair['last_4_secret'],
        ];

        if (!$this->apiKeyModel->update($apiKeyId, $updateData)) {
            return false;
        }

        $updatedKey = $this->apiKeyModel->find($apiKeyId);
        $updatedKey['key_secret'] = $keyPair['key_secret'];

        return $updatedKey;
    }

    /**
     * Generate documentation for API integration
     *
     * @param string $apiKeyId
     * @return array Documentation data
     */
    public function generateDocumentation(string $apiKeyId): array
    {
        $apiKey = $this->apiKeyModel->find($apiKeyId);
        $institution = $this->institutionModel->find($apiKey['institution_id']);
        $permissions = $this->apiKeyPermissionModel->getPermissions($apiKeyId);

        $baseUrl = base_url();

        return [
            'institution' => $institution['name'],
            'key_id' => $apiKey['key_id'],
            'created_at' => $apiKey['created_at'],
            'expires_at' => $apiKey['expires_at'],
            'rate_limits' => [
                'per_minute' => $apiKey['rate_limit_per_minute'],
                'per_day' => $apiKey['rate_limit_per_day'],
            ],
            'permissions' => $permissions,
            'authentication' => [
                'method' => 'HMAC-SHA256',
                'description' => 'All API requests must be authenticated using HMAC-SHA256 signature',
            ],
            'endpoints' => [
                'base_url' => $baseUrl . '/api',
                'example_endpoints' => $this->getAvailableEndpoints($permissions),
            ],
            'integration_guide' => $this->getIntegrationGuide($apiKey['key_id']),
        ];
    }

    /**
     * Get available endpoints based on permissions
     */
    private function getAvailableEndpoints(array $permissions): array
    {
        $endpointMap = [
            'View_Practitioners' => 'GET /api/external/practitioners',
            'View_License_Details' => 'GET /api/external/licenses/{license_number}',
            'Verify_License' => 'GET /api/external/licenses/verify/{license_number}',
            'View_CPD' => 'GET /api/external/cpd',
            'Create_Application' => 'POST /api/external/applications',
        ];

        $availableEndpoints = [];
        foreach ($permissions as $permission) {
            if (isset($endpointMap[$permission])) {
                $availableEndpoints[] = $endpointMap[$permission];
            }
        }

        return $availableEndpoints;
    }

    /**
     * Get integration guide with code examples
     */
    private function getIntegrationGuide(string $keyId): array
    {
        return [
            'step_1' => 'Store your API Key ID and Secret securely (never commit to version control)',
            'step_2' => 'For each request, generate HMAC signature',
            'step_3' => 'Include required headers: X-API-Key, X-Signature, X-Timestamp',
            'example_request' => [
                'method' => 'GET',
                'url' => base_url() . '/api/external/practitioners',
                'headers' => [
                    'X-API-Key' => $keyId,
                    'X-Signature' => '{HMAC-SHA256 signature}',
                    'X-Timestamp' => '{Unix timestamp}',
                    'Content-Type' => 'application/json',
                ],
            ],
            'signature_generation' => [
                'algorithm' => 'HMAC-SHA256',
                'message_format' => '{METHOD}:{PATH}:{TIMESTAMP}:{BODY_HASH}',
                'example_php' => $this->getPhpExample($keyId),
                'example_python' => $this->getPythonExample($keyId),
                'example_javascript' => $this->getJavaScriptExample($keyId),
            ],
        ];
    }

    private function getPhpExample(string $keyId): string
    {
        return <<<'PHP'
<?php
$keyId = 'YOUR_KEY_ID';
$keySecret = 'YOUR_KEY_SECRET';
$method = 'GET';
$path = '/api/external/practitioners';
$timestamp = time();
$body = ''; // Empty for GET requests

// Generate body hash
$bodyHash = hash('sha256', $body);

// Create signature message
$message = "{$method}:{$path}:{$timestamp}:{$bodyHash}";

// Generate HMAC signature
$signature = hash_hmac('sha256', $message, $keySecret);

// Make request with headers
$ch = curl_init('https://your-api-domain.com' . $path);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'X-API-Key: ' . $keyId,
    'X-Signature: ' . $signature,
    'X-Timestamp: ' . $timestamp,
    'Content-Type: application/json',
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);
PHP;
    }

    private function getPythonExample(string $keyId): string
    {
        return <<<'PYTHON'
import hmac
import hashlib
import time
import requests

key_id = 'YOUR_KEY_ID'
key_secret = 'YOUR_KEY_SECRET'
method = 'GET'
path = '/api/external/practitioners'
timestamp = str(int(time.time()))
body = ''  # Empty for GET requests

# Generate body hash
body_hash = hashlib.sha256(body.encode()).hexdigest()

# Create signature message
message = f"{method}:{path}:{timestamp}:{body_hash}"

# Generate HMAC signature
signature = hmac.new(
    key_secret.encode(),
    message.encode(),
    hashlib.sha256
).hexdigest()

# Make request
headers = {
    'X-API-Key': key_id,
    'X-Signature': signature,
    'X-Timestamp': timestamp,
    'Content-Type': 'application/json',
}

response = requests.get('https://your-api-domain.com' + path, headers=headers)
print(response.json())
PYTHON;
    }

    private function getJavaScriptExample(string $keyId): string
    {
        return <<<'JAVASCRIPT'
const crypto = require('crypto');
const axios = require('axios');

const keyId = 'YOUR_KEY_ID';
const keySecret = 'YOUR_KEY_SECRET';
const method = 'GET';
const path = '/api/external/practitioners';
const timestamp = Math.floor(Date.now() / 1000).toString();
const body = ''; // Empty for GET requests

// Generate body hash
const bodyHash = crypto.createHash('sha256').update(body).digest('hex');

// Create signature message
const message = `${method}:${path}:${timestamp}:${bodyHash}`;

// Generate HMAC signature
const signature = crypto
  .createHmac('sha256', keySecret)
  .update(message)
  .digest('hex');

// Make request
axios.get('https://your-api-domain.com' + path, {
  headers: {
    'X-API-Key': keyId,
    'X-Signature': signature,
    'X-Timestamp': timestamp,
    'Content-Type': 'application/json',
  },
})
.then(response => console.log(response.data))
.catch(error => console.error(error));
JAVASCRIPT;
    }
}
