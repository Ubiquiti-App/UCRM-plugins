<?php

declare(strict_types=1);


namespace QBExport;


use QBExport\Facade\QuickBooksFacade;
use QBExport\Service\Logger;
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

    /**
     * @var Logger
     */
    private $logger;

    public function __construct(OptionsManager $optionsManager, QuickBooksFacade $quickBooksFacade, Logger $logger)
    {
        $this->optionsManager = $optionsManager;
        $this->quickBooksFacade = $quickBooksFacade;
        $this->logger = $logger;
    }

    public function run(): void
    {
        if (PHP_SAPI === 'fpm-fcgi') {
            $this->processHttpRequest();
        } elseif (PHP_SAPI === 'cli') {
            $this->processCli();
        }
    }

    private function processCli(): void
    {
        $this->logger->info('CLI process started');
        $pluginData = $this->optionsManager->load();
        if (! $pluginData->qbAuthorizationUrl) {
            $this->quickBooksFacade->obtainAuthotizationURL();
        } elseif (! $pluginData->oauthRefreshToken) {
            $this->quickBooksFacade->obtainTokens();
        } else {
            $this->quickBooksFacade->refreshExpiredToken();
            $this->quickBooksFacade->exportClients();
            $this->quickBooksFacade->exportPayments();
        }
        $this->logger->info('CLI process ended');
    }

    private function processHttpRequest(): void
    {
        $pluginData = $this->optionsManager->load();

        if (
            \count(array_intersect(['code', 'realmId', 'state'], array_keys($_GET))) === 3
            && $_GET['state'] === $pluginData->qbStateCSRF
        ) {
            $pluginData->oauthRealmID = $_GET['realmId'];
            $pluginData->oauthCode = $_GET['code'];
            $this->logger->notice('Authorization Code obtained.');
            $this->optionsManager->update();
        }
    }
}
