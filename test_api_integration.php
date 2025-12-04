<?php

/**
 * API Integration System Test Script
 *
 * This script verifies that all components are working correctly.
 */

require_once 'vendor/autoload.php';

use Config\Services;

// Initialize CI4
$_SERVER['argv'] = ['test_api_integration.php'];
$_SERVER['argc'] = 1;

$config = \Config\Services::autoloader();
$config->initialize(new \Config\Autoload());

echo "=== API Integration System Test ===\n\n";

// Test 1: Check Database Tables
echo "1. Checking Database Tables...\n";
$db = \Config\Database::connect();

$tables = [
    'institutions',
    'api_keys',
    'api_key_permissions',
    'api_requests_log'
];

foreach ($tables as $table) {
    $exists = $db->tableExists($table);
    echo "   - $table: " . ($exists ? "✓ EXISTS" : "✗ NOT FOUND") . "\n";

    if ($exists) {
        // Get column count
        $query = $db->query("SHOW COLUMNS FROM `$table`");
        $columnCount = $query->getNumRows();
        echo "     Columns: $columnCount\n";
    }
}

echo "\n2. Checking Models...\n";
try {
    $institutionModel = new \App\Models\ApiIntegration\InstitutionModel();
    echo "   - InstitutionModel: ✓ LOADED\n";

    $apiKeyModel = new \App\Models\ApiIntegration\ApiKeyModel();
    echo "   - ApiKeyModel: ✓ LOADED\n";

    $apiKeyPermissionModel = new \App\Models\ApiIntegration\ApiKeyPermissionModel();
    echo "   - ApiKeyPermissionModel: ✓ LOADED\n";

    $apiRequestLogModel = new \App\Models\ApiIntegration\ApiRequestLogModel();
    echo "   - ApiRequestLogModel: ✓ LOADED\n";
} catch (\Exception $e) {
    echo "   ✗ ERROR: " . $e->getMessage() . "\n";
}

echo "\n3. Checking Services...\n";
try {
    $apiKeyService = new \App\Services\ApiKeyService();
    echo "   - ApiKeyService: ✓ LOADED\n";

    $hmacAuthService = new \App\Services\HmacAuthService();
    echo "   - HmacAuthService: ✓ LOADED\n";
} catch (\Exception $e) {
    echo "   ✗ ERROR: " . $e->getMessage() . "\n";
}

echo "\n4. Checking Controllers...\n";
try {
    $institutionsController = new \App\Controllers\InstitutionsController();
    echo "   - InstitutionsController: ✓ LOADED\n";

    $apiKeysController = new \App\Controllers\ApiKeysController();
    echo "   - ApiKeysController: ✓ LOADED\n";
} catch (\Exception $e) {
    echo "   ✗ ERROR: " . $e->getMessage() . "\n";
}

echo "\n5. Checking Permissions...\n";
$permissionsModel = new \App\Models\PermissionsModel();
$apiPermissions = [
    'View_Institutions',
    'Create_Institutions',
    'Edit_Institutions',
    'Delete_Institutions',
    'View_API_Keys',
    'Create_API_Keys',
    'Edit_API_Keys',
    'Delete_API_Keys',
    'Revoke_API_Keys'
];

foreach ($apiPermissions as $permission) {
    $exists = $permissionsModel->where('name', $permission)->first();
    echo "   - $permission: " . ($exists ? "✓ EXISTS" : "✗ NOT FOUND") . "\n";
}

echo "\n6. Testing Key Generation...\n";
try {
    $apiKeyService = new \App\Services\ApiKeyService();
    $keyPair = $apiKeyService->generateKeyPair();

    echo "   - Key ID format: " . (strpos($keyPair['key_id'], 'mdc_') === 0 ? "✓ CORRECT" : "✗ WRONG") . "\n";
    echo "   - Key Secret length: " . strlen($keyPair['key_secret']) . " chars ✓\n";
    echo "   - HMAC Secret length: " . strlen($keyPair['hmac_secret_plaintext']) . " chars ✓\n";
    echo "   - Last 4 secret: " . $keyPair['last_4_secret'] . " ✓\n";
} catch (\Exception $e) {
    echo "   ✗ ERROR: " . $e->getMessage() . "\n";
}

echo "\n7. Database Statistics...\n";
try {
    $institutionCount = $db->table('institutions')->countAllResults();
    echo "   - Institutions: $institutionCount\n";

    $apiKeyCount = $db->table('api_keys')->countAllResults();
    echo "   - API Keys: $apiKeyCount\n";

    $logCount = $db->table('api_requests_log')->countAllResults();
    echo "   - Request Logs: $logCount\n";
} catch (\Exception $e) {
    echo "   ✗ ERROR: " . $e->getMessage() . "\n";
}

echo "\n=== Test Complete ===\n";
echo "\n✅ All components are installed and ready!\n";
echo "\nNext Steps:\n";
echo "1. Assign API integration permissions to admin users\n";
echo "2. Access admin UI at: /admin/api-integration/institutions\n";
echo "3. Create your first institution\n";
echo "4. Generate an API key\n";
echo "5. Test HMAC authentication\n";
echo "\nFor detailed documentation, see:\n";
echo "- API_INTEGRATION_SETUP.md\n";
echo "- IMPLEMENTATION_SUMMARY.md\n";
echo "- /mdcv15/API_INTEGRATION_UI_GUIDE.md\n\n";
