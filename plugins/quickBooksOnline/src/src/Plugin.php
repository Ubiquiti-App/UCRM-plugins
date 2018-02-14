<?php

declare(strict_types=1);


namespace QBExport;


use QBExport\Facade\QuickBooksFacade;
use QBExport\Service\OptionsManager;

class Plugin
{
    /**
     * @var OptionsManager
     */
    private $optionsManager;

    /**
     * @var QuickBooksFacade
     */
    private $quickBooksFacade;

    public function __construct(OptionsManager $optionsManager, QuickBooksFacade $quickBooksFacade)
    {
        $this->optionsManager = $optionsManager;
        $this->quickBooksFacade = $quickBooksFacade;
    }

    public function run(): void
    {
        $this->checkConfiguration();
    }

    private function checkConfiguration()
    {
        $pluginData = $this->optionsManager->loadOptions();
        if (! $pluginData->oauthRealmID) {
            $this->quickBooksFacade->logAuthotizationURL();
        }
    }
}
