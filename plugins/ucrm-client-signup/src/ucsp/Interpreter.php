<?php
declare(strict_types=1);
namespace Ucsp;

class Interpreter {
  private static $whiteListedGet = ['countries'];
  private static $whiteListedPost = ['client'];

  private static function validateGet($needle) {
    return in_array($needle, self::$whiteListedGet);
  }

  private static function validatePost($needle) {
    return in_array($needle, self::$whiteListedPost);
  }

  public function __construct() {
    $this->api = \Ubnt\UcrmPluginSdk\Service\UcrmApi::create();
  }

  public function get($endpoint, $data) {
    if (self::validateGet($endpoint)) {
      return $this->api->get(
        $endpoint,
        $data
      );
    } else {
      throw new \UnexpectedValueException('{"code":404,"message":"No route GET: '.$endpoint.'"}');
    }
  }

  public function post($endpoint, $data) {
    if (self::validatePost($endpoint)) {
      return $this->api->post(
        $endpoint,
        $data
      );
    } else {
      throw new \UnexpectedValueException('{"code":404,"message":"No route POST: '.$endpoint.'"}');
    }
  }


}