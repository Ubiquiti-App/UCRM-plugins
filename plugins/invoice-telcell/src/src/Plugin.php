<?php
declare(strict_types=1);


namespace Telcell;

class Plugin
{
    private $buyer;
    private $currency;
    private $sum;
    private $description;
    private $invoiceNumber;
    private $validDays;


    public function __construct( )
    {
        $this->api = new \Telcell\Service\API();
    }

    public function run(): void
    {

        $this->api->debug('Started...');
        $userInput = file_get_contents('php://input');
        if (! $userInput) 
        {
            $this->api->warning('no input');
            return;
        }

        $jsonData = @json_decode($userInput, true, 10);
        if (isset($jsonData['uuid']) && isset($jsonData['changeType']) && isset($jsonData['entity']) && isset($jsonData['entityId']) && isset($jsonData['eventName'])) 
        {

            $entity = $jsonData['entity'];
            if($entity != 'invoice')
            {
                $this->api->debug('entity is not invoice');
                return;
            }

            $eventName = $jsonData['eventName'];           
            if($eventName != 'invoice.add')
            {
                $this->api->debug('eventName is not invoice.add');
                return;
            }

            $changeType = $jsonData['changeType'];
            if($changeType != 'insert')
            {
                $this->api->debug('changeType is not insert');
                return;
            }


            $jsonData = $jsonData['extraData']['entity'];

            $this->buyer = $this->getClientTelcellId($jsonData['clientId']);
            if(!$this->buyer)
            {
                $this->api->debug('Client have not telcell wallet, skiping....');
                return;
            }

            $this->api->debug('Stage 1: buyer id detected ' . $this->buyer);



            $this->currency = '51';
            $this->sum = strval($jsonData['amountToPay']);
            $this->description = 'Invoice';
            $this->invoiceNumber = md5(random_bytes(10));
            $this->validDays = $jsonData['maturityDays'];

            $url = 'https://telcellmoney.am/invoices';
            $data = array(
                'bill:issuer' => $this->api->getPluginConfig('shop_id'), 
                'buyer' => $this->buyer,
                'currency' => $this->currency,
                'description' => $this->description,
                'issuer_id' => $this->invoiceNumber,
                'sum' => $this->sum,
                'valid_days' => $this->validDays,
                'checksum' => $this->generateChecksum()
            );

            $this->api->debug($data);

            $options = array(
                'http' => array(
                    'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                    'method'  => 'POST',
                    'content' => http_build_query($data)
                )
            );

            $this->api->debug($options);
            $context  = stream_context_create($options);
            $result = file_get_contents($url, false, $context);

            $result = $this->api->addCustomAttributeToInvoice($jsonData['id'], 'telcellInvoiceNumber', $result);
            $this->api->debug($result);
            
        }
        else if (isset($jsonData['a']) && isset($jsonData['b']) && isset($jsonData['c']) && isset($jsonData['d']) && isset($jsonData['e'])) 
        {

        }
        $this->api->debug('Ended!');
    }

    public function getClientTelcellId($clientId) 
    {
        $client = $this->api->getClientById($clientId);
        foreach ($client['attributes'] as &$attr) 
        {
            if($attr['key'] == 'telcellWalletId')
            {
                return $attr['value'];
            }
        }
        return '';
    }

    public function getInvoiceByTelcellId($telcellId) 
    {
    }

    public function postTelcellInvoice()
    {

    }

    public function generateChecksum() : string
    {
        $temp = $this->api->getPluginConfig('shop_key') . $this->api->getPluginConfig('shop_id') . $this->buyer . $this->currency . $this->sum . $this->description . $this->validDays . $this->invoiceNumber;
        $this->api->debug('Checksum MD5(' . $temp . ')');
        return md5($temp);  
    }
}