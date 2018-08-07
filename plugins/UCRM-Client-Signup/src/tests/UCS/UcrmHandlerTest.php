<?php
namespace UCS\Test;
// require dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

use PHPUnit\Framework\TestCase;
// use UCS\UcrmHandler;

class UcrmHandlerTest extends TestCase {
    protected function setUp() {
      $UcrmHandler = new \UCS\UcrmHandler();
    }

    public function testCreateClient() {
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }
}