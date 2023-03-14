<?php

# Autor: CWNICO

namespace App\Argentinacae;

class WSFEv1
{
    //const CUIT = "20353246322";                 # CUIT del emisor de las facturas // PREVIAMENTE AUTORIZADO EN AFIP
  //const TA   = "classes/Argentinacae/xmlgenerados/TA.xml";         # Archivo con el Token y Sign
  public const PASSPHRASE = '';                      # The passphrase (if any) to sign

  public const PROXY_ENABLE = false;

    public const LOG_XMLS = false;

    /*
     * manejo de errores
     */
    public $error = '';
    # TESTING
    //const WSDL = "classes/Argentinacae/wsfev1test.wsdl";             # WSDL TESTING
    //const WSFEURL = "https://wswhomo.afip.gov.ar/wsfev1/service.asmx"; // testing
    // const CERT = "data/files/cwcert.crt";             # El certificado X.509 en formato PEM
    // const PRIVATEKEY = "data/files/cwkey.key";        # La clave privada


    # PRODUCCION
    //  const WSDL = "classes/Argentinacae/wsfev1prod.wsdl";           # WSDL PRODUCCION
    //  const WSFEURL = "https://servicios1.afip.gov.ar/wsfev1/service.asmx"; // PRODUCCION
    //  const CERT = "data/files/CW-FACTURACION.crt";             # El certificado X.509 en formato PEM
    //  const PRIVATEKEY = "data/files/privada.key";        # La clave privada

    /*
     * el path relativo, terminado en /
     */
    private $path = './';

    /**
     * Cliente SOAP
     */
    private $client;

    /**
     * objeto que va a contener el xml de TA
     */
    private $TA;

    /**
     * tipo_cbte tipo de comprobante: si es factura A = 1 o B = 6
     */
    private $tipo_cbte = '2';

    /*
     * Constructor
     */
    public function __construct($path, $orgSelected)  //Number or organization selected, if 0 => TESTING , if >0 => PRODUCTION
    {
        if ($orgSelected == 0) {
            $WSDL = 'classes/Argentinacae/wsfev1test.wsdl';             # WSDL TESTING
    $WSFEURL = 'https://wswhomo.afip.gov.ar/wsfev1/service.asmx'; // testing
        } else {
            $WSDL = 'classes/Argentinacae/wsfev1prod.wsdl';           # WSDL PRODUCCION
    $WSFEURL = 'https://servicios1.afip.gov.ar/wsfev1/service.asmx'; // PRODUCCION
        }
        $this->path = $path;

        // seteos en php
        ini_set('soap.wsdl_cache_enabled', '0');

        // validar archivos necesarios
        if (! file_exists($this->path . $WSDL)) {
            $this->error .= ' Failed to open ' . $WSDL;
        }

        if (! empty($this->error)) {
            throw new \Exception('WSFEv1 class. Faltan archivos para el funcionamiento de la clase');
        }

        $this->client = new \SoapClient(
            $this->path . $WSDL,
            [
                'soap_version' => SOAP_1_2,
                'location' => $WSFEURL,
                'exceptions' => 0,
                'trace' => 1,
            ]
        );
    }

    /**
     * Abre el archivo de TA xml,
     * si hay algun problema devuelve false
     */
    public function openTA($orgSelected)
    {
        $this->TA = simplexml_load_file($this->path . 'classes/Argentinacae/xmlgenerados/TA' . $orgSelected . '.xml');

        return $this->TA == false ? false : true;
    }

    /**
     * Retorna la cantidad maxima de registros de detalle que
     * puede tener una invocacion al FEAutorizarRequest
     */
    public function recuperaQTY($cuit)
    {
        $results = $this->client->FERecuperaQTYRequest(
            [
                'argAuth' => [
                    'Token' => $this->TA->credentials->token,
                    'Sign' => $this->TA->credentials->sign,
                    'cuit' => $cuit,
                ],
            ]
        );

        $e = $this->_checkErrors($results, 'FERecuperaQTYRequest');

        return $e == false ? $results->FERecuperaQTYRequestResult->qty->value : false;
    }

    /*
     * Retorna el Ticket de Acceso.
     */
    public function getTA()
    {
        return $this->TA;
    }

    /*
     * Retorna el ultimo número de Request.
     */
    public function FECompUltimoAutorizado($tipo_cbte, $punto_vta, $cuit)
    {
        //Castea el cuit para ser aceptado en el Request (Pide LONG)
        //$cuit = (float)self::CUIT;

        $results = $this->client->FECompUltimoAutorizado(
            [
                'Auth' => [
                    'Token' => $this->TA->credentials->token,
                    'Sign' => $this->TA->credentials->sign,
                    'Cuit' => $cuit,
                ],
                'PtoVta' => $punto_vta,
                'CbteTipo' =>
 $tipo_cbte,
            ]
        );

        return $results;
    }

