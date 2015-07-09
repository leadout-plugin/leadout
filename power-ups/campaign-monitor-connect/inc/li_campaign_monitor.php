<?php
/**
* Simple Campaign Monitor API wrapper
* 
* Uses cURL
* When called it returns HTTP response code and associative array of JSON response
* The HTTP response code can be used to determine if the request processed successfully
* If there is an error it is described in the associative array of JSON response
*
* @author Mehedi Hasan <mha_bd@yahoo.com>
*
* @usage
*
* $cm = new CMWrapper("{api_key}");
* $r = $cm->call("{method}", "{httpmethod}", {jsonargs}. {urlargs});
*
* @example
* the following updates a subscribers email address from name@test.com to newname@test.com
* $cm = new CMWrapper("abcd1234");
* $r = $cm->call("subscribers/xyz789", "PUT", array("EmailAddress" => "newname@test.com"), array("EmailAddress" => "name@test.com"));
*/

class LI_Campaign_Monitor
{
    private $api_key;
    private $api_endpoint = 'https://api.createsend.com/api/v3.1';
    private $verify_ssl   = false;

    /**
    * Create a new instance
    * @param string $api_key Your Campaign Monitor API key
    */
    function __construct($api_key)
    {
        $this->api_key = $api_key;
    }

    /**
    * Call an API method. Every request needs the API key, so that is added automatically -- you don't need to pass it in.
    * @param  string $method     The API method to call, e.g. 'subscribers/{listid}', 
    * @param  string $httpmethod The HTTP method to be used, e.g. 'GET', 'POST', 'PUT', 'DELETE'
    * @param  array  $jsonargs   An array of arguments to pass to the method while using POST/PUT. Will be json encoded for you.
    * @param  array  $urlargs    An array of arguments to pass to the method. Will be appended to URL.
    * @return array              An array of response HTTP code and associative array of json decoded API response.
    */
    public function call($method, $httpmethod, $jsonargs = array(), $urlargs = array())
    {
        return $this->makeRequest($method, $httpmethod, $jsonargs, $urlargs);
    }

    /**
    * Performs the underlying HTTP request.
    * @param  string $method     The API method to call, e.g. 'subscribers/{listid}', 
    * @param  string $httpmethod The HTTP method to be used, e.g. 'GET', 'POST', 'PUT', 'DELETE'
    * @param  array  $jsonargs   An array of arguments to pass to the method while using POST/PUT. Will be json encoded for you.
    * @param  array  $urlargs    An array of arguments to pass to the method. Will be appended to URL.
    * @return array              An array of response HTTP code and associative array of json decoded API response.
    */
    private function makeRequest($method, $httpmethod, $jsonargs = array(), $urlargs = array())
    { 
        $args['apikey'] = $this->api_key;

        $url = $this->api_endpoint.'/'.$method.'.json';

        if( count($urlargs) > 0 ){
            $url = $url . "?" . http_build_query($urlargs);
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, 
            array('Content-Type: application/json',
                "Authorization: Basic " . base64_encode($this->api_key . ":x")
                )
        );
        curl_setopt($ch, CURLOPT_USERAGENT, 'PHP-CMAPI');       
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $this->verify_ssl);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLINFO_HEADER_OUT, true);

        if( $httpmethod === "POST" ){
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($jsonargs));
        }

        if( $httpmethod === "PUT" ){
            curl_setopt($ch, CURLOPT_PUT, true);

            $json = json_encode($jsonargs);

            $fp = fopen('php://temp/maxmemory:256000', 'w');
            fwrite($fp, $json);
            fseek($fp, 0);

            curl_setopt($ch, CURLOPT_INFILE, $fp);
            curl_setopt($ch, CURLOPT_INFILESIZE, strlen($json));
        }

        if( $httpmethod === "DELETE" ){
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
        }

        $result = curl_exec($ch);

        $ret = array(
            "code" => curl_getinfo($ch, CURLINFO_HTTP_CODE),
            "response" => json_decode($result, true),
        );

        curl_close($ch);

        if( $httpmethod === "PUT" ){
            fclose($fp);
        }

        //var_dump($httpmethod . " " . $url);
        //var_dump($ret);

        return $ret;
    }
}
