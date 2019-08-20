<?php
declare(strict_types=1);
namespace Ugpp\Test;

chdir(__DIR__);

use PHPUnit\Framework\TestCase;
use \Ugpp\GocardlessHandler;

class GocardlessHandlerTest extends TestCase {
  public function clientDetailsProvider() {
    $client = Array (
      'id' => 170,
      'userIdent' => null,
      'previousIsp' => null,
      'isLead' => false,
      'clientType' => 1,
      'companyName' => null,
      'companyRegistrationNumber' => null,
      'companyTaxId' => null,
      'companyWebsite' => null,
      'street1' => 'Street 1',
      'street2' => null,
      'city' => 'City',
      'countryId' => null,
      'stateId' => null,
      'zipCode' => '12345',
      'invoiceStreet1' => null,
      'invoiceStreet2' => null,
      'invoiceCity' => null,
      'invoiceStateId' => null,
      'invoiceCountryId' => null,
      'invoiceZipCode' => null,
      'invoiceAddressSameAsContact' => true,
      'note' => null,
      'sendInvoiceByPost' => null,
      'invoiceMaturityDays' => null,
      'stopServiceDue' => null,
      'stopServiceDueDays' => null,
      'organizationId' => 3,
      'tax1Id' => null,
      'tax2Id' => null,
      'tax3Id' => null,
      'registrationDate' => '2019-01-27T00:00:00-0800',
      'companyContactFirstName' => null,
      'companyContactLastName' => null,
      'isActive' => false,
      'firstName' => 'Torg',
      'lastName' => 'Lastname',
      'username' => 'test@test.com',
      'contacts' => Array (
          0 => Array (
              'id' => 170,
              'clientId' => 170,
              'email' => 'test@test.com',
              'phone' => '22222222222',
              'name' => 'Torg Lastname',
              'isBilling' => true,
              'isContact' => true,
              'types' => Array (
                  0 => Array (
                      'id' => 1,
                      'name' => 'Billing'
                  ),
                  1 => Array (
                      'id' => 2,
                      'name' => 'General'
                  )
              )
          )
      ),
      'attributes' => Array (
          0 => Array (
              'id' => 107,
              'clientId' => 170,
              'customAttributeId' => 39,
              'name' => 'Ucsp Form Email',
              'key' => 'ucspFormEmail',
              'value' => 'test@test.com'
          ),
          1 => Array (
              'id' => 108,
              'clientId' => 170,
              'customAttributeId' => 37,
              'name' => 'Ucsp Service Data',
              'key' => 'ucspServiceData',
              'value' => '14,66'
          )
      ),
      'accountBalance' => 0,
      'accountCredit' => 0,
      'accountOutstanding' => 0,
      'currencyCode' => 'USD',
      'organizationName' => 'torg',
      'bankAccounts' => Array (),
      'tags' => Array (),
      'invitationEmailSentDate' => null,
      'avatarColor' => '#ff8f00',
      'addressGpsLat' => null,
      'addressGpsLon' => null,
      'isArchived' => false,
      'generateProformaInvoices' => null,
      'usesProforma' => false
    );
    return [
      "example client" => [$client]
    ];
  }

  public function invoiceProvider() {
    $invoice = Array(
      'id' => 51,
      'clientId' => 170,
      'number' => '000005',
      'createdDate' => '2019-01-27T18:13:02-0800',
      'dueDate' => '2019-01-27T18:13:02-0800',
      'emailSentDate' => '2019-02-07T00:00:00-0800',
      'maturityDays' => 0,
      'subtotal' => 10,
      'discount' => null,
      'discountLabel' => 'Discount',
      'taxes' => Array(),
      'total' => 10,
      'amountPaid' => 0,
      'currencyCode' => 'GBP',
      'status' => 1,
    );
    return [
      "example invoice" => [$invoice]
    ];
  }
  public function goCardlessPaymentObjectProvider() {
    $payment_object = Array (
        'model_name' => 'Payment',
        'amount' => 10,
        'amount_refunded' => 0,
        'charge_date' => '2019-03-06',
        'created_at' => '2019-03-01T02:52:21.603Z',
        'currency' => 'GBP',
        'description' => null,
        'id' => 'PM000F796HS1DG',
        'links' => Array (
            'mandate' => 'MD000589HKY0KN',
            'creditor' => 'CR00005KXCGFN0'
        ),
        'metadata' => Array (
            'client_id' => '170',
            'invoice_id' => '51',
            'invoice_number' => '000001'
        ),
        'reference' => null,
        'status' => 'pending_submission',
        'data' => Array (
            'id' => 'PM000F796HS1DG',
            'created_at' => '2019-03-01T02:52:21.603Z',
            'charge_date' => '2019-03-06',
            'amount' => 10,
            'description' => null,
            'currency' => 'GBP',
            'status' => 'pending_submission',
            'amount_refunded' => 0,
            'reference' => null,
            'metadata' => Array(),
            'links' => Array()
        ),
        'api_response' => Array (
            'headers' => Array (
                'Date' => Array (
                    0 => 'Fri, 01 Mar 2019 02:52:21 GMT'
                ),
                'Content-Type' => Array (
                    0 => 'application/json'
                ),
                'Location' => Array (
                    0 => '/enterprise/payments/PM000F796HS1DG'
                ),
                'Pragma' => Array (
                    0 => 'no-cache'
                ),
                'Cache-Control' => Array (
                    0 => 'no-store'
                ),
                'ETag' => Array (
                    0 => 'W/"6563f9667338b23a4fc48c92c6aea61b"'
                ),
                'X-Request-Id' => Array (
                    0 => '0AA40017CC8F_AC12149F1F90_5C789E65_FF430009'
                ),
                'Strict-Transport-Security' => Array (
                    0 => 'max-age=31556926; includeSubDomains; preload'
                ),
                'Vary' => Array (
                    0 => 'Origin'
                ),
                'X-XSS-Protection' => Array (
                    0 => '1; mode=block'
                ),
                'X-Content-Type-Options' => Array (
                    0 => 'nosniff'
                ),
                'RateLimit-Limit' => Array (
                    0 => '1000'
                ),
                'RateLimit-Remaining' => Array (
                    0 => '998'
                ),
                'RateLimit-Reset' => Array (
                    0 => 'Fri, 01 Mar 2019 02:53:00 GMT'
                ),
                'Via' => Array (
                    0 => '1.1 google'
                ),
                'Transfer-Encoding' => Array (
                    0 => 'chunked'
                ),
                'Alt-Svc' => Array (
                    0 => 'clear'
                )
            ),
            'status_code' => 201,
            'body' => Array (
                'payments' => Array()
            )
        )
    );
    return [
      "example payment OBJECT" => [$payment_object]
    ];
  }

