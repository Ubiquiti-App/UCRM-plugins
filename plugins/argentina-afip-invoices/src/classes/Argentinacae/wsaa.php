<?php

# Autor: CWNICO

namespace App\Argentinacae;

use Ubnt\UcrmPluginSdk\Exception\ConfigurationException;

class WSAA
{
    //const TA   = "classes/Argentinacae/xmlgenerados/TA.xml";    # Archivo con el Token y Sign
  public const PASSPHRASE = '';         		 # The passphrase (if any) to sign

  public const PROXY_ENABLE = false;

    /*
     * manejo de errores
     */
    public $error = '';

    # TESTING
    //  const CERT = "data/files/cwcert.crt";      # The X.509 certificate in PEM format
    //  const PRIVATEKEY = "data/files/cwkey.key"; # The private key correspoding to CERT (PEM)
    //  const WSDL = "classes/Argentinacae/wsaa.wsdl";      		 # The WSDL corresponding to WSAA TEST
    //  const URL = "https://wsaahomo.afip.gov.ar/ws/services/LoginCms"; // testing

    /*
    # PRODUCCION
      const CERT = "data/files/CW-FACTURACION.crt";        	# The X.509 certificate in PEM format
      const PRIVATEKEY = "data/files/privada.key";  			# The private key correspoding to CERT (PEM)
      const WSDL = "classes/Argentinacae/wsaaprod.wsdl";      				# The WSDL corresponding to WSAA PROD
      const URL = "https://wsaa.afip.gov.ar/ws/services/LoginCms";
    */

    /*
     * el path relativo, terminado en /
     */
    private $path = './';

    /**
     * Cliente SOAP
     */
    private $client;

    /*
     * servicio del cual queremos obtener la autorizacion
     */
    private $service;

    /*
     * Constructor
     */
    public function __construct($path, $orgSelected, $service = 'wsfe')
    {
        chdir('./');
        $orgSelected = 0;
        $this->path = $path;
        $this->service = $service;

        // seteos en php
        ini_set('soap.wsdl_cache_enabled', '0');

        if ($orgSelected == 0) {
            $WSDL = 'classes/Argentinacae/wsaa.wsdl';             # WSDL TESTING
    $CERT = 'classes/Argentinacae/claves/cwcert.crt';      # The X.509 certificate in PEM format
    $PRIVATEKEY = 'classes/Argentinacae/claves/cwkey.key'; # The private key correspoding to CERT (PEM)
    $URL = 'https://wsaahomo.afip.gov.ar/ws/services/LoginCms'; // testing
        } else {
            $WSDL = 'classes/Argentinacae/wsaaprod.wsdl';           # WSDL PRODUCCION
    $CERT = 'data/files/cwcert' . $orgSelected . '.crt';      # The X.509 certificate in PEM format
    $PRIVATEKEY = 'data/files/cwkey' . $orgSelected . '.key'; # The private key correspoding to CERT (PEM)
    $URL = 'https://wsaa.afip.gov.ar/ws/services/LoginCms'; // PRODUCCION
        }

        // directorio actual
        // validar archivos necesarios
        if (! file_exists($this->path . $CERT)) {
            $this->error .= ' Failed to open ' . $CERT;
        } elseif (DEBUG) {
            echo 'cert ok <br>';
        }
        if (! file_exists($this->path . $PRIVATEKEY)) {
            $this->error .= ' Failed to open ' . $PRIVATEKEY;
        } elseif (DEBUG) {
            echo 'privatekey ok <br>';
        }
        if (! file_exists($this->path . $WSDL)) {
            $this->error .= ' Failed to open ' . $WSDL;
        } elseif (DEBUG) {
            echo 'wsdl ok <br>';
        }

        if (! empty($this->error)) {
            throw new \Exception('WSAA class. Faltan archivos necesarios para el funcionamiento');
            //   throw new ConfigurationException('WSAA class. Faltan archivos necesarios para el funcionamiento');
        }

        $this->client = new \SoapClient(
            $this->path . $WSDL,
            [
                'soap_version' => SOAP_1_2,
                'location' => $URL,
                'trace' => 1,
                'exceptions' => 0,
            ]
        );
    }

    /**
     * funcion principal que llama a las demas para generar el archivo TA.xml
     * que contiene el token y sign
     */
    public function generar_TA($orgSelected)
    {
        $this->create_TRA($orgSelected);
        $TA = $this->call_WSAA($this->sign_TRA($orgSelected));

        if (! file_put_contents($this->path . 'classes/Argentinacae/xmlgenerados/TA' . $orgSelected . '.xml', $TA)) {
            throw new \Exception('Error al generar al archivo TA.xml');
        }

        $this->TA = $this->xml2Array($TA);

        return true;
    }

