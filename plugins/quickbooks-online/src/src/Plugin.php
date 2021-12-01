<?php

declare(strict_types=1);


namespace QBExport;


use QBExport\Exception\QBAuthorizationException;
use QBExport\Facade\QuickBooksFacade;
use QBExport\Service\PluginDataValidator;
use QBExport\Service\Logger;
use QBExport\Service\OptionsManager;

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
                    $this->quickBooksFacade->exportCreditNotes();
                    $this->cleanLog();
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

    private function cleanLog(): void {
        $dir = Logger::logFileDirectory;
        $file = Logger::logFileName;
        $ext = Logger::logFileExtension;
        $logPath = "$dir/$file.$ext";

        $mb = 1000000;
        $size = filesize($logPath);
        // never trim file if it's less than 1Mb
        if ($size < $mb) return;

        $mbSize = $size / $mb;
        $this->logger->info("Cleaning up log, size is $mbSize MB");
        $this->trimLogToLength($logPath, 10000);
    }

    /**
     * Idea from [here](https://stackoverflow.com/a/45090213), but modified
     */
    function trimLogToLength($path, $numRowsToKeep) {
        $file = file($path);
        if (!$file) return;

        // if this file is long enough that we should be truncating it
        $countFile = count($file);
        if ($countFile > $numRowsToKeep) {
            // figure out the rows we want to keep
            $dataRowsToKeep = array_slice($file,$countFile-$numRowsToKeep, $numRowsToKeep);
            file_put_contents($path, implode($dataRowsToKeep));
        }
    }
}
