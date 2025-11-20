<?php

/**
 * OpenAPI/Swagger Base Configuration
 * This file contains the base OpenAPI configuration that will be picked up by swagger-php scanner
 *
 * @OA\Info(
 *     version="1.0.0",
 *     title="MDC Server API Documentation",
 *     description="API documentation for the MDC Server application - Medical/Healthcare Regulatory Management System for license management, practitioner registration, CPD tracking, and examinations"
 * )
 *
 * @OA\Server(
 *     url="http://localhost:8080",
 *     description="Local Development Server"
 * )
 *
 * @OA\Server(
 *     url="https://api.mdcghana.org",
 *     description="Production Server"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Enter your JWT token (without Bearer prefix)"
 * )
 *
 * @OA\Tag(
 *     name="Authentication",
 *     description="Authentication and authorization endpoints"
 * )
 *
 * @OA\Tag(
 *     name="Licenses",
 *     description="License management endpoints"
 * )
 *
 * @OA\Tag(
 *     name="Practitioners",
 *     description="Practitioner management endpoints"
 * )
 *
 * @OA\Tag(
 *     name="CPD",
 *     description="Continuing Professional Development endpoints"
 * )
 *
 * @OA\Tag(
 *     name="Applications",
 *     description="Application form management endpoints"
 * )
 *
 * @OA\Tag(
 *     name="Payments",
 *     description="Payment processing endpoints"
 * )
 *
 * @OA\Tag(
 *     name="Examinations",
 *     description="Examination management endpoints"
 * )
 *
 * @OA\Tag(
 *     name="Documentation",
 *     description="API documentation endpoints"
 * )
 */