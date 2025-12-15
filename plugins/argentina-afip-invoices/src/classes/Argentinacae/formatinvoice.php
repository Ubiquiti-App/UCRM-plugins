<?php

declare(strict_types=1);

use App\Argentinacae\WSAA;
use App\Argentinacae\WSFEv1;
use App\Service\TemplateRenderer;
use Ubnt\UcrmPluginSdk\Exception\ConfigurationException;
use Ubnt\UcrmPluginSdk\Security\PermissionNames;
use Ubnt\UcrmPluginSdk\Service\PluginConfigManager;
use Ubnt\UcrmPluginSdk\Service\UcrmApi;
use Ubnt\UcrmPluginSdk\Service\UcrmOptionsManager;
use Ubnt\UcrmPluginSdk\Service\UcrmSecurity;

//chdir(__DIR__);

//require_once __DIR__ . '/vendor/autoload.php';
include __DIR__ . '/runcae.php';
include __DIR__ . '/funciones.php';

//define('DEBUG', true); //UnComment for debug comments get shown

function formatInvoice($orgId, $salesPoint, $activitiesStartDate, $orgSelected)
{
    if (DEBUG) {
        echo 'estoy en formatinvoice';
    }
    // directorio actual
    if (DEBUG) {
        echo getcwd() . "\n";
    }
    // Retrieve API connection.
    $api = UcrmApi::create();

    // Ensure that user is logged in and has permission to view invoices.
    $security = UcrmSecurity::create();
    $user = $security->getUser();
    if (! $user || $user->isClient || ! $user->hasViewPermission(PermissionNames::BILLING_INVOICES)) {
        \App\Http::forbidden();
    }

    // Retrieve UCRM Config.
    $pluginConfigManager = PluginConfigManager::create();
    $config = $pluginConfigManager->loadConfig();
    if (DEBUG) {
        echo 'Fecha de inicio para tomar facturas en cuenta para CAE: ' . $config['startDate'] . '<br>';
    }
    if (DEBUG) {
        echo 'Punto de venta: ' . $config['salesPoint'] . '<br>';
    }
    if ($config['isTesting'] == true) {	//Check if TESTING is selected
        $orgSelected = 0;
    } // If not testing, take the Org Number checking the order as it was charged on the salesPoint

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

    //update de $customattributes array in case it was modified
    $customattributes = $api->get('custom-attributes');

    //Retrieve Invoice Template Id
    $templateId = getInvoiceTemplateId($config['invoiceTemplateId']);

    //Retrieve all clients with requiereCae custom attribute === 1
    $clients = $api->get(
        'clients',
        [
            'organizationId' => $orgId,
            'customAttributeKey' => 'requiereCae',
            'customAttributeValue' => '1',
        ]
    );

    $organization = $api->get(
        'organizations/' . $orgId,
        [
        ]
    );

    if ($config['isTesting'] == true) {	//Check if TESTING is selected
        $cuitSolicitante = 20353246322;
    } else { 	// If not testing, take the Org TAX-ID
        $cuitSolicitante = $organization['taxId'];
    }
    if ($cuitSolicitante == false || $cuitSolicitante == 0) {
        echo '<br> La organizacion no tiene asignado CUIT <br>';
    } else {

// Check all clients
        foreach ($clients as $client) {
            if (DEBUG) {
                echo 'Cliente encontrado <br>';
            }
            $verifyClientOk = true; //Check Flag
            if (DEBUG) {
                echo '<pre>';
                print_r($client);
                echo '</pre>';
            }

            // Verify type of invoice needed
            $tipoFC = getCustomAttributeValue($client['attributes'], 'tipoFactura');
            if ($tipoFC === 'A' || $tipoFC === 'a') {
                $tipocbte = '01';
            } elseif ($tipoFC === 'B' || $tipoFC === 'b') {
                $tipocbte = '06';
            } elseif ($tipoFC === 'C' || $tipoFC === 'c') {
                $tipocbte = '11';
            } else {
                echo '<br> Cliente ' . htmlspecialchars($client['id'] ?? '', ENT_QUOTES) . ' sin tipo de factura asignado / O mal asignado, recuerde los valores validos son A,B,C <br>';
                $verifyClientOk = false;
            }

            // Verify type of identification number for needed for invoice
            $tipoCliente = getCustomAttributeValue($client['attributes'], 'tipoCliente');
            if ($tipoCliente === 'RI' || $tipoCliente === 'ri' || $tipoCliente === 'RM' || $tipoCliente === 'rm' || $tipoCliente === 'EX' || $tipoCliente === 'ex') {
                $regfac['tipodocumento'] = 80;
            } elseif ($tipoCliente === 'CF' || $tipoCliente === 'cf') {
                $regfac['tipodocumento'] = 96;
            } else {
                echo '<br> Cliente sin tipo de cliente asignado, recuerde tipos disponibles DNI|CUIT|CF (Consumidor final) <br>';
                $verifyClientOk = false;
            }

            // Verify identification number for invoice
            $numeroDeDocumento = getCustomAttributeValue($client['attributes'], 'numeroDeDocumento');
            if ($regfac['tipodocumento'] === 80) {
                $regfac['numerodocumento'] = 0;
                $regfac['cuit'] = $numeroDeDocumento;
            } elseif ($regfac['tipodocumento'] === 96) {
                $regfac['numerodocumento'] = $numeroDeDocumento;
                $regfac['cuit'] = 0;
            } else {
                echo '<br> Cliente sin numero de documento asignado, recuerde asignarlos al igual que el tipo de documento. <br>';
                $verifyClientOk = false;
            }

            //Creating array with invoice hardcodes.
    $regfac['capitalafinanciar'] = 0;			# subtotal de conceptos no gravados // ESTO ES CERO SI LA FACTURA ES C
    $regfac['imp_trib'] = 0;					# TIENE QUE SER 0 para FACTURAS C
    $regfac['imp_op_ex'] = 0.0;
            $regfac['nrofactura'] = 0;

            // Verify if CAE is retrieved when invoice is PAID cuandoHacerFactura === paid || pago or when is UNPAID cuandoHacerFactura === unpaid || impaga
            $cuandoHacerFactura = getCustomAttributeValue($client['attributes'], 'cuandoHacerFactura');
            if ($cuandoHacerFactura === 'paid' || $cuandoHacerFactura === 'paga' || $cuandoHacerFactura === 'PAID' || $cuandoHacerFactura === 'PAGA') {
                if (DEBUG) {
                    echo 'Cliente es paid <br>';
                }
                $invoicestatus = 3;
            } elseif ($cuandoHacerFactura === 'unpaid' || $cuandoHacerFactura === 'impaga' || $cuandoHacerFactura === 'UNPAID' || $cuandoHacerFactura === 'IMPAGA') {
                if (DEBUG) {
                    echo 'Cliente es unpaid <br>';
                }
                $invoicestatus = 1;
            } else {
                echo '<br> El cliente no posee correctamente especificado algun atributo, recuerde que \"Cuando Hacer Factura?\", tiene valores validos: paga | paid | impaga | unpaid ';
                $verifyClientOk = false;
            }

            if ($verifyClientOk) {
                foreach ($api->get('invoices', [
                    'clientId' => $client['id'],
                    'statuses[0]' => $invoicestatus,
                    'createdDateFrom' => $config['startDate'],
                    //'proforma' => false,
                ]) as $invoice) {
                    // Verify identification number for invoice
                    // search for caeNumero Value
                    if (DEBUG) {
                        echo '<pre>';
                        print_r($invoice);
                        echo '</pre>';
                    }
                    $actualCaeNum = getCustomAttributeValue($invoice['attributes'], 'caeNumero');
                    if (is_null($actualCaeNum)) {			//Verify that invoice has not CAE number already
                        $startOk = true;
                        if ($regfac['tipodocumento'] === 96 && $invoice['total'] < 7689.99 && $regfac['numerodocumento'] == 0) {
                            $regfac['tipodocumento'] = 99;
                        } elseif ($regfac['tipodocumento'] === 96 && $invoice['total'] > 7689.99 && $regfac['numerodocumento'] == 0) {//Final customers with no DNI associated can not have any invoice with ammount over $7690
                            echo '<br> Factura consumidor final no debe exceder los $7690.00 sin tener DNI <br>';
                            $startOk = false; //Don't Start the invoice if it not has Document number
                        }
                        if ($startOk) {//Process Start
                            if (DEBUG) {
                                echo 'Factura encontrada <br>';
                            }
                            // Verify invoice attribute for concept of invoice (1- Supplies, 2- Services, 3-Both
                            $conceptoMalEstablecido = false;
                            //Search for key where conceptoFactura is
                            //$attributeconceptoFactura = array_search('conceptoFactura', array_column($invoice['attributes'], 'key')); //Disable for test AUTO CONCEPT DETECTION
                            // search for conceptoFactura Value

                            $conceptoFactura = getInvoiceConcept($invoice['items']); //NEW AUTO CONCEPT DETECTION METHOD
        if ($conceptoFactura == 1 || $conceptoFactura == 2 || $conceptoFactura == 3) {//NEW AUTO CONCEPT DETECTION METHOD
        //if(!(gettype($attributeconceptoFactura)=='boolean' && $attributeconceptoFactura == false)){  //Disable for test AUTO CONCEPT DETECTION
        //if(!is_null($attributeconceptoFactura)){

        //$conceptoFactura = $invoice['attributes'][$attributeconceptoFactura]['value']; //Disable for test AUTO CONCEPT DETECTION
            if ($conceptoFactura === 1 || $conceptoFactura === 'Bienes' || $conceptoFactura === 'bienes' || $conceptoFactura === 'BIENES') {
                $regfac['concepto'] = 1;
                $api->patch(
                    'invoices/' . $invoice['id'],
                    [
                        'attributes' => [
                            [
                                'customAttributeId' => getCustomAttributesId($customattributes, 'conceptoFactura'),
                                'value' => 'Bienes',
                            ],
                        ],

                    ]
                );
            } elseif ($conceptoFactura === 2 || $conceptoFactura === 'Servicios' || $conceptoFactura === 'servicios' || $conceptoFactura === 'SERVICIOS') {
                $regfac['concepto'] = 2;
                $regfac['FchServDesde'] = date('Ym01');
                $regfac['FchServHasta'] = date('Ymt');
                $api->patch(
                    'invoices/' . $invoice['id'],
                    [
                        'attributes' => [
                            [
                                'customAttributeId' => getCustomAttributesId($customattributes, 'conceptoFactura'),
                                'value' => 'Servicios',
                            ],
                        ],

                    ]
                );
            } elseif ($conceptoFactura === 3 || $conceptoFactura === 'Ambos' || $conceptoFactura === 'ambos' || $conceptoFactura === 'AMBOS') {
                $regfac['concepto'] = 3;
                $regfac['FchServDesde'] = date('Ym01');
                $regfac['FchServHasta'] = date('Ymt');
                $api->patch(
                    'invoices/' . $invoice['id'],
                    [
                        'attributes' => [
                            [
                                'customAttributeId' => getCustomAttributesId($customattributes, 'conceptoFactura'),
                                'value' => 'Ambos (Bienes + Servicios)',
                            ],
                        ],

                    ]
                );
            } else {
                $regfac['concepto'] = 2; // As default "Services"
                $regfac['FchServDesde'] = date('Ym01');
                $regfac['FchServHasta'] = date('Ymt');
                $conceptoMalEstablecido = true;
                $api->patch(
                    'invoices/' . $invoice['id'],
                    [
                        'attributes' => [
                            [
                                'customAttributeId' => getCustomAttributesId($customattributes, 'conceptoFactura'),
                                'value' => 'Servicios (Seleccion Default)',
                            ],
                        ],

                    ]
                );
            }
        } else {
            $regfac['concepto'] = 2; // As default "Services"
            $regfac['FchServDesde'] = date('Ym01');
            $regfac['FchServHasta'] = date('Ymt');
            $conceptoMalEstablecido = true;
            $api->patch(
                'invoices/' . $invoice['id'],
                [
                    'attributes' => [
                        [
                            'customAttributeId' => getCustomAttributesId($customattributes, 'conceptoFactura'),
                            'value' => 'Servicios (Seleccion Default)',
                        ],
                    ],

                ]
            );
        }

                            $regfac['importetotal'] = $invoice['total']; //invoice Total
        $regfac['importeneto'] = $invoice['subtotal'] - ($invoice['subtotal'] * $invoice['discount'] / 100); //invoice subtotal
        //Taxes
        $taxTotal = 0;
                            foreach ($invoice['taxes'] as $tax) {
                                $taxTotal += $tax['totalValue'];
                            }
                            $regfac['importeiva'] = $taxTotal; //invoice taxes
                            $dueDate = new DateTime($invoice['dueDate']);
                            $regfac['fecha_venc_pago'] = $dueDate->format('Ymd'); //due date
                            if ($regfac['fecha_venc_pago'] < date('Ymd')) {
                                $regfac['fecha_venc_pago'] = date('Ymd');
                                $invoiceCreatedDate = new DateTime($invoice['createdDate']);
                                //echo 'Diferencia de Dias' . (date("Ymd") - $createdDate->format('Ymd'));
                                $api->patch(
                                    'invoices/' . $invoice['id'],
                                    [
                                        'maturityDays' => (date('Ymd') - $invoiceCreatedDate->format('Ymd')),
                                    ]
                                );
                            }
                            if (DEBUG) {
                                echo '<pre>';
                                print_r($regfac);
                                echo '</pre>';
                            }
                            $caeResultrunCae = runCae($salesPoint, $tipoFC, $tipocbte, $regfac, $cuitSolicitante, $orgSelected);
                            //runCae();
                            if (DEBUG) {
                                echo '<pre>';
                                print_r($caeResultrunCae);
                                echo '</pre>';
                            }
                            if ($caeResultrunCae['resultado'] === 'A') {
                                $numeroFacturaAfip = $salesPoint . '-' . sprintf("%'.08d", $caeResultrunCae['cbtenumero']);
                                echo '<br> Solicitando CAE para factura id: ' . htmlspecialchars($invoice['id'] ?? '', ENT_QUOTES) . ' numero: ' . htmlspecialchars($tipoFC ?? '', ENT_QUOTES) . '-' . htmlspecialchars($numeroFacturaAfip ?? '', ENT_QUOTES) . ' del cliente ' . htmlspecialchars($client['firstName'] ?? '', ENT_QUOTES) . ' ' . htmlspecialchars($client['lastName'] ?? '', ENT_QUOTES) . htmlspecialchars($client['companyName'] ?? '', ENT_QUOTES) . ' => Resultado OK <br>';
                                if ($conceptoMalEstablecido) {
                                    echo '<br> CONCEPTO DE FACTURA MAL ESTABLECIDO, SE CONSIDERA SERVICIOS POR DEFAULT <br>';
                                }
                                $fechaVtoCae = new DateTime($caeResultrunCae['caefechavto']);

                                //Patch invoice with new values if aproved

                                $api->patch(
                                    'invoices/' . $invoice['id'],
                                    [
                                        'attributes' => [
                                            [
                                                'customAttributeId' => getCustomAttributesId($customattributes, 'caeNumero'),
                                                'value' => $caeResultrunCae['caenumero'],
                                            ],
                                        ],

                                    ]
                                );
                                $api->patch(
                                    'invoices/' . $invoice['id'],
                                    [
                                        'attributes' => [
                                            [
                                                'customAttributeId' => getCustomAttributesId($customattributes, 'caeFecha'),
                                                'value' => $fechaVtoCae->format('d/m/Y'),
                                            ],
                                        ],

                                    ]
                                );
                                $api->patch(
                                    'invoices/' . $invoice['id'],
                                    [
                                        'attributes' => [
                                            [
                                                'customAttributeId' => getCustomAttributesId($customattributes, 'numeroFacturaAfip'),
                                                'value' => $numeroFacturaAfip,
                                            ],
                                        ],

                                    ]
                                );
                                $api->patch(
                                    'invoices/' . $invoice['id'],
                                    [
                                        'number' => $tipoFC . '-' . $numeroFacturaAfip,
                                    ]
                                );
                                $api->patch(
                                    'invoices/' . $invoice['id'],
                                    [
                                        'attributes' => [
                                            [
                                                'customAttributeId' => getCustomAttributesId($customattributes, 'inicioDeActividadesComercio'),
                                                'value' => $activitiesStartDate,
                                            ],
                                        ],

                                    ]
                                );
                                $api->patch(
                                    'invoices/' . $invoice['id'],
                                    [
                                        'attributes' => [
                                            [
                                                'customAttributeId' => getCustomAttributesId($customattributes, 'letraFactura'),
                                                'value' => $tipoFC,
                                            ],
                                        ],

                                    ]
                                );
                                $api->patch(
                                    'invoices/' . $invoice['id'],
                                    [
                                        'attributes' => [
                                            [
                                                'customAttributeId' => getCustomAttributesId($customattributes, 'tipoComprobante'),
                                                'value' => $tipocbte,
                                            ],
                                        ],

                                    ]
                                );
                                $api->patch(
                                    'invoices/' . $invoice['id'],
                                    [
                                        'attributes' => [
                                            [
                                                'customAttributeId' => getCustomAttributesId($customattributes, 'tipoClienteFactura'),
                                                'value' => $tipoCliente,
                                            ],
                                        ],

                                    ]
                                );
                                $api->patch(
                                    'invoices/' . $invoice['id'],
                                    [
                                        'attributes' => [
                                            [
                                                'customAttributeId' => getCustomAttributesId($customattributes, 'fechaComprobanteAfip'),
                                                'value' => date('d/m/Y'),
                                            ],
                                        ],

                                    ]
                                );
                                $api->patch(
                                    'invoices/' . $invoice['id'],
                                    [
                                        'proforma' => false,
                                    ]
                                );
                                $api->patch(
                                    'invoices/' . $invoice['id'],
                                    [
                                        'invoiceTemplateId' => $templateId,
                                    ]
                                );
                                $api->patch(
                                    'invoices/' . $invoice['id'] . '/regenerate-pdf'
                                );
                                $enviarFactura = getCustomAttributeValue($client['attributes'], 'enviarFacturaAutomaticamente');
                                if (! is_null($enviarFactura)) {
                                    if ($enviarFactura == 1 || $enviarFactura == 'si' || $enviarFactura == 'SI') {
                                        $api->patch(
                                            'invoices/' . $invoice['id'] . '/send'
                                        );
                                    }
                                }
                            } elseif ($caeResultrunCae['resultado'] === 'R') {
                                echo '<br> Solicitando CAE para factura id: ' . htmlspecialchars($invoice['id'] ?? '', ENT_QUOTES) . ' del cliente ' . htmlspecialchars($client['firstName'] ?? '', ENT_QUOTES) . ' ' . htmlspecialchars($client['lastName'] ?? '', ENT_QUOTES) . htmlspecialchars($client['companyName'] ?? '', ENT_QUOTES) . ' => Resultado ERROR, verifique detalles a continuacion: <br>';
                                if ($conceptoMalEstablecido) {
                                    echo 'CONCEPTO DE FACTURA MAL ESTABLECIDO, SE CONSIDERA SERVICIOS POR DEFAULT <br>';
                                }
                                echo '<pre>';
                                print_r(htmlspecialchars($caeResultrunCae['obs'] ?? '', ENT_QUOTES));
                                echo '</pre>';
                            } else {
                                echo '<br> ERRORES ENCONTRADOS EN LA COMUNICACION CON LOS SERVIDORES DE AFIP!!!!!!!!!! <br> ';
                                echo '<pre>';
                                print_r(htmlspecialchars($caeResultrunCae['Err'] ?? '', ENT_QUOTES));
                                echo '</pre>';
                            }
                        }//end Verify CF else
                    } //end of if caeNumero
                } //end of foreach invoice
            }
        } // en of foreach client
    } //end of else (Organization taxId Check)

echo '</ul>';
}
