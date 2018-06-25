<?php 
class UcrmApi {
  // ### Class Properties
  private $response;

  /**
   * # Static Properties
   *
   */
  private static $ucrm_api_url;
  protected static $ucrm_key;

  /**
   * # Public Setters
   *
   * @param string self::$ucrm_key
   * @param string self::$ucrm_api_url
   *
   */
  public static function setUcrmKey($value='')    { self::$ucrm_key     = $value; }
  public static function setUcrmApiUrl($value='') { self::$ucrm_api_url = $value; }

  /**
   * # Handle Guzzle Exception and exit
   *
   * @param array $e
   * @param boolean $log
   *
   * @return exit();
   */
  protected function handleGuzzleException($e, $log = false, $endpoint='') {
    // # Get json response
    $body = $e->getResponse()->getBody();
    // # Get get code from response
    $json_decoded = json_decode($body);
    $code = $json_decoded->code;
    // # Send response and exit
    if ($log) {
      log_event('Exception', "{$body}: {$code} - Endpoint: {$endpoint}", 'error');
    }
    echo json_response($body, $code, true);
    exit();
  }

  /**
   * # Setup Guzzle for UCRM
   *
   * @param string $method // "GET", "POST", "PATCH"
   * @param string $endpoint
   * @param array  $content
   *
   * @return array
   */
  protected function guzzle(
    $method, 
    $endpoint,
    array $content = []
  ) {    
    // log_event('method', $method, 'test');
    // log_event('endpoint', $endpoint, 'test');
    // log_event('content', print_r($content, true), 'test');
    try {      
      $client = new GuzzleHttp\Client([
        'headers' => ['X-Auth-App-Key' => self::$ucrm_key]
      ]);
      $res = $client->request($method, self::$ucrm_api_url.$endpoint, ['json' => $content]);
      $code = $res->getStatusCode();
      $body = (string)$res->getBody();
      // log_event('body', print_r($body, true), 'test');

      return ["status" => $code, "message" => $body];
    } catch (GuzzleHttp\Exception\ClientException $e) {
      $this->handleGuzzleException($e);
    } catch (GuzzleHttp\Exception\ServerException $e) {
      $this->handleGuzzleException($e, true, $endpoint);
    }
  }


  /**
   * # VALIDATE PAYLOAD OBJECTS
   *
   * @param array $object
   * @param boolean $requireKey
   *
   * @return boolean
   */
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
        throw new UnexpectedValueException(json_encode($resp));   
      }

      if ($requireKey) {
        if ($object['pluginAppKey'] != FRONTEND_PUBLIC_KEY) {
          throw new UnexpectedValueException("Invalid pluginAppKey");   
        }
      }

    } catch(\UnexpectedValueException $e) {
      log_event('UCRM exception', $e->getMessage(), 'error');
      echo json_response($e->getMessage(), 422, true);
      exit();
    }
    return true;
  }



}