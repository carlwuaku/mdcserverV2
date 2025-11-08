<?php

namespace App\Helpers;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class NetworkUtils
{
    private static $client = null;

    /**
     * Get or create Guzzle HTTP client
     */
    private static function getClient()
    {
        if (self::$client === null) {
            self::$client = new Client([
                // 'timeout' => 30, // 30 seconds timeout
                // 'connect_timeout' => 10, // 10 seconds connection timeout
                // 'read_timeout' => 30, // 30 seconds read timeout
                'http_errors' => false // Don't throw exceptions on HTTP error status codes
            ]);
        }

        return self::$client;
    }

    /**
     * Make a GET request
     * @param string $url
     * @param array $options
     * @return array
     */
    public static function makeGetRequest($url, $options = [])
    {
        try {
            log_message('info', 'Making GET request to: ' . $url);

            $client = self::getClient();
            $response = $client->get($url, $options);

            $result = [
                'status_code' => $response->getStatusCode(),
                'headers' => $response->getHeaders(),
                'body' => $response->getBody()->getContents(),
                'success' => true
            ];

            // Try to decode JSON response
            $jsonData = json_decode($result['body'], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $result['data'] = $jsonData;
            }

            log_message('info', 'GET request successful. Status: ' . $result['status_code']);

            return $result;

        } catch (RequestException $e) {
            log_message('error', 'GET request failed: ' . $e->getMessage());
            return self::handleRequestException($e);
        } catch (\Throwable $e) {
            log_message('error', 'GET request error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'status_code' => 500
            ];
        }
    }


    public static function makePostRequest($url, $options = [])
    {
        try {
            log_message('info', 'Making POST request to: ' . $url);


            $client = self::getClient();

            // Add default timeout if not specified
            // if (!isset($options['timeout'])) {
            //     $options['timeout'] = 30;
            // }

            $response = $client->post($url, $options);
            log_message('info', 'POST res data: ' . print_r($response, true));
            $statusCode = $response->getStatusCode();
            $body = $response->getBody()->getContents();

            $result = [
                'status_code' => $statusCode,
                'headers' => $response->getHeaders(),
                'body' => $body,
                'success' => $statusCode >= 200 && $statusCode < 400
            ];

            // Try to decode JSON response
            if (!empty($body)) {
                $jsonData = json_decode($body, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $result['data'] = $jsonData;
                }
            }

            log_message('info', 'POST request completed. Status: ' . $statusCode);

            // Log error responses for debugging
            if (!$result['success']) {
                log_message('error', 'POST request returned error status: ' . $statusCode . ', Body: ' . $body);
            }

            return $result;

        } catch (RequestException $e) {
            log_message('error', 'POST request failed: ' . $e->getMessage());
            return self::handleRequestException($e);
        } catch (\Throwable $e) {
            log_message('error', 'POST request error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'status_code' => 500
            ];
        }
    }

    /**
     * Make a PUT request
     * @param string $url
     * @param array $options
     * @return array
     */
    public static function makePutRequest($url, $options = [])
    {
        try {
            log_message('info', 'Making PUT request to: ' . $url);

            $client = self::getClient();
            $response = $client->put($url, $options);

            $result = [
                'status_code' => $response->getStatusCode(),
                'headers' => $response->getHeaders(),
                'body' => $response->getBody()->getContents(),
                'success' => true
            ];

            // Try to decode JSON response
            $jsonData = json_decode($result['body'], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $result['data'] = $jsonData;
            }

            log_message('info', 'PUT request successful. Status: ' . $result['status_code']);

            return $result;

        } catch (RequestException $e) {
            log_message('error', 'PUT request failed: ' . $e->getMessage());
            return self::handleRequestException($e);
        } catch (\Throwable $e) {
            log_message('error', 'PUT request error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'status_code' => 500
            ];
        }
    }

