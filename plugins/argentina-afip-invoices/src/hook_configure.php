<?php

declare(strict_types=1);

use App\Service\TemplateRenderer;
use Ubnt\UcrmPluginSdk\Exception\ConfigurationException;
use Ubnt\UcrmPluginSdk\Security\PermissionNames;
use Ubnt\UcrmPluginSdk\Service\PluginConfigManager;
use Ubnt\UcrmPluginSdk\Service\UcrmApi;
use Ubnt\UcrmPluginSdk\Service\UcrmOptionsManager;
use Ubnt\UcrmPluginSdk\Service\UcrmSecurity;

chdir(__DIR__);

require_once __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/vendor/autoload.php';
include __DIR__ . '/classes/Argentinacae/funciones.php';

define('DEBUG', false); //Change to true debug comments get shown

if (DEBUG) {
    echo 'estoy en instalar/actualizar custom attributes';
}
// directorio actual
if (DEBUG) {
    echo getcwd() . "\n";
}
// Retrieve API connection.
$api = UcrmApi::create();

//Retrieve actual Custom Attributes, if necesary one that doesn't exist, it will be created
$customattributes = $api->get('custom-attributes');
verifyCustomAttributes($customattributes, 'Requiere CAE?', 'requiereCae', 'client', false);
verifyCustomAttributes($customattributes, 'Tipo Factura?', 'tipoFactura', 'client', false);
verifyCustomAttributes($customattributes, 'Tipo Cliente?', 'tipoCliente', 'client', false);
verifyCustomAttributes($customattributes, 'Numero de documento?', 'numeroDeDocumento', 'client', true);
verifyCustomAttributes($customattributes, 'Cuando hacer Factura?', 'cuandoHacerFactura', 'client', false);
verifyCustomAttributes($customattributes, 'Enviar factura automaticamente?', 'enviarFacturaAutomaticamente', 'client', false);
verifyCustomAttributes($customattributes, 'Cae Numero:', 'caeNumero', 'invoice', true);
verifyCustomAttributes($customattributes, 'Cae Fecha:', 'caeFecha', 'invoice', true);
verifyCustomAttributes($customattributes, 'Numero Factura AFIP:', 'numeroFacturaAfip', 'invoice', false);
verifyCustomAttributes($customattributes, 'Concepto Factura?', 'conceptoFactura', 'invoice', false);
verifyCustomAttributes($customattributes, 'Inicio de Actividades - Comercio?', 'inicioDeActividadesComercio', 'invoice', false);
verifyCustomAttributes($customattributes, 'Letra Factura', 'letraFactura', 'invoice', false);
verifyCustomAttributes($customattributes, 'Tipo Cliente Factura', 'tipoClienteFactura', 'invoice', false);
verifyCustomAttributes($customattributes, 'Tipo Comprobante', 'tipoComprobante', 'invoice', false);
verifyCustomAttributes($customattributes, 'Fecha Comprobante Afip', 'fechaComprobanteAfip', 'invoice', false);
