<?php
declare(strict_types=1);
namespace Ugpp;

chdir(__DIR__);
define("GENERATOR_PATH", __DIR__);

class Generator {
  private $UscpCustomAttributes = [
    'Ucsp Gateway Customer' => 'ucspGatewayCustomer', 
    'Ucsp Gateway Token' => 'ucspGatewayToken', 
  ];

  public function customAttributes() {
    return $this->UscpCustomAttributes;
  }

  public function __construct() {
    $this->log = new \Ubnt\UcrmPluginSdk\Service\PluginLogManager();
    $this->api = \Ubnt\UcrmPluginSdk\Service\UcrmApi::create();
  }

  public function get($endpoint, $data = []) {
    return $this->api->get($endpoint, $data);
  }

  public function post($endpoint, $data = []) {
    return $this->api->post($endpoint, $data);
  }

  public function customAttributesExists() {
    // # Get attributes from UCRM
    $results = $this->get('custom-attributes');

    // # Make sure empty or null is set to an empty array
    if (empty($results) || is_null($results)) {
      $results = [];
    }

    // # Retrieve only keys from attributes and assign to new array
    $keys = array_map(function($i) {
      if ($i['attributeType'] == 'client') {
        return $i['key'];
      }
    }, $results);
    // # Check against required attributes and return any that do not match/exist in UCRM
    $remainder = array_diff($this->UscpCustomAttributes, $keys);

    // # If any remain return them...
    if (count($remainder) > 0) {
      return $remainder;
    } else {
      // # ...else custom attributes exist return true
      return true;
    }

  }

  public function createCustomAttributes() {

    // # Do not create attributes if they already exist
    if ($this->customAttributesExists() === true) {
      return false;
    } else {
      // # Otherwise, get missing attributes...
      $missingAttributes = $this->customAttributesExists();

      // # ...and generate them
      foreach ($missingAttributes as $key => $value) {
        $this->post('custom-attributes', ['name' => $key, 'attributeType' => 'client', 'clientZoneVisible' => false]);
      }
      $attributes = $this->get('custom-attributes');

      // # They should exist now which returns true and should not be an array
      if ($this->customAttributesExists() === true) {
        return true;
      } else {
        return false;
        // # ...Log error if they don't
        $this->log->appendLog('failed to create custom attributes');
      }

    }
  }

  public function getAttributeId($attrKey) {
    // # Check for existing gateway customer attribute and get ID
    $getAttributeId = null;
    $attributes = $this->get('custom-attributes');
    foreach ($attributes as $attribute) {
      if ($attribute['attributeType'] == 'client') {
        if ($attribute['key'] == $attrKey) {
          $getAttributeId = $attribute['id'];
          break;
        }
      }
    }
    return $getAttributeId;
  }

  public function getAttribute($attributes, $key) {
    $attributeValue = null;
    foreach ($attributes as $attribute) {
      if ($attribute['key'] == $key) {
        $attributeValue = $attribute['value'];
        break;
      }
    }
    return $attributeValue;
  }


  public function run($type, $data = []) {

    if ($type == 'plugin-config') {
      $data['gatewayAttributeId'] = $this->getAttributeId('ucspGatewayCustomer');
      $data['tokenAttributeId'] = $this->getAttributeId('ucspGatewayToken');
      $data['formEmailAttributeId'] = $this->getAttributeId('ucspFormEmail');
      $data['serviceDataAttributeId'] = $this->getAttributeId('ucspServiceData');
      $data['errorsAttributeId'] = $this->getAttributeId('ucspErrors');
    }

    return $data;

  }

}