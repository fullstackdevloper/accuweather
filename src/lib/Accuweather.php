<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Accuweather\App\Lib;

use GuzzleHttp\Client;

/**
 * Accuweather Api class
 *
 * @author Hansraj
 */
class Accuweather
{
    /**
     * Accuweather API Key
     * @var String $apiKey
     */
    private $apiKey = '4sXStoFjwJ5F7zuvz5KpatUBGj8OV0pr';
    
    /**
     * guzzle client 
     * 
     * @var GuzzleHttp\Client $client
     */
    private $client;
    
    /**
     * constructor
     */
    public function __construct()
    {
        $this->client = new Client([
            // Base URI is used with relative requests
            'base_uri' => 'http://dataservice.accuweather.com',
            // You can set any number of default request options.
            'timeout'  => 2.0,
        ]);
    }
    
    /**
     * get top cities 
     * @param Int $limit
     * @return stdClass $obj
     */
    public function getCitiesData($limit = 50)
    {
        $response = $this->client->request('GET', "currentconditions/v1/topcities/{$limit}?apikey={$this->apiKey}");
        
        return $this->__response($response);
    }
    
    /**
     * normalize response
     * 
     * @param GuzzleHttp\response $response
     * @return \stdClass $obj
     */
    private function __response($response)
    {
        $obj = new \stdClass;
        $obj->statusCode = $response->getStatusCode();
        $obj->data = json_decode($response->getBody());
        
        return $obj;
    }
}
