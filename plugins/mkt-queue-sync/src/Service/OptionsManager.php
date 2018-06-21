<?php

declare(strict_types=1);

namespace FioCz\Service;

use FioCz\Data\PluginData;

class OptionsManager
{
    private const UCRM_JSON = 'ucrm.json';
    private const CONFIG_JSON = 'data/config.json';

    /**
     * @var PluginData
     */
    private $optionsData;

    /**
     * @throws \ReflectionException
     */
    public function loadOptions(): PluginData
    {
        if ($this->optionsData) {
            return $this->optionsData;
        }

        $options = array_merge(
            $this->getDataFromJson(self::UCRM_JSON),
            $this->getDataFromJson(self::CONFIG_JSON)
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

    public function updateOptions(): void
    {
        $oldData = $this->getDataFromJson(self::CONFIG_JSON);
        $newData = (array) $this->optionsData;
        
        file_put_contents(self::CONFIG_JSON, json_encode(array_intersect_key($newData, $oldData)));
    }

    private function getDataFromJson(string $filename): array
    {
        if (! file_exists($filename)) {
            return [];
        }

        return json_decode(file_get_contents($filename), true);
    }
}
