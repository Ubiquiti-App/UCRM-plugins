<?php
namespace UCS;

class UcrmHandler extends UcrmApi {
  
  ## Create Client
  # @param array $client
  # @return object
  public function createClient($client, $json_response=false) {

    $content = [
      "firstName" => (empty($client->firstName)) ? null : $client->firstName,
      "lastName" => (empty($client->lastName)) ? null : $client->lastName,
      "street1" => (empty($client->street1)) ? null : $client->street1,
      "city" => (empty($client->city)) ? null : $client->city,
      "countryId" => (empty($client->countryId)) ? null : $client->countryId,
      "stateId" => (empty($client->stateId)) ? null : $client->stateId,
      "zipCode" => (empty($client->zipCode)) ? null : $client->zipCode,
      "username" => (empty($client->username)) ? null : $client->username,
      "contacts" => (empty($client->contacts)) ? null : $client->contacts,
    ];
    $this->validateObject($content);

    $content['isLead'] = (empty(Config::$LEAD)) ? false : Config::$LEAD;

    $response = $this->guzzle('POST', '/clients', $content);
    if ($json_response) {
      echo json_response($response['message'], 200, true);
      exit();
    } else {
      return json_decode($response['message']);
    }
  }

  public function testMethod() {
    return 'testing';
  }
  
  ## Get Countries
  # @return json
  public function getCountries() {
    $endpoint = "/countries";
    $response = $this->guzzle('GET', $endpoint);
    $this->response = $response['message'];
    return $response['message'];
  }

  ## Get States by Country
  #
  # @return json
  public function getStatesByCountry($country_id) {
    $endpoint = "/countries/{$country_id}/states";
    $response = $this->guzzle('GET', $endpoint);
    $this->response = $response['message'];
    return $response['message'];
  }



}