    /**
     * Make a DELETE request
     * @param string $url
     * @param array $options
     * @return array
     */
    public static function makeDeleteRequest($url, $options = [])
    {
        try {
            log_message('info', 'Making DELETE request to: ' . $url);

            $client = self::getClient();
            $response = $client->delete($url, $options);

            $result = [
                'status_code' => $response->getStatusCode(),
                'headers' => $response->getHeaders(),
                'body' => $response->getBody()->getContents(),
                'success' => true
            ];

            // Try to decode JSON response
            $jsonData = json_decode($result['body'], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $result['data'] = $jsonData;
            }

            log_message('info', 'DELETE request successful. Status: ' . $result['status_code']);

            return $result;

        } catch (RequestException $e) {
            log_message('error', 'DELETE request failed: ' . $e->getMessage());
            return self::handleRequestException($e);
        } catch (\Throwable $e) {
            log_message('error', 'DELETE request error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'status_code' => 500
            ];
        }
    }

    /**
     * Make a PATCH request
     * @param string $url
     * @param array $options
     * @return array
     */
    public static function makePatchRequest($url, $options = [])
    {
        try {
            log_message('info', 'Making PATCH request to: ' . $url);

            $client = self::getClient();
            $response = $client->patch($url, $options);

            $result = [
                'status_code' => $response->getStatusCode(),
                'headers' => $response->getHeaders(),
                'body' => $response->getBody()->getContents(),
                'success' => true
            ];

            // Try to decode JSON response
            $jsonData = json_decode($result['body'], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $result['data'] = $jsonData;
            }

            log_message('info', 'PATCH request successful. Status: ' . $result['status_code']);

            return $result;

        } catch (RequestException $e) {
            log_message('error', 'PATCH request failed: ' . $e->getMessage());
            return self::handleRequestException($e);
        } catch (\Throwable $e) {
            log_message('error', 'PATCH request error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'status_code' => 500
            ];
        }
    }

    /**
     * Handle Guzzle request exceptions
     * @param RequestException $e
     * @return array
     */
    private static function handleRequestException(RequestException $e)
    {
        $response = $e->getResponse();
        $statusCode = $response ? $response->getStatusCode() : 500;
        $body = $response ? $response->getBody()->getContents() : '';

        $result = [
            'success' => false,
            'error' => $e->getMessage(),
            'status_code' => $statusCode,
            'body' => $body
        ];

        // Try to decode JSON error response
        if ($body) {
            $jsonData = json_decode($body, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $result['error_data'] = $jsonData;
            }
        }

        return $result;
    }

    /**
     * Build a complete URL from base and path
     * @param string $base
     * @param string $path
     * @return string
     */
    public static function buildUrl($base, $path)
    {
        $base = rtrim($base, '/');
        $path = ltrim($path, '/');
        return $base . '/' . $path;
    }

    /**
     * Validate URL format
     * @param string $url
     * @return bool
     */
    public static function isValidUrl($url)
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * Send a CURL request to the given URL with the given method and data
     * @param string $method The method to use for the request (e.g. GET, POST, PUT)
     * @param string $url The URL to send the request to
     * @param array|string $data The data to send with the request
     * @param array $header_options The headers to include in the request
     * @return string The response from the server
     */
    public static function makeCURLRequest($method, $url, $data, $header_options)
    {
        $curl = curl_init();
        switch ($method) {
            case "POST":
                curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
                // curl_setopt($curl, CURLOPT_POSTFIELDS, "your_var");
                curl_setopt($curl, CURLOPT_POSTREDIR, 3);

                // curl_setopt($curl, CURLOPT_POST, 1);
                if ($data)
                    curl_setopt(
                        $curl,
                        CURLOPT_POSTFIELDS,
                        $data
                    );
                log_message('info', 'url: ' . $url . ' POST data: ' . print_r($data, true));
                break;
            case "PUT":
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
                if ($data)
                    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
                break;
            default:
                if ($data)
                    $url = sprintf("%s?%s", $url, http_build_query($data));
        }
        // OPTIONS:
        curl_setopt($curl, CURLOPT_URL, $url);
        foreach ($header_options as $key => $value) {
            # code...
        }
        //if no header set, default set the content type to form
        if (empty($header_options)) {
            curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            // curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));   
        } else {
            curl_setopt($curl, CURLOPT_HTTPHEADER, $header_options);
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        // EXECUTE:
        // echo "executing";
        $result = curl_exec($curl);
        // echo $result . "done executing";
        if (!$result) {
            die("Connection Failure");
        }
        curl_close($curl);
        return $result;
    }

}