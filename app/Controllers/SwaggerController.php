<?php
/**
 * @OA\Info(
 *     version="1.0.0",
 *     title="MDC Server API Documentation",
 *     description="API documentation for the MDC Server application",
 *     @OA\Contact(
 *         email="admin@example.com",
 *         name="API Support"
 *     ),
 *     @OA\License(
 *         name="Apache 2.0",
 *         url="http://www.apache.org/licenses/LICENSE-2.0.html"
 *     )
 * )
 * 
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT"
 * )
 * 
 * @OA\Server(
 *     description="Local Development",
 *     url="http://localhost:8080"
 * )
 */

namespace App\Controllers;

use CodeIgniter\Controller;
use Config\Swagger as SwaggerConfig;

/**
 * @OA\Tag(
 *     name="Documentation",
 *     description="API Documentation interface endpoints"
 * )
 */
class SwaggerController extends Controller
{
    /**
     * @OA\Get(
     *     path="/swagger",
     *     summary="Display Swagger UI",
     *     description="Renders the Swagger UI interface for API documentation",
     *     tags={"Documentation"},
     *     @OA\Response(
     *         response=200,
     *         description="HTML page containing Swagger UI"
     *     )
     * )
     */
    public function index()
    {
        $config = new SwaggerConfig();

        return view('swagger/index', [
            'title' => $config->openapi['title'],
            'specUrl' => base_url('swagger/spec')
        ]);
    }

    /**
     * @OA\Get(
     *     path="/swagger/spec",
     *     summary="Get OpenAPI specification",
     *     description="Returns the OpenAPI/Swagger specification in JSON format",
     *     tags={"Documentation"},
     *     @OA\Response(
     *         response=200,
     *         description="OpenAPI specification in JSON format",
     *         @OA\JsonContent(type="object")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Specification file not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function spec()
    {
        $config = new SwaggerConfig();
        $specPath = $config->outputDir . '/openapi.json';

        if (!file_exists($specPath)) {
            return $this->response->setJSON(['error' => 'Swagger specification not found'])->setStatusCode(404);
        }

        $spec = file_get_contents($specPath);
        return $this->response
            ->setJSON(json_decode($spec))
            ->setHeader('Content-Type', 'application/json')
            ->setHeader('Access-Control-Allow-Origin', '*');
    }
}