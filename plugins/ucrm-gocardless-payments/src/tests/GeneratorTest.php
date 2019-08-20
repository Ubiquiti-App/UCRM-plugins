<?php
declare(strict_types=1);
namespace Ucsp\Test;

use PHPUnit\Framework\TestCase;
use \Ugpp\Generator;

class GeneratorTest extends TestCase {
  public $customAttributes = [
    'Ucsp Gateway Customer' => 'ucspGatewayCustomer', 
    'Ucsp Gateway Token' => 'ucspGatewayToken', 
  ];
  public function customAttributesFromUcrm() {
    $eachAttr = [];
    $i = 0;
    foreach ($this->customAttributes as $key => $val) {
      $eachAttr[$key] = ['id' => $i, 'name' => $key, 'key' => $val, 'attributeType' => 'client'];
      $i++;
    }

    return $eachAttr;
  }

  public function nonMatchingCustomAttributeProvider() {
      return [
          "isNull" => [null],
          "empty array" => [[]],
          "with some attributes" => [[['id' => 1, 'name' => 'Gateway Customer ID', 'key' => 'GatewayCustomerId', 'attributeType' => 'client'], ['id' => 3, 'name' => 'test', 'key' => 'test', 'attributeType' => 'invoice']]]
      ];
  }

  /**
  * @test
  * @covers Generator::customAttributesExists
  * @dataProvider nonMatchingCustomAttributeProvider
  **/
  public function missingCustomAttributes($mock_results) {
    $mock = $this->getMockBuilder(Generator::class)
                 ->setMethods(['get', 'updateFile'])
                 ->getMock();
    $mock->method('get')->will($this->returnValue($mock_results));

    $this->assertInternalType('array', $mock->customAttributesExists());
  }

  public function customAttributeProvider() {
    $results = $this->customAttributesFromUcrm();
    return [[$results]];
  }

  /**
  * @test
  * @covers Generator::customAttributesExists
  * @dataProvider customAttributeProvider
  **/
  public function customAttributesExists($mock_results) {
    $mock = $this->getMockBuilder(Generator::class)
                 ->setMethods(['get', 'updateFile'])
                 ->getMock();
    $mock->method('get')->will($this->returnValue($mock_results));

    $this->assertTrue($mock->customAttributesExists());
  }

  /**
  * @test
  * @covers Generator::getAttribute
  **/
  public function getAttributeTest() {
    $attributes = [
      [
        "id" => 2000,
        "clientId" => 1000,
        "name" => "Personal Code",
        "key" => "personalCode",
        "value" => "lorem ipsum",
        "customAttributeId" => 1
      ],
      [
        "id" => 2001,
        "clientId" => 1000,
        "name" => "Ucsp Gateway Token",
        "key" => "ucspGatewayToken",
        "value" => "token_test1234",
        "customAttributeId" => 1
      ]
    ];

    $Generator = new Generator();
    $this->assertSame('token_test1234', $Generator->getAttribute($attributes, 'ucspGatewayToken'), 'should get value from array');
    $this->assertSame(null, $Generator->getAttribute($attributes, 'ucspGatewayCustomer'), 'should return null when not found');
  }

  public function eachCustomAttributeProvider() {
    $results = $this->customAttributesFromUcrm();
    return [$results];
  }

  /**
  * @test
  * @covers Generator->getAttributeId
  * @dataProvider eachCustomAttributeProvider
  **/
  public function getAttributeId($mock_results) {
    $mock = $this->getMockBuilder(Generator::class)
                 ->setMethods(['get'])
                 ->getMock();
    $mock->method('get')->will($this->returnValue($this->customAttributesFromUcrm()));

    $this->assertSame($mock_results['id'], $mock->getAttributeId($mock_results['key']));
  }

  /**
  * @test
  * @covers Generator::createCustomAttributes
  **/
  public function expectFalseIfCustomAttributesExist() {
    $mock = $this->getMockBuilder(Generator::class)
                 ->setMethods(['customAttributesExists'])
                 ->getMock();
    $mock->method('customAttributesExists')->will($this->returnValue(true));

    $missing = $mock->createCustomAttributes();
    $this->assertFalse($missing, 'should not create attributes if they exist');
  }

  /**
  * @test
  * @covers Generator::createCustomAttributes
  **/
  public function expectCreateCustomAttributes() {
    $mock_get_attributes = $this->customAttributes;
    $mock_results = ['Ucsp Gateway Customer' => 'ucspGatewayCustomer', 'Ucsp Gateway Token' => 'ucspGatewayToken', 'Ucsp Form Email' => 'ucspFormEmail', 'Ucsp Form Step' => 'ucspFormStep', 'Ucsp Errors' => 'ucspErrors'];
    $mock = $this->getMockBuilder(Generator::class)
                 ->setMethods(['customAttributesExists', 'post', 'get'])
                 ->getMock();
    $mock->method('customAttributesExists')->will($this->onConsecutiveCalls($mock_results, $mock_results, true));
    $mock->method('post')->will($this->returnValue($mock_results));
    $mock->method('get')->will($this->returnValue($mock_get_attributes));

    $result = $mock->createCustomAttributes();
    $this->assertTrue($result);
  }

  public function autoUpdatesProvider() {
    $expectedArray = [
      'gatewayAttributeId' => 1,
      'tokenAttributeId' => 2,
      'formEmailAttributeId' => 3,
      'serviceDataAttributeId' => 4,
      'errorsAttributeId' => 5
    ];
    return [
      'array should not change' => ['endpoint', ['test' => 'array'], ['test' => 'array']],
      'array should include plugin-config data' => ['plugin-config', [], $expectedArray],
    ];
  }
  

  /**
  * @test
  * @covers Generator->run
  * @dataProvider autoUpdatesProvider
  **/
  public function expectUpdatedArray($endpoint, $data, $expected_data) {
    $mock = $this->getMockBuilder(Generator::class)
                 ->setMethods(['getAttributeId'])
                 ->getMock();

    $mock->method('getAttributeId')->will($this->onConsecutiveCalls(1,2,3,4,5));

    $modifiedData = $mock->run($endpoint, $data);
    $this->assertSame($expected_data, $modifiedData);
  }

}