    /*
     * Retorna el ultimo comprobante autorizado para el tipo de comprobante /cuit / punto de venta ingresado.
     */
    public function recuperaLastCMP($ptovta, $cuit)
    {
        $results = $this->client->FERecuperaLastCMPRequest(
            [
                'argAuth' => [
                    'Token' => $this->TA->credentials->token,
                    'Sign' => $this->TA->credentials->sign,
                    'cuit' => $cuit,
                ],
                'argTCMP' => [
                    'PtoVta' => $ptovta,
                    'TipoCbte' => $this
                        ->tipo_cbte,
                ],
            ]
        );

        $e = $this->_checkErrors($results, 'FERecuperaLastCMPRequest');

        return $e == false ? $results->FERecuperaLastCMPRequestResult->cbte_nro : false;
    }

    /**
     * Setea el tipo de comprobante
     * A = 1
     * B = 6
     */
    public function setTipoCbte($tipo)
    {
        switch ($tipo) {
      case 'a': case 'A': case '1':
        $this->tipo_cbte = 1;
      break;

      case 'b': case 'B': case 'c': case 'C': case '6':
        $this->tipo_cbte = 6;
      break;

      default:
        return false;
    }
        return true;
    }

    public function armadoFacturaUnica($tipofactura, $puntoventa, $nc, $renglon, $cuit)
    {
        //$cuit = (float)self::CUIT;
        switch ($tipofactura) {
            case 'A':
                if ($nc == '') {
                    $CbteTipo = 1;
                } else {
                    if ($nc == 'SI') {
                        $CbteTipo = 3;
                    } else {
                        $CbteTipo = 2;
                    }
                }
                break;
            case 'B':
                if ($nc == '') {
                    $CbteTipo = 6;
                } else {
                    if ($nc == 'SI') {
                        $CbteTipo = 8;
                    } else {
                        $CbteTipo = 7;
                    }
                }
                break;
            case 'C':
                if ($nc == '') {
                    $CbteTipo = 11;
                } else {
                    if ($nc == 'SI') {
                        $CbteTipo = 13;
                    } else {
                        $CbteTipo = 12;
                    }
                }
                break;
        }

        $FeCabReq = [
            'CantReg' => 1,
            'PtoVta' => $puntoventa,
            'CbteTipo' => $CbteTipo,
        ];
        $tipodoc = $renglon['tipodocumento'];
        if ($tipodoc == 96) {
            $nrodoc = $renglon['numerodocumento'];
        } elseif ($tipodoc == 80) {
            $nrodoc = $renglon['cuit'];
        } else {
            $tipodoc = 99;
            if ($renglon['numerodocumento'] != 0) {
                $nrodoc = $renglon['numerodocumento'];
                $tipodoc = 96;
            } elseif ($tipofactura == 'B' and $renglon['importetotal'] < 1000) {
                $nrodoc = 0;
            } elseif ($tipofactura == 'B' and $renglon['importetotal'] > 1000 and $renglon['cuit'] != '') {
                $nrodoc = str_replace('-', '', $renglon['cuit']);
                $tipodoc = 80;
            } else {
                $nrodoc = str_replace('-', '', $renglon['cuit']);
                if ($tipofactura == 'A') {
                    $tipodoc = 80;
                }
            }
        }

        $neto = $renglon['importeneto'];

        if ($renglon['importeneto'] != '0') {
            $baseimp = $renglon['importeneto'];
        }

        // si el iva es 0 no informo nada
        if ($renglon['importeiva'] > 0) {
            $detalleiva = [
                'AlicIva' => [
                    'Id' => 5,
                    'BaseImp' => $baseimp,
                    'Importe' => $renglon['importeiva'],
                ],
            ];
            $FECAEDetRequest = [
                'Concepto' => $renglon['concepto'],
                'DocTipo' => $tipodoc,
                'DocNro' => $nrodoc,
                'CbteDesde' => $renglon['nrofactura'],
                'CbteHasta' => $renglon['nrofactura'],
                'CbteFch' => date('Ymd'),
                'ImpTotal' => round($renglon['importetotal'], 2),
                'ImpTotConc' => round($renglon['capitalafinanciar'], 2),
                'ImpNeto' => round($neto, 2),
                'ImpOpEx' => 0.00,
                'ImpTrib' => 0.00,
                'ImpIVA' => $renglon['importeiva'],
                //'FchServDesde' => $renglon['FchServDesde'],
                //'FchServHasta' => $renglon['FchServHasta'],
                //if($renglon['concepto'] != 1) 'FchVtoPago' => $renglon['fecha_venc_pago'],
                'MonId' => 'PES',
                'MonCotiz' => '1.00',
                'Iva' => $detalleiva,
            ];
            if ($renglon['concepto'] != 1) {
                $FECAEDetRequest['FchVtoPago'] = $renglon['fecha_venc_pago'];
                $FECAEDetRequest['FchServDesde'] = $renglon['FchServDesde'];
                $FECAEDetRequest['FchServHasta'] = $renglon['FchServHasta'];
            }
        } else {
            $FECAEDetRequest = [
                'Concepto' => $renglon['concepto'],
                'DocTipo' => $tipodoc,
                'DocNro' => $nrodoc,
                'CbteDesde' => $renglon['nrofactura'],
                'CbteHasta' => $renglon['nrofactura'],
                'CbteFch' => date('Ymd'),
                'ImpTotal' => round($renglon['importetotal'], 2),
                'ImpTotConc' => round($renglon['capitalafinanciar'], 2),
                'ImpNeto' => round($neto, 2),
                'ImpOpEx' => 0.00,
                'ImpTrib' => 0.00,
                'ImpIVA' => $renglon['importeiva'],
                //'FchServDesde' => $renglon['FchServDesde'],
                //'FchServHasta' => $renglon['FchServHasta'],
                //if($renglon['concepto'] != 1) 'FchVtoPago' => $renglon['fecha_venc_pago'],
                'MonId' => 'PES',
                'MonCotiz' => 1,
            ];
            if ($renglon['concepto'] != 1) {
                $FECAEDetRequest['FchVtoPago'] = $renglon['fecha_venc_pago'];
                $FECAEDetRequest['FchServDesde'] = $renglon['FchServDesde'];
                $FECAEDetRequest['FchServHasta'] = $renglon['FchServHasta'];
            }
        }	//var_dump($FECAEDetRequest);
        $fedetreq = [
            'FECAEDetRequest' => $FECAEDetRequest,
        ];
        $params = [
            'FeCabReq' => $FeCabReq,
            'FeDetReq' => $fedetreq,
        ];
        return $params;
    }

