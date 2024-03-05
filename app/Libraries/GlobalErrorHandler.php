<?php

namespace App\Libraries;

use CodeIgniter\Debug\BaseExceptionHandler;
use CodeIgniter\Debug\ExceptionHandlerInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Throwable;

class GlobalErrorHandler extends BaseExceptionHandler implements ExceptionHandlerInterface
{
    
    public function handle(
        Throwable $exception,
        RequestInterface $request,
        ResponseInterface $response,
        int $statusCode,
        int $exitCode
    ): void {
        log_message("error", $exception->getMessage());
        $response->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        
        // Set the JSON response
        $response->setJSON(['message'=> 'Server error. Please try again!']);
        
        // Ensure the Content-Type header is set to application/json
        $response->setHeader('Content-Type', 'application/json');
        
        // Send the response back to the client
        $response->send();
        exit($exitCode);

    }
}