<?php
declare(strict_types=1);
namespace Ugpp\Test;

use PHPUnit\Framework\TestCase;

class CurrencyHandlerTest extends TestCase {
  
  /**
  * @test
  * @covers SquareHandler::notZeroDecimal
  **/
  public function expectTrueIfNotZeroDecimalCurrency() {
    $currencyHandler = new \Ugpp\CurrencyHandler();
    $result = $currencyHandler->notZeroDecimal('usd'); 

    $this->assertTrue($result);
  }

  /**
  * @test
  * @covers SquareHandler::notZeroDecimal
  **/
  public function expectFalseIfNotZeroDecimalCurrency() {
    $currencyHandler = new \Ugpp\CurrencyHandler();
    $result = $currencyHandler->notZeroDecimal('mga'); 

    $this->assertFalse($result);
  }
}

