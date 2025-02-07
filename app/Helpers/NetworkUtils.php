<?php
namespace App\Helpers;
use GuzzleHttp;
use Exception;
///https://docs.guzzlephp.org/en/stable/request-options.html
class NetworkUtils
{
    /**
     * this method makes a get request to a given url
     * @param string $url
     * @param \GuzzleHttp\RequestOptions[] $options
     * @return mixed
     */
    public static function makeGetRequest($url, $options = []): mixed
    {
        $client = new \GuzzleHttp\Client();
        $response = $client->request('GET', $url, $options);
        return json_decode($response->getBody()->getContents());
    }

    /**
     * this method makes a post request to a given url
     * @param string $url
     * @param array $data
     * @param \GuzzleHttp\RequestOptions[] $options
     * @return mixed
     */
    public static function makePostRequest($url, $options = []): mixed
    {
        $client = new \GuzzleHttp\Client();
        $response = $client->request('POST', $url, $options);
        return json_decode($response->getBody()->getContents());
    }
}