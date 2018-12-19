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
  **/
  public function expectExceptionOnGetThatIsNotWhiteListed() {
    $this->Interpreter->get('invalide/endpoint', []);
  }
  
  /**
  * @test
  * @expectedException UnexpectedValueException
  **/
  public function expectExceptionOnPostThatIsNotWhiteListed() {
    $this->Interpreter->post('invalide/endpoint', []);
  }
}