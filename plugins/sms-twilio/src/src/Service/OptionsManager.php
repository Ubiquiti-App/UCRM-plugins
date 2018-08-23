<?php

declare(strict_types=1);


namespace SmsNotifier\Service;

use SmsNotifier\Data\PluginData;

class OptionsManager
{
    private const UCRM_JSON = 'ucrm.json';
    private const CONFIG_JSON = 'data/config.json';
    private const PLUGIN_JSON = 'data/plugin.json';

    /**
     * @var PluginData
     */
    private $optionsData;

    /**
     * @throws \ReflectionException
     */
    public function load(): PluginData
    {
        if ($this->optionsData) {
            return $this->optionsData;
        }

        $options = array_merge(
            $this->getDataFromJson(self::UCRM_JSON),
            $this->getDataFromJson(self::CONFIG_JSON),
            $this->getDataFromJson(self::PLUGIN_JSON)
        );

        $this->optionsData = new PluginData();
        $reflectionClass = new \ReflectionClass($this->optionsData);

        foreach ($reflectionClass->getProperties() as $reflectionProperty) {
            if (array_key_exists($reflectionProperty->getName(), $options)) {
                $reflectionProperty->setValue($this->optionsData, $options[$reflectionProperty->getName()]);
            }
        }

        return $this->optionsData;
    }

    public function update(): void
    {
        $newData = (array) $this->optionsData;

        $oldConfigData = $this->getDataFromJson(self::CONFIG_JSON);
        $newConfigData = array_intersect_key($newData, $oldConfigData);
        file_put_contents(self::CONFIG_JSON, json_encode($newConfigData));

        $ucrmData = $this->getDataFromJson(self::UCRM_JSON);

        file_put_contents(self::PLUGIN_JSON, json_encode(array_diff_key($newData, $ucrmData, $newConfigData)));
    }

    private function getDataFromJson(string $filename): array
    {
        if (! file_exists($filename)) {
            return [];
        }

        return  json_decode(file_get_contents($filename), true);
    }
}
