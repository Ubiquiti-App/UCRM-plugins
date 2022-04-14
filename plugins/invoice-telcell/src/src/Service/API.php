<?php
declare(strict_types=1);

namespace Telcell\Service;

class API
{
    private $api;
    private $pluginConfigManager;
    private $config;
    private $logger;


    
    public function __construct()
    {
        $this->api = \Ubnt\UcrmPluginSdk\Service\UcrmApi::create();
        $this->logger = new \Telcell\Service\Logger();
        $this->logger->setLogLevelThreshold(\Psr\Log\LogLevel::DEBUG);

        $this->pluginConfigManager = \Ubnt\UcrmPluginSdk\Service\PluginConfigManager::create();
        $this->config = $this->pluginConfigManager->loadConfig();
    }

    public function getClientById( $id)
    {
        return $this->api->get('clients/' . $id );
    }

    public function createCustomAttribute($name, $attrType, $valueType, $clientZoneVisible = false)
    {
        $customattributes = $this->api->get('custom-attributes');
        if (array_search($name, array_column($customattributes, 'name')) === false)
        {            
            $this->logger->debug($name . ' custom attribute not found');
            $this->api->post('custom-attributes',['name'=>$name,'attributeType'=>$attrType,'type'=>$valueType,'clientZoneVisible'=>$clientZoneVisible]);
        } 
        else 
        {
            $this->logger->debug($name . ' custom attribute found');
        }
    }

    public function getCustomAttributeByKey($key) 
    {
        
        $customattributes = $this->api->get('custom-attributes');
        foreach ($customattributes as &$value)
        {
            if($value['key'] === $key)
            {
                return $value;
            }
        }
        return array();        
    }

    public function addCustomAttributeToInvoice($invoiceId, $key, $value)
    {
        $attr = $this->getCustomAttributeByKey($key);
        if(!isset($attr['id']))
        {
           return false; 
        }

        $this->api->patch('invoices/' . $invoiceId, [ "attributes" => [ ['customAttributeId' => $attr['id'],  'value' => $value ] ] ]);
    }

    public function getPluginConfig($key) 
    {
        return $this->config[$key];
    }

    public function debug($content)
    {
        $this-> logger->debug($content);
    }

    public function warning($content)
    {
        $this-> logger->warning($content);
    }
}
