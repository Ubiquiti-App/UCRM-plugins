<?php

if (!empty($_POST['nonce']) && !is_null($_POST['nonce']) && $_SERVER['REQUEST_METHOD'] == 'POST') {
  $nonce = $_POST['nonce'];

  # To learn more about splitting transactions with additional recipients,
  # see the Transactions API documentation on our [developer site]
  # (https://docs.connect.squareup.com/payments/transactions/overview#mpt-overview).
  $request_body = array (
    "card_nonce" => $nonce,
    # Monetary amounts are specified in the smallest unit of the applicable currency.
    # This amount is in cents. It's also hard-coded for $1.00, which isn't very useful.
    "amount_money" => array (
      "amount" => 100,
      "currency" => "USD"
    ),
    # Every payment you process with the SDK must have a unique idempotency key.
    # If you're unsure whether a particular payment succeeded, you can reattempt
    # it with the same idempotency key without worrying about double charging
    # the buyer.
    "idempotency_key" => uniqid()
  );
  # The SDK throws an exception if a Connect endpoint responds with anything besides
  # a 200-level HTTP code. This block catches any exceptions that occur from the request.
  try {
    $result = $transactions_api->charge($config['SQUARE_LOCATION_ID'], $request_body);
    echo "<pre>";
    print_r($result);
    echo "</pre>";
  } catch (\SquareConnect\ApiException $e) {
    echo "Caught exception!<br/>";
    print_r("<strong>Response body:</strong><br/>");
    echo "<pre>"; var_dump($e->getResponseBody()); echo "</pre>";
    echo "<br/><strong>Response headers:</strong><br/>";
    echo "<pre>"; var_dump($e->getResponseHeaders()); echo "</pre>";
  }
  exit();
}