<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Swagger as SwaggerConfig;
use OpenApi\Generator;

class GenerateSwagger extends BaseCommand
{
    protected $group       = 'Development';
    protected $name        = 'swagger:generate';
    protected $description = 'Generate OpenAPI/Swagger documentation from controller annotations';
    protected $usage       = 'swagger:generate';

    public function run(array $params)
    {
        $config = new SwaggerConfig();

        // Ensure output directory exists
        if (!is_dir($config->outputDir)) {
            mkdir($config->outputDir, 0755, true);
            CLI::write('Created output directory: ' . $config->outputDir, 'green');
        }

        CLI::write('Scanning directories for OpenAPI annotations...', 'yellow');

        foreach ($config->scanDirs as $dir) {
            CLI::write('  - ' . $dir);
        }

        try {
            // Generate the OpenAPI specification
            $openapi = Generator::scan($config->scanDirs);

            // Save to file
            $outputFile = $config->outputDir . '/openapi.json';
            file_put_contents($outputFile, $openapi->toJson());

            CLI::write('', 'green');
            CLI::write('✓ Swagger documentation generated successfully!', 'green');
            CLI::write('  Output: ' . $outputFile, 'green');
            CLI::write('', 'green');
            CLI::write('View documentation at: ' . base_url('api-docs'), 'cyan');
            CLI::write('', 'green');

        } catch (\Exception $e) {
            CLI::error('Error generating Swagger documentation:');
            CLI::error($e->getMessage());
            return EXIT_ERROR;
        }

        return EXIT_SUCCESS;
    }
}
