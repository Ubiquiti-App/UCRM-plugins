<?php 
namespace UCS;

use GuzzleHttp\Exception\RequestException;

class UcrmApi {
    ## Class Properties
    protected $response;

    ## Static Properties
    private static $ucrm_api_url;

    private static $verify_ucrm_api_ssl;

    protected static $ucrm_key;

    ## Public Setters
    public static function setUcrmKey($value='')    { self::$ucrm_key     = $value; }

    public static function setUcrmApiUrl($value = '')
    {
        // if SSL, do not verify
        self::$verify_ucrm_api_ssl = strpos($value, 'https://') !== 0;
        // make API requests on loopback interface - never leaves the container
        self::$ucrm_api_url = self::$verify_ucrm_api_ssl ? 'http://localhost/' : 'https://localhost/';
    }

    ## Handle Guzzle Exception and exit
    # @param array $e
    # @param boolean $log
    # @return exit();
    protected function handleGuzzleException(RequestException $e, $log = false, $endpoint = '')
    {
        // # Get json response
        $body = $e->getResponse() ? $e->getResponse()->getBody() : '';
        // # Get get code from response
        $json_decoded = json_decode($body);
        $code = $json_decoded->code;
        // # Send response and exit
        if ($log) {
            \log_event('Exception', "{$body}: {$code} - Endpoint: {$endpoint}", 'error');
        }
        echo json_response($body, $code, true);
        exit();
    }

    ## Setup Guzzle for UCRM
    # @param string $method // "GET", "POST", "PATCH"
    # @param string $endpoint
    # @param array  $content
    # @return array
    protected function guzzle(
        $method,
        $endpoint,
        array $content = []
    ): ?array
    {
        try {
            $client = new \GuzzleHttp\Client([
                'headers' => ['X-Auth-App-Key' => self::$ucrm_key]
            ]);
            // we're using local address here, a HTTPS certificate won't match
            $res = $client->request(
                $method,
                self::$ucrm_api_url.$endpoint,
                ['json' => $content, 'verify' => self::$verify_ucrm_api_ssl]
            );
            $code = $res->getStatusCode();
            $body = (string)$res->getBody();

            return ["status" => $code, "message" => $body];
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $this->handleGuzzleException($e);
        } catch (\GuzzleHttp\Exception\ServerException $e) {
            $this->handleGuzzleException($e, true, $endpoint);
        }
    }

    ## VALIDATE PAYLOAD OBJECTS
    # @param array $object
    # @param boolean $requireKey
    # @return boolean
    protected function validateObject($object, $requireKey=false) {
        try {
            $errors = [];
            foreach($object as $key => $value) {
                if (empty($value) && ($value !== 0)) {
                    $errors[$key] = "cannot be empty";
                }
            }
            if (!empty($errors)) {
                $resp = ["code" => 422, "message" => "Validation failed.", "errors" => $errors ];
                throw new \UnexpectedValueException(json_encode($resp));
            }

            if ($requireKey) {
                if ($object['pluginAppKey'] != FRONTEND_PUBLIC_KEY) {
                    throw new \UnexpectedValueException("Invalid pluginAppKey");
                }
            }

        } catch(\UnexpectedValueException $e) {
            echo json_response($e->getMessage(), 422, true);
            exit();
        }

        return true;
    }

}
