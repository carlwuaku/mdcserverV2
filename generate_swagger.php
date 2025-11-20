<?php

/**
 * Manual Swagger/OpenAPI specification generator
 * Run with: php generate_swagger.php
 */

require __DIR__ . '/vendor/autoload.php';

$openapi = \OpenApi\Generator::scan([
    __DIR__ . '/app/Controllers',
    __DIR__ . '/app/Config/OpenApi.php'
]);

// Ensure writable/swagger directory exists
$outputDir = __DIR__ . '/writable/swagger';
if (!is_dir($outputDir)) {
    mkdir($outputDir, 0755, true);
}

// Save the JSON spec
$outputFile = $outputDir . '/openapi.json';
file_put_contents($outputFile, $openapi->toJson());

echo "✓ Swagger documentation generated successfully!\n";
echo "  Output: $outputFile\n";
echo "\n";
echo "View documentation at: http://localhost:8080/api-docs\n";
