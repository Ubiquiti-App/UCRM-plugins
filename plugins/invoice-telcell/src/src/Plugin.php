<?php
declare(strict_types=1);


namespace Telcell;


use Psr\Log\LogLevel;
use Telcell\Service\Logger;

class Plugin
{

    private $api;
    private $logger;

    private $pluginConfigManager;
    private $config;

    private $buyer;
    private $currency;
    private $sum;
    private $description;
    private $invoiceNumber;
    private $validDays;


    public function __construct( )
    {

        $this->api = \Ubnt\UcrmPluginSdk\Service\UcrmApi::create();
        $this->logger = new \Telcell\Service\Logger();
        $this->logger->setLogLevelThreshold(LogLevel::DEBUG);

        $this->pluginConfigManager = \Ubnt\UcrmPluginSdk\Service\PluginConfigManager::create();
        $this->config = $this->pluginConfigManager->loadConfig();
        $this->logger->debug($this->config);
    }

    public function run(): void
    {

        $this->logger->debug('Started...');
        $userInput = file_get_contents('php://input');
        if (! $userInput) 
        {
            $this->logger->warning('no input');
            return;
        }

        $jsonData = @json_decode($userInput, true, 10);
        if (isset($jsonData['uuid']) && isset($jsonData['changeType']) && isset($jsonData['entity']) && isset($jsonData['entityId']) && isset($jsonData['eventName'])) 
        {

            $entity = $jsonData['entity'];
            if($entity != 'invoice')
            {
                $this->logger->debug('entity is not invoice');
                return;
            }

            $eventName = $jsonData['eventName'];           
            if($eventName != 'invoice.add')
            {
                $this->logger->debug('eventName is not invoice.add');
                return;
            }

            $changeType = $jsonData['changeType'];
            if($changeType != 'insert')
            {
                $this->logger->debug('changeType is not insert');
                return;
            }


            $jsonData = $jsonData['extraData']['entity'];

            $this->buyer = $this->getClientTelcellId($jsonData['clientId']);
            if(!$this->buyer)
            {
                $this->logger->debug('Client have not telcell wallet, skiping....');
                return;
            }

            $this->logger->debug('Stage 1: buyer id detected ' . $this->buyer);



            $this->currency = '51';
            $this->sum = strval($jsonData['amountToPay']) . '.00' ;
            $this->description = 'Invoice';
            $this->invoiceNumber = md5(random_bytes(10));
            $this->validDays = '1'; //$jsonData['maturityDays'];

            $url = 'https://telcellmoney.am/invoices';
            $data = array(
                'bill:issuer' => $this->config['shop_id'], 
                'buyer' => $this->buyer,
                'checksum' => $this->generateChecksum(),
                'currency' => $this->currency,
                'description' => $this->description,
                'issuer_id' => $this->invoiceNumber,
                'sum' => $this->sum,
                'valid_days' => $this->validDays
            );

            $this->logger->debug($data);

            $urlEncoded = http_build_query($data);
            $urlEncoded = str_replace("bill%3Aissuer","bill:issuer", $urlEncoded);
            $this->logger->debug($urlEncoded);
            $options = array(
                'http' => array(
                    'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                    'method'  => 'POST',
                    'content' => $urlEncoded
                )
            );


            $this->logger->debug($options);
            $context  = stream_context_create($options);
            $result = file_get_contents($url, false, $context);

            $this->logger->debug('Stage 2 invoice created :' . $result);


            $url = 'https://telcellmoney.am/payments/invoice';
            $data = array(
                'invoice' => $result, 
                'return_url' => $this->config['webhook_url']
            );

            $this->logger->debug('registering callback');
            $this->logger->debug($data);

            $options = array(
                'http' => array(
                    'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                    'method'  => 'GET',
                    'content' => http_build_query($data)
                )
            );
            $this->logger->debug('registering callback : options: ');
            $this->logger->debug($options);

            $context  = stream_context_create($options);
            $result = file_get_contents($url, false, $context);
            $this->logger->debug('Stage 3 callback registered :');
            $this->logger->debug($result);
            
        }
        else if (isset($jsonData['a']) && isset($jsonData['b']) && isset($jsonData['c']) && isset($jsonData['d']) && isset($jsonData['e'])) 
        {

        }
        $this->logger->debug('Ended!');
    }

    public function getClientTelcellId($clientId) 
    {
        $client = $this->api->get('clients/' . $clientId );
        foreach ($client['attributes'] as &$attr) 
        {
            if($attr['key'] == 'telcellWalletId')
            {
                return $attr['value'];
            }
        }
        return '60616692';
    }

    public function getInvoiceByTelcellId($telcellId) 
    {
    }

    public function postTelcellInvoice()
    {

    }

    public function generateChecksum() : string
    {
        $temp = $this->config['shop_key'] . $this->config['shop_id'] . $this->buyer . $this->currency . $this->sum . $this->description . $this->validDays . $this->invoiceNumber;
        $this->logger->debug('Checksum MD5(' . $temp . ')');
        return md5($temp);  
    }
}