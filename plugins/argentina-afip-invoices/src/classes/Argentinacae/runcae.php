<?php

# Autor: CWNICO

use App\Argentinacae\WSAA;
use App\Argentinacae\WSFEv1;

function runCae($ptovta, $tipofactura, $tipocbte, $regfac, $cuitSolicitante, $orgSelected)
{
    $wsaa = new WSAA('./', $orgSelected);

    // Si la fecha de expiracion es menor a hoy, obtengo un nuevo Ticket de Acceso.

    date_default_timezone_set('America/Argentina/Buenos_Aires');
    if (date_default_timezone_get()) {
        //echo 'date_default_timezone_set: ' . date_default_timezone_get() . '<br />';
    }

    if (ini_get('date.timezone')) {
        echo 'date.timezone: ' . ini_get('date.timezone');
    }

    $cert_expiration = date('Y-m-d H:m:i', strtotime($wsaa->get_expiration($orgSelected)));
    if ($cert_expiration < date('Y-m-d H:m:i')) {
        if ($wsaa->generar_TA($orgSelected)) {
            echo 'Obtenido nuevo TA<br>';
        } else {
            echo 'error al obtener el TA';
        }
    } else {
        if (DEBUG) {
            echo 'Fecha de Vencimiento: ' . $cert_expiration . '<br>';
        }
    };

    $wsfev1 = new WSFEv1('./', $orgSelected);

    // Carga el archivo TA.xml
    $wsfev1->openTA($orgSelected);

    /*
    - 01, 02, 03, 04, 05,34,39,60, 63 para los clase A
    - 06, 07, 08, 09, 10, 35, 40,64, 61 para los clase B.
    - 11, 12, 13, 15 para los clase C.
    - 51, 52, 53, 54 para los clase M.
    - 49 para los Bienes Usados
    Consultar método FEParamGetTiposCbte.
    */

    //Esto para saber si es nota de credito o debito. Si esta vacio, entonces el comprobante es para facturas, si tiene SI es nota credito, si tiene NO es nota debito.
    $nc = '';

    // Ultimo comprobante autorizado, a este le sumo uno para procesar el siguiente.
    $cmp = $wsfev1->FECompUltimoAutorizado($tipocbte, $ptovta, $cuitSolicitante);

    $regfac['nrofactura'] = $cmp->FECompUltimoAutorizadoResult->CbteNro + 1;

    // Armo con la factura los parametros de entrada para el pedido
    $params = $wsfev1->armadoFacturaUnica(
        $tipofactura,
        $ptovta,    // el punto de venta
        $nc,		// puede ser SI, NO o vacio.
        $regfac,     // los datos a facturar
        $cuitSolicitante //Cuit del generador de Factura
    );

    //Solicito el CAE
    if (DEBUG) {
        echo '<pre>';
        print_r($params);
        echo '</pre>';
    }
    $cae = $wsfev1->solicitarCAE($params, $cuitSolicitante);

    // Lo muestro
    if (isset($cae->FECAESolicitarResult->FeCabResp->Resultado)) {
        if (DEBUG) {
            print_r('Resultado ' . $cae->FECAESolicitarResult->FeCabResp->Resultado . '<br>');
        }
        if ($cae->FECAESolicitarResult->FeCabResp->Resultado == 'A') {
            $caeResult['caenumero'] = $cae->FECAESolicitarResult->FeDetResp->FECAEDetResponse->CAE;
            $caeResult['caefechavto'] = $cae->FECAESolicitarResult->FeDetResp->FECAEDetResponse->CAEFchVto;
            $caeResult['cbtenumero'] = $cae->FECAESolicitarResult->FeDetResp->FECAEDetResponse->CbteDesde;
            $caeResult['resultado'] = $cae->FECAESolicitarResult->FeCabResp->Resultado;
        } elseif ($cae->FECAESolicitarResult->FeCabResp->Resultado == 'R') {
            $caeResult['resultado'] = $cae->FECAESolicitarResult->FeCabResp->Resultado;
            print_r('DETALLE Observaciones: ' . $cae->FECAESolicitarResult->FeDetResp->FECAEDetResponse->Observaciones->Obs->Msg . '<br>');
            $counter = 0;
            foreach ($cae->FECAESolicitarResult->FeDetResp->FECAEDetResponse->Observaciones->Obs as $msg) {
                $caeResult['obs'][$counter] = $msg;
                $counter = $counter + 1;
            }
        }
    } else {
        $counter = 0;
        foreach ($cae->FECAESolicitarResult->Errors as $ErrMsg) {
            $caeResult['Err'][$counter] = $ErrMsg;
            $counter = $counter + 1;
        }
    }

    if (DEBUG) {
        echo '<pre>';
        print_r($cae);
        echo '</pre>';
    }

    if (DEBUG) {
        echo '<pre>';
        print_r($caeResult);
        echo '</pre>';
    }
    return $caeResult;
}
