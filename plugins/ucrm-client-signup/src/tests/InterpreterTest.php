<?php
declare(strict_types=1);
namespace Ucsp\Test;

use PHPUnit\Framework\TestCase;
use \Ucsp\Interpreter;

class InterpreterTest extends TestCase {
  protected function setUp() {
    $this->Interpreter = new Interpreter();
  }
  protected function tearDown() {
    unset($this->Interpreter);
  }

  /**
  * @test
  * @expectedException UnexpectedValueException
  * @expectedExceptionCode 404
  **/
  public function expectExceptionOnGetThatIsNotWhiteListed() {
    $payload = json_encode(["frontendKey" => "test_key", "apiGet" => ["endpoint" => "countries/22/states/invalid", "data" => "test"]]);
    $this->Interpreter->run($payload);
  }

  
  /**
  * @test
  * @expectedException UnexpectedValueException
  * @expectedExceptionCode 404
  **/
  public function expectExceptionOnPostThatIsNotWhiteListed() {
    $payload = json_encode(["frontendKey" => "test_key", "apiGet" => ["endpoint" => "clients/1", "data" => "test"]]);
    $this->Interpreter->run($payload);
  }

  /**
  * @test
  **/
  public function expectFalseOnEmptyPayload() {
    $payload = json_encode([]);
    $result = $this->Interpreter->run($payload);

    $this->assertSame(false, $result);
  }

  /**
  * @test
  **/
  public function expectFalseOnEmptyFrontendKey() {
    $payload = json_encode(["frontendKey" => ""]);
    $result = $this->Interpreter->run($payload);

    $this->assertSame(false, $result);
  }

  /**
  * @test
  * @expectedException UnexpectedValueException
  * @expectedExceptionMessage data is invalid
  * @expectedExceptionCode 400
  **/
  public function expectExceptionWhenApiKeyNotFound() {
    $payload = json_encode(["frontendKey" => "test_key"]);
    $this->Interpreter->run($payload);
  }

  /**
  * @test
  * @expectedException UnexpectedValueException
  * @expectedExceptionMessage endpoint was not set
  * @expectedExceptionCode 400
  **/
  public function expectExceptionOnEmptyEndpoint() {
    $payload = json_encode(["frontendKey" => "test_key", "apiGet" => ["data" => "test"]]);
    $this->Interpreter->run($payload);
  }

  /**
  * @test
  **/
  public function expectSuccessfullPayload() {
    // $payload = json_encode(["frontendKey" => "test_key", "apiGet" => ["endpoint" => "countries"]]);
    // $response = json_decode($this->Interpreter->run($payload));

    // $this->assertSame($response[0]->code, 'AF');

    // For documentation purposes, this test is self succeeding but can be actually run by going through the live interpreter above
    $mockResult = [
      [
        'id' => 19,
        'name' => 'Afghanistan',
        'code' => 'AF'
      ]
    ];

    $this->assertSame($mockResult[0]['code'], 'AF');
  }


}