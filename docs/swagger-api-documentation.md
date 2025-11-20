# Swagger API Documentation

## Overview

Swagger/OpenAPI documentation is now enabled for the MDC Server API. This provides an interactive interface to explore and test API endpoints.

## Accessing Swagger UI

1. **Start your development server:**
   ```bash
   php spark serve
   ```

2. **Open Swagger UI in your browser:**
   ```
   http://localhost:8080/api-docs
   ```

## Features

- **Interactive API Explorer**: Browse all available endpoints
- **Try It Out**: Test API calls directly from the browser
- **Authentication Support**: JWT Bearer token authentication
- **Request/Response Examples**: See example payloads for each endpoint
- **Schema Definitions**: View data models and validation rules

## How to Use

### 1. Authentication

Most endpoints require authentication. To use protected endpoints:

1. First, call the `/api/auth/login` endpoint to get a JWT token
2. Click the "Authorize" button at the top of the Swagger UI
3. Enter your JWT token (without the "Bearer" prefix)
4. Click "Authorize"
5. Now you can test protected endpoints

### 2. Testing Endpoints

1. Click on any endpoint to expand it
2. Click "Try it out"
3. Fill in required parameters and request body
4. Click "Execute"
5. View the response below

## Current Endpoints

The following endpoints are currently documented:

### Authentication
- `POST /api/auth/login` - User login and JWT token generation

### Licenses
- `GET /api/licenses` - Get paginated list of licenses
- `POST /api/licenses` - Create new license (with auto-generated license number)
- `GET /api/licenses/{uuid}` - Get specific license details
- `PUT /api/licenses/{uuid}` - Update license
- `DELETE /api/licenses/{uuid}` - Delete license (soft delete)

## Adding More Endpoints

### Option 1: Edit the OpenAPI JSON directly

Edit the file at `writable/swagger/openapi.json` and add your endpoints under the `paths` section.

Example:
```json
"/api/practitioners": {
  "get": {
    "tags": ["Practitioners"],
    "summary": "Get list of practitioners",
    "security": [{"bearerAuth": []}],
    "responses": {
      "200": {
        "description": "List of practitioners"
      }
    }
  }
}
```

### Option 2: Use Annotations (Future Enhancement)

You can add PHPDoc annotations to your controllers:

```php
/**
 * @OA\Get(
 *     path="/api/practitioners",
 *     summary="Get list of practitioners",
 *     tags={"Practitioners"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Response(
 *         response=200,
 *         description="List of practitioners"
 *     )
 * )
 */
public function index()
{
    // Your code here
}
```

Then regenerate the spec:
```bash
php generate_swagger.php
```

## Files and Structure

- `app/Controllers/SwaggerController.php` - Swagger UI controller
- `app/Views/swagger/index.php` - Swagger UI HTML template
- `app/Config/Swagger.php` - Swagger configuration
- `writable/swagger/openapi.json` - OpenAPI specification file
- `app/Config/Routes.php` - Routes for `/api-docs` and `/swagger/spec`
- `generate_swagger.php` - Script to generate spec from annotations

## Debugging Tips

### Swagger UI not loading?

1. Check that your server is running: `php spark serve`
2. Verify the routes are enabled in `app/Config/Routes.php`
3. Make sure `writable/swagger/openapi.json` exists and is valid JSON

### Authentication not working?

1. Ensure you're using a valid JWT token
2. Don't include "Bearer" prefix when entering the token
3. Check that the token hasn't expired

### Endpoints not showing?

1. Check that `writable/swagger/openapi.json` contains your endpoints
2. Refresh the browser (Ctrl+F5 or Cmd+Shift+R)
3. Clear browser cache

## VSCode Debugging with Swagger

Now that you have Swagger UI, you can:

1. **Test endpoints interactively** instead of using curl or Postman
2. **Set breakpoints** in VSCode in your controller methods
3. **Make requests** from Swagger UI while debugging is active
4. **Step through code** as the requests hit your endpoints

### Setting up VSCode Debugging:

1. Create `.vscode/launch.json`:
```json
{
    "version": "0.2.0",
    "configurations": [
        {
            "name": "Launch Server",
            "type": "php",
            "request": "launch",
            "port": 9003,
            "runtimeArgs": [
                "spark",
                "serve",
                "--host=localhost",
                "--port=8080"
            ],
            "pathMappings": {
                "/Users/carl/Documents/projects/mdc/mdcServer": "${workspaceFolder}"
            }
        }
    ]
}
```

2. Install Xdebug if not already installed
3. Start debugging in VSCode (F5)
4. Use Swagger UI to make requests
5. Breakpoints will be hit automatically

## Next Steps

1. **Add more endpoints**: Document all your API endpoints in the OpenAPI spec
2. **Add schemas**: Define reusable schemas for your data models
3. **Add examples**: Provide request/response examples for clarity
4. **Add descriptions**: Write detailed descriptions for complex endpoints
5. **Organize with tags**: Group related endpoints together

## Resources

- [OpenAPI Specification](https://swagger.io/specification/)
- [Swagger UI Documentation](https://swagger.io/tools/swagger-ui/)
- [swagger-php Documentation](https://zircote.github.io/swagger-php/)
