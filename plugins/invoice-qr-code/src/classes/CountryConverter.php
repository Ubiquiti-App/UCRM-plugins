<?php

namespace App;

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

    public function convertUcrmIdToISO(int $urmCountryId): ?string
    {
        $countriesCollection = $this->countriesCollectionCache ?? $this->ucrmApi->get('countries');
        foreach ($countriesCollection as $country) {
            if ((int)$country['id'] === $urmCountryId) {
                return $country['code'];
            }
        }

        return null;
    }
}
