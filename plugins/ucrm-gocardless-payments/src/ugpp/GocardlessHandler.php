<?php 
declare(strict_types=1);
namespace Ugpp;

class GocardlessHandler {
  public function __construct() {
    $configManager = \Ubnt\UcrmPluginSdk\Service\PluginConfigManager::create();
    $this->config = $configManager->loadConfig();

    $ucrmOptionsManager = new \Ubnt\UcrmPluginSdk\Service\UcrmOptionsManager();
    $this->options = $ucrmOptionsManager->loadOptions();


    $this->api = \Ubnt\UcrmPluginSdk\Service\UcrmApi::create();

    if ($this->config['GC_ENVIRONMENT'] == 'LIVE') {
      $this->gocardless_api = new \GoCardlessPro\Client([
          'access_token' => $this->config['GOCARDLESS_ACCESS_TOKEN'],
          'environment' => \GoCardlessPro\Environment::LIVE
      ]);
    } else {
      $this->gocardless_api = new \GoCardlessPro\Client([
          'access_token' => $this->config['GOCARDLESS_ACCESS_TOKEN'],
          'environment' => \GoCardlessPro\Environment::SANDBOX
      ]);
    }


    $this->Generator = new Generator;
  }

  public function log_event($log_title, $event, $type='log') {
    $current_time = date(DATE_ATOM);
    $message = "\n[{$current_time}][{$type}]  - [#{$log_title}] \n";
    $message .= $event;
    $message .= "\n[{$current_time}][{$type}] - [/{$log_title}] \n";
    
    file_put_contents(PROJECT_PATH.'/data/gocardless.log', $message, FILE_APPEND);
  }

  public function get($endpoint, $data = []) {
    return $this->api->get($endpoint, $data);
  }

  public function patch($endpoint, $data = []) {
    return $this->api->patch($endpoint, $data);
  }

  public function post($endpoint, $data = []) {
    return $this->api->post($endpoint, $data);
  }

  public function initiateRedirectFlow($client) {
    $redirect_url = $this->options->pluginPublicUrl."?clientId=".$client['id'];
    return $this->gocardless_api->redirectFlows()->create([
        "params" => [
            // This will be shown on the payment pages
            "description" => (string)$this->config['GOCARDLESS_PAGE_DESCRIPTION'],
            // Not the access token
            "session_token" => $_COOKIE['PHPSESSID'],
            "success_redirect_url" => $redirect_url,
            // Optionally, prefill customer details on the payment page
            "prefilled_customer" => [
              "given_name" => (string)$client['firstName'],
              "family_name" => (string)$client['lastName'],
              "address_line1" => (string)$client['street1'],
              "city" => (string)$client['city'],
              "postal_code" => (string)$client['zipCode']
            ]
        ]
    ]);
  }

  public function link($clientId, $customer, $mandate) {

    $ucspGatewayCustomer = $this->Generator->getAttributeId('ucspGatewayCustomer');
    $ucspGatewayToken = $this->Generator->getAttributeId('ucspGatewayToken');
    
    try {
      $this->patch('clients/'.$clientId, [
        "attributes" => [
          [
            "customAttributeId" => $ucspGatewayCustomer, 
            "value" => $customer
          ],
          [
            "customAttributeId" => $ucspGatewayToken, 
            "value" => $mandate
          ]
        ]
      ]);
      return true;
    } catch (\Exception $e) {
      throw $e;
    }
  }

  public function unlink($clientId) {
    $ucspGatewayCustomer = $this->Generator->getAttributeId('ucspGatewayCustomer');
    $ucspGatewayToken = $this->Generator->getAttributeId('ucspGatewayToken');

    try {
      $this->patch('clients/'.$clientId, [
        "attributes" => [
          [
            "customAttributeId" => $ucspGatewayCustomer, 
            "value" => null
          ],
          [
            "customAttributeId" => $ucspGatewayToken, 
            "value" => null
          ]
        ]
      ]);
      return true;
    } catch (\Exception $e) {
      throw $e;
    }
  }
  