  /**
  * @test
  * @covers GocardlessHandler->initiateRedirectFlow
  * @dataProvider clientDetailsProvider
  **/
  public function expectUcrmToReturnClientDetails($client) {
    // $handler = new \Ugpp\GocardlessHandler;
    // $client = $handler->get('clients/170');
    $mock = $this->getMockBuilder(GocardlessHandler::class)
                 ->setMethods(['get'])
                 ->getMock();
    $mock->method('get')->will($this->returnValue($client));
    $client_response = $mock->get('client/170');

    $this->assertNotEmpty($client_response['firstName']);
    $this->assertNotEmpty($client_response['lastName']);
    $this->assertNotEmpty($client_response['contacts'][0]['email']);
    $this->assertNotEmpty($client_response['street1']);
    $this->assertNotEmpty($client_response['city']);
    $this->assertNotEmpty($client_response['zipCode']);
  }
  
  /**
  * @test
  * @covers GocardlessHandler->initiateRedirectFlow
  * @dataProvider clientDetailsProvider
  **/
  public function expectGoCardlessApiToReturnId($client) {
    // $handler = new \Ugpp\GocardlessHandler;
    // $client = $handler->get('clients/170');
    $mock = $this->getMockBuilder(GocardlessHandler::class)
                 ->setMethods(['get', 'initiateRedirectFlow'])
                 ->getMock();
    $mock->method('get')->will($this->returnValue($client));
    $mock->method('initiateRedirectFlow')->will($this->returnValue(json_decode(json_encode(["redirect_url" => "http://192.168.1.5"]))));
    $client_response = $mock->get('client/170');

    $response = $mock->initiateRedirectFlow($client_response);
    $this->assertNotEmpty($response->redirect_url);
  }

  /**
  * @test
  * @dataProvider clientDetailsProvider
  * @covers GocardlessHandler->link
  **/
  public function expectSuccessWhenLinked($client) {
    $mock = $this->getMockBuilder(GocardlessHandler::class)
                 ->setMethods(['patch'])
                 ->getMock();
    $mock->method('patch')->will($this->returnValue($client));

    $response = $mock->link($client['id'], 'customer_token', 'mandate_token');
    $this->assertTrue($response);
  }
  
  /**
  * @test
  * @dataProvider clientDetailsProvider
  * @covers GocardlessHandler->unlink
  **/
  public function expectSuccessWhenUnlinked($client) {
    $mock = $this->getMockBuilder(GocardlessHandler::class)
                 ->setMethods(['patch'])
                 ->getMock();
    $mock->method('patch')->will($this->returnValue($client));

    $response = $mock->unlink($client['id']);
    $this->assertTrue($response);
  }

  /**
  * @test
  * @dataProvider clientDetailsProvider
  * @covers getCustomer
  **/
  public function getCustomer($client) {
    $handler = new \Ugpp\GocardlessHandler;
    $this->assertFalse($handler->getCustomer($client));
  }

  // /**
  // * @test
  // * @expectedException \GoCardlessPro\Core\Exception\InvalidStateException
  // **/
  // public function expectGoCardlessApiToReturnException() {
  //   $handler = new \Ugpp\GocardlessHandler;
  //   $redirectFlow = $handler->gocardless_api->redirectFlows()->complete(
  //       'RE0001GE16QPNJN9VBF8YKDV9AZG8DA0',
  //       ["params" => ["session_token" => "session_token"]]
  //   );
  // }


  // /**
  // * @test
  // **/
  // public function expectGoCardlessApiToReturnArray() {
  //   $handler = new \Ugpp\GocardlessHandler;
  //   $customers = $handler->gocardless_api->customers()->list()->records;
  //   $this->assertSame([], $customers);
  // }

}