<?php
/**
 * ApiAxle (https://github.com/fillup/apiaxle-module)
 *
 * @link      https://github.com/fillup/apiaxle-module for the canonical source repository
 * @license   MIT
 */

namespace ApiAxle\Shared;
use ApiAxle\Shared\HttpRequest;
use ApiAxle\Shared\ApiException;

/**
 * Utilities class to perform common functions for API activities.
 * 
 * @author Phillip Shipley <phillip@phillipshipley.com>
 */
class Utilities
{
    /**
     * Wrapper function for making the HttpRequest to the ApiAxle API
     * 
     * This method will set the proper content type and attach the api key
     * and api signature if needed before making the call.
     * 
     * @param string $apiPath
     * @param string $method
     * @param array $data
     * @param \ApiAxle\Shared\Config $config
     * @return \stdClass
     * @throws \ApiAxle\Shared\ApiException
     * @throws \ErrorException
     */
    public static function callApi($apiPath, $method='GET', $data=null, $config)
    {
        $headers = array(
            "Accept: application/json",
        );
        
        if(($method == 'POST' || $method == 'PUT') && is_array($data)){
            $headers[] = "Content-Type: application/json";
        }
        
        $api_key = $config->getKey();
        $api_sig = $config->getSignature();
        
        if(strpos($apiPath,'?')){
            $apiPath .= "&api_key=$api_key&api_sig=$api_sig";
        } else {
            $apiPath .= "?api_key=$api_key&api_sig=$api_sig";
        }
        
        if($method == 'GET' && is_array($data)){
            foreach($data as $param => $value){
                $apiPath .= '&'.$param.'='.$value;
            }
        }
        
        $json_data = false;
        
        if(is_array($data) && $method != 'GET'){
            $json_data = json_encode($data);
            $headers[] = "Content-Length: ".  strlen($json_data);
        }
        
        $url = $config->getEndpoint().'/'.$apiPath;
        $request = HttpRequest::request($url, $method, $json_data, $headers, $config);
        if($request){
            $results = json_decode($request);
            
            // If unable to decode the response as JSON...
            if (is_null($results)) {
                
                // Throw an exception, suggesting to the user that the problem
                // may be that ApiAxle is not running.
                throw new \ErrorException('API did not return properly. ' .
                                          'Is ApiAxle running?');
            }
            $statusCode = $results->meta->status_code;
            if($statusCode >= 200 && $statusCode < 300){
                return $results->results;
            } elseif($statusCode >= 300 && $statusCode < 400){
                throw new ApiException('API returned a redirection', '200', null, $statusCode, $results);
            } elseif($statusCode >= 400 && $statusCode < 600){
                throw new ApiException('API returned error: '.$results->results->error->message, '201', null, $statusCode, $results);
            }
        } else {
            throw new \ErrorException('API did not return properly.','202',null);
        }
    }
}