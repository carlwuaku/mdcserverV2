<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Swagger extends BaseConfig
{
    /**
     * The output directory for generated documentation
     */
    public string $outputDir = WRITEPATH . 'swagger';

    /**
     * Directories to scan for annotations
     */
    public array $scanDirs = [
        APPPATH . 'Controllers'
    ];

    /**
     * Basic info for the API documentation
     */
    public array $openapi = [
        'title' => 'MDC Server API Documentation',
        'version' => '1.0.0',
        'description' => 'API documentation for MDC Server',
    ];

    /**
     * Security schemes
     */
    public array $securitySchemes = [
        'bearerAuth' => [
            'type' => 'http',
            'scheme' => 'bearer',
            'bearerFormat' => 'JWT'
        ]
    ];
} 