    /**
     * Obtener la fecha de expiracion del TA
     * si no existe el archivo, devuelve false
     */
    public function get_expiration($orgSelected)
    {
        if (empty($this->TA)) {
            $TA_file = file($this->path . 'classes/Argentinacae/xmlgenerados/TA' . $orgSelected . '.xml', FILE_IGNORE_NEW_LINES);

            if ($TA_file) {
                $TA_xml = '';
                for ($i = 0; $i < sizeof($TA_file); $i++) {
                    $TA_xml .= $TA_file[$i];
                }

                $this->TA = $this->xml2Array($TA_xml);

                $r = $this->TA['header']['expirationTime'];
            } else {
                $r = false;
            }
        } else {
            $r = $this->TA['header']['expirationTime'];
        }

        return $r;
    }

    /**
     * Crea el archivo xml de TRA
     */
    private function create_TRA($orgSelected)
    {
        $TRA = new \SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>' .
      '<loginTicketRequest version="1.0">' .
      '</loginTicketRequest>'
        );
        $TRA->addChild('header');
        $TRA->header->addChild('uniqueId', date('U'));
        $TRA->header->addChild('generationTime', date('c', date('U') - 180));
        $TRA->header->addChild('expirationTime', date('c', date('U') + 180));
        $TRA->addChild('service', $this->service);
        $TRA->asXML($this->path . 'classes/Argentinacae/xmlgenerados/TRA' . $orgSelected . '.xml');
    }

    /*
     * This functions makes the PKCS#7 signature using TRA as input file, CERT and
     * PRIVATEKEY to sign. Generates an intermediate file and finally trims the
     * MIME heading leaving the final CMS required by WSAA.
     *
     * devuelve el CMS
     */
    private function sign_TRA($orgSelected)
    {
        if ($orgSelected == 0) {
            $CERT = 'classes/Argentinacae/claves/cwcert.crt';      # The X.509 certificate in PEM format
    $PRIVATEKEY = 'classes/Argentinacae/claves/cwkey.key'; # The private key correspoding to CERT (PEM)
        } else {
            $CERT = 'data/files/cwcert' . $orgSelected . '.crt';      # The X.509 certificate in PEM format
    $PRIVATEKEY = 'data/files/cwkey' . $orgSelected . '.key'; # The private key correspoding to CERT (PEM)
        }

        $STATUS = openssl_pkcs7_sign(
            realpath('classes/Argentinacae/xmlgenerados/TRA' . $orgSelected . '.xml'),
            realpath('classes/Argentinacae/xmlgenerados/TRA' . $orgSelected . '.tmp'),
            'file://' . realpath($CERT),
            ['file://' . realpath($PRIVATEKEY), self::PASSPHRASE],
            [],
            ! PKCS7_DETACHED
        );

        if (! $STATUS) {
            throw new \Exception('ERROR generating PKCS#7 signature');
        }

        $inf = fopen($this->path . 'classes/Argentinacae/xmlgenerados/TRA' . $orgSelected . '.tmp', 'r');
        $i = 0;
        $CMS = '';
        while (! feof($inf)) {
            $buffer = fgets($inf);
            if ($i++ >= 4) {
                $CMS .= $buffer;
            }
        }

        fclose($inf);
        //unlink("TRA.xml");
        //unlink($this->path."xmlgenerados/TRA.tmp");
        //var_dump ($CMS);

        return $CMS;
    }

    /**
     * Conecta con el web service y obtiene el token y sign
     */
    private function call_WSAA($cms)
    {
        $results = $this->client->loginCms([
            'in0' => $cms,
        ]);
        //var_dump ($results);
        $ta_xml = simplexml_load_string($results->loginCmsReturn);
        $TOKEN = $ta_xml->credentials->token;
        $SIGN = $ta_xml->credentials->sign;

        // para logueo
        file_put_contents($this->path . 'request-loginCms.xml', $this->client->__getLastRequest());
        file_put_contents($this->path . 'response-loginCms.xml', $this->client->__getLastResponse());

        if (is_soap_fault($results)) {
            throw new \Exception('SOAP Fault: ' . $results->faultcode . ': ' . $results->faultstring);
        }

        return $results->loginCmsReturn;
    }

    /*
     * Convertir un XML a Array
     */
    private function xml2array($xml)
    {
        $json = json_encode(simplexml_load_string($xml));
        return json_decode($json, true);
    }
}
