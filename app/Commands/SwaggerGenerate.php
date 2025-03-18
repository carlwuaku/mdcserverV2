<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Swagger as SwaggerConfig;
use OpenApi\Generator;

class SwaggerGenerate extends BaseCommand
{
    protected $group = 'Swagger';
    protected $name = 'swagger:generate';
    protected $description = 'Generates OpenAPI documentation';

    public function run(array $params)
    {
        $config = new SwaggerConfig();

        // Create output directory if it doesn't exist
        if (!is_dir($config->outputDir)) {
            mkdir($config->outputDir, 0777, true);
        }

        try {
            $openapi = Generator::scan($config->scanDirs);
            
            // Add security schemes
            if (!empty($config->securitySchemes)) {
                $openapi->components->securitySchemes = $config->securitySchemes;
            }

            // Add basic info
            $openapi->info = $config->openapi;

            // Generate documentation
            $output = $openapi->toYaml();
            file_put_contents($config->outputDir . '/openapi.yaml', $output);

            // Also generate JSON version
            $output = $openapi->toJson();
            file_put_contents($config->outputDir . '/openapi.json', $output);

            CLI::write('OpenAPI documentation generated successfully!', 'green');
            CLI::write('Documentation saved to: ' . $config->outputDir);
        } catch (\Exception $e) {
            CLI::error($e->getMessage());
            CLI::error('Failed to generate OpenAPI documentation');
        }
    }
} 