    public function llamarmetodo($metodo, $params, $cuit)
    {
        //$cuit = (float)self::CUIT;

        switch ($metodo) {
            case 'Funciones':
                $resu = $this->client->__getFunctions();
                break;
            case 'FEDummy':
                $resu = $this->client->FEDummy();
                break;
            case 'FECAESolicitar':
                $resu = $this->client->FECAESolicitar($params);
                break;
            case 'FECompUltimoAutorizado':
                $resu = $this->client->FECompUltimoAutorizado($params);
                break;
            case 'FEParamGetPtosVenta':
                $resu = $this->client->FEParamGetPtosVenta(
                    [
                        'Auth' => [
                            'Token' => $this->TA->credentials->token,
                            'Sign' => $this->TA->credentials->sign,
                            'Cuit' => $cuit,

                        ],
                    ]
                );
                break;
            case 'FEParamGetTiposMonedas':
                $resu = $this->client->FEParamGetTiposMonedas($params);
                break;
            case 'FEParamGetTiposCbte':
                $resu = $this->client->FEParamGetTiposCbte($params);
                break;
            case 'FEParamGetTiposDoc':
                $params->Auth->Token = $this->TA->credentials->token;
                $params->Auth->Sign = $this->TA->credentials->sign;
                $params->Auth->Cuit = $cuit;
                $resu = $this->client->FEParamGetTiposDoc($params);
                break;

            default: echo 'falta definir metodo';
                break;
        }
        return $resu;
    }

    public function solicitarCAE($params, $cuit)
    {
        //$cuit = (float)self::CUIT;
        $results = $this->client->FECAESolicitar(
            [
                'Auth' => [
                    'Token' => $this->TA->credentials->token,
                    'Sign' => $this->TA->credentials->sign,
                    'Cuit' => $cuit,
                ],
                'FeCAEReq' =>
$params,
            ]
        );
        return $results;
    }

    /**
     * Chequea los errores en la operacion, si encuentra algun error falta lanza una exepcion
     * si encuentra un error no fatal, loguea lo que paso en $this->error
     */
    private function _checkErrors($results, $method)
    {
        if (self::LOG_XMLS) {
            file_put_contents('xmlgenerados/request-' . $method . '.xml', $this->client->__getLastRequest());
            file_put_contents('xmlgenerados/response-' . $method . '.xml', $this->client->__getLastResponse());
        }

        if (is_soap_fault($results)) {
            throw new \Exception('WSFEv1 class. FaultString: ' . $results->faultcode . ' ' . $results->faultstring);
        }

        if ($method == 'FEDummy') {
            return;
        }

        $XXX = $method . 'Result';
        if ($results->{$XXX}->RError->percode != 0) {
            $this->error = "Method=${method} errcode=" . $results->{$XXX}->RError->percode . ' errmsg=' . $results->{$XXX}->RError->perrmsg;
        }

        return $results->{$XXX}->RError->percode != 0 ? true : false;
    }
}