  public function gatewayCharge($invoice) {
    $CurrencyHandler = new CurrencyHandler;

    $client = $this->get('clients/'.$invoice['clientId']);
    $ucspGatewayTokenId = $this->Generator->getAttribute($client['attributes'], 'ucspGatewayToken');

    if ($client) {
      if ($ucspGatewayTokenId) {

        if ($CurrencyHandler->notZeroDecimal($invoice['currencyCode'])) {
          $amount = ($invoice['total'] - $invoice['amountPaid']) * 100;
        } else {
          $amount = $invoice['total'] - $invoice['amountPaid'];
        }

        $payment = $this->gocardless_api->payments()->create([
          "params" => [
              "amount" => $amount,
              "currency" => $invoice['currencyCode'],
              "links" => [
                  "mandate" => $ucspGatewayTokenId
              ],
              "metadata" => [
                  "description" => (string)$this->config['GOCARDLESS_PAYMENT_DESCRIPTION'],
                  "invoice_id" => (string)$invoice['id'],
                  "client_id" => (string)$invoice['clientId']
              ]
          ],
          "headers" => [
              "Idempotency-Key" => "payment_amount_". $amount ."_id_". $invoice['id']
          ]
        ]);

        return $payment;
        

      } else {
        $this->log_event('Ucsp Gateway Token (Mandate ID) not set on client', "Client ID: {$client['id']}");
        return false;
      }
    } else {
      $this->log_event('Client not found', "Client ID: {$client['id']}");
      return false;
    }

  }

  public function processPayments($invoices = []) {
    file_put_contents(PROJECT_PATH.'/data/gocardless.log', '== Start Processing ==');

    // ## Get all invoices marked as unpaid
    if (empty($invoices)) {
      $invoices = $this->get('invoices', ['statuses' => [1,2]]);
    }
    $invoice_array = [];
    

    // ## Loop invoices and handle if due
    foreach($invoices as $invoice) {
      // If client has GoCardless Mandate Token
      $client = $this->get('clients/'.$invoice['clientId']);
      
      // ## Do not process if invoice ignored
      $is_ignored = $this->Generator->getAttribute($invoice['attributes'], 'ucspIgnoreInvoice');
      if ($is_ignored) {
        $this->log_event('Processing Invoice - '.$invoice['id'], 'Ignore invoice custom attribute was set, no action taken.');
        continue;
      }

      // ## Do not process if invoice is uncollectible
      if ($invoice['uncollectible']) {
        $this->log_event('Processing Invoice - '.$invoice['id'], 'Invoice is marked uncollectible.');
        continue;
      }
      
      // ## Setup dates
      $days_before = $this->config['DAYS_BEFORE_INVOICE_DUE'];
      $days_before_formatted = 'P'.$days_before.'D';
      $projected_time = new \DateTime();
      $projected_time->add(new \DateInterval($days_before_formatted));
      $due_date = strtotime($invoice['dueDate']);
      $invoice_due_date = new \DateTime("@{$due_date}");
      $process_date = new \DateTime("@{$due_date}");
      $process_date->sub(new \DateInterval($days_before_formatted));

      // ## Do not process if due date isn't within processing window
      if ($invoice_due_date >= $projected_time) {
        $message = 'Invoice payment will be processed '.$days_before.' days prior to the invoice due date on '.$process_date->format('Y-m-d');
        $this->log_event('Processing Invoice - '.$invoice['id'], $message);
        continue;
      }
        
      try {
        // Create Payment with GoCardless Mandate
        $gateway_charge = $this->gatewayCharge($invoice);
        $log_response = $this->post('client-logs', [
          "message" => "Payment was scheduled on GoCardless",
          "clientId" => $invoice['clientId']
        ]);

      } catch (\Exception $e) {
        $this->log_event('Processing Invoice - '.$invoice['id'], $e->getMessage());
        // throw $e;
      }


    }
    return $invoice_array;
  }
  public function EventPaymentIds($event, $function) {
    try {
      $payment = $this->gocardless_api->payments()->get($event->links->payment);
      if (!empty($payment->metadata->client_id) && !empty($payment->metadata->invoice_id)) {
        return $function($event, $payment);
      } else {
        $missing = empty($payment->metadata->client_id) ? 'Client ID' : 'Invoice ID';
        $message = ["action" => $event->action, "details" => $event->details];
        $this->log_event("Payment {$payment->id} did not have a {$missing}, no action taken.", print_r($message, true) );
        return "Payment {$payment->id} did not have a {$missing}, no action taken.";
      }

    } catch (\Exception $e) {
      return 'Error getting payment: ' . $e->getMessage();
    }

  }

