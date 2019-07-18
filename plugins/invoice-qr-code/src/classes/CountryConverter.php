<?php

namespace App;

use InvalidArgumentException;
use Ubnt\UcrmPluginSdk\Service\UcrmApi;

class CountryConverter
{
    /** @var UcrmApi */
    private $ucrmApi;

    /** @var array */
    private $countriesCollectionCache;

    public function __construct($ucrmApi)
    {
        $this->ucrmApi = $ucrmApi;
    }

    public function convertCountryNameToISO(string $countryName): ?string
    {
        $this->countriesCollectionCache = $this->countriesCollectionCache ?? $this->ucrmApi->get('countries');
        foreach ($this->countriesCollectionCache as $country) {
            if ($country['name'] === $countryName) {
                return $country['code'];
            }
        }

        throw new InvalidArgumentException("Country '{$countryName}' was not found in database.");
    }
}
