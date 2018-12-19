<?php

$payload = @file_get_contents("php://input");

$pluginLogManager = new \Ubnt\UcrmPluginSdk\Service\PluginLogManager();
$pluginLogManager->appendLog('This plugin just did something and wants to let you know about it.');

# Requests should include frontendKey
if (!empty($payload['frontendKey'])) {
  

  if (!empty($payload['apiGet'])) {
    \USCP\Interpreter::get($payload['apiGet']['endpoint'], $payload['apiGet']['data']);
  } elseif (!empty($payload['apiPost'])) {
    \USCP\Interpreter::post($payload['apiPost']['endpoint'], $payload['apiPost']['data']);
  }

  # Exit public.php to prevent following HTML from rendering
  exit();
}