  public function paidOut($event) {
    return $this->EventPaymentIds($event, function($event, $payment) {
      $content = [
        "clientId" => intval($payment->metadata->client_id),
        "method" => 99, // custom
        "providerName" => 'GoCardless', // Required in case of Custom method.
        "providerPaymentId" => $payment->id, // Required in case of Custom method.
        "amount" => $payment->amount / 100,
        "currencyCode" => $payment->currency,
        "note" => 'Paid Out via GoCardless',
        "invoiceIds" => [$payment->metadata->invoice_id],
      ];
      
      // // ## https://ucrm.docs.apiary.io/#reference/payments/payments/post
      $this->post('payments', $content);     

      $message = ["action" => $event->action, "details" => $event->details];
      $this->log_event("Payment added to UCRM {$payment->id}.", print_r($message, true) );
      return 'Success! Payment added to UCRM';
    });

  }

  public function handleWebhook($webhook, $signature_header) {
    $webhook_endpoint_secret = $this->config['GOCARDLESS_WEBHOOK_SECRET'];
    $events = \GoCardlessPro\Webhook::parse($webhook, $signature_header, $webhook_endpoint_secret);

    $response = 'test';


    foreach($events as $event) {

      if ($event->resource_type == 'payments') {
        switch ($event->action) {
          case "paid_out":
           $response = $this->paidOut($event);
          break;
          case "cancelled":
            $this->EventPaymentIds($event, function($event, $payment) {
              $this->post('ticketing/tickets', [
                "subject" => "Payment Canceled",
                "clientId" => intval($payment->metadata->client_id),
                'status' => 0,
                'public' => false,
                'activity' => [
                  [
                    'public' => false,
                    'comment' => [
                      'body' => print_r($event, true),
                    ]
                  ]
                ]
              ]);
            });
            $response = 'Payment Cancellation Ticketed';
          break;
          case "charged_back":
            $this->EventPaymentIds($event, function($event, $payment) {
              $this->post('ticketing/tickets', [
                "subject" => "Payment Charged Back",
                "clientId" => intval($payment->metadata->client_id),
                'status' => 0,
                'public' => false,
                'activity' => [
                  [
                    'public' => false,
                    'comment' => [
                      'body' => print_r($event, true),
                    ]
                  ]
                ]
              ]);
            });
            $response = 'Payment Charge Back Ticketed';
          break;

          case "failed":
            $this->EventPaymentIds($event, function($event, $payment) {
              $this->post('ticketing/tickets', [
                "subject" => "Payment Failed",
                "clientId" => intval($payment->metadata->client_id),
                'status' => 0,
                'public' => false,
                'activity' => [
                  [
                    'public' => false,
                    'comment' => [
                      'body' => print_r($event, true),
                    ]
                  ]
                ]
              ]);
            });
            $response = 'Payment Failure ticketed';
          break;

          default:
            $response = 'Not setup to handle this payment event type.';
        }

      } else {
        $response = 'Not setup to handle this type of webhook.';
      }
    }


    return $response;

  }

  public function getCustomer($client) {
    $gatewayCustomerId = $this->Generator->getAttribute($client['attributes'], 'ucspGatewayCustomer');
    if ($gatewayCustomerId) {
      return $this->gocardless_api->customers()->get($gatewayCustomerId);
    } else {
      return false;
    }
  }
    // ## Charge Client invoice total
    // try {
    //   if (!empty($gateway_charge)) {
    //     $CurrencyHandler = new CurrencyHandler;

    //     if ($CurrencyHandler->notZeroDecimal($invoice['currencyCode'])) {
    //       $amount = $gateway_charge->amount / 100;
    //     } else {
    //       $amount = $gateway_charge->amount;
    //     }


    //     // ## push ID onto array
    //     $invoice_array[] = $invoice['id'];
    //     $this->log_event('Invoice payment processed - '.$invoice['id'], $amount);
    //   }
    // } catch (\Exception $e) {
    //   $this->log_event('Processing Invoice - '.$invoice['id'], $e->getMessage());
    //   throw $e;
    // }



}