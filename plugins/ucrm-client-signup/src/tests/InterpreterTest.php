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
  public function expectExceptionOnGetEndpointThatIsNotWhiteListed() {
    $payload = json_encode(["frontendKey" => "test_key", "api" => ["type" => "GET", "endpoint" => "countries/22/states/invalid", "data" => "test"]]);
    $this->Interpreter->run($payload);
  }

  
  /**
  * @test
  * @expectedException UnexpectedValueException
  * @expectedExceptionCode 404
  **/
  public function expectExceptionOnPostEndpointThatIsNotWhiteListed() {
    $payload = json_encode(["frontendKey" => "test_key", "api" => ["type" => "POST", "endpoint" => "clients/1", "data" => "test"]]);
    $this->Interpreter->run($payload);
  }

  /**
  * @test
  **/
  public function expectFalseOnEmptyPayload() {
    $payload = json_encode([]);
    $this->Interpreter->run($payload);

    $this->assertSame(false, $this->Interpreter->isReady());
  }

  /**
  * @test
  **/
  public function expectFalseOnEmptyFrontendKey() {
    $payload = json_encode(["frontendKey" => ""]);
    $this->Interpreter->run($payload);

    $this->assertSame(false, $this->Interpreter->isReady());
  }

  /**
  * @test
  * @expectedException UnexpectedValueException
  * @expectedExceptionMessage frontendKey is invalid
  **/
  public function expectExceptionForInvalidKey() {
    $payload = json_encode(["frontendKey" => "invalid_key", "api" => ["type" => "GET", "endpoint" => "countries", "data" => []]]);
    $this->Interpreter->run($payload);
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
  * @expectedExceptionMessage endpoint is not set
  * @expectedExceptionCode 400
  **/
  public function expectExceptionOnEmptyEndpoint() {
    $payload = json_encode(["frontendKey" => "test_key", "api" => ["type" => "GET", "data" => "test"]]);
    $this->Interpreter->run($payload);
  }
  /**
  * @test
  * @expectedException UnexpectedValueException
  * @expectedExceptionMessage type is not set
  * @expectedExceptionCode 400
  **/
  public function expectExceptionOnEmptyType() {
    $payload = json_encode(["frontendKey" => "test_key", "api" => ["endpoint" => "clients", "data" => "test"]]);
    $this->Interpreter->run($payload);
  }

  /**
  * @test
  **/
  public function expectSuccessfullPayloadOnGet() {
    // $payload = json_encode(["frontendKey" => "test_key", "api" => ["type" => "GET", "endpoint" => "countries"]]);
    // $response = json_decode($this->Interpreter->run($payload));

    // $this->assertSame($response[0]->code, 'AF');

    $this->markTestSkipped('For documentation and speed of test suite, this test is skipped by default but can be actually run by going through the live interpreter above');
  }
  /**
  * @test
  **/
  public function expectSuccessfullPayloadOnPost() {
    // $payload = json_encode([
    //   "frontendKey" => "test_key", 
    //   "api" => [
    //                 "type" => "POST", 
    //                 "endpoint" => "clients", 
    //                 "data" => [
    //                   "clientType" => 1,
    //                   "isLead" => true,
    //                   "firstName" => "brandon",
    //                   "lastName" => "lastname",
    //                   "street1" => "street1",
    //                   "street2" => "street2",
    //                   "city" => "city",
    //                   "countryId" => 19,
    //                   "zipCode" => "55555",
    //                   "username" => "brandon+testapi@charuwts.com",
    //                   "contacts" => [
    //                     [
    //                       "email" => "brandon@charuwts.com", 
    //                       "phone" => "2222222222", 
    //                       "name" => "brandon lastname" 
    //                     ]
    //                   ],
                      
    //                 ]
    //               ]
    // ]);
    // $this->Interpreter->run($payload);

    // $this->assertSame(200, $this->Interpreter->getCode());

    $this->markTestSkipped('For documentation and speed of test suite, this test is skipped by default but can be actually run by going through the live interpreter above');
  }


}