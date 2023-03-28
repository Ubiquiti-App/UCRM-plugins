<?php

declare(strict_types=1);


namespace QBExport;

use QBExport\Exception\QBAuthorizationException;
use QBExport\Facade\QuickBooksFacade;
use QBExport\Service\Logger;
use QBExport\Service\OptionsManager;
use QBExport\Service\PluginDataValidator;

class Plugin
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var OptionsManager
     */
    private $optionsManager;

    /**
     * @var PluginDataValidator
     */
    private $pluginDataValidator;

    /**
     * @var QuickBooksFacade
     */
    private $quickBooksFacade;

    public function __construct(
        Logger $logger,
        OptionsManager $optionsManager,
        PluginDataValidator $pluginDataValidator,
        QuickBooksFacade $quickBooksFacade
    ) {
        $this->logger = $logger;
        $this->optionsManager = $optionsManager;
        $this->pluginDataValidator = $pluginDataValidator;
        $this->quickBooksFacade = $quickBooksFacade;
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
        if ($this->pluginDataValidator->validate()) {
            $pluginData = $this->optionsManager->load();
            $this->logger->info('CLI process started');
            try {
                if (! $pluginData->qbAuthorizationUrl) {
                    $this->quickBooksFacade->obtainAuthorizationURL();
                } elseif (! $pluginData->oauthRefreshToken) {
                    $this->quickBooksFacade->obtainTokens();
                } else {
                    $this->quickBooksFacade->refreshExpiredToken();
                    $this->quickBooksFacade->exportClients();
                    $this->quickBooksFacade->exportInvoices();
                    $this->quickBooksFacade->exportPayments();
                }
                $this->logger->info('CLI process ended');
            } catch (QBAuthorizationException $exception) {
                $this->logger->info('Authorization failed - CLI process stopped');
            }
        }
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
            echo 'Authorization Code obtained.';
        }
    